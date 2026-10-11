<?php

namespace App\Models;

use App\Services\BookingService;
use App\Support\Format;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'service';

    protected $primaryKey = 'serviceID';

    const UPDATED_AT = null;

    protected $fillable = [
        'serviceName', 'catID', 'servicePrice', 'duration', 'description', 'image',
        'is_home_service', 'is_active',
    ];

    protected function casts(): array
    {
        return ['servicePrice' => 'decimal:2'];
    }

    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'catID', 'catID');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    // What customers can actually book. "Home Service Fee" is a price line, not a service.
    public function scopeCatalog($query)
    {
        return $query->where('is_active', 1)->where('serviceName', '<>', BookingService::HOME_FEE_NAME);
    }

    // Picture address: a full web link is used as it is, a saved upload comes from storage,
    // and a service with no picture falls back to the salon logo.
    public function getImageUrlAttribute(): string
    {
        $image = trim((string) $this->image);

        if ($image === '') {
            return asset('assets/images/salonLogo.jpg');
        }

        if (preg_match('#^(https?:)?//#i', $image) || str_starts_with($image, 'data:')) {
            return $image;
        }

        $image = ltrim(preg_replace('#^/?storage/#', '', $image), '/');

        // Pictures uploaded from the Services page are served by a route, so they work without storage:link.
        if (str_starts_with($image, 'services/')) {
            return route('media', ['path' => $image]);
        }

        return asset('storage/'.$image);
    }

    /**
     * Length of the service in whole minutes (the column stores a time such as 01:30:00).
     */
    public function getDurationMinutesAttribute(): int
    {
        if (! $this->duration) {
            return 0;
        }

        [$hours, $minutes] = array_map('intval', array_pad(explode(':', (string) $this->duration), 2, 0));

        return $hours * 60 + $minutes;
    }

    /**
     * Turn a number of minutes into the time value the duration column stores.
     */
    public static function minutesToDuration(int $minutes): string
    {
        return sprintf('%02d:%02d:00', intdiv($minutes, 60), $minutes % 60);
    }

    public function getDurationLabelAttribute(): string
    {
        return Format::duration($this->duration);
    }
}
