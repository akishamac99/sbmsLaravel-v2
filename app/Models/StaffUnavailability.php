<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffUnavailability extends Model
{
    protected $table = 'staff_unavailability';
    protected $primaryKey = 'unavailID';

    public $timestamps = false;

    protected $guarded = ['unavailID'];

    protected function casts(): array
    {
        return ['unavailable_date' => 'date'];
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staffID', 'staffID');
    }
}
