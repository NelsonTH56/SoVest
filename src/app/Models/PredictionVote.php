<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * PredictionVote Model
 * 
 * Represents a user's vote on a stock prediction.
 */
class PredictionVote extends Model {
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'prediction_votes';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'vote_id';

    /**
     * Indicates if the model should be timestamped.
     * 
     * This model uses a custom timestamp field (vote_date) instead of Laravel's defaults.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'prediction_id',
        'user_id',
        'vote_type',
        'vote_date'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'vote_date' => 'datetime',
        ];
    }

    /**
     * Get the prediction this vote belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function prediction()
    {
        return $this->belongsTo(Prediction::class, 'prediction_id');
    }

    /**
     * Get the user that owns this vote.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}