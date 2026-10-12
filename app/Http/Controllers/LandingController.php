<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\BookingService;
use App\Support\TimeSlots;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

// The public home page (your old index.php).
class LandingController extends Controller
{
    private const ICONS = [
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
    ];

    private const GALLERY = [
        'curl.jpg', 'nail1.jpg', 'hair.jpg', 'mu.jpg', 'eyelash.jpg', 'wax.jpg',
        'nails.jpg', 'hairs.jpg', 'hair1.jpg', 'makeup.jpg', 'nail1.jpg', 'men.jpg',
    ];

    public function __invoke(): View
    {
        // "Home Service Fee" is a price line, not a real service, so it is never counted or listed.
        $categories = ServiceCategory::query()
            ->withCount(['services' => fn ($q) => $q
                ->where('is_active', 1)
                ->where('serviceName', '<>', BookingService::HOME_FEE_NAME)])
            ->orderBy('catID')
            ->get()
            ->each(fn ($category) => $category->icon = self::ICONS[$category->category_name] ?? 'fa-spa');

        $popularServices = Service::catalog()
            ->with('category')
            ->orderBy('servicePrice')
            ->limit(8)
            ->get();

        // Only feedback that has a written comment. Names are shortened: "Maria S."
        $testimonials = DB::table('feedback as f')
            ->join('service as s', 'f.serviceID', '=', 's.serviceID')
            ->join('customer as c', 'f.customerID', '=', 'c.customerID')
            ->leftJoin('salon_staff as st', 'f.staffID', '=', 'st.staffID')
            ->whereRaw("TRIM(COALESCE(f.comment, '')) <> ''")
            ->orderByDesc('f.created_at')
            ->orderByDesc('f.feedbackID')
            ->limit(12)
            ->get([
                'f.rating', 'f.service_rating', 'f.comment', 's.serviceName',
                'c.firstName', 'c.lastName',
                'st.firstName as staffFirst', 'st.lastName as staffLast',
            ])
            ->map(fn ($row) => (object) [
                'stars' => max(1, min(5, (int) ($row->rating ?? $row->service_rating ?? 5))),
                'comment' => $row->comment,
                'customerName' => trim($row->firstName . ' ' . mb_substr((string) $row->lastName, 0, 1)) . '.',
                'serviceName' => $row->serviceName,
                'staffName' => trim(($row->staffFirst ?? '') . ' ' . ($row->staffLast ?? '')),
            ]);

        $feedbackTotals = DB::table('feedback')
            ->selectRaw('COUNT(*) as total, AVG(COALESCE(rating, service_rating)) as average')
            ->first();

        return view('landing', [
            'categories' => $categories,
            'popularServices' => $popularServices,
            'testimonials' => $testimonials,
            'feedbackSummary' => (object) [
                'total' => (int) ($feedbackTotals->total ?? 0),
                'average' => $feedbackTotals->average !== null ? round((float) $feedbackTotals->average, 1) : null,
            ],
            'openingHours' => TimeSlots::summary(),
            'gallery' => array_map(fn ($file) => asset('assets/images/' . $file), self::GALLERY),
        ]);
    }
}