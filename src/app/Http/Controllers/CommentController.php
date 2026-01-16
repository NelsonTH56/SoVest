<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PredictionComment;
use App\Models\Prediction;
use App\Services\Interfaces\ResponseFormatterInterface;
use Exception;

class CommentController extends Controller
{
    public function __construct(ResponseFormatterInterface $responseFormatter)
    {
        parent::__construct($responseFormatter);
    }

    /**
     * Store a new comment.
     */
    public function store(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'You must be logged in to comment'
            ], 401);
        }

        $validated = $request->validate([
            'prediction_id' => 'required|exists:predictions,prediction_id',
            'content' => 'required|string|max:600',
            'parent_comment_id' => 'nullable|exists:prediction_comments,comment_id',
        ]);

        try {
            // Check if prediction exists
            $prediction = Prediction::find($validated['prediction_id']);
            if (!$prediction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Prediction not found'
                ], 404);
            }

            // Create the comment
            $comment = new PredictionComment([
                'prediction_id' => $validated['prediction_id'],
                'user_id' => Auth::id(),
                'content' => $validated['content'],
                'parent_comment_id' => $validated['parent_comment_id'] ?? null,
            ]);

            $comment->save();

            // Load the user relationship for the response
            $comment->load('user');

            return response()->json([
                'success' => true,
                'message' => 'Comment posted successfully',
                'data' => [
                    'comment_id' => $comment->comment_id,
                    'content' => $comment->content,
                    'created_at' => $comment->created_at->format('M j, Y g:i A'),
                    'user' => [
                        'id' => $comment->user->id,
                        'name' => $comment->user->first_name . ' ' . $comment->user->last_name,
                        'reputation_score' => $comment->user->reputation_score ?? 0,
                    ],
                    'is_reply' => $comment->isReply(),
                    'parent_comment_id' => $comment->parent_comment_id,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error posting comment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a comment.
     */
    public function destroy(Request $request, int $commentId)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'You must be logged in to delete comments'
            ], 401);
        }

        try {
            $comment = PredictionComment::find($commentId);

            if (!$comment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comment not found'
                ], 404);
            }

            // Check if user owns the comment
            if ($comment->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only delete your own comments'
                ], 403);
            }

            $comment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Comment deleted successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting comment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get comments for a prediction (for lazy loading/pagination).
     */
    public function index(Request $request, int $predictionId)
    {
        try {
            $prediction = Prediction::find($predictionId);

            if (!$prediction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Prediction not found'
                ], 404);
            }

            // Get top-level comments with their replies
            $comments = PredictionComment::with(['user', 'replies.user'])
                ->where('prediction_id', $predictionId)
                ->whereNull('parent_comment_id')
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            $formattedComments = $comments->map(function ($comment) {
                return $this->formatComment($comment);
            });

            return response()->json([
                'success' => true,
                'data' => $formattedComments,
                'pagination' => [
                    'current_page' => $comments->currentPage(),
                    'last_page' => $comments->lastPage(),
                    'per_page' => $comments->perPage(),
                    'total' => $comments->total(),
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching comments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format a comment for JSON response.
     */
    private function formatComment($comment)
    {
        $formatted = [
            'comment_id' => $comment->comment_id,
            'content' => $comment->content,
            'created_at' => $comment->created_at->format('M j, Y g:i A'),
            'user' => [
                'id' => $comment->user->id,
                'name' => $comment->user->first_name . ' ' . $comment->user->last_name,
                'reputation_score' => $comment->user->reputation_score ?? 0,
            ],
            'is_reply' => $comment->isReply(),
            'parent_comment_id' => $comment->parent_comment_id,
            'replies' => [],
        ];

        // Format replies recursively
        if ($comment->replies && $comment->replies->count() > 0) {
            $formatted['replies'] = $comment->replies->map(function ($reply) {
                return $this->formatComment($reply);
            })->toArray();
        }

        return $formatted;
    }
}
