<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\ValidationTrait;

/**
 * Stock Model
 * 
 * Represents a stock in the application
 */
class Stock extends Model {
    use ValidationTrait;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stocks';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'stock_id';

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
    protected $fillable = ['symbol', 'company_name', 'sector', 'active', 'created_at'];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'sector' => 'Unknown', // Default value for sector
    ];
    
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'active' => 'boolean'
        ];
    }

    /**
     * Get validation rules for Stock model
     *
     * @return array
     */
    protected function getValidationRules()
    {
        return [
            'symbol' => ['required', 'max:10', 'unique'],
            'company_name' => ['required'],
            'sector' => []
        ];
    }

    /**
     * Get custom error messages for validation
     *
     * @return array
     */
    protected function getValidationMessages()
    {
        return [
            'symbol.required' => 'Stock symbol is required',
            'symbol.max' => 'Stock symbol cannot exceed 10 characters',
            'symbol.unique' => 'This stock symbol is already registered in the system',
            'company_name.required' => 'Company name is required'
        ];
    }

    /**
     * Validate uniqueness of stock symbol in database
     * 
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @return boolean
     */
    public function validateUnique($attribute, $value, $parameters = [])
    {
        if (empty($value)) {
            return true;
        }

        // Build query to check for existing records
        $query = self::where($attribute, $value);
        
        // If updating an existing record, exclude the current record
        if ($this->exists) {
            $query->where($this->primaryKey, '!=', $this->{$this->primaryKey});
        }
        
        // If a record with this value exists, validation fails
        if ($query->exists()) {
            $this->addError($attribute, $this->getMessage($attribute, 'unique', "The $attribute has already been taken."));
            return false;
        }
        
        return true;
    }
    
    /**
     * Get the predictions associated with the stock.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function predictions()
    {
        return $this->hasMany(Prediction::class, 'stock_id');
    }
    
    /**
     * Get the price history for the stock.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function prices()
    {
        return $this->hasMany(StockPrice::class, 'stock_id');
    }
}