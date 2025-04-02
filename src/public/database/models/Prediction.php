<?php

namespace Database\Models;

use Illuminate\Database\Eloquent\Model;

class Prediction extends Model {
    // Table name
    protected $table = 'predictions';

    // Primary key
    protected $primaryKey = 'prediction_id';

    // Timestamps (using prediction_date instead of created_at)
    public $timestamps = false;

    // Allow mass assignment for these columns
    protected $fillable = [
        'user_id',
        'stock_id',
        'prediction_type',
        'target_price',
        'prediction_date',
        'end_date',
        'is_active',
        'accuracy',
        'reasoning'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }

    public function votes()
    {
        return $this->hasMany(PredictionVote::class, 'prediction_id');
    }
}