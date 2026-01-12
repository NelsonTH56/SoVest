<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PredictionComment extends Model
{
    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'comment_id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'prediction_id',
        'user_id',
        'parent_comment_id',
        'content',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Validation rules for the comment.
     */
    protected $rules = [
        'prediction_id' => 'required|exists:predictions,prediction_id',
        'user_id' => 'required|exists:users,id',
        'content' => 'required|string|max:600',
        'parent_comment_id' => 'nullable|exists:prediction_comments,comment_id',
    ];

    /**
     * Get the prediction that owns the comment.
     */
    public function prediction(): BelongsTo
    {
        return $this->belongsTo(Prediction::class, 'prediction_id', 'prediction_id');
    }

    /**
     * Get the user who wrote the comment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the parent comment (for replies).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(PredictionComment::class, 'parent_comment_id', 'comment_id');
    }

    /**
     * Get the replies to this comment.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(PredictionComment::class, 'parent_comment_id', 'comment_id')
                    ->orderBy('created_at', 'asc');
    }

    /**
     * Get the replies to this comment with nested replies (recursive).
     */
    public function allReplies(): HasMany
    {
        return $this->replies()->with('allReplies.user');
    }

    /**
     * Check if this comment is a reply.
     */
    public function isReply(): bool
    {
        return $this->parent_comment_id !== null;
    }

    /**
     * Get validation rules.
     */
    public function getRules(): array
    {
        return $this->rules;
    }
}
