<?php

namespace App\Models;

use App\Models\Traits\ValidationTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Group extends Model
{
    use HasFactory, ValidationTrait;

    protected $table = 'groups';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'code',
        'description',
        'passcode',
        'admin_id',
    ];

    protected $hidden = [
        'passcode',
    ];

    /**
     * Get validation rules for Group model
     */
    protected function getValidationRules()
    {
        return [
            'name' => ['required', 'min:3', 'max:100'],
            'passcode' => ['required', 'min:4'],
        ];
    }

    /**
     * Get custom error messages for validation
     */
    protected function getValidationMessages()
    {
        return [
            'name.required' => 'Group name is required',
            'name.min' => 'Group name must be at least 3 characters',
            'name.max' => 'Group name cannot exceed 100 characters',
            'passcode.required' => 'Passcode is required',
            'passcode.min' => 'Passcode must be at least 4 characters',
        ];
    }

    /**
     * Hash the passcode before saving
     */
    public function setPasscodeAttribute($value)
    {
        $this->attributes['passcode'] = Hash::make($value);
    }

    /**
     * Verify a passcode against the stored hash
     */
    public function verifyPasscode(string $passcode): bool
    {
        return Hash::check($passcode, $this->attributes['passcode']);
    }

    /**
     * Check if a user is a member of this group
     */
    public function isMember(int $userId): bool
    {
        return $this->members()->where('users.id', $userId)->exists();
    }

    /**
     * Check if a user is the admin of this group
     */
    public function isAdmin(int $userId): bool
    {
        return $this->admin_id === $userId;
    }

    // Relationships

    /**
     * Get the admin user of this group
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Get all members of this group
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'group_members')
            ->withPivot('joined_at');
    }

    /**
     * Get member count
     */
    public function getMemberCountAttribute(): int
    {
        return $this->members()->count();
    }

    /**
     * Generate a unique group code
     */
    public static function generateUniqueCode(): string
    {
        do {
            // Generate 6-character alphanumeric code (uppercase)
            $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /**
     * Boot method to auto-generate code on creation
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($group) {
            if (empty($group->code)) {
                $group->code = self::generateUniqueCode();
            }
        });
    }
}
