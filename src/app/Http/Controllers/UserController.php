<?php

namespace App\Http\Controllers;

use App\Services\Interfaces\PredictionScoringServiceInterface;
use App\Services\Interfaces\ResponseFormatterInterface;
use App\Models\PredictionVote;
use App\Http\Controllers\PredictionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Prediction;
use Exception;


class UserController extends Controller
{
    protected $scoringService;

    public function __construct(ResponseFormatterInterface $responseFormatter, PredictionScoringServiceInterface $scoringService)
    {
        parent::__construct($responseFormatter);
        $this->scoringService = $scoringService;
    }

    public function home()
    {

        $predictions = Prediction::with('user')->orderBy('prediction_date', 'desc')->paginate(10);
        $userID = Auth::id();
        $Userpredictions = Prediction::with('user')->orderBy('prediction_date', 'desc')
            ->where('user_id', $userID) // Replace $userId with the actual ID or variable
            ->orderBy('prediction_date', 'desc')
            ->paginate(10);
        return view('home', compact('predictions'), compact('Userpredictions'));
    }

    /**
     * Display user account page
     * 
     * @return void
     */
    public function account()
    {
        // Get current user data
        $userData = Auth::user();
        $userID = Auth::id();
        
        // Use the injected scoring service to get user stats
        $userStats = $this->scoringService->getUserPredictionStats($userID);
        
        try {
            // Get user predictions with related stock data
            $predictionModels = Prediction::with('stock')
                ->where('user_id', $userID)
                ->orderBy('prediction_date', 'DESC')
                ->limit(5)
                ->get();
            
            $predictions = [];
            
            if ($predictionModels->count() > 0) {
                foreach ($predictionModels as $prediction) {
                    $row = [
                        'prediction_id' => $prediction->prediction_id,
                        'symbol' => $prediction->stock->symbol,
                        'prediction' => $prediction->prediction_type,
                        'accuracy' => $prediction->accuracy,
                        'target_price' => $prediction->target_price,
                        'end_date' => $prediction->end_date,
                        'is_active' => $prediction->is_active
                    ];
                    
                    // Keep the raw accuracy value for styling
                    $row['raw_accuracy'] = $row['accuracy'];
                    
                    // Format accuracy as percentage if not null
                    if ($row['accuracy'] !== null) {
                        $row['accuracy'] = number_format($row['accuracy'], 0) . '%';
                    } else {
                        $row['accuracy'] = 'Pending';
                    }
                    
                    $predictions[] = $row;
                }
            }
        } catch (Exception $e) {
            // Error handling
            error_log('Error fetching predictions: ' . $e->getMessage());
            //$this->withError('Error fetching predictions: ' . $e->getMessage());
            $predictions = [];
        }
        
        // Prepare user data for display
        $user = [
            'username' => $userData['email'],
            'full_name' => ($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? ''),
            'bio' => $userData['major'] ? $userData['major'] . ' | ' . $userData['year'] : 'Stock enthusiast',
            'profile_picture' => 'images/logo.png',
            'reputation_score' => isset($userData['reputation_score']) ? $userData['reputation_score'] : 0,
            'avg_accuracy' => $userStats['avg_accuracy'],
            'predictions' => $predictions
        ];
 
        // Render the view
        return view('account', [
            'user' => $user,
            'userStats' => $userStats
        ]);
    }

    /**
     * Display leaderboard page
     * 
     * @return void
     */
    public function leaderboard()
    {
        // Use the injected scoring service to get top users
        $topUsers = $this->scoringService->getTopUsers(10);
        
        // Set page title
        $pageTitle = 'Leaderboard';
        
        // Render the view
        return view('leaderboard', [
            'topUsers' => $topUsers,
            'pageTitle' => $pageTitle
        ]);
    }

    public function vote(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:predictions,prediction_id',
            'action' => 'required|in:upvote,downvote'
        ]);
    
        $userId = Auth::id();
        $predictionId = $request->id;
        $voteType = $request->action;
    
        $existingVote = PredictionVote::where('prediction_id', $predictionId)
                                      ->where('user_id', $userId)
                                      ->first();
    
        if ($existingVote) {
            if ($existingVote->vote_type === $voteType) {
                $existingVote->delete(); // toggle off
            } else {
                $existingVote->vote_type = $voteType;
                $existingVote->save(); // switch vote
            }
        } else {
            PredictionVote::create([
                'prediction_id' => $predictionId,
                'user_id' => $userId,
                'vote_type' => $voteType,
            ]);
        }
    
        $upvotes = PredictionVote::where('prediction_id', $predictionId)->where('vote_type', 'upvote')->count();
        $downvotes = PredictionVote::where('prediction_id', $predictionId)->where('vote_type', 'downvote')->count();
    
        return response()->json([
            'success' => true,
            'upvotes' => $upvotes,
            'downvotes' => $downvotes
        ]);
    }
}