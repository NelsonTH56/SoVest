<?php

namespace App\Http\Controllers;

use App\Models\Prediction;
use App\Models\PredictionVote;
use App\Models\Stock;
use App\Models\User;
use App\Services\Interfaces\ResponseFormatterInterface;
use App\Services\Interfaces\StockDataServiceInterface;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Auth Controller
 *
 * Handles authentication and user registration.
 */
class PredictionController extends Controller
{
    protected $stockService;

    public function __construct(ResponseFormatterInterface $responseFormatter, StockDataServiceInterface $stockService)
    {
        parent::__construct($responseFormatter);
        $this->stockService = $stockService;
    }

    public function index(Request $request)
    {
        $Curruser = Auth::user();
        $userId = Auth::id();

        try {
            // Get user's predictions with related stock (including latest price) and user data
            $predictions = Prediction::with(['stock.latestPrice', 'user'])
                ->withCount(['votes as upvotes' => function ($query) {
                    $query->where('vote_type', 'upvote');
                }])
                ->withCount(['votes as downvotes' => function ($query) {
                    $query->where('vote_type', 'downvote');
                }])
                ->withCount('comments as comments_count')
                ->where('user_id', $userId)
                ->orderBy('prediction_date', 'desc')
                ->get();

            // Format data for the view (no additional queries - all data is eager loaded)
            $formattedPredictions = $predictions->map(function ($prediction) {
                $predictionData = $prediction->toArray();
                $predictionData['symbol'] = $prediction->stock->symbol;
                $predictionData['company_name'] = $prediction->stock->company_name;

                // Include user info for the prediction card component
                $predictionData['username'] = $prediction->user->first_name;
                $predictionData['first_name'] = $prediction->user->first_name;
                $predictionData['reputation_score'] = $prediction->user->reputation_score;
                $predictionData['profile_picture'] = $prediction->user->profile_picture;

                // Use eager-loaded latest price (no additional query)
                $predictionData['current_price'] = $prediction->stock->latestPrice?->close_price;

                return $predictionData;
            })->toArray();

            // Render the view with the predictions data
            return view('predictions/my_predictions', [
                'predictions' => $formattedPredictions,
                'pageTitle' => 'My Predictions',
                'Curruser' => $Curruser,
            ]);
        } catch (Exception $e) {
            error_log('Error retrieving predictions: '.$e->getMessage());

            return view('my_predictions', [
                'predictions' => [],
                'pageTitle' => 'My Predictions',
                'Curruser' => $Curruser,
            ]);
        }
    }

    public function create(Request $request)
    {
        $Curruser = Auth::user();
        $cssPage = 'public/css/index.css';
        try {
            // Check for stock parameters in the URL
            $stockId = $request->query('stock_id');
            $symbol = $request->query('symbol');
            $companyName = $request->query('company_name');

            // Default prediction data
            $prediction = null;
            $currentPrice = null;

            // If stock parameters are provided, pre-populate the form
            if ($stockId && $symbol && $companyName) {
                $prediction = [
                    'stock_id' => $stockId,
                    'symbol' => $symbol,
                    'company_name' => $companyName,
                    // Add other default fields to ensure the form works correctly
                    'prediction_type' => null,
                    'target_price' => null,
                    'end_date' => null,
                    'reasoning' => null,
                ];

                // Fetch current price for the preselected stock
                $currentPrice = $this->stockService->getLatestPrice($symbol);
            }

            // Render the create prediction form
            return view('predictions/create', [
                'isEditing' => false,
                'prediction' => $prediction,
                'pageTitle' => 'Create Prediction',
                'hasPreselectedStock' => ($stockId && $symbol && $companyName),
                'currentPrice' => $currentPrice,
                'Curruser' => $Curruser,
                'cssPage' => $cssPage,
            ]);
        } catch (Exception $e) {
            error_log('Error loading stock data: '.$e->getMessage());
            $this->withError('Error loading stock data: '.$e->getMessage());

            return $this->responseFormatter->redirect('home');
        }
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $userId = Auth::id();

        try {
            // Get the stock to validate and fetch current price
            $stockId = request()->input('stock_id');
            $stock = Stock::find($stockId);

            if (! $stock) {
                if ($this->isApiRequest()) {
                    return $this->jsonError('Invalid stock selected');
                } else {
                    return back()->withInput()->withErrors(['stock_id' => 'Invalid stock selected']);
                }
            }

            // Re-fetch current stock price server-side to ensure accuracy
            $currentPrice = $this->stockService->getLatestPrice($stock->symbol);

            // Validate target price against prediction direction
            $targetPrice = ! empty(request()->input('target_price')) ?
                (float) request()->input('target_price') : null;
            $predictionType = request()->input('prediction_type');

            if ($targetPrice !== null && $currentPrice !== false) {
                // Enforce direction constraints server-side
                if ($predictionType === 'Bullish' && $targetPrice <= $currentPrice) {
                    $errorMsg = "Bullish predictions require a target price above the current price (\${$currentPrice})";
                    if ($this->isApiRequest()) {
                        return $this->jsonError($errorMsg);
                    } else {
                        return back()->withInput()->withErrors(['target_price' => $errorMsg]);
                    }
                } elseif ($predictionType === 'Bearish' && $targetPrice >= $currentPrice) {
                    $errorMsg = "Bearish predictions require a target price below the current price (\${$currentPrice})";
                    if ($this->isApiRequest()) {
                        return $this->jsonError($errorMsg);
                    } else {
                        return back()->withInput()->withErrors(['target_price' => $errorMsg]);
                    }
                }
            }

            // Create a new Prediction model instance
            $prediction = new Prediction([
                'user_id' => $userId,
                'stock_id' => $stockId,
                'prediction_type' => $predictionType,
                'target_price' => $targetPrice,
                'end_date' => request()->input('end_date'),
                'reasoning' => request()->input('reasoning'),
                'prediction_date' => date('Y-m-d H:i:s'),
                'is_active' => 1,
                'accuracy' => null,
            ]);

            // Use model validation
            if ($prediction->validate()) {
                // Validation passed, save the prediction
                $prediction->save();

                // Determine if this is an API request or a form submission
                if ($this->isApiRequest()) {
                    return $this->jsonSuccess('Prediction created successfully',
                        ['prediction_id' => $prediction->prediction_id],
                        '/predictions');
                } else {
                    $this->withSuccess('Prediction created successfully');

                    return $this->responseFormatter->redirect('/predictions');
                }
            } else {
                // Get validation errors
                $errors = $prediction->getErrors();

                // Determine if this is an API request or a form submission
                if ($this->isApiRequest()) {
                    $errorMessage = 'Validation failed: ';
                    foreach ($errors as $field => $fieldErrors) {
                        foreach ($fieldErrors as $error) {
                            $errorMessage .= $error.' ';
                        }
                    }

                    return $this->jsonError(trim($errorMessage));
                } else {
                    return back()->withInput()->withErrors($errors);
                }
            }
        } catch (Exception $e) {
            if ($this->isApiRequest()) {
                return $this->jsonError('Error creating prediction: '.$e->getMessage());
            } else {
                $this->withError('Error creating prediction: '.$e->getMessage());

                return redirect()->back()->withInput();
            }
        }
    }

    /**
     * Show the prediction edit form
     */
    public function edit(Request $request, int $id)
    {
        // Get user data
        $userId = Auth::id();

        if (! $id) {
            $this->withError('Missing prediction ID');

            return $this->responseFormatter->redirect('/predictions');
        }

        try {
            // Fetch the prediction with its related stock using Eloquent
            $predictionModel = Prediction::with('stock')
                ->where('prediction_id', $id)
                ->where('user_id', $userId)
                ->first();

            if (! $predictionModel) {
                $this->withError("Prediction not found or you don't have permission to edit it");

                return $this->responseFormatter->redirect('/predictions');
            }

            // Check if prediction is still active
            if (! $predictionModel->is_active) {
                $this->withError('Cannot edit inactive predictions');

                return $this->responseFormatter->redirect('/predictions');
            }

            // Convert to array format to maintain compatibility with the view
            $prediction = $predictionModel->toArray();
            // Add stock attributes that were previously fetched by JOIN
            $prediction['symbol'] = $predictionModel->stock->symbol;
            $prediction['company_name'] = $predictionModel->stock->company_name;

            // Get all active stocks for the dropdown using the injected service
            $stocks = $this->stockService->getStocks(true);

            // Render the edit form
            return view('predictions/create', [
                'stocks' => $stocks,
                'isEditing' => true,
                'prediction' => $prediction,
                'pageTitle' => 'Edit Prediction',
            ]);
        } catch (Exception $e) {
            $this->withError('Error loading prediction: '.$e->getMessage());

            return $this->responseFormatter->redirect('/predictions');
        }
    }

    /**
     * Handle prediction update form submission
     */
    public function update(Request $request, int $id)
    {
        // Get user data
        $userId = Auth::id();

        if (! $id) {
            if ($this->isApiRequest()) {
                $this->jsonError('Missing prediction ID');
            } else {
                $this->withError('Missing prediction ID');

                return $this->responseFormatter->redirect('/predictions');
            }

            return;
        }

        try {
            // Check if prediction exists and belongs to user using Eloquent
            $prediction = Prediction::where('prediction_id', $id)
                ->where('user_id', $userId)
                ->first();

            if (! $prediction) {
                if ($this->isApiRequest()) {
                    $this->jsonError("Prediction not found or you don't have permission to edit it");
                } else {
                    $this->withError("Prediction not found or you don't have permission to edit it");

                    return $this->responseFormatter->redirect('/predictions');
                }

                return;
            }

            // Check if prediction can be edited (is still active)
            if (! $prediction->is_active) {
                if ($this->isApiRequest()) {
                    $this->jsonError('Cannot edit inactive predictions');
                } else {
                    $this->withError('Cannot edit inactive predictions');

                    return $this->responseFormatter->redirect('/predictions');
                }

                return;
            }

            // Update prediction attributes
            $prediction->prediction_type = $request->has('prediction_type') && ! empty($request->input('prediction_type')) ?
                            $request->input('prediction_type') : $prediction->prediction_type;

            $prediction->target_price = $request->has('target_price') && $request->input('target_price') !== '' ?
                            (float) $request->input('target_price') : $prediction->target_price;

            $prediction->end_date = $request->has('end_date') && ! empty($request->input('end_date')) ?
                        $request->input('end_date') : $prediction->end_date;

            $prediction->reasoning = $request->has('reasoning') && ! empty($request->input('reasoning')) ?
                        $request->input('reasoning') : $prediction->reasoning;

            // Use model validation
            if ($prediction->validate()) {
                // Validation passed, save the prediction
                $prediction->save();

                if ($this->isApiRequest()) {
                    $this->jsonSuccess('Prediction updated successfully', [], '/predictions');
                } else {
                    $this->withSuccess('Prediction updated successfully');

                    return $this->responseFormatter->redirect('/predictions');
                }
            } else {
                // Get validation errors
                $errors = $prediction->getErrors();

                if ($this->isApiRequest()) {
                    $errorMessage = 'Validation failed: ';
                    foreach ($errors as $field => $fieldErrors) {
                        foreach ($fieldErrors as $error) {
                            $errorMessage .= $error.' ';
                        }
                    }
                    $this->jsonError(trim($errorMessage));
                } else {
                    return back()->withInput()->withErrors($errors);
                }
            }
        } catch (Exception $e) {
            if ($this->isApiRequest()) {
                $this->jsonError('Error updating prediction: '.$e->getMessage());
            } else {
                $this->withError('Error updating prediction: '.$e->getMessage());

                return $this->responseFormatter->redirect('/predictions');
            }
        }
    }

    /**
     * Handle prediction deletion
     */
    public function delete(Request $request, int $id)
    {
        $userId = Auth::id();

        if (! $id) {
            if ($this->isApiRequest()) {
                $this->jsonError('Missing prediction ID');
            } else {
                $this->withError('Missing prediction ID');

                return $this->responseFormatter->redirect('/predictions');
            }

            return;
        }

        try {
            // Check if prediction exists and belongs to user using Eloquent
            $prediction = Prediction::where('prediction_id', $id)
                ->where('user_id', $userId)
                ->first();

            if (! $prediction) {
                if ($this->isApiRequest()) {
                    $this->jsonError("Prediction not found or you don't have permission to delete it");
                } else {
                    $this->withError("Prediction not found or you don't have permission to delete it");

                    return $this->responseFormatter->redirect('/predictions');
                }

                return;
            }

            // Delete prediction using Eloquent
            $prediction->delete();

            if ($this->isApiRequest()) {
                $this->jsonSuccess('Prediction deleted successfully');
            } else {
                $this->withSuccess('Prediction deleted successfully');

                return $this->responseFormatter->redirect('/predictions');
            }
        } catch (Exception $e) {
            if ($this->isApiRequest()) {
                $this->jsonError('Error deleting prediction: '.$e->getMessage());
            } else {
                $this->withError('Error deleting prediction: '.$e->getMessage());

                return $this->responseFormatter->redirect('/predictions');
            }
        }
    }

    /**
     * Show a specific prediction
     */
    public function view(Request $request, ?int $id = null)
    {
        // Get the prediction ID from route parameter or request input
        $predictionId = $id ?? $request->input('id');

        if (! $predictionId) {
            $this->withError('Missing prediction ID');

            return $this->responseFormatter->redirect('/predictions/trending');
        }

        try {
            // Use Eloquent with eager loading to get prediction with related data
            $prediction = Prediction::with(['stock.latestPrice', 'user', 'votes'])
                ->where('prediction_id', $predictionId)
                ->first();

            if (! $prediction) {
                $this->withError('Prediction not found');

                return $this->responseFormatter->redirect('/predictions/trending');
            }

            // Mark prediction as viewed if the current user is the owner
            if (Auth::check() && (int) $prediction->user_id === (int) Auth::id()) {
                $prediction->markAsViewed();
            }

            // Format data for the view
            $predictionData = $prediction->toArray();
            $predictionData['username'] = $prediction->user->first_name.' '.$prediction->user->last_name;
            $predictionData['upvotes'] = $prediction->votes->where('vote_type', 'upvote')->count();
            $predictionData['downvotes'] = $prediction->votes->where('vote_type', 'downvote')->count();

            // Use eager-loaded latest price (no additional query)
            if ($prediction->stock->latestPrice) {
                $predictionData['stock']['current_price'] = $prediction->stock->latestPrice->close_price;
            }

            // Include prediction score display component
            require_once __DIR__.'/../../includes/prediction_score_display.php';

            // Render the view with the prediction data
            return view('predictions/view', [
                'prediction' => $predictionData,
                'pageTitle' => $prediction->stock->symbol.' '.$prediction->prediction_type.' Prediction',
                'Curruser' => Auth::user(),
            ]);
        } catch (Exception $e) {
            $this->withError('Error retrieving prediction: '.$e->getMessage());

            return $this->responseFormatter->redirect('/predictions/trending');
        }
    }

    /**
     * Show trending predictions
     */
    public function trending(Request $request)
    {
        try {
            $Curruser = Auth::user();
            // Get trending predictions using Eloquent ORM
            $trending_predictions = Prediction::select([
                'predictions.prediction_id',
                'predictions.prediction_date',
                'predictions.reasoning',
                'users.id as user_id',
                'users.reputation_score',
                'users.profile_picture',
                'stocks.symbol',
                'stocks.stock_id',
                'stocks.company_name',
                'predictions.prediction_type',
                'predictions.accuracy',
                'predictions.target_price',
                'predictions.end_date',
                'predictions.is_active',
            ])
                ->join('users', 'predictions.user_id', '=', 'users.id')
                ->join('stocks', 'predictions.stock_id', '=', 'stocks.stock_id')
                ->withCount(['votes as upvotes' => function ($query) {
                    $query->where('vote_type', 'upvote');
                }])
                ->withCount(['votes as downvotes' => function ($query) {
                    $query->where('vote_type', 'downvote');
                }])
                ->withCount('comments as comments_count')
                ->addSelect([
                        'users.first_name',
                        'users.last_name',
                    ])
                ->where(function ($query) {
                    $query->where('predictions.is_active', 1)
                        ->orWhere(function ($query) {
                            $query->whereNotNull('predictions.accuracy')
                                ->where('predictions.accuracy', '>=', 70);
                        });
                })
                ->orderBy('upvotes', 'desc')
                ->orderBy('predictions.accuracy', 'desc')
                ->orderBy('predictions.prediction_date', 'desc')
                ->limit(15)
                ->get();

            // Map the results to include the full name as username and fetch latest stock prices
            $trending_predictions = $trending_predictions->map(function ($prediction) {
                $prediction = $prediction->toArray();
                // Use first_name for username display (component checks 'username' ?? 'first_name')
                $prediction['username'] = $prediction['first_name'];

                // Fetch latest stock price
                $latestPrice = $this->stockService->getLatestPrice($prediction['symbol']);
                $prediction['current_price'] = $latestPrice !== false ? $latestPrice : null;

                return $prediction;
            })->toArray();

            // If no predictions found, use dummy data
            if (empty($trending_predictions)) {
                $trending_predictions = [
                    ['prediction_id' => 1, 'username' => 'Investor123', 'first_name' => 'Investor123', 'symbol' => 'AAPL', 'prediction_type' => 'Bullish', 'upvotes' => 120, 'downvotes' => 5, 'accuracy' => 92, 'reputation_score' => 100, 'is_active' => 1, 'reasoning' => 'Strong earnings expected'],
                    ['prediction_id' => 2, 'username' => 'MarketGuru', 'first_name' => 'MarketGuru', 'symbol' => 'TSLA', 'prediction_type' => 'Bearish', 'upvotes' => 95, 'downvotes' => 10, 'accuracy' => 85, 'reputation_score' => 85, 'is_active' => 1, 'reasoning' => 'Overvalued at current levels'],
                    ['prediction_id' => 3, 'username' => 'StockSavvy', 'first_name' => 'StockSavvy', 'symbol' => 'AMZN', 'prediction_type' => 'Bullish', 'upvotes' => 75, 'downvotes' => 3, 'accuracy' => null, 'reputation_score' => 70, 'is_active' => 1, 'reasoning' => 'Cloud growth potential'],
                ];
            }

            // Render the view with the trending predictions data
            return view('trending', [
                'Curruser' => $Curruser,
                'trending_predictions' => $trending_predictions,
                'pageTitle' => 'Trending Predictions',
                'pageCss' => 'css/index.css', // ← Laravel handles 'public/' automatically
            ]);
        } catch (Exception $e) {
            // Fallback to dummy data if an error occurs
            $trending_predictions = [
                ['prediction_id' => 1, 'username' => 'Investor123', 'first_name' => 'Investor123', 'symbol' => 'AAPL', 'prediction_type' => 'Bullish', 'upvotes' => 120, 'downvotes' => 5, 'accuracy' => 92, 'reputation_score' => 100, 'is_active' => 1, 'reasoning' => 'Strong earnings expected'],
                ['prediction_id' => 2, 'username' => 'MarketGuru', 'first_name' => 'MarketGuru', 'symbol' => 'TSLA', 'prediction_type' => 'Bearish', 'upvotes' => 95, 'downvotes' => 10, 'accuracy' => 85, 'reputation_score' => 85, 'is_active' => 1, 'reasoning' => 'Overvalued at current levels'],
                ['prediction_id' => 3, 'username' => 'StockSavvy', 'first_name' => 'StockSavvy', 'symbol' => 'AMZN', 'prediction_type' => 'Bullish', 'upvotes' => 75, 'downvotes' => 3, 'accuracy' => null, 'reputation_score' => 70, 'is_active' => 1, 'reasoning' => 'Cloud growth potential'],
            ];

            $this->withError('Error retrieving trending predictions: '.$e->getMessage());

            // Render the view with the fallback data
            return view('trending', ['Curruser' => $Curruser, 'pageTitle' => 'Trending Predictions', 'trending_predictions' => $trending_predictions, 'pageCss' => 'public/css/index.css']);
        }
    }

    /**
     * Handle voting on predictions
     */
    public function vote(Request $request)
    {

        $user = Auth::user();
        $userId = Auth::id();

        // Get prediction ID and vote type from the request
        $predictionId = $request->input('prediction_id');
        $voteType = $request->input('vote_type', 'upvote'); // Default to upvote

        \Log::info('Vote called with ID: '.$predictionId);
        \Log::info('Request body: ', $request->all());

        if (! $predictionId) {
            return response()->json(['success' => false, 'message' => 'Missing prediction ID'], 400);
        }

        try {
            // Check if prediction exists
            $prediction = Prediction::find($predictionId);

            if (! $prediction) {
                return response()->json(['success' => false, 'message' => 'Prediction not found'], 404);
            }

            // Check if user has already voted on this prediction
            $existingVote = PredictionVote::where('prediction_id', $predictionId)
                ->where('user_id', $userId)
                ->first();

            if ($existingVote) {
                // Update existing vote if vote type is different
                if ($existingVote->vote_type !== $voteType) {
                    $existingVote->vote_type = $voteType;
                    $existingVote->vote_date = date('Y-m-d H:i:s');
                    $existingVote->save();

                    return response()->json(['success' => true, 'message' => 'Vote updated successfully']);
                } else {
                    // Remove vote if same type (toggle functionality)
                    $existingVote->delete();

                    return response()->json(['success' => true, 'message' => 'Vote removed successfully']);
                }
            } else {
                // Create new vote
                $vote = new PredictionVote([
                    'prediction_id' => $predictionId,
                    'user_id' => $userId,
                    'vote_type' => $voteType,
                    'vote_date' => date('Y-m-d H:i:s'),
                ]);

                $vote->save();

                return response()->json(['success' => true, 'message' => 'Vote recorded successfully']);
            }
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error processing vote: '.$e->getMessage()], 500);
        }
    }

    /**
     * Legacy upvote method - redirects to main vote() method
     * Kept for backward compatibility with older routes
     */
    public function upvote($predictionId)
    {
        if (! auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Use the main vote method with proper PredictionVote tracking
        $request = request();
        $request->merge([
            'prediction_id' => $predictionId,
            'vote_type' => 'upvote',
        ]);

        return $this->vote($request);
    }

    /**
     * Legacy downvote method - redirects to main vote() method
     * Kept for backward compatibility with older routes
     */
    public function downvote($predictionId)
    {
        if (! auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Use the main vote method with proper PredictionVote tracking
        $request = request();
        $request->merge([
            'prediction_id' => $predictionId,
            'vote_type' => 'downvote',
        ]);

        return $this->vote($request);
    }

    /**
     * Handle API requests for backward compatibility with the legacy prediction_operations.php API endpoint
     *
     * This method provides a compatibility layer that maps legacy API operations to the appropriate
     * controller methods. It maintains the same parameter structure and response format as the
     * original API to ensure existing client code continues to work during the transition to Laravel.
     *
     * Supported actions:
     * - create: Maps to store()
     * - update: Maps to update()
     * - delete: Maps to delete()
     * - get: Maps to apiGetPrediction()
     */
    public function apiHandler()
    {
        // Check if user is authenticated - replicating the old behavior
        if (! Auth::check()) {
            $this->json([
                'success' => false,
                'message' => 'User not logged in',
                'redirect' => '/login',
            ]);

            return;
        }

        $userId = Auth::id();

        try {
            // Verify user exists using Eloquent
            $user = User::find($userId);
            if (! $user) {
                $this->jsonError('User not found');

                return;
            }
        } catch (Exception $e) {
            $this->jsonError('Database connection failed: '.$e->getMessage());

            return;
        }

        // Determine action from the request
        $action = request()->input('action', '');

        switch ($action) {
            case 'create':
                $this->apiStore();
                break;
            case 'update':
                $this->apiUpdate();
                break;
            case 'delete':
                $this->apiDelete();
                break;
            case 'get':
                $this->apiGetPrediction($userId);
                break;
            default:
                $this->jsonError('Invalid action specified');
                break;
        }
    }

    /**
     * API method to get a single prediction
     *
     * Retrieves a prediction by ID and returns it in a standardized API format.
     * Can be accessed both directly via API routes and through the legacy apiHandler method.
     *
     * @param  int  $userId  The ID of the user making the request
     * @return void Outputs JSON response directly
     */
    public function apiGetPrediction($userId)
    {
        try {
            if (! request()->has('prediction_id') || empty(request()->input('prediction_id'))) {
                $this->jsonError('Missing prediction ID');

                return;
            }

            $predictionId = request()->input('prediction_id');

            // Use Eloquent with eager loading to get prediction with related stock data
            $prediction = Prediction::with('stock')
                ->where('prediction_id', $predictionId)
                ->where('user_id', $userId)
                ->first();

            if ($prediction) {
                // Format data to match the old response structure
                $predictionData = $prediction->toArray();
                $predictionData['symbol'] = $prediction->stock->symbol;
                $predictionData['company_name'] = $prediction->stock->company_name;

                $this->jsonSuccess('Prediction retrieved successfully', $predictionData);
            } else {
                $this->jsonError("Prediction not found or you don't have permission to view it");
            }
        } catch (Exception $e) {
            $this->jsonError('Error retrieving prediction: '.$e->getMessage());
        }
    }

    /**
     * API method to create a prediction
     *
     * Gets the current request and passes it to the store method.
     * Used by the apiHandler compatibility layer.
     *
     * @return void Outputs JSON response directly
     */
    public function apiStore()
    {
        try {
            $userId = Auth::id();

            // Validate required fields for API
            if (! request()->has('stock_id') || empty(request()->input('stock_id'))) {
                return $this->jsonError('Missing required field: stock_id');
            }
            if (! request()->has('prediction_type') || empty(request()->input('prediction_type'))) {
                return $this->jsonError('Missing required field: prediction_type');
            }
            if (! request()->has('end_date') || empty(request()->input('end_date'))) {
                return $this->jsonError('Missing required field: end_date');
            }
            if (! request()->has('reasoning') || empty(request()->input('reasoning'))) {
                return $this->jsonError('Missing required field: reasoning');
            }

            // Get the stock and validate direction constraints
            $stockId = request()->input('stock_id');
            $stock = Stock::find($stockId);

            if (! $stock) {
                return $this->jsonError('Invalid stock selected');
            }

            // Re-fetch current stock price server-side
            $currentPrice = $this->stockService->getLatestPrice($stock->symbol);
            $targetPrice = ! empty(request()->input('target_price')) ?
                (float) request()->input('target_price') : null;
            $predictionType = request()->input('prediction_type');

            // Enforce direction constraints
            if ($targetPrice !== null && $currentPrice !== false) {
                if ($predictionType === 'Bullish' && $targetPrice <= $currentPrice) {
                    return $this->jsonError("Bullish predictions require a target price above the current price (\${$currentPrice})");
                } elseif ($predictionType === 'Bearish' && $targetPrice >= $currentPrice) {
                    return $this->jsonError("Bearish predictions require a target price below the current price (\${$currentPrice})");
                }
            }

            // Create a new Prediction model instance
            $prediction = new Prediction([
                'user_id' => $userId,
                'stock_id' => $stockId,
                'prediction_type' => $predictionType,
                'target_price' => $targetPrice,
                'end_date' => request()->input('end_date'),
                'reasoning' => request()->input('reasoning'),
                'prediction_date' => date('Y-m-d H:i:s'),
                'is_active' => 1,
                'accuracy' => null,
            ]);

            // Use model validation
            if ($prediction->validate()) {
                // Validation passed, save the prediction
                $prediction->save();

                return $this->jsonSuccess('Prediction created successfully',
                    ['prediction_id' => $prediction->prediction_id]);
            } else {
                // Get validation errors
                $errors = $prediction->getErrors();

                $errorMessage = 'Validation failed: ';
                foreach ($errors as $field => $fieldErrors) {
                    foreach ($fieldErrors as $error) {
                        $errorMessage .= $error.' ';
                    }
                }

                return $this->jsonError(trim($errorMessage));
            }
        } catch (Exception $e) {
            return $this->jsonError('Error creating prediction: '.$e->getMessage());
        }
    }

    /**
     * API method to update a prediction
     *
     * Gets the current request and passes it to the update method.
     * Used by the apiHandler compatibility layer.
     *
     * @return void Outputs JSON response directly
     */
    public function apiUpdate()
    {
        try {
            $userId = Auth::id();

            // Validate prediction_id is provided
            if (! request()->has('prediction_id') || empty(request()->input('prediction_id'))) {
                return $this->jsonError('Missing required field: prediction_id');
            }

            $predictionId = request()->input('prediction_id');

            // Check if prediction exists and belongs to user using Eloquent
            $prediction = Prediction::where('prediction_id', $predictionId)
                ->where('user_id', $userId)
                ->first();

            if (! $prediction) {
                return $this->jsonError("Prediction not found or you don't have permission to edit it");
            }

            // Check if prediction can be edited (is still active)
            if (! $prediction->is_active) {
                return $this->jsonError('Cannot edit inactive predictions');
            }

            // Update prediction attributes - only update fields that are provided
            if (request()->has('prediction_type') && ! empty(request()->input('prediction_type'))) {
                $prediction->prediction_type = request()->input('prediction_type');
            }

            if (request()->has('target_price')) {
                $prediction->target_price = request()->input('target_price') !== '' ?
                                (float) request()->input('target_price') : null;
            }

            if (request()->has('end_date') && ! empty(request()->input('end_date'))) {
                $prediction->end_date = request()->input('end_date');
            }

            if (request()->has('reasoning') && ! empty(request()->input('reasoning'))) {
                $prediction->reasoning = request()->input('reasoning');
            }

            // Use model validation
            if ($prediction->validate()) {
                // Validation passed, save the prediction
                $prediction->save();

                return $this->jsonSuccess('Prediction updated successfully',
                    ['prediction_id' => $prediction->prediction_id]);
            } else {
                // Get validation errors
                $errors = $prediction->getErrors();

                $errorMessage = 'Validation failed: ';
                foreach ($errors as $field => $fieldErrors) {
                    foreach ($fieldErrors as $error) {
                        $errorMessage .= $error.' ';
                    }
                }

                return $this->jsonError(trim($errorMessage));
            }
        } catch (Exception $e) {
            return $this->jsonError('Error updating prediction: '.$e->getMessage());
        }
    }

    /**
     * Get detailed prediction data for modal display
     *
     * Returns all prediction data including user info, stock info, votes, and comments count
     * for rendering in a modal overlay on the profile page.
     *
     * @param  int  $id  The prediction ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDetails(int $id)
    {
        try {
            $userId = Auth::id();

            // Fetch prediction with all related data (including latest price)
            $prediction = Prediction::with(['stock.latestPrice', 'user', 'votes'])
                ->withCount(['votes as upvotes' => function ($query) {
                    $query->where('vote_type', 'upvote');
                }])
                ->withCount(['votes as downvotes' => function ($query) {
                    $query->where('vote_type', 'downvote');
                }])
                ->withCount('comments as comments_count')
                ->where('prediction_id', $id)
                ->first();

            if (! $prediction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Prediction not found',
                ], 404);
            }

            // Get current user's vote on this prediction (if logged in)
            $userVote = null;
            if ($userId) {
                $vote = PredictionVote::where('prediction_id', $id)
                    ->where('user_id', $userId)
                    ->first();
                $userVote = $vote?->vote_type;
            }

            // Format response data (use eager-loaded latest price)
            $data = [
                'prediction_id' => $prediction->prediction_id,
                'prediction_type' => $prediction->prediction_type,
                'target_price' => $prediction->target_price,
                'end_date' => $prediction->end_date,
                'prediction_date' => $prediction->prediction_date,
                'reasoning' => $prediction->reasoning,
                'is_active' => $prediction->is_active,
                'accuracy' => $prediction->accuracy,
                'upvotes' => $prediction->upvotes,
                'downvotes' => $prediction->downvotes,
                'comments_count' => $prediction->comments_count,
                'user_vote' => $userVote,
                'stock' => [
                    'stock_id' => $prediction->stock->stock_id,
                    'symbol' => $prediction->stock->symbol,
                    'company_name' => $prediction->stock->company_name,
                    'current_price' => $prediction->stock->latestPrice?->close_price,
                ],
                'user' => [
                    'id' => $prediction->user->id,
                    'first_name' => $prediction->user->first_name,
                    'last_name' => $prediction->user->last_name,
                    'profile_picture' => $prediction->user->profile_picture,
                    'reputation_score' => $prediction->user->reputation_score,
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving prediction: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API method to delete a prediction
     *
     * Gets the current request and passes it to the delete method.
     * Used by the apiHandler compatibility layer.
     *
     * @return void Outputs JSON response directly
     */
    public function apiDelete()
    {
        try {
            $userId = Auth::id();

            // Validate prediction_id is provided
            if (! request()->has('prediction_id') || empty(request()->input('prediction_id'))) {
                return $this->jsonError('Missing required field: prediction_id');
            }

            $predictionId = request()->input('prediction_id');

            // Check if prediction exists and belongs to user using Eloquent
            $prediction = Prediction::where('prediction_id', $predictionId)
                ->where('user_id', $userId)
                ->first();

            if (! $prediction) {
                return $this->jsonError("Prediction not found or you don't have permission to delete it");
            }

            // Delete prediction using Eloquent (cascading deletes handled by DB foreign keys)
            $prediction->delete();

            return $this->jsonSuccess('Prediction deleted successfully',
                ['prediction_id' => $predictionId]);
        } catch (Exception $e) {
            return $this->jsonError('Error deleting prediction: '.$e->getMessage());
        }
    }
}
