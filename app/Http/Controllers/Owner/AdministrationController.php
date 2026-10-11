<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Promo;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffAvailability;
use App\Models\SystemLog;
use App\Models\SystemPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdministrationController extends Controller
{
    private const MODULES = ['bookings', 'walkin', 'payments', 'reports', 'attendance', 'customers', 'staff', 'services'];

    public function staff(Request $request): View
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'in:Owner,Manager,Staff'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $search = trim((string) $request->query('q', ''));

        $staffMembers = Staff::where('is_archived', 0)
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.mb_strtolower($search).'%';
                $query->where(fn ($inner) => $inner
                    ->whereRaw('LOWER("firstName" || \' \' || "lastName") LIKE ?', [$like])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(username) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(specialization) LIKE ?', [$like]));
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->query('role')))
            ->when($request->query('status') === 'active', fn ($query) => $query->where('is_active', 1))
            ->when($request->query('status') === 'inactive', fn ($query) => $query->where('is_active', 0))
            ->orderByRaw("CASE role WHEN 'Owner' THEN 0 WHEN 'Manager' THEN 1 ELSE 2 END")
            ->orderBy('firstName')
            ->paginate(25)
            ->withQueryString();

        return view('owner.staff', ['staffMembers' => $staffMembers]);
    }

    public function toggleStaff(Staff $staff): RedirectResponse
    {
        abort_if($staff->isOwner(), 403);

        $staff->update(['is_active' => ! $staff->is_active]);
        SystemLog::record('owner', (int) Auth::guard('staff')->id(), 'toggle_staff', ($staff->is_active ? 'Activated' : 'Deactivated')." staff account {$staff->username}");

        return back()->with('status', $staff->is_active ? 'Staff account activated.' : 'Staff account deactivated. They can no longer log in.');
    }

    public function createStaff(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'firstName' => ['required', 'string', 'max:50'],
            'lastName' => ['required', 'string', 'max:50'],
            'username' => ['required', 'string', 'max:50', 'unique:salon_staff,username'],
            'email' => ['required', 'email', 'max:100', 'unique:salon_staff,email'],
            'role' => ['required', 'in:Manager,Staff'],
            'specialization' => ['nullable', 'string', 'max:100'],
            'contactNumber' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $staff = Staff::create($validated + ['is_active' => 1, 'is_archived' => 0]);

        // New Staff and Manager accounts start available every day, all day. The owner can change it later.
        if (in_array($staff->role, ['Staff', 'Manager'], true)) {
            StaffAvailability::openAllWeek((int) $staff->getKey());
        }

        SystemLog::record('owner', (int) Auth::guard('staff')->id(), 'create_staff', "Created {$staff->role} account {$staff->username}");

        return back()->with('status', 'Staff account created.');
    }

    public function updateStaff(Request $request, Staff $staff): RedirectResponse
    {
        abort_if($staff->isOwner(), 403);

        $validated = $request->validate([
            'firstName' => ['required', 'string', 'max:50'],
            'lastName' => ['required', 'string', 'max:50'],
            'username' => ['required', 'string', 'max:50', 'unique:salon_staff,username,'.$staff->getKey().',staffID'],
            'email' => ['required', 'email', 'max:100', 'unique:salon_staff,email,'.$staff->getKey().',staffID'],
            'role' => ['required', 'in:Manager,Staff'],
            'specialization' => ['nullable', 'string', 'max:100'],
            'contactNumber' => ['nullable', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $staff->update($validated);
        SystemLog::record('owner', (int) Auth::guard('staff')->id(), 'update_staff', "Updated staff account {$staff->username}");

        return back()->with('status', 'Staff account updated.');
    }

    public function archiveStaff(Staff $staff): RedirectResponse
    {
        abort_if($staff->isOwner() || (int) $staff->getKey() === (int) Auth::guard('staff')->id(), 403);
        $staff->update(['is_active' => 0, 'is_archived' => 1]);
        SystemLog::record('owner', (int) Auth::guard('staff')->id(), 'archive_staff', "Archived staff account {$staff->username}");

        return back()->with('status', 'Staff account archived; appointment history was preserved.');
    }

    public function policies(): View
    {
        return view('owner.policies', ['policies' => SystemPolicy::all()->keyBy('policy_key')]);
    }

    public function updatePolicies(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'downpayment_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'cancellation_window_hours' => ['required', 'integer', 'min:0', 'max:720'],
            'max_reschedule_count' => ['required', 'integer', 'min:0', 'max:20'],
            'max_bookings_per_slot' => ['required', 'integer', 'min:1', 'max:100'],
            'walkins_allowed' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($validated): void {
            foreach ($validated as $key => $value) {
                SystemPolicy::updateOrCreate(['policy_key' => $key], ['policy_value' => (string) $value]);
            }
        });
        SystemLog::record('owner', (int) Auth::guard('staff')->id(), 'update_policies', 'Updated booking policies');

        return back()->with('status', 'System policies updated.');
    }

    public function permissions(): View
    {
        $permissions = Permission::where('role_name', 'Manager')->get()->keyBy('permission_key');

        return view('owner.permissions', ['modules' => self::MODULES, 'permissions' => $permissions]);
    }

    public function updatePermissions(Request $request): RedirectResponse
    {
        $rules = ['permissions' => ['nullable', 'array']];

        foreach (self::MODULES as $module) {
            foreach (['view', 'create', 'edit', 'delete'] as $action) {
                $rules["permissions.{$module}.{$action}"] = ['nullable', 'boolean'];
            }
        }

        $validated = $request->validate($rules);
        DB::transaction(function () use ($validated): void {
            foreach (self::MODULES as $module) {
                $values = $validated['permissions'][$module] ?? [];
                Permission::updateOrCreate(
                    ['role_name' => 'Manager', 'permission_key' => $module],
                    [
                        'can_view' => (int) ($values['view'] ?? false),
                        'can_create' => (int) ($values['create'] ?? false),
                        'can_edit' => (int) ($values['edit'] ?? false),
                        'can_delete' => (int) ($values['delete'] ?? false),
                    ]
                );
            }
        });
        SystemLog::record('owner', (int) Auth::guard('staff')->id(), 'update_permissions', 'Updated manager permissions');

        return back()->with('status', 'Manager permissions updated.');
    }

    public function promos(Request $request): View
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:running,scheduled,expired,inactive'],
        ]);

        $search = trim((string) $request->query('q', ''));
        $today = today()->toDateString();

        $promos = Promo::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->whereRaw('LOWER(promo_code) LIKE ?', ['%'.mb_strtolower($search).'%'])
                ->orWhereRaw('LOWER(promo_name) LIKE ?', ['%'.mb_strtolower($search).'%'])))
            ->when($request->query('status') === 'running', fn ($query) => $query->where('is_active', 1)->whereDate('valid_from', '<=', $today)->whereDate('valid_until', '>=', $today))
            ->when($request->query('status') === 'scheduled', fn ($query) => $query->where('is_active', 1)->whereDate('valid_from', '>', $today))
            ->when($request->query('status') === 'expired', fn ($query) => $query->whereDate('valid_until', '<', $today))
            ->when($request->query('status') === 'inactive', fn ($query) => $query->where('is_active', 0))
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('owner.promos', ['promos' => $promos]);
    }

    public function createPromo(Request $request): RedirectResponse
    {
        $validated = $this->validatePromo($request);

        Promo::create($validated + ['is_active' => 1]);
        SystemLog::record('owner', (int) Auth::guard('staff')->id(), 'create_promo', "Created promotion {$validated['promo_code']}");

        return back()->with('status', 'Promotion created.');
    }

    public function updatePromo(Request $request, Promo $promo): RedirectResponse
    {
        $validated = $this->validatePromo($request, $promo);

        $promo->update($validated);
        SystemLog::record('owner', (int) Auth::guard('staff')->id(), 'update_promo', "Updated promotion {$promo->promo_code}");

        return back()->with('status', 'Promotion updated.');
    }

    /**
     * Shared rules for the add/edit promotion form.
     *
     * The discount cap ("max_discount") only applies to percentage promos: 20% off with a
     * PHP 500 cap never takes more than PHP 500 off, however large the bill.
     *
     * @return array<string, mixed>
     */
    private function validatePromo(Request $request, ?Promo $promo = null): array
    {
        $request->merge(['promo_code' => strtoupper(trim((string) $request->input('promo_code')))]);

        $validated = $request->validate([
            'promo_code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('promos', 'promo_code')->ignore($promo?->getKey(), 'promoID')],
            'promo_name' => ['required', 'string', 'max:100'],
            'discount_type' => ['required', 'in:percentage,fixed'],
            'discount_value' => ['required', 'numeric', 'gt:0'],
            'min_spend' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'gt:0'],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:valid_from'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validated['discount_type'] === 'percentage' && (float) $validated['discount_value'] > 100) {
            throw ValidationException::withMessages(['discount_value' => 'Percentage discounts cannot exceed 100%.']);
        }

        $validated['min_spend'] = $validated['min_spend'] ?? 0;

        if ($validated['discount_type'] === 'fixed') {
            $validated['max_discount'] = null;
        }

        return $validated;
    }

    public function togglePromo(Promo $promo): RedirectResponse
    {
        $promo->update(['is_active' => ! $promo->is_active]);

        return back()->with('status', 'Promotion status updated.');
    }

    public function deletePromo(Promo $promo): RedirectResponse
    {
        $promo->delete();

        return back()->with('status', 'Promotion deleted.');
    }

    public function logs(Request $request): View
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'in:customer,staff,manager,owner'],
            'date' => ['nullable', 'date'],
        ]);

        $search = trim((string) $request->query('q', ''));

        $logs = SystemLog::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->whereRaw('LOWER(action) LIKE ?', ['%'.mb_strtolower(str_replace(' ', '_', $search)).'%'])
                ->orWhereRaw('LOWER(details) LIKE ?', ['%'.mb_strtolower($search).'%'])))
            ->when($request->filled('type'), fn ($query) => $query->where('user_type', $request->query('type')))
            ->when($request->filled('date'), fn ($query) => $query->whereDate('created_at', $request->query('date')))
            ->latest('created_at')
            ->paginate(50)
            ->withQueryString();

        // Show names instead of bare account numbers.
        $customerIds = $logs->where('user_type', 'customer')->pluck('userID')->unique();
        $staffIds = $logs->where('user_type', '!=', 'customer')->pluck('userID')->unique();

        return view('owner.logs', [
            'logs' => $logs,
            'customerNames' => Customer::whereIn('customerID', $customerIds)->get()->mapWithKeys(fn ($c) => [$c->customerID => $c->full_name]),
            'staffNames' => Staff::whereIn('staffID', $staffIds)->get()->mapWithKeys(fn ($s) => [$s->staffID => $s->full_name]),
        ]);
    }

    public function createService(Request $request): RedirectResponse
    {
        $validated = $this->validateService($request);
        $validated['image'] = $request->hasFile('image') ? $this->storeServiceImage($request) : null;

        $service = Service::create($validated + ['is_active' => 1]);
        SystemLog::record('owner', (int) Auth::guard('staff')->id(), 'create_service', "Created service {$service->serviceName}");

        return back()->with('status', 'Service added to the catalog.');
    }

    public function updateService(Request $request, Service $service): RedirectResponse
    {
        $validated = $this->validateService($request);
        $oldImage = $service->image;

        if ($request->hasFile('image')) {
            $validated['image'] = $this->storeServiceImage($request);
        } elseif ($request->boolean('remove_image')) {
            $validated['image'] = null;
        }

        $service->update($validated);

        if (array_key_exists('image', $validated) && $oldImage && str_starts_with((string) $oldImage, 'services/')) {
            Storage::disk('public')->delete($oldImage);
        }

        SystemLog::record('owner', (int) Auth::guard('staff')->id(), 'update_service', "Updated service {$service->serviceName}");

        return back()->with('status', 'Service updated.');
    }

    /**
     * Shared rules for the add/edit service form. Duration is entered in minutes.
     *
     * @return array{serviceName: string, catID: ?int, servicePrice: string, duration: string, description: ?string, is_home_service: bool}
     */
    private function validateService(Request $request): array
    {
        $validated = $request->validate([
            'serviceName' => ['required', 'string', 'max:100'],
            'catID' => ['nullable', 'integer', 'exists:service_categories,catID'],
            'servicePrice' => ['required', 'numeric', 'min:0'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:720'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_home_service' => ['required', 'boolean'],
        ], [
            'duration_minutes.required' => 'Enter how long the service takes, in minutes.',
            'duration_minutes.min' => 'A service must take at least 5 minutes.',
            'duration_minutes.max' => 'A service cannot take longer than 12 hours (720 minutes).',
            'image.image' => 'The photo must be a JPG, PNG or WEBP image.',
            'image.max' => 'The photo must be 5 MB or smaller.',
        ]);

        return [
            'serviceName' => $validated['serviceName'],
            'catID' => $validated['catID'] ?? null,
            'servicePrice' => $validated['servicePrice'],
            'duration' => Service::minutesToDuration((int) $validated['duration_minutes']),
            'description' => $validated['description'] ?? null,
            'is_home_service' => (bool) $validated['is_home_service'],
        ];
    }

    private function storeServiceImage(Request $request): string
    {
        return $request->file('image')->store('services', 'public');
    }

    public function toggleService(Service $service): RedirectResponse
    {
        $service->update(['is_active' => ! $service->is_active]);
        SystemLog::record('owner', (int) Auth::guard('staff')->id(), 'toggle_service', "Changed availability for {$service->serviceName}");

        return back()->with('status', 'Service availability updated.');
    }
}
