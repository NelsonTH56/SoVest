<?php

namespace Database\Models; // Change this if you're using a different namespace

use Illuminate\Database\Eloquent\Model;

class Stock extends Model {
    // Table name (optional if follows Laravel's naming convention)
    protected $table = 'stocks';

    // Primary key (optional, as Eloquent assumes 'id' by default)
    protected $primaryKey = 'stock_id';

    // Disable timestamps if the table does not have `updated_at`
    public $timestamps = false;

    // Allow mass assignment for these columns
    protected $fillable = ['symbol', 'company_name', 'sector', 'created_at'];

    // Set default values
    protected $attributes = [
        'sector' => 'Unknown', // Default value for sector
    ];
    
    // Relationships
    public function predictions()
    {
        return $this->hasMany(Prediction::class, 'stock_id');
    }
    
    public function prices()
    {
        return $this->hasMany(StockPrice::class, 'stock_id');
    }
}