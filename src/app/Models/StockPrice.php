<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * StockPrice Model
 * 
 * Represents historical stock price data for a specific stock
 * 
 * @property int $price_id
 * @property int $stock_id
 * @property \DateTime $price_date
 * @property float $open_price
 * @property float $close_price
 * @property float $high_price
 * @property float $low_price
 * @property int $volume
 */
class StockPrice extends Model {
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'stock_prices';

    /**
     * The primary key for the model.
     * 
     * @var string
     */
    protected $primaryKey = 'price_id';

    /**
     * Indicates if the model should be timestamped.
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
        'stock_id',
        'price_date',
        'open_price',
        'close_price',
        'high_price',
        'low_price',
        'volume'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_date' => 'datetime',
            'open_price' => 'float',
            'close_price' => 'float',
            'high_price' => 'float',
            'low_price' => 'float',
            'volume' => 'integer',
        ];
    }

    /**
     * Get the stock that owns the price.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function stock()
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }
}