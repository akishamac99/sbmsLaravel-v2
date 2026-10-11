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

        return view('staff.availability', [
            'staff' => $staff,
            'schedule' => $staff->availability->keyBy('day_of_week'),
            'days' => self::DAYS,
        ]);
    }

    public function updateAvailability(Request $request): RedirectResponse
    {
        $validated = $this->validateSchedule($request);
        $staff = Staff::findOrFail(Auth::guard('staff')->id());
        $this->replaceSchedule($staff, $validated['availability'] ?? []);

        return back()->with('status', 'Availability updated.');
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

        return view('owner.staff-availability', [
            'staffMembers' => $staffMembers,
            'openStaffId' => (int) $request->query('staff_id'),
            'days' => self::DAYS,
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
