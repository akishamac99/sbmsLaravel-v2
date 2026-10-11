<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingCart extends Model
{
    protected $table = 'booking_cart';
    protected $primaryKey = 'cartID';

    const UPDATED_AT = null;

    protected $guarded = ['cartID'];

    public function service()
    {
        return $this->belongsTo(Service::class, 'serviceID', 'serviceID');
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staffID', 'staffID');
    }
}
