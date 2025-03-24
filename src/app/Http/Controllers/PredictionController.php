<?php

namespace App\Http\Controllers;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Prediction;
use App\Models\Stock;
use App\Models\User;
use App\Models\PredictionVote;
use App\Services\Interfaces\StockDataServiceInterface;
use App\Services\Interfaces\ResponseFormatterInterface;
use Exception;

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
        $user = Auth::user();
        $userId = Auth::id();

        try {
            // Get user's predictions with related stock data using Eloquent
            $predictions = Prediction::with('stock')
                ->where('user_id', $userId)
                ->orderBy('prediction_date', 'desc')
                ->get();

            // Format data for the view
            $formattedPredictions = $predictions->map(function ($prediction) {
                $predictionData = $prediction->toArray();
                $predictionData['symbol'] = $prediction->stock->symbol;
                $predictionData['company_name'] = $prediction->stock->company_name;
                return $predictionData;
            })->toArray();

            // Render the view with the predictions data
            return view('predictions/my_predictions', [
                'predictions' => $formattedPredictions,
                'pageTitle' => 'My Predictions'
            ]);
        } catch (\Exception $e) {
            error_log("Error retrieving predictions: " . $e->getMessage());
            return view('my_predictions', [
                'predictions' => [],
                'pageTitle' => 'My Predictions'
            ]);
        }
    }

    public function create(Request $request)
    {
        try {
            // Get all active stocks for the dropdown using the injected service
            $stocks = $this->stockService->getStocks(true);
            
            // Render the create prediction form
            return view('predictions/create', [
                'stocks' => $stocks,
                'isEditing' => false,
                'prediction' => null,
                'pageTitle' => 'Create Prediction'
            ]);
        } catch (\Exception $e) {
            error_log("Error loading stock data: " . $e->getMessage());
            redirect('home');
        }
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $userId = Auth::id();
        
        try {
            // Create a new Prediction model instance
            $prediction = new Prediction([
                'user_id' => $userId,
                'stock_id' => request()->input('stock_id'),
                'prediction_type' => request()->input('prediction_type'),
                'target_price' => !empty(request()->input('target_price')) ? 
                                (float) request()->input('target_price') : null,
                'end_date' => request()->input('end_date'),
                'reasoning' => request()->input('reasoning'),
                'prediction_date' => date('Y-m-d H:i:s'),
                'is_active' => 1,
                'accuracy' => null
            ]);
            
            // Use model validation
            if ($prediction->validate()) {
                // Validation passed, save the prediction
                $prediction->save();
                
                // Determine if this is an API request or a form submission
                if ($this->isApiRequest()) {
                    $this->jsonSuccess("Prediction created successfully", 
                        ['prediction_id' => $prediction->prediction_id], 
                        '/predictions');
                } else {
                    $this->withSuccess("Prediction created successfully");
                    $request->redirect('/predictions');
                }
            } else {
                // Get validation errors
                $errors = $prediction->getErrors();
                
                // Determine if this is an API request or a form submission
                if ($this->isApiRequest()) {
                    $errorMessage = "Validation failed: ";
                    foreach ($errors as $field => $fieldErrors) {
                        foreach ($fieldErrors as $error) {
                            $errorMessage .= $error . " ";
                        }
                    }
                    $this->jsonError(trim($errorMessage));
                } else {
                    $this->withModelErrors($prediction);
                    
                    // Get stocks using the injected service for the form
                    $stocks = $this->stockService->getStocks(true);
                    
                    // Re-render the form with errors
                    return view('predictions/create', [
                        'stocks' => $stocks,
                        'isEditing' => false,
                        'prediction' => $_POST,
                        'pageTitle' => 'Create Prediction'
                    ]);
                }
            }
        } catch (\Exception $e) {
            if ($this->isApiRequest()) {
                $this->jsonError("Error creating prediction: " . $e->getMessage());
            } else {
                $this->withError("Error creating prediction: " . $e->getMessage());
                $request->redirect('predictions/create');
            }
        }
    }

    /**
     * Show the prediction edit form
     */
    public function edit(Request $request)
    {
        // Get user data
        $user = Auth::user();
        $userId = Auth::id();
        
        // Get the prediction ID from the request
        $predictionId = $request->input('id');
        
        if (!$predictionId) {
            $this->withError("Missing prediction ID");
            $request->redirect('/predictions');
            return;
        }
        
        try {
            // Fetch the prediction with its related stock using Eloquent
            $predictionModel = Prediction::with('stock')
                ->where('prediction_id', $predictionId)
                ->where('user_id', $userId)
                ->first();
            
            if (!$predictionModel) {
                $this->withError("Prediction not found or you don't have permission to edit it");
                $request->redirect('/predictions');
                return;
            }
            
            // Check if prediction is still active
            if (!$predictionModel->is_active) {
                $this->withError("Cannot edit inactive predictions");
                $request->redirect('/predictions');
                return;
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
                'pageTitle' => 'Edit Prediction'
            ]);
        } catch (\Exception $e) {
            $this->withError("Error loading prediction: " . $e->getMessage());
            $request->redirect('/predictions');
        }
    }
    
    /**
     * Handle prediction update form submission
     */
    public function update(Request $request)
    {
        // Get user data
        $user = Auth::user();
        $userId = Auth::id();
        
        // Get the prediction ID from the request
        $predictionId = $request->input('prediction_id');
        
        if (!$predictionId) {
            if ($this->isApiRequest()) {
                $this->jsonError("Missing prediction ID");
            } else {
                $this->withError("Missing prediction ID");
                $request->redirect('/predictions');
            }
            return;
        }
        
        try {
            // Check if prediction exists and belongs to user using Eloquent
            $prediction = Prediction::where('prediction_id', $predictionId)
                                  ->where('user_id', $userId)
                                  ->first();
            
            if (!$prediction) {
                if ($this->isApiRequest()) {
                    $this->jsonError("Prediction not found or you don't have permission to edit it");
                } else {
                    $this->withError("Prediction not found or you don't have permission to edit it");
                    $request->redirect('/predictions');
                }
                return;
            }
            
            // Check if prediction can be edited (is still active)
            if (!$prediction->is_active) {
                if ($this->isApiRequest()) {
                    $this->jsonError("Cannot edit inactive predictions");
                } else {
                    $this->withError("Cannot edit inactive predictions");
                    $request->redirect('/predictions');
                }
                return;
            }
            
            // Update prediction attributes
            $prediction->prediction_type = $request->has('prediction_type') && !empty($request->input('prediction_type')) ? 
                            $request->input('prediction_type') : $prediction->prediction_type;
            
            $prediction->target_price = $request->has('target_price') && $request->input('target_price') !== '' ? 
                            (float) $request->input('target_price') : $prediction->target_price;
            
            $prediction->end_date = $request->has('end_date') && !empty($request->input('end_date')) ? 
                        $request->input('end_date') : $prediction->end_date;
            
            $prediction->reasoning = $request->has('reasoning') && !empty($request->input('reasoning')) ? 
                        $request->input('reasoning') : $prediction->reasoning;
            
            // Use model validation
            if ($prediction->validate()) {
                // Validation passed, save the prediction
                $prediction->save();
                
                if ($this->isApiRequest()) {
                    $this->jsonSuccess("Prediction updated successfully", [], '/predictions');
                } else {
                    $this->withSuccess("Prediction updated successfully");
                    $request->redirect('/predictions');
                }
            } else {
                // Get validation errors
                $errors = $prediction->getErrors();
                
                if ($this->isApiRequest()) {
                    $errorMessage = "Validation failed: ";
                    foreach ($errors as $field => $fieldErrors) {
                        foreach ($fieldErrors as $error) {
                            $errorMessage .= $error . " ";
                        }
                    }
                    $this->jsonError(trim($errorMessage));
                } else {
                    $this->withModelErrors($prediction);
                    
                    // Re-render the edit form with errors
                    $request->redirect('predictions/edit?id=' . $predictionId);
                }
            }
        } catch (\Exception $e) {
            if ($this->isApiRequest()) {
                $this->jsonError("Error updating prediction: " . $e->getMessage());
            } else {
                $this->withError("Error updating prediction: " . $e->getMessage());
                $request->redirect('/predictions');
            }
        }
    }
    
    /**
     * Handle prediction deletion
     */
    public function delete(Request $request)
    {
        $user = Auth::user();
        $userId = Auth::id();
        
        // Get the prediction ID from the request
        $predictionId = $request->input('prediction_id');
        
        if (!$predictionId) {
            if ($this->isApiRequest()) {
                $this->jsonError("Missing prediction ID");
            } else {
                $this->withError("Missing prediction ID");
                $request->redirect('/predictions');
            }
            return;
        }
        
        try {
            // Check if prediction exists and belongs to user using Eloquent
            $prediction = Prediction::where('prediction_id', $predictionId)
                                  ->where('user_id', $userId)
                                  ->first();
            
            if (!$prediction) {
                if ($this->isApiRequest()) {
                    $this->jsonError("Prediction not found or you don't have permission to delete it");
                } else {
                    $this->withError("Prediction not found or you don't have permission to delete it");
                    $request->redirect('/predictions');
                }
                return;
            }
            
            // Delete prediction using Eloquent
            $prediction->delete();
            
            if ($this->isApiRequest()) {
                $this->jsonSuccess("Prediction deleted successfully");
            } else {
                $this->withSuccess("Prediction deleted successfully");
                $request->redirect('/predictions');
            }
        } catch (\Exception $e) {
            if ($this->isApiRequest()) {
                $this->jsonError("Error deleting prediction: " . $e->getMessage());
            } else {
                $this->withError("Error deleting prediction: " . $e->getMessage());
                $request->redirect('/predictions');
            }
        }
    }
    
    /**
     * Show a specific prediction
     */
    public function view(Request $request)
    {
        // Get the prediction ID from the request
        $predictionId = $request->input('id');
        
        if (!$predictionId) {
            $this->withError("Missing prediction ID");
            $request->redirect('/predictions/trending');
            return;
        }
        
        try {
            // Use Eloquent with eager loading to get prediction with related data
            $prediction = Prediction::with(['stock', 'user', 'votes'])
                                  ->where('prediction_id', $predictionId)
                                  ->first();
            
            if (!$prediction) {
                $this->withError("Prediction not found");
                $request->redirect('/predictions/trending');
                return;
            }
            
            // Format data for the view
            $predictionData = $prediction->toArray();
            $predictionData['username'] = $prediction->user->first_name . ' ' . $prediction->user->last_name;
            $predictionData['upvotes'] = $prediction->votes->where('vote_type', 'upvote')->count();
            $predictionData['downvotes'] = $prediction->votes->where('vote_type', 'downvote')->count();
            
            // Include prediction score display component
            require_once __DIR__ . '/../../includes/prediction_score_display.php';
            
            // Render the view with the prediction data
            return view('predictions/view', [
                'prediction' => $predictionData,
                'pageTitle' => $prediction->stock->symbol . ' ' . $prediction->prediction_type . ' Prediction'
            ]);
        } catch (\Exception $e) {
            $this->withError("Error retrieving prediction: " . $e->getMessage());
            $request->redirect('/predictions/trending');
        }
    }
    
    /**
     * Show trending predictions
     */
    public function trending(Request $request)
    {
        try {
            // Get trending predictions using Eloquent ORM
            $trending_predictions = Prediction::select([
                    'predictions.prediction_id',
                    'users.id as user_id',
                    'users.reputation_score',
                    'stocks.symbol',
                    'predictions.prediction_type as prediction',
                    'predictions.accuracy',
                    'predictions.target_price',
                    'predictions.end_date',
                    'predictions.is_active'
                ])
                ->join('users', 'predictions.user_id', '=', 'users.id')
                ->join('stocks', 'predictions.stock_id', '=', 'stocks.stock_id')
                ->withCount(['votes as votes' => function($query) {
                    $query->where('vote_type', 'upvote');
                }])
                ->addSelect([
                    'users.first_name', 
                    'users.last_name'
                ])
                ->where(function($query) {
                    $query->where('predictions.is_active', 1)
                          ->orWhere(function($query) {
                              $query->whereNotNull('predictions.accuracy')
                                    ->where('predictions.accuracy', '>=', 70);
                          });
                })
                ->orderBy('votes', 'desc')
                ->orderBy('predictions.accuracy', 'desc')
                ->orderBy('predictions.prediction_date', 'desc')
                ->limit(15)
                ->get();
            
            // Map the results to include the full name as username
            $trending_predictions = $trending_predictions->map(function($prediction) {
                $prediction = $prediction->toArray();
                $prediction['username'] = $prediction['first_name'] . ' ' . $prediction['last_name'];
                return $prediction;
            })->toArray();
            
            // If no predictions found, use dummy data
            if (empty($trending_predictions)) {
                $trending_predictions = [
                    ['username' => 'Investor123', 'symbol' => 'AAPL', 'prediction' => 'Bullish', 'votes' => 120, 'accuracy' => 92],
                    ['username' => 'MarketGuru', 'symbol' => 'TSLA', 'prediction' => 'Bearish', 'votes' => 95, 'accuracy' => 85],
                    ['username' => 'StockSavvy', 'symbol' => 'AMZN', 'prediction' => 'Bullish', 'votes' => 75, 'accuracy' => null],
                ];
            }
            
            // Render the view with the trending predictions data
            return view('trending', [
                'trending_predictions' => $trending_predictions,
                'pageTitle' => 'Trending Predictions'
            ]);
        } catch (\Exception $e) {
            // Fallback to dummy data if an error occurs
            $trending_predictions = [
                ['username' => 'Investor123', 'symbol' => 'AAPL', 'prediction' => 'Bullish', 'votes' => 120, 'accuracy' => 92],
                ['username' => 'MarketGuru', 'symbol' => 'TSLA', 'prediction' => 'Bearish', 'votes' => 95, 'accuracy' => 85],
                ['username' => 'StockSavvy', 'symbol' => 'AMZN', 'prediction' => 'Bullish', 'votes' => 75, 'accuracy' => null],
            ];
            
            $this->withError("Error retrieving trending predictions: " . $e->getMessage());
            
            // Render the view with the fallback data
            return view('trending', [
                'trending_predictions' => $trending_predictions,
                'pageTitle' => 'Trending Predictions'
            ]);
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
        
        if (!$predictionId) {
            $this->jsonError("Missing prediction ID");
            return;
        }
        
        try {
            // Check if prediction exists
            $prediction = Prediction::find($predictionId);
            
            if (!$prediction) {
                $this->jsonError("Prediction not found");
                return;
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
                    $this->jsonSuccess("Vote updated successfully");
                } else {
                    // Remove vote if same type (toggle functionality)
                    $existingVote->delete();
                    $this->jsonSuccess("Vote removed successfully");
                }
            } else {
                // Create new vote
                $vote = new PredictionVote([
                    'prediction_id' => $predictionId,
                    'user_id' => $userId,
                    'vote_type' => $voteType,
                    'vote_date' => date('Y-m-d H:i:s')
                ]);
                
                $vote->save();
                $this->jsonSuccess("Vote recorded successfully");
            }
        } catch (\Exception $e) {
            $this->jsonError("Error processing vote: " . $e->getMessage());
        }
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
        if (!Auth::check()) {
            $this->json([
                'success' => false,
                'message' => 'User not logged in',
                'redirect' => '/login'
            ]);
            return;
        }
        
        $userId = Auth::id();
        
        try {
            // Verify user exists using Eloquent
            $user = User::find($userId);
            if (!$user) {
                $this->jsonError('User not found');
                return;
            }
        } catch (\Exception $e) {
            $this->jsonError('Database connection failed: ' . $e->getMessage());
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
     * @param int $userId The ID of the user making the request
     * @return void Outputs JSON response directly
     */
    public function apiGetPrediction($userId)
    {
        try {
            if (!request()->has('prediction_id') || empty(request()->input('prediction_id'))) {
                $this->jsonError("Missing prediction ID");
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
                
                $this->jsonSuccess("Prediction retrieved successfully", $predictionData);
            } else {
                $this->jsonError("Prediction not found or you don't have permission to view it");
            }
        } catch (Exception $e) {
            $this->jsonError("Error retrieving prediction: " . $e->getMessage());
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
            $req = request();
            //TODO: write code to create a prediction from the api
        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
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
            $req = request();
            //TODO: write code to update a prediction from the api
        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
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
            $req = request();
            //TODO: write code to delete a prediction from the api
        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }
}