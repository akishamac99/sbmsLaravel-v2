<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceCategory extends Model
{
    protected $table = 'service_categories';
    protected $primaryKey = 'catID';

    const UPDATED_AT = null;

    protected $fillable = ['category_name', 'description', 'is_event_type'];

    public function services()
    {
        return $this->hasMany(Service::class, 'catID', 'catID');
    }

    // The little icon shown next to the category name.
    public function getIconAttribute(): string
    {
        return [
            'Hair Services' => 'fa-cut',
            'Massage Services' => 'fa-hand-holding-heart',
            'Makeup Services' => 'fa-paint-brush',
            'Wedding Makeup' => 'fa-ring',
            'Facial Services' => 'fa-face-smile',
            'Eyelash Services' => 'fa-eye',
            'Brow Services' => 'fa-archway',
            'Waxing Services' => 'fa-feather',
            'Hand & Foot Spa' => 'fa-hand-peace',
            'Nail Services' => 'fa-paint-roller',
        ][$this->category_name] ?? 'fa-spa';
    }
}
