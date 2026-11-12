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

            // Totals for the summary card (same staff scoping, ignores the search and rating filters).
            $scope = DB::table('feedback')->when($user->role_slug === 'staff', fn ($q) => $q->where('staffID', $user->getKey()));
            $distribution = (clone $scope)->selectRaw('rating, COUNT(*) as n')->groupBy('rating')->pluck('n', 'rating')->all();
            $total = array_sum($distribution);
            $average = $total ? array_sum(array_map(fn ($r, $n) => $r * $n, array_keys($distribution), $distribution)) / $total : 0;
        } catch (QueryException $e) {
            report($e);

            return view('portal.feedback', ['feedback' => null, 'role' => $user->role_slug, 'loadError' => true, 'total' => 0, 'average' => 0, 'distribution' => []]);
        }

        return view('portal.feedback', ['feedback' => $feedback, 'role' => $user->role_slug, 'loadError' => false, 'total' => $total, 'average' => $average, 'distribution' => $distribution]);
    }
}
