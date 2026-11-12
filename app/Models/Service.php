<?php

namespace App\Models;

use App\Services\BookingService;
use App\Support\Format;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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

    // Picture address: a full web link is used as it is. Otherwise the picture is looked for in
    // public/assets/images (pictures shipped with the app, even if only a file name is saved) and then in
    // storage (pictures uploaded from the Services page). If the file is missing, the salon logo is shown
    // instead of a broken image.
    public function getImageUrlAttribute(): string
    {
        $fallback = asset('assets/images/salonLogo.jpg');
        $image = trim((string) $this->image);

        if ($image === '') {
            return $fallback;
        }

        if (preg_match('#^(https?:)?//#i', $image) || str_starts_with($image, 'data:')) {
            return $image;
        }

        $image = ltrim(preg_replace('#^/?(public/)?storage/#', '', $image), '/');

        if (str_contains($image, '..')) {
            return $fallback;
        }

        $shipped = 'assets/images/'.basename($image);

        if (is_file(public_path($image))) {
            return asset($image);
        }

        if (is_file(public_path($shipped))) {
            return asset($shipped);
        }

        if (Storage::disk('public')->exists($image)) {
            // Uploaded pictures are served by a route, so they work without storage:link.
            return str_starts_with($image, 'services/')
                ? route('media', ['path' => $image])
                : asset('storage/'.$image);
        }

        return $fallback;
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
