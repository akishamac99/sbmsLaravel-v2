<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpToken extends Model
{
    protected $table = 'otp_storage';

    public $timestamps = false;

    protected $fillable = [
        'contact_number', 'otp_code', 'otp_type', 'expires_at', 'is_used', 'used_at', 'attempts',
    ];

    protected function casts(): array
    {
        return [
            'otp_code' => 'string',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
