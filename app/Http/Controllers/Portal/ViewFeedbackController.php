<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** UC36 View Feedback. Owner and manager see all feedback; a salon staff member sees feedback about themselves. */
class ViewFeedbackController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $user = Auth::guard('staff')->user();
        $search = trim((string) ($filters['q'] ?? ''));

        try {
            $feedback = DB::table('feedback as f')
                ->join('customer as c', 'c.customerID', '=', 'f.customerID')
                ->join('service as s', 's.serviceID', '=', 'f.serviceID')
                ->join('salon_staff as st', 'st.staffID', '=', 'f.staffID')
                ->select('f.feedbackID', 'f.rating', 'f.service_rating', 'f.staff_rating', 'f.comment', 'f.created_at',
                    's.serviceName', DB::raw('"c"."firstName" as customer_first'), DB::raw('"st"."firstName" || \' \' || "st"."lastName" as staff_name'))
                ->when($user->role_slug === 'staff', fn ($q) => $q->where('f.staffID', $user->getKey()))
                ->when(isset($filters['rating']), fn ($q) => $q->where('f.rating', $filters['rating']))
                ->when($search !== '', fn ($q) => $q->where(fn ($i) => $i
                    ->whereRaw('LOWER(s."serviceName") LIKE ?', ['%'.mb_strtolower($search).'%'])
                    ->orWhereRaw('LOWER(f.comment) LIKE ?', ['%'.mb_strtolower($search).'%'])))
                ->orderByDesc('f.created_at')
                ->paginate(20)
                ->withQueryString();
        } catch (QueryException $e) {
            report($e);

            return view('portal.feedback', ['feedback' => null, 'role' => $user->role_slug, 'loadError' => true]);
        }

        return view('portal.feedback', ['feedback' => $feedback, 'role' => $user->role_slug, 'loadError' => false]);
    }
}
