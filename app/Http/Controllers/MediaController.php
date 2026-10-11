<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves uploaded service pictures from the "public" disk.
 *
 * Going through a route (instead of /storage/...) means pictures show up even on a
 * machine where `php artisan storage:link` was never run.
 */
class MediaController extends Controller
{
    public function __invoke(string $path): Response
    {
        abort_if(str_contains($path, '..') || ! Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, null, [
            'Cache-Control' => 'public, max-age=604800',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
