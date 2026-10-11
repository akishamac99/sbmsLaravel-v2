<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payment';
    protected $primaryKey = 'payID';

    public $timestamps = false;

    protected $guarded = ['payID'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payDate' => 'datetime',
        ];
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'bookingID', 'bookingID');
    }
}
