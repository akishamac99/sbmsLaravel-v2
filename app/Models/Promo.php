<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    protected $table = 'promos';
    protected $primaryKey = 'promoID';

    const UPDATED_AT = null;

    protected $guarded = ['promoID'];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }
}
