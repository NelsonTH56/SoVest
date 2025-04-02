<?php

namespace Database\Models;

use Illuminate\Database\Eloquent\Model;

class SearchHistory extends Model {
    // Table name
    protected $table = 'search_history';

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