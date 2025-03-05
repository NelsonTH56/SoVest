<?php

namespace Database\Models;

use Illuminate\Database\Eloquent\Model;

class StockPrice extends Model {
    // Table name
    protected $table = 'stock_prices';

    // Primary key
    protected $primaryKey = 'price_id';

    // No timestamps
    public $timestamps = false;

    // Allow mass assignment for these columns
    protected $fillable = [
        'stock_id',
        'price_date',
        'open_price',
        'close_price',
        'high_price',
        'low_price',
        'volume'
    ];

    // Relationships
    public function stock()
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }
}