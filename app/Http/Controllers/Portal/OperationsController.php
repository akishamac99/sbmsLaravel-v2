<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Feedback;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Staff;
use App\Models\SystemLog;
use App\Models\UserNotification;
use App\Services\BookingService;
use App\Services\StaffMatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OperationsController extends Controller
{
    public function bookings(Request $request, StaffMatcher $matcher): View
    {
        $statuses = ['pending', 'approved', 'completed', 'declined', 'cancelled', 'no-show'];
        $request->validate([
            'status' => ['nullable', 'in:'.implode(',', $statuses)],
            'type' => ['nullable', 'in:online,walkin'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim((string) $request->query('q', ''));
        $type = (string) $request->query('type', '');

        // Type and search narrow everything, including the counts on the status tabs.
        $filtered = Booking::query()
            ->when($type !== '', fn ($query) => $query->where('booking_source', $type))
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.mb_strtolower($search).'%';
                $query->where(fn ($inner) => $inner
                    ->whereRaw('LOWER("bookingRef") LIKE ?', [$like])
                    ->orWhereRaw('LOWER(service) LIKE ?', [$like])
                    ->orWhereHas('customer', fn ($customer) => $customer
                        ->whereRaw('LOWER("firstName" || \' \' || "lastName") LIKE ?', [$like])
                        ->orWhere('contactNumber', 'like', '%'.$search.'%')));
            });

        $counts = (clone $filtered)
            ->selectRaw('"bookingStatus" as status, COUNT(*) as total')
            ->groupBy('bookingStatus')
            ->pluck('total', 'status');

        $bookings = (clone $filtered)
            ->with(['customer', 'serviceItem', 'staff'])
            ->when($request->filled('status'), fn ($query) => $query->where('bookingStatus', $request->string('status')->toString()))
            ->latest('bookingDate')
            ->latest('bookingTime')
            ->paginate(25)
            ->withQueryString();

        $availableStaff = [];

        foreach ($bookings as $booking) {
            if ($booking->bookingStatus === 'pending') {
                $availableStaff[$booking->bookingID] = $matcher
                    ->availableStaff(Carbon::parse($booking->bookingDate)->toDateString(), (string) $booking->bookingTime, (int) $booking->serviceID, (int) $booking->bookingID)
                    ->filter(fn (Staff $staff): bool => in_array($staff->role, ['Staff', 'Manager'], true))
                    ->map(fn (Staff $staff): array => ['id' => (int) $staff->getKey(), 'name' => $staff->full_name])
                    ->values()
                    ->all();
            }
        }

        return view('portal.bookings', [
            'bookings' => $bookings,
            'availableStaff' => $availableStaff,
            'statuses' => $statuses,
            'counts' => $counts,
            'role' => $this->role(),
            'canEdit' => $this->role() === 'owner' || Permission::allows('Manager', 'bookings', 'edit'),
        ]);
    }

    public function approveBooking(Request $request, Booking $booking, StaffMatcher $matcher): RedirectResponse
    {
        $validated = $request->validate(['staff_id' => ['nullable', 'integer', 'exists:salon_staff,staffID']]);
        $staffId = (int) ($validated['staff_id'] ?? $booking->staffID ?? 0);
        $staff = Staff::find($staffId);

        if ($booking->bookingStatus !== 'pending'
            || ! $staff
            || ! $staff->is_active
            || $staff->is_archived
            || ! in_array($staff->role, ['Staff', 'Manager'], true)
                || ! $matcher->isAvailable($staffId, Carbon::parse($booking->bookingDate)->toDateString(), (string) $booking->bookingTime, (int) $booking->serviceID, (int) $booking->bookingID)) {
            return back()->with('error', 'Select an active, qualified staff member who is available for this booking.');
        }

        DB::transaction(function () use ($booking, $staffId): void {
            $booking->update(['bookingStatus' => 'approved', 'staffID' => $staffId]);
            UserNotification::send('customer', (int) $booking->customerID, 'Booking Approved', "Your booking {$booking->bookingRef} has been approved.");
        });

        SystemLog::record($this->role(), (int) Auth::guard('staff')->id(), 'approve_booking', "Approved booking {$booking->bookingRef}");

        return back()->with('status', 'Booking approved.');
    }

    public function declineBooking(Booking $booking): RedirectResponse
    {
        if ($booking->bookingStatus !== 'pending') {
            return back()->with('error', 'Only pending bookings can be declined.');
        }

        DB::transaction(function () use ($booking): void {
            $booking->update(['bookingStatus' => 'declined']);
            UserNotification::send('customer', (int) $booking->customerID, 'Booking Declined', "Your booking {$booking->bookingRef} has been declined.");
        });

        SystemLog::record($this->role(), (int) Auth::guard('staff')->id(), 'decline_booking', "Declined booking {$booking->bookingRef}");

        return back()->with('status', 'Booking declined.');
    }

    public function payments(): View
    {
        $payments = Payment::with(['booking.customer', 'booking.serviceItem'])
            ->latest('payDate')
            ->paginate(25);

        return view('portal.payments', [
            'payments' => $payments,
            'canReview' => $this->role() === 'owner' || Permission::allows('Manager', 'payments', 'edit'),
            'role' => $this->role(),
        ]);
    }

    public function attendance(Request $request): View
    {
        $request->validate(['type' => ['nullable', 'in:online,walkin']]);
        $type = (string) $request->query('type', '');

        return view('portal.attendance', [
            'bookings' => Booking::with(['customer', 'serviceItem', 'staff'])
                ->whereIn('bookingStatus', ['approved', 'completed', 'no-show'])
                ->when($type !== '', fn ($query) => $query->where('booking_source', $type))
                ->orderByDesc('bookingDate')
                ->orderByDesc('bookingTime')
                ->paginate(25)
                ->withQueryString(),
            'type' => $type,
            'role' => $this->role(),
            'canMark' => $this->role() === 'owner' || Permission::allows('Manager', 'attendance', 'edit'),
            'canCompleteWalkins' => $this->role() === 'owner' || Permission::allows('Manager', 'walkin', 'create'),
        ]);
    }

    public function updateAttendance(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate(['attendance_status' => ['required', 'in:present,no-show']]);
        $attendance = $validated['attendance_status'];
        $newStatus = $attendance === 'present' ? 'completed' : 'no-show';

        if ($booking->is_walkin) {
            return back()->with('error', 'Walk-ins are checked in on arrival. Use "Mark completed" when the service is done.');
        }

        if (! in_array($booking->bookingStatus, ['approved', 'completed', 'no-show'], true)) {
            return back()->with('error', 'Attendance can only be recorded for approved bookings.');
        }

        $previous = $booking->bookingStatus;
        $isCorrection = $previous !== 'approved';

        if ($isCorrection && $previous === $newStatus) {
            return back()->with('error', 'This booking is already marked '.$attendance.'.');
        }

        if ($isCorrection && $newStatus === 'no-show' && $booking->feedback()->exists()) {
            return back()->with('error', 'The customer already rated this appointment, so it cannot be changed to no-show.');
        }

        DB::transaction(function () use ($booking, $attendance, $newStatus): void {
            $booking->update([
                'attendance_status' => $attendance,
                'bookingStatus' => $newStatus,
            ]);

            if ($attendance === 'present') {
                UserNotification::send('customer', (int) $booking->customerID, 'Service Completed', 'Your service has been completed. Thank you for visiting!');
            }
        });

        SystemLog::record(
            $this->role(),
            (int) Auth::guard('staff')->id(),
            $isCorrection ? 'correct_attendance' : 'update_attendance',
            $isCorrection
                ? "Corrected {$booking->bookingRef} from {$previous} to {$newStatus}"
                : "Marked {$booking->bookingRef} as {$attendance}"
        );

        return back()->with('status', $isCorrection ? 'Attendance record corrected.' : 'Attendance recorded.');
    }

    public function customers(Request $request): View
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'in:registered,walkin'],
            'status' => ['nullable', 'in:active,inactive,blocked,pending'],
        ]);

        $search = trim((string) $request->query('q', ''));

        $customers = Customer::withCount('bookings')
            ->withMax('bookings', 'bookingDate')
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.mb_strtolower($search).'%';
                $query->where(fn ($inner) => $inner
                    ->whereRaw('LOWER("firstName" || \' \' || "lastName") LIKE ?', [$like])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$like])
                    ->orWhere('contactNumber', 'like', '%'.$search.'%'));
            })
            ->when($request->query('type') === 'walkin', fn ($query) => $query->where('is_walkin', 1))
            ->when($request->query('type') === 'registered', fn ($query) => $query->where('is_walkin', 0))
            ->when($request->filled('status'), fn ($query) => $query->where('accountStatus', $request->query('status'))->where('is_walkin', 0))
            ->latest('createdAt')
            ->paginate(25)
            ->withQueryString();

        return view('portal.customers', [
            'customers' => $customers,
            'role' => $this->role(),
            'canEdit' => $this->allowed('customers', 'edit'),
        ]);
    }

    public function updateCustomerStatus(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate(['accountStatus' => ['required', 'in:active,inactive,blocked']]);

        if ($customer->is_walkin) {
            return back()->with('error', 'Walk-in customers have no login, so their account status cannot be changed.');
        }

        $customer->update($validated);
        SystemLog::record($this->role(), (int) Auth::guard('staff')->id(), 'update_customer_status', "Updated account {$customer->getKey()} to {$validated['accountStatus']}");

        return back()->with('status', 'Customer account status updated.');
    }

    public function staffDirectory(): View
    {
        return view('portal.staff', [
            'staffMembers' => Staff::withCount('bookings')->where('is_archived', 0)->orderBy('firstName')->paginate(25),
            'role' => $this->role(),
            'canCreate' => $this->allowed('staff', 'create'),
            'canEdit' => $this->allowed('staff', 'edit'),
            'canDelete' => $this->allowed('staff', 'delete'),
        ]);
    }

    public function services(Request $request): View
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:active,inactive'],
            'home' => ['nullable', 'in:1'],
        ]);

        $search = trim((string) $request->query('q', ''));

        $services = Service::with('category')
            ->where('serviceName', '<>', BookingService::HOME_FEE_NAME)
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->whereRaw('LOWER("serviceName") LIKE ?', ['%'.mb_strtolower($search).'%'])
                ->orWhereRaw('LOWER(description) LIKE ?', ['%'.mb_strtolower($search).'%'])))
            ->when($request->filled('category'), fn ($query) => $query->where('catID', (int) $request->query('category')))
            ->when($request->query('status') === 'active', fn ($query) => $query->where('is_active', 1))
            ->when($request->query('status') === 'inactive', fn ($query) => $query->where('is_active', 0))
            ->when($request->filled('home'), fn ($query) => $query->where('is_home_service', 1))
            ->orderByDesc('is_active')
            ->orderBy('serviceName')
            ->paginate(25)
            ->withQueryString();

        return view('portal.services', [
            'services' => $services,
            'categories' => ServiceCategory::withCount('services')->orderBy('category_name')->get(),
            'role' => $this->role(),
            'canCreate' => $this->allowed('services', 'create'),
            'canEdit' => $this->allowed('services', 'edit'),
        ]);
    }

    public function reports(Request $request): View
    {
        $statuses = ['pending', 'approved', 'completed', 'declined', 'cancelled', 'no-show'];

        $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', 'in:'.implode(',', $statuses)],
            'type' => ['nullable', 'in:online,walkin'],
            'staff_id' => ['nullable', 'integer'],
            'service_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $from = $request->query('from');
        $to = $request->query('to');

        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        $search = trim((string) $request->query('q', ''));

        // Every number and table on the page comes from this one filtered set of bookings.
        $filtered = Booking::query()
            ->when($from, fn ($query) => $query->whereDate('booking.bookingDate', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('booking.bookingDate', '<=', $to))
            ->when($request->filled('status'), fn ($query) => $query->where('booking.bookingStatus', $request->query('status')))
            ->when($request->filled('type'), fn ($query) => $query->where('booking.booking_source', $request->query('type')))
            ->when($request->filled('staff_id'), fn ($query) => $query->where('booking.staffID', (int) $request->query('staff_id')))
            ->when($request->filled('service_id'), fn ($query) => $query->where('booking.serviceID', (int) $request->query('service_id')))
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.mb_strtolower($search).'%';
                $query->where(fn ($inner) => $inner
                    ->whereRaw('LOWER(booking."bookingRef") LIKE ?', [$like])
                    ->orWhereRaw('LOWER(booking.service) LIKE ?', [$like])
                    ->orWhereHas('customer', fn ($customer) => $customer
                        ->whereRaw('LOWER("firstName" || \' \' || "lastName") LIKE ?', [$like])
                        ->orWhere('contactNumber', 'like', '%'.$search.'%')));
            });

        $totals = (clone $filtered)->toBase()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN booking."bookingStatus" = \'pending\' THEN 1 ELSE 0 END) as pending')
            ->selectRaw('SUM(CASE WHEN booking."bookingStatus" = \'completed\' THEN 1 ELSE 0 END) as completed')
            ->selectRaw('SUM(CASE WHEN booking."bookingStatus" = \'completed\' THEN booking.total_amount ELSE 0 END) as revenue')
            ->first();

        $byService = (clone $filtered)->toBase()
            ->whereNotIn('booking.bookingStatus', ['cancelled', 'declined', 'no-show'])
            ->join('service as s', 'booking.serviceID', '=', 's.serviceID')
            ->leftJoin('service_categories as c', 's.catID', '=', 'c.catID')
            ->groupBy('s.serviceID', 's.serviceName', 'c.category_name')
            ->select('s.serviceName', 'c.category_name as category')
            ->selectRaw('COUNT(*) as bookings_count')
            ->selectRaw('SUM(booking.total_amount) as total_revenue')
            ->orderByRaw('SUM(booking.total_amount) DESC')
            ->limit(10)
            ->get();

        $staffPerformance = (clone $filtered)->toBase()
            ->join('salon_staff as st', 'booking.staffID', '=', 'st.staffID')
            ->leftJoin('feedback as f', 'f.bookingID', '=', 'booking.bookingID')
            ->groupBy('st.staffID', 'st.firstName', 'st.lastName')
            ->select('st.firstName', 'st.lastName')
            ->selectRaw('SUM(CASE WHEN booking."bookingStatus" = \'completed\' THEN 1 ELSE 0 END) as completed')
            ->selectRaw('COUNT(f."feedbackID") as feedback_count')
            ->selectRaw('AVG(f.rating) as avg_rating')
            ->orderByRaw('SUM(CASE WHEN booking."bookingStatus" = \'completed\' THEN 1 ELSE 0 END) DESC')
            ->limit(10)
            ->get()
            ->map(fn ($row) => (object) [
                'staff_name' => trim($row->firstName.' '.$row->lastName),
                'completed' => (int) $row->completed,
                'feedback_count' => (int) $row->feedback_count,
                'avg_rating' => $row->avg_rating,
            ]);

        $records = (clone $filtered)
            ->with(['customer', 'serviceItem', 'staff'])
            ->orderByDesc('booking.bookingDate')
            ->orderByDesc('booking.bookingTime')
            ->paginate(25)
            ->withQueryString();

        $role = $this->role();

        return view('portal.reports', [
            'role' => $role,
            'statuses' => $statuses,
            'totalBookings' => (int) ($totals->total ?? 0),
            'pendingBookings' => (int) ($totals->pending ?? 0),
            'completedBookings' => (int) ($totals->completed ?? 0),
            'completedRevenue' => (float) ($totals->revenue ?? 0),
            'feedbackCount' => Feedback::whereIn('bookingID', (clone $filtered)->select('booking.bookingID'))->count(),
            'byService' => $byService,
            'staffPerformance' => $staffPerformance,
            'records' => $records,
            'serviceOptions' => Service::where('serviceName', '<>', BookingService::HOME_FEE_NAME)->orderBy('serviceName')->get(),
            'staffOptions' => Staff::whereIn('role', ['Owner', 'Manager', 'Staff'])->where('is_archived', 0)->orderBy('firstName')->get(),
            'canOpen' => $role === 'owner' || Permission::allows('Manager', 'bookings', 'view'),
        ]);
    }

    public function confirmPayment(Payment $payment): RedirectResponse
    {
        return $this->updatePayment($payment, 'confirmed');
    }

    public function rejectPayment(Payment $payment): RedirectResponse
    {
        return $this->updatePayment($payment, 'failed');
    }

    private function updatePayment(Payment $payment, string $status): RedirectResponse
    {
        $booking = $payment->booking;

        if ($payment->payStatus !== 'proof-submitted' || ! $booking) {
            return back()->with('error', 'Only submitted payment proofs can be reviewed.');
        }

        DB::transaction(function () use ($payment, $booking, $status): void {
            $payment->update(['payStatus' => $status]);
            $booking->update(['paymentStatus' => $status === 'confirmed' ? 'confirmed' : 'rejected']);
            UserNotification::send(
                'customer',
                (int) $booking->customerID,
                $status === 'confirmed' ? 'Payment Confirmed' : 'Payment Rejected',
                $status === 'confirmed' ? 'Your payment has been confirmed.' : 'Your payment proof was rejected. Please upload a clear screenshot.'
            );
        });

        SystemLog::record($this->role(), (int) Auth::guard('staff')->id(), 'review_payment', "Marked payment {$payment->getKey()} as {$status}");

        return back()->with('status', $status === 'confirmed' ? 'Payment confirmed.' : 'Payment rejected.');
    }

    private function allowed(string $module, string $action): bool
    {
        return $this->role() === 'owner' || Permission::allows('Manager', $module, $action);
    }

    private function role(): string
    {
        return Auth::guard('staff')->user()->role_slug;
    }
}
