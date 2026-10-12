<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffAvailabilityRequest extends Model
{
    protected $table = 'staff_availability_requests';
    protected $primaryKey = 'requestID';
    public $timestamps = false;
    protected $guarded = ['requestID'];

    protected function casts(): array
    {
        return [
            'schedule' => 'array',
            'created_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staffID', 'staffID');
    }
}
