<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Traits\ValidationTrait;

class User extends Authenticatable {
    use HasFactory, Notifiable, ValidationTrait;

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

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        //'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get validation rules for User model
     * 
     * @return array
     */
    protected function getValidationRules()
    {
        return [
            'email' => ['required', 'email', 'unique'],
            'password' => ['required', 'min:6'], // This clearly specifies min length of 6
            'first_name' => ['max:50'],
            'last_name' => ['max:50']
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
            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'This email address is already registered',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 6 characters long',
            'first_name.max' => 'First name cannot exceed 50 characters',
            'last_name.max' => 'Last name cannot exceed 50 characters'
        ];
    }

    /**
     * Validate uniqueness of email in database
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
    
    // Full name accessor
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}