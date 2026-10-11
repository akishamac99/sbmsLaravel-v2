<?php

namespace App\Models;

use App\Notifications\SbmsResetPassword;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\RoutesNotifications;

class Customer extends Authenticatable
{
    use RoutesNotifications;

    protected $table = 'customer';

    protected $primaryKey = 'customerID';

    const CREATED_AT = 'createdAt';

    const UPDATED_AT = null;

    protected $fillable = [
        'firstName', 'middleName', 'lastName', 'email', 'password', 'contactNumber',
        'home_address', 'accountStatus', 'is_verified', 'is_walkin',
    ];

    protected $hidden = ['password', 'reset_token', 'otp_code'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'is_walkin' => 'boolean'];
    }

    public function getRememberTokenName(): string
    {
        return '';
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->firstName.' '.$this->lastName);
    }

    /**
     * The address to show staff, or null when the customer gave none.
     * Old walk-in records used a generated placeholder address, which is hidden too.
     */
    public function getContactEmailAttribute(): ?string
    {
        $email = trim((string) $this->email);

        return $email === '' || str_ends_with($email, '@invalid.local') ? null : $email;
    }

    /**
     * What the account status means for this customer, in plain words.
     */
    public function getAccountTypeLabelAttribute(): string
    {
        return $this->is_walkin ? 'Walk-in' : 'Registered';
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'customerID', 'customerID');
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new SbmsResetPassword($token, 'customers'));
    }
}
