<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $table = 'feedback';
    protected $primaryKey = 'feedbackID';

    const UPDATED_AT = null;

    protected $guarded = ['feedbackID'];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'bookingID', 'bookingID');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customerID', 'customerID');
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staffID', 'staffID');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'serviceID', 'serviceID');
    }
}
