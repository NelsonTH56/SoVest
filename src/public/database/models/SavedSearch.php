<?php

namespace Database\Models;

use Illuminate\Database\Eloquent\Model;

class SavedSearch extends Model {
    // Table name
    protected $table = 'saved_searches';

    // Primary key
    protected $primaryKey = 'id';

    // Only has created_at, not updated_at
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    // Allow mass assignment for these columns
    protected $fillable = [
        'user_id',
        'search_query',
        'search_type'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}