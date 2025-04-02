<?php

namespace Database\Models;

use Illuminate\Database\Eloquent\Model;

class PredictionVote extends Model {
    // Table name
    protected $table = 'prediction_votes';

    // Primary key
    protected $primaryKey = 'vote_id';

    // Timestamps (using vote_date instead of created_at)
    public $timestamps = false;

    // Allow mass assignment for these columns
    protected $fillable = [
        'prediction_id',
        'user_id',
        'vote_type',
        'vote_date'
    ];

    // Relationships
    public function prediction()
    {
        return $this->belongsTo(Prediction::class, 'prediction_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}