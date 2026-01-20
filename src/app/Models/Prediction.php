<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\ValidationTrait;

class Prediction extends Model {
    use ValidationTrait;

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
        'reasoning',
        'upvotes',
        'viewed_at'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'prediction_date' => 'datetime',
            'end_date' => 'datetime',
            'target_price' => 'float',
            'accuracy' => 'float',
            'is_active' => 'boolean',
            'viewed_at' => 'datetime',
        ];
    }

    /**
     * Check if this prediction has been viewed by its owner
     */
    public function isViewed(): bool
    {
        return $this->viewed_at !== null;
    }

    /**
     * Check if this prediction is unread (not viewed)
     */
    public function isUnread(): bool
    {
        return $this->viewed_at === null;
    }

    /**
     * Mark this prediction as viewed
     */
    public function markAsViewed(): bool
    {
        if ($this->viewed_at === null) {
            $this->viewed_at = now();
            return $this->save();
        }
        return true;
    }

    /**
     * Get validation rules for Prediction model
     * 
     * @return array<string, mixed>
     */
    protected function getValidationRules()
    {
        return [
            'user_id' => ['required', 'exists'],
            'stock_id' => ['required', 'exists'],
            'prediction_type' => ['required', 'in:Bullish,Bearish'],
            'target_price' => ['numeric'],
            'end_date' => ['required', 'date', 'futureDate'],
            'reasoning' => ['required']
        ];
    }

    /**
     * Get custom validation messages for Prediction model
     *
     * @return array<string, string>
     */
    protected function getValidationMessages()
    {
        return [
            'user_id.required' => 'User ID is required',
            'user_id.exists' => 'The selected user does not exist',
            'stock_id.required' => 'Stock ID is required',
            'stock_id.exists' => 'The selected stock does not exist',
            'prediction_type.required' => 'Prediction type is required',
            'prediction_type.in' => 'Prediction type must be either Bullish or Bearish',
            'target_price.numeric' => 'Target price must be a numeric value',
            'end_date.required' => 'End date is required',
            'end_date.date' => 'End date must be a valid date',
            'end_date.futureDate' => 'End date must be at least 7 days in the future',
            'reasoning.required' => 'Reasoning for your prediction is required'
        ];
    }

    /**
     * Validate if a record exists in the database
     * 
     * @param string $attribute Attribute name being validated
     * @param mixed $value Value to validate
     * @param array $parameters Additional parameters
     * @return boolean Whether validation passes
     */
    public function validateExists($attribute, $value, $parameters = [])
    {
        if (empty($value)) {
            return true;
        }

        // Determine the table and column to check
        $table = null;
        if ($attribute === 'user_id') {
            $model = new User();
            $table = $model->getTable();
            $column = $model->getKeyName();
        } elseif ($attribute === 'stock_id') {
            $model = new Stock();
            $table = $model->getTable();
            $column = $model->getKeyName();
        } else {
            $this->addError($attribute, "Cannot validate existence for $attribute");
            return false;
        }

        // Check if the record exists
        $exists = $model->where($column, $value)->exists();
        
        if (!$exists) {
            $this->addError($attribute, $this->getMessage($attribute, 'exists', "The selected $attribute does not exist."));
            return false;
        }
        
        return true;
    }

    /**
     * Check if a date is a business day (Monday to Friday)
     * 
     * @param \DateTime $date The date to check
     * @return boolean Whether the date is a business day
     */
    protected function isBusinessDay(\DateTime $date)
    {
        $dayOfWeek = (int)$date->format('N'); // 1 (Monday) to 7 (Sunday)
        return $dayOfWeek >= 1 && $dayOfWeek <= 5; // Monday to Friday
    }

    /**
     * Get the next business day from a given date
     * 
     * @param \DateTime $date The starting date
     * @return \DateTime The next business day
     */
    protected function getNextBusinessDay(\DateTime $date)
    {
        $nextDay = clone $date;
        $nextDay->modify('+1 day');
        
        // Keep adding days until we find a business day
        while (!$this->isBusinessDay($nextDay)) {
            $nextDay->modify('+1 day');
        }
        
        return $nextDay;
    }

    /**
     * Calculate a date that is N business days from a given date
     * 
     * @param \DateTime $startDate The starting date
     * @param int $businessDays Number of business days to add
     * @return \DateTime The resulting date
     */
    protected function addBusinessDays(\DateTime $startDate, int $businessDays)
    {
        $result = clone $startDate;
        
        // If the start date is not a business day, start from the next business day
        if (!$this->isBusinessDay($result)) {
            $result = $this->getNextBusinessDay($result);
            // We already moved to the first business day, so subtract 1 from the total
            $businessDays--;
        }
        
        // Add the remaining business days
        while ($businessDays > 0) {
            $result->modify('+1 day');
            if ($this->isBusinessDay($result)) {
                $businessDays--;
            }
        }
        
        return $result;
    }

    /**
     * Validate that a date is at least 7 days in the future
     *
     * @param string $attribute Attribute name being validated
     * @param mixed $value Date value to validate
     * @param array $parameters Additional parameters
     * @return boolean Whether validation passes
     */
    public function validateFutureDate($attribute, $value, $parameters = [])
    {
        if (empty($value)) {
            return true;
        }

        // Parse the date
        $date = date_parse($value);
        if ($date['error_count'] > 0 || !checkdate($date['month'], $date['day'], $date['year'])) {
            // This is already checked by the date validator
            return true;
        }

        // Convert to a DateTime object
        $dateObj = new \DateTime($value);
        $dateObj->setTime(0, 0, 0);

        // Set today's time to midnight for accurate comparison
        $today = new \DateTime();
        $today->setTime(0, 0, 0);

        // Calculate the minimum allowable date (7 days from today)
        $minDate = clone $today;
        $minDate->modify('+7 days');

        // Check if the date is at least 7 days in the future
        if ($dateObj < $minDate) {
            $minDateStr = $minDate->format('l, F j, Y');
            $this->addError($attribute, $this->getMessage($attribute, 'futureDate',
                "The $attribute must be at least 7 days from today ($minDateStr or later)."));
            return false;
        }

        return true;
    }

    /**
     * Get the user that owns the prediction.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the stock for this prediction.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function stock()
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }

    /**
     * Get the votes for this prediction.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function votes()
    {
        return $this->hasMany(PredictionVote::class, 'prediction_id');
    }

    /**
     * Get the comments for this prediction.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->hasMany(PredictionComment::class, 'prediction_id', 'prediction_id');
    }
}