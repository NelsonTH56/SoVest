<?php

namespace Database\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model {
    // Table name
    protected $table = 'users';

    // Primary key
    protected $primaryKey = 'id';

    // Timestamps are enabled in this table
    public $timestamps = true;

    // Allow mass assignment for these columns
    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name',
        'major',
        'year',
        'scholarship',
        'reputation_score'
    ];

    // Relationships
    public function predictions()
    {
        return $this->hasMany(Prediction::class, 'user_id');
    }

    public function predictionVotes()
    {
        return $this->hasMany(PredictionVote::class, 'user_id');
    }

    public function searchHistory()
    {
        return $this->hasMany(SearchHistory::class, 'user_id');
    }

    public function savedSearches()
    {
        return $this->hasMany(SavedSearch::class, 'user_id');
    }
}