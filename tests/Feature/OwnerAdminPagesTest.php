<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Promo;
use App\Models\Service;
use App\Models\Staff;
use App\Models\SystemLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OwnerAdminPagesTest extends TestCase
{
    use RefreshDatabase;

    private Staff $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = Staff::create([
            'firstName' => 'Janet',
            'lastName' => 'Owner',
            'username' => 'janet',
            'password' => 'password',
            'email' => 'janet@salon.test',
            'role' => 'Owner',
            'is_active' => 1,
            'is_archived' => 0,
        ]);
    }

    public function test_service_duration_is_entered_in_minutes_and_photo_is_uploaded(): void
    {
        Storage::fake('public');

        $this->actingAs($this->owner, 'staff')
            ->post(route('owner.services.create'), [
                'serviceName' => 'Keratin Treatment',
                'servicePrice' => 2500,
                'duration_minutes' => 150,
                'is_home_service' => 0,
                'image' => UploadedFile::fake()->image('keratin.jpg', 800, 600),
            ])
            ->assertSessionHas('status');

        $service = Service::firstOrFail();
        $this->assertSame('02:30:00', $service->duration);
        $this->assertSame(150, $service->duration_minutes);
        $this->assertSame('2 hr 30 min', $service->duration_label);
        Storage::disk('public')->assertExists($service->image);
        $this->assertStringContainsString('/media/services/', $service->image_url);

        $this->get($service->image_url)->assertOk();
    }

    public function test_service_update_can_replace_duration_and_remove_photo(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('old.jpg')->store('services', 'public');
        $service = Service::create(['serviceName' => 'Gel Manicure', 'servicePrice' => 300, 'duration' => '01:00:00', 'image' => $path, 'is_active' => 1]);

        $this->actingAs($this->owner, 'staff')
            ->put(route('owner.services.update', $service), [
                'serviceName' => 'Gel Manicure',
                'servicePrice' => 350,
                'duration_minutes' => 45,
                'is_home_service' => 0,
                'remove_image' => 1,
            ])
            ->assertSessionHas('status');

        $service->refresh();
        $this->assertSame('00:45:00', $service->duration);
        $this->assertNull($service->image);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_services_page_filters_by_search_and_status(): void
    {
        Service::create(['serviceName' => 'Brazilian Wax', 'servicePrice' => 900, 'duration' => '00:50:00', 'is_active' => 1]);
        Service::create(['serviceName' => 'Old Perm', 'servicePrice' => 900, 'duration' => '00:50:00', 'is_active' => 0]);

        $this->actingAs($this->owner, 'staff')
            ->get(route('owner.services', ['q' => 'wax']))
            ->assertOk()
            ->assertSee('Brazilian Wax')
            ->assertDontSee('Old Perm');

        $this->actingAs($this->owner, 'staff')
            ->get(route('owner.services', ['status' => 'inactive']))
            ->assertOk()
            ->assertSee('Old Perm')
            ->assertDontSee('Brazilian Wax');
    }

    public function test_promo_can_be_edited_and_fixed_promos_drop_the_cap(): void
    {
        $promo = Promo::create([
            'promo_code' => 'SAVE20', 'promo_name' => 'Save', 'discount_type' => 'percentage',
            'discount_value' => 20, 'min_spend' => 0, 'max_discount' => 500,
            'valid_from' => today(), 'valid_until' => today()->addMonth(), 'is_active' => 1,
        ]);

        $this->actingAs($this->owner, 'staff')
            ->put(route('owner.promos.update', $promo), [
                'promo_code' => 'save100', 'promo_name' => 'Save 100', 'discount_type' => 'fixed',
                'discount_value' => 100, 'max_discount' => 500,
                'valid_from' => today()->toDateString(), 'valid_until' => today()->addMonth()->toDateString(),
            ])
            ->assertSessionHas('status');

        $promo->refresh();
        $this->assertSame('SAVE100', $promo->promo_code);
        $this->assertNull($promo->max_discount);
    }

    public function test_staff_can_be_deactivated_from_the_row_menu(): void
    {
        $staff = Staff::create([
            'firstName' => 'Pau', 'lastName' => 'B', 'username' => 'pau', 'password' => 'password',
            'email' => 'pau@salon.test', 'role' => 'Staff', 'is_active' => 1, 'is_archived' => 0,
        ]);

        $this->actingAs($this->owner, 'staff')->post(route('owner.staff.toggle', $staff))->assertSessionHas('status');
        $this->assertFalse((bool) $staff->fresh()->is_active);
    }

    public function test_settings_pages_render_with_tabs(): void
    {
        foreach (['owner.profile', 'owner.policies', 'owner.permissions', 'owner.logs', 'owner.staff-availability', 'owner.staff.manage', 'owner.promos', 'owner.customers', 'owner.bookings', 'owner.payments', 'owner.attendance', 'owner.walkin'] as $route) {
            $response = $this->actingAs($this->owner, 'staff')->get(route($route));
            $response->assertOk();
        }

        $this->actingAs($this->owner, 'staff')
            ->get(route('owner.policies'))
            ->assertSee('sub-tabs', false)
            ->assertSee('Permissions');
    }

    public function test_system_logs_hide_ip_addresses(): void
    {
        SystemLog::record('owner', (int) $this->owner->getKey(), 'update_policies', 'Updated booking policies');

        $this->actingAs($this->owner, 'staff')
            ->get(route('owner.logs'))
            ->assertOk()
            ->assertSee('Janet Owner')
            ->assertDontSee('IP address');
    }

    public function test_blocked_customers_are_told_why_they_cannot_log_in(): void
    {
        Customer::create([
            'firstName' => 'Bea', 'lastName' => 'Cruz', 'email' => 'bea@example.com', 'password' => 'secret123',
            'contactNumber' => '09170000009', 'accountStatus' => 'blocked', 'is_verified' => 1,
        ]);

        $this->post(route('login.store'), ['email' => 'bea@example.com', 'password' => 'secret123'])
            ->assertSessionHasErrors(['email' => 'This account has been blocked. Please contact the salon.']);
    }
}
