<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\StaffAvailability;
use App\Models\SystemLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\Booking;
use App\Models\StaffAvailabilityRequest;
use App\Models\UserNotification;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AccountController extends Controller
{
    private const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    /** Everyone who can be assigned to appointments, so everyone whose hours the owner manages. */
    private const SCHEDULABLE_ROLES = ['Staff', 'Manager', 'Owner'];

    public function profile(): View
    {
        return view('staff.profile', ['staff' => Staff::findOrFail(Auth::guard('staff')->id())]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $staff = Staff::findOrFail(Auth::guard('staff')->id());
        $validated = $request->validate([
            'firstName' => ['required', 'string', 'max:50'],
            'lastName' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', 'unique:salon_staff,email,'.$staff->getKey().',staffID'],
            'contactNumber' => ['nullable', 'string', 'max:20'],
        ]);

        $staff->update($validated);
        SystemLog::record($staff->role_slug, (int) $staff->getKey(), 'update_profile', 'Updated profile details');

        return back()->with('status', 'Profile updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password:staff'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $staff = Staff::findOrFail(Auth::guard('staff')->id());
        $staff->update(['password' => $validated['password']]);
        SystemLog::record($staff->role_slug, (int) $staff->getKey(), 'change_password', 'Changed account password');

        return back()->with('status', 'Password updated.');
    }

        public function availability(): View
    {
        $staff = Staff::findOrFail(Auth::guard('staff')->id());
        $requests = StaffAvailabilityRequest::where('staffID', $staff->getKey());

        return view('staff.availability', [
            'staff' => $staff,
            'schedule' => $staff->availability->keyBy('day_of_week'),
            'days' => self::DAYS,
            'pendingRequest' => (clone $requests)->where('status', 'pending')->latest('created_at')->first(),
            'recentRequests' => (clone $requests)->where('status', '<>', 'pending')->latest('created_at')->limit(5)->get(),
        ]);
    }

    public function updateAvailability(Request $request): RedirectResponse
    {
        $validated = $this->validateSchedule($request) + $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ], [
            'reason.required' => 'Tell the owner why you are changing your hours.',
            'reason.min' => 'Please give a short reason (at least 5 characters).',
        ]);

        $staff = Staff::findOrFail(Auth::guard('staff')->id());

        if (StaffAvailabilityRequest::where('staffID', $staff->getKey())->where('status', 'pending')->exists()) {
            return back()->withInput()->with('error', 'You already have a request waiting for the owner. Wait for a decision before sending another.');
        }

        $requested = [];
        foreach (self::DAYS as $day) {
            $slot = $validated['availability'][$day] ?? [];
            if (! empty($slot['enabled'])) {
                $requested[$day] = ['start_time' => $slot['start_time'], 'end_time' => $slot['end_time']];
            }
        }

        $current = $staff->availability
            ->where('is_available', 1)
            ->mapWithKeys(fn ($slot) => [$slot->day_of_week => [
                'start_time' => substr($slot->start_time, 0, 5),
                'end_time' => substr($slot->end_time, 0, 5),
            ]])
            ->all();

        ksort($requested);
        ksort($current);

        if ($requested == $current) {
            return back()->withInput()->with('error', 'These hours match your current schedule, so there is nothing to send.');
        }

        $changeRequest = StaffAvailabilityRequest::create([
            'staffID' => $staff->getKey(),
            'schedule' => $requested,
            'reason' => trim($validated['reason']),
        ]);

        Staff::where('role', 'Owner')->where('is_active', 1)->where('is_archived', 0)->get()
            ->each(fn (Staff $owner) => UserNotification::send(
                'owner',
                (int) $owner->getKey(),
                'Availability change request',
                "{$staff->full_name} asked to change their working hours. Review it under Staff Availability."
            ));

        SystemLog::record($staff->role_slug, (int) $staff->getKey(), 'request_availability_change', "Requested a working hours change (#{$changeRequest->getKey()})");

        return back()->with('status', 'Request sent. Your current hours stay in place until the owner approves.');
    }
    public function manageAvailability(Request $request): View
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'in:Owner,Manager,Staff'],
            'staff_id' => ['nullable', 'integer'],
        ]);

        $search = trim((string) $request->query('q', ''));

        $staffMembers = Staff::with('availability')
            ->whereIn('role', self::SCHEDULABLE_ROLES)
            ->where('is_archived', 0)
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->whereRaw('LOWER("firstName" || \' \' || "lastName") LIKE ?', ['%'.mb_strtolower($search).'%'])
                ->orWhereRaw('LOWER(specialization) LIKE ?', ['%'.mb_strtolower($search).'%'])))
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->query('role')))
            ->orderByRaw("CASE role WHEN 'Staff' THEN 0 WHEN 'Manager' THEN 1 ELSE 2 END")
            ->orderBy('firstName')
            ->paginate(25)
            ->withQueryString();

        $pending = StaffAvailabilityRequest::with('staff.availability')
            ->where('status', 'pending')
            ->oldest('created_at')
            ->get();

        return view('owner.staff-availability', [
            'staffMembers' => $staffMembers,
            'openStaffId' => (int) $request->query('staff_id'),
            'days' => self::DAYS,
            'requests' => $pending,
            'conflictCounts' => $pending->mapWithKeys(fn ($r) => [$r->requestID => $this->conflictCount($r)])->all(),
        ]);
    }

    public function updateStaffAvailability(Request $request): RedirectResponse
    {
        $validated = $this->validateSchedule($request) + $request->validate([
            'staff_id' => ['required', 'integer', 'exists:salon_staff,staffID'],
        ]);
        $staff = Staff::whereKey($validated['staff_id'])->whereIn('role', self::SCHEDULABLE_ROLES)->where('is_archived', 0)->firstOrFail();
        $this->replaceSchedule($staff, $validated['availability'] ?? []);
        SystemLog::record('owner', (int) Auth::guard('staff')->id(), 'update_staff_availability', "Updated availability for {$staff->full_name}");

        return back()->with('status', "Working hours saved for {$staff->full_name}.");
    }

    private function validateSchedule(Request $request): array
    {
        $rules = ['availability' => ['nullable', 'array']];

        foreach (self::DAYS as $day) {
            $rules["availability.{$day}.enabled"] = ['nullable', 'boolean'];
            $rules["availability.{$day}.start_time"] = ['nullable', 'date_format:H:i'];
            $rules["availability.{$day}.end_time"] = ['nullable', 'date_format:H:i'];
        }

        $validated = $request->validate($rules);

        foreach ($validated['availability'] ?? [] as $day => $slot) {
            if (! empty($slot['enabled']) && (empty($slot['start_time']) || empty($slot['end_time']) || $slot['start_time'] >= $slot['end_time'])) {
                throw ValidationException::withMessages([
                    "availability.{$day}.end_time" => "Choose an end time later than the start time for {$day}.",
                ]);
            }
        }

        return $validated;


    }
    public function approveAvailabilityRequest(StaffAvailabilityRequest $availabilityRequest): RedirectResponse
    {
        if ($availabilityRequest->status !== 'pending') {
            return back()->with('error', 'This request was already reviewed.');
        }

        $staff = Staff::whereKey($availabilityRequest->staffID)
            ->whereIn('role', self::SCHEDULABLE_ROLES)
            ->where('is_archived', 0)
            ->first();

        if (! $staff) {
            return back()->with('error', 'This staff member is no longer active.');
        }

        $schedule = array_map(fn ($slot) => $slot + ['enabled' => 1], $availabilityRequest->schedule ?? []);

        DB::transaction(function () use ($availabilityRequest, $staff, $schedule): void {
            $this->replaceSchedule($staff, $schedule);
            $availabilityRequest->update([
                'status' => 'approved',
                'reviewed_by' => Auth::guard('staff')->id(),
                'reviewed_at' => now(),
            ]);
            UserNotification::send($staff->role_slug, (int) $staff->getKey(), 'Availability approved', 'Your new working hours are now active.');
        });

        SystemLog::record('owner', (int) Auth::guard('staff')->id(), 'approve_availability', "Approved availability request #{$availabilityRequest->getKey()} for {$staff->full_name}");

        return back()->with('status', "Approved. {$staff->full_name}'s new hours are active.");
    }

    public function rejectAvailabilityRequest(Request $request, StaffAvailabilityRequest $availabilityRequest): RedirectResponse
    {
        $validated = $request->validate(['review_note' => ['nullable', 'string', 'max:255']]);

        if ($availabilityRequest->status !== 'pending') {
            return back()->with('error', 'This request was already reviewed.');
        }

        $staff = Staff::find($availabilityRequest->staffID);
        $note = trim((string) ($validated['review_note'] ?? ''));

        DB::transaction(function () use ($availabilityRequest, $staff, $note): void {
            $availabilityRequest->update([
                'status' => 'rejected',
                'reviewed_by' => Auth::guard('staff')->id(),
                'reviewed_at' => now(),
                'review_note' => $note !== '' ? $note : null,
            ]);

            if ($staff) {
                UserNotification::send(
                    $staff->role_slug,
                    (int) $staff->getKey(),
                    'Availability request declined',
                    'Your working hours stay the same.'.($note !== '' ? " Owner's note: {$note}" : '')
                );
            }
        });

        SystemLog::record('owner', (int) Auth::guard('staff')->id(), 'reject_availability', "Rejected availability request #{$availabilityRequest->getKey()}");

        return back()->with('status', 'Request declined.');
    }

    /** Upcoming pending or approved bookings that the requested hours would leave uncovered. */
    private function conflictCount(StaffAvailabilityRequest $changeRequest): int
    {
        $schedule = $changeRequest->schedule ?? [];

        return Booking::where('staffID', $changeRequest->staffID)
            ->whereIn('bookingStatus', ['pending', 'approved'])
            ->whereDate('bookingDate', '>=', Carbon::today()->toDateString())
            ->get(['bookingDate', 'bookingTime'])
            ->filter(function ($booking) use ($schedule): bool {
                $slot = $schedule[$booking->bookingDate->format('l')] ?? null;
                $time = substr((string) $booking->bookingTime, 0, 5);

                return ! $slot || $time < $slot['start_time'] || $time >= $slot['end_time'];
            })
            ->count();
    }
    private function replaceSchedule(Staff $staff, array $schedule): void
    {
        DB::transaction(function () use ($staff, $schedule): void {
            StaffAvailability::where('staffID', $staff->getKey())->delete();

            foreach (self::DAYS as $day) {
                $slot = $schedule[$day] ?? [];

                if (! empty($slot['enabled'])) {
                    StaffAvailability::create([
                        'staffID' => $staff->getKey(),
                        'day_of_week' => $day,
                        'start_time' => $slot['start_time'].':00',
                        'end_time' => $slot['end_time'].':00',
                        'is_available' => 1,
                    ]);
                }
            }
        });
    }
}
