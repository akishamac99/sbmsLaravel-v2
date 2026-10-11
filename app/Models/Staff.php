<?php

namespace App\Models;

use App\Notifications\SbmsResetPassword;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\RoutesNotifications;

class Staff extends Authenticatable
{
    use RoutesNotifications;

    protected $table = 'salon_staff';
    protected $primaryKey = 'staffID';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'firstName', 'middleName', 'lastName', 'username', 'password', 'email', 'role',
        'specialization', 'contactNumber', 'emergency_contact_name', 'emergency_contact_number',
        'schedule', 'is_active', 'is_archived',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    public function getRememberTokenName(): string
    {
        return '';
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    // owner | manager | staff (used for routes, redirects and log entries)
    public function getRoleSlugAttribute(): string
    {
        return strtolower($this->role ?? 'staff');
    }

    public function isOwner(): bool
    {
        return $this->role_slug === 'owner';
    }

    public function isManager(): bool
    {
        return $this->role_slug === 'manager';
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'staffID', 'staffID');
    }

    public function availability()
    {
        return $this->hasMany(StaffAvailability::class, 'staffID', 'staffID');
    }

    public function unavailability()
    {
        return $this->hasMany(StaffUnavailability::class, 'staffID', 'staffID');
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new SbmsResetPassword($token, 'staff'));
    }
}
