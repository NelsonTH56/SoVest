<?php

namespace App\Http\Controllers;

use App\Services\Interfaces\PredictionScoringServiceInterface;
use App\Services\Interfaces\ResponseFormatterInterface;
use App\Models\PredictionVote;
use App\Http\Controllers\PredictionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Prediction;
use App\Models\User;
use Exception;


class UserController extends Controller
{
    protected $scoringService;

    public function __construct(ResponseFormatterInterface $responseFormatter, PredictionScoringServiceInterface $scoringService)
    {
        parent::__construct($responseFormatter);
        $this->scoringService = $scoringService;
    }

    public function home(Request $request)
    {
        $userID = Auth::id();
        $Curruser = Auth::user();

        // Get sort parameter (default to 'trending')
        $sort = $request->query('sort', 'trending');
        $validSorts = ['recent', 'trending', 'controversial'];
        if (!in_array($sort, $validSorts)) {
            $sort = 'trending';
        }

        // Build query with vote counts and comments count
        $query = Prediction::with(['user', 'stock'])
            ->withCount([
                'votes as upvotes' => function ($query) {
                    $query->where('vote_type', 'upvote');
                },
                'votes as downvotes' => function ($query) {
                    $query->where('vote_type', 'downvote');
                },
                'comments as comments_count'
            ]);

        // Apply sorting based on parameter
        // Calculate date thresholds for database-agnostic queries
        $sevenDaysAgo = now()->subDays(7)->toDateTimeString();
        $thirtyDaysAgo = now()->subDays(30)->toDateTimeString();

        switch ($sort) {
            case 'recent':
                $query->orderBy('prediction_date', 'desc');
                break;
            case 'trending':
                // Trending: High engagement (upvotes) in recent timeframe
                // Score = upvotes weighted by recency (posts from last 7 days get boost)
                $query->selectRaw("predictions.*,
                    (SELECT COUNT(*) FROM prediction_votes WHERE prediction_votes.prediction_id = predictions.prediction_id AND vote_type = 'upvote') as vote_score")
                    ->orderByRaw("
                        CASE
                            WHEN prediction_date >= '{$sevenDaysAgo}' THEN vote_score * 2
                            WHEN prediction_date >= '{$thirtyDaysAgo}' THEN vote_score * 1.5
                            ELSE vote_score
                        END DESC
                    ")
                    ->orderBy('prediction_date', 'desc');
                break;
            case 'controversial':
                // Controversial: High total engagement with balanced upvotes/downvotes
                // Posts with lots of both upvotes AND downvotes
                $query->selectRaw("predictions.*,
                    (SELECT COUNT(*) FROM prediction_votes WHERE prediction_votes.prediction_id = predictions.prediction_id AND vote_type = 'upvote') as up_count,
                    (SELECT COUNT(*) FROM prediction_votes WHERE prediction_votes.prediction_id = predictions.prediction_id AND vote_type = 'downvote') as down_count")
                    ->orderByRaw("
                        (SELECT COUNT(*) FROM prediction_votes WHERE prediction_votes.prediction_id = predictions.prediction_id) *
                        (1 - ABS(
                            (SELECT COUNT(*) FROM prediction_votes WHERE prediction_votes.prediction_id = predictions.prediction_id AND vote_type = 'upvote') -
                            (SELECT COUNT(*) FROM prediction_votes WHERE prediction_votes.prediction_id = predictions.prediction_id AND vote_type = 'downvote')
                        ) / MAX(
                            (SELECT COUNT(*) FROM prediction_votes WHERE prediction_votes.prediction_id = predictions.prediction_id), 1
                        )) DESC
                    ")
                    ->orderBy('prediction_date', 'desc');
                break;
        }

        $predictions = $query->paginate(10)->appends(['sort' => $sort]);

        // Get user's predictions with vote counts (limited to 5 for sidebar)
        $Userpredictions = collect();
        if ($userID) {
            $Userpredictions = Prediction::with(['user', 'stock'])
                ->withCount([
                    'votes as upvotes' => function ($query) {
                        $query->where('vote_type', 'upvote');
                    },
                    'votes as downvotes' => function ($query) {
                        $query->where('vote_type', 'downvote');
                    },
                    'comments as comments_count'
                ])
                ->where('user_id', $userID)
                ->orderBy('prediction_date', 'desc')
                ->limit(5)
                ->get();
        }

        // Get top 10 users for leaderboard card (cached for 5 minutes)
        $leaderboardUsers = cache()->remember('home:leaderboard', 300, function () {
            return $this->scoringService->getTopUsers(10);
        });

        // Get hot predictions for carousel (not cached - vote counts need to be fresh)
        // Hot = recent posts (last 7 days) from users in the top 10% by reputation
        // Falls back to most recent predictions if not enough from top users

        // Calculate the reputation threshold for top 10% of users
        $totalUsers = User::count();
        $top10PercentCount = max(1, (int) ceil($totalUsers * 0.10));

        $reputationThreshold = User::orderByDesc('reputation_score')
            ->skip($top10PercentCount - 1)
            ->take(1)
            ->value('reputation_score') ?? 0;

        // First try: Get predictions from top 10% users in last 7 days
        $hotPredictions = Prediction::with(['user', 'stock'])
            ->withCount([
                'votes as upvotes' => fn($q) => $q->where('vote_type', 'upvote'),
                'votes as downvotes' => fn($q) => $q->where('vote_type', 'downvote'),
                'comments as comments_count'
            ])
            ->whereHas('user', fn($q) => $q->where('reputation_score', '>=', $reputationThreshold))
            ->where('prediction_date', '>=', now()->subDays(7))
            ->where('is_active', 1)
            ->orderByDesc('prediction_date')
            ->limit(8)
            ->get();

        // Fallback: If not enough hot predictions, get most voted recent predictions
        if ($hotPredictions->count() < 3) {
            $hotPredictions = Prediction::with(['user', 'stock'])
                ->withCount([
                    'votes as upvotes' => fn($q) => $q->where('vote_type', 'upvote'),
                    'votes as downvotes' => fn($q) => $q->where('vote_type', 'downvote'),
                    'comments as comments_count'
                ])
                ->where('is_active', 1)
                ->orderByDesc('prediction_date')
                ->limit(8)
                ->get();
        }

        return view('home', compact('Curruser', 'predictions', 'Userpredictions', 'leaderboardUsers', 'hotPredictions', 'sort'));
    }


    /**
     * Display user account page
     * 
     *
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
        $Curruser = [
            'username' => $userData['email'],
            'full_name' => ($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? ''),
            'profile_picture' => $userData['profile_picture'],
            'reputation_score' => isset($userData['reputation_score']) ? $userData['reputation_score'] : 0,
            'avg_accuracy' => $userStats['avg_accuracy'],
            'predictions' => $predictions,
            'bio' => $userData['bio']
        ];
 

        return view('account', compact('Curruser', 'userStats'));

        /* Render the view
        return view('account', [
            'user' => $user,
            'userStats' => $userStats
        ]); */
    }

    /**
     * Display leaderboard page
     *
     * Implements caching to reduce database queries and improve performance.
     * Cache is refreshed every 5 minutes or when predictions are scored.
     */
    public function leaderboard()
    {
        $userData = Auth::user();
        $userID = Auth::id();

        if (!$userID) {
            return redirect()->route('login');
        }

        // Cache leaderboard data for 5 minutes
        $topUsers = cache()->remember('leaderboard:top_users', 300, function () {
            return $this->scoringService->getTopUsers(20);
        });

        // Find user's rank in the leaderboard
        $userRank = 0;
        $userInfo = null;

        foreach ($topUsers as $index => $user) {
            if ($user['id'] == $userID) {
                $userRank = $index + 1;
                $userInfo = $user;
                break;
            }
        }

        // If user is not in top 20, get their stats separately
        if ($userRank == 0) {
            // Cache individual user stats for 5 minutes
            $userStats = cache()->remember("user:stats:{$userID}", 300, function () use ($userID) {
                return $this->scoringService->getUserPredictionStats($userID);
            });

            $userModel = User::find($userID);
            if ($userModel) {
                $userInfo = [
                    'id' => $userModel->id,
                    'first_name' => $userModel->first_name,
                    'last_name' => $userModel->last_name,
                    'email' => $userModel->email,
                    'reputation_score' => $userModel->reputation_score,
                    'avg_accuracy' => $userStats['avg_accuracy'],
                    'prediction_count' => $userStats['total_predictions'] ?? 0,
                ];
            }
        }

        // Prepare current user data for display
        $Curruser = [
            'username' => $userData['email'],
            'full_name' => ($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? ''),
            'profile_picture' => $userData['profile_picture'],
            'reputation_score' => $userData['reputation_score'] ?? 0
        ];

        // Render the view
        return view('user.leaderboard', [
            'topUsers' => $topUsers,
            'userRank' => $userRank,
            'userInfo' => $userInfo,
            'userID' => $userID,
            'Curruser' => $Curruser,
            'pageCss' => 'css/leaderboard.css',
        ]);
    }

    public function uploadPhoto(Request $request)
    {
        // Validate the uploaded file
            $request->validate([
                'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:8000',
            ]);
        
            $user = Auth::user();
        
            $image = $request->file('profile_picture');
        
            // Give each file a unique name (timestamp + user ID)
            $filename = time() . '_user' . $user->id . '.' . $image->getClientOriginalExtension();
        
            // Move to the profile_pictures folder
            $image->move(public_path('images/profile_pictures'), $filename);
        
            // Save only the filename in DB
            $user->profile_picture = $filename;
            $user->save();
        
            return back()->with('success', 'Profile picture updated successfully.');
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

    public function updateBio(Request $request)
    {
        // Validate bio input
        $request->validate([
            'bio' => 'nullable|string|max:300',
        ]);

        $user = Auth::user(); // Get the currently authenticated user
        $user->bio = $request->input('bio'); // Update the bio field
        $user->save(); // Save the updated user

        return back()->with('success', 'Bio updated successfully.');
    }

    /**
     * Display settings page
     *
     * @return \Illuminate\View\View
     */
    public function settings()
    {
        $userData = Auth::user();
        $userID = Auth::id();

        // Get user statistics
        $userStats = $this->scoringService->getUserPredictionStats($userID);

        // Prepare user data for display
        $Curruser = [
            'id' => $userData['id'],
            'username' => $userData['email'],
            'full_name' => ($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? ''),
            'first_name' => $userData['first_name'],
            'last_name' => $userData['last_name'],
            'email' => $userData['email'],
            'profile_picture' => $userData['profile_picture'],
            'reputation_score' => isset($userData['reputation_score']) ? $userData['reputation_score'] : 0,
            'bio' => $userData['bio'],
            'created_at' => $userData['created_at']
        ];

        return view('settings', compact('Curruser', 'userStats'));
    }

    /**
     * Display public user profile
     *
     * @param int $id User ID
     * @return \Illuminate\View\View
     */
    public function profile($id)
    {
        $user = User::findOrFail($id);

        // Get user statistics
        $userStats = $this->scoringService->getUserPredictionStats($id);

        // Get user's recent public predictions (limited to 10)
        $recentPredictions = Prediction::with(['stock'])
            ->withCount([
                'votes as upvotes' => fn($q) => $q->where('vote_type', 'upvote'),
                'votes as downvotes' => fn($q) => $q->where('vote_type', 'downvote'),
                'comments as comments_count'
            ])
            ->where('user_id', $id)
            ->orderBy('prediction_date', 'desc')
            ->limit(10)
            ->get();

        // Current logged-in user for navbar
        $Curruser = null;
        if (Auth::check()) {
            $userData = Auth::user();
            $Curruser = [
                'username' => $userData['email'],
                'full_name' => ($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? ''),
                'profile_picture' => $userData['profile_picture'],
                'reputation_score' => $userData['reputation_score'] ?? 0
            ];
        }

        return view('user.profile', compact('user', 'userStats', 'recentPredictions', 'Curruser'));
    }

}