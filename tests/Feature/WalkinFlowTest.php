<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffAvailability;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalkinFlowTest extends TestCase
{
    use RefreshDatabase;

    private Staff $owner;

    private Staff $worker;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(today()->setTime(10, 10));
        $this->owner = $this->makeStaff('owner', 'Owner');
        $this->worker = $this->makeStaff('worker', 'Staff');
        StaffAvailability::openAllWeek((int) $this->worker->getKey());
        $this->service = Service::create([
            'serviceName' => 'Classic Facial',
            'servicePrice' => 1000,
            'duration' => '01:00:00',
            'is_active' => 1,
        ]);
    }

    public function test_walkin_is_checked_in_immediately_without_staff_confirmation(): void
    {
        $this->actingAs($this->owner, 'staff')
            ->post(route('owner.walkin.create'), $this->payload())
            ->assertRedirect(route('owner.walkin'))
            ->assertSessionHas('status');

        $booking = Booking::firstOrFail();
        $this->assertSame('walkin', $booking->booking_source);
        $this->assertSame('approved', $booking->bookingStatus);
        $this->assertSame('present', $booking->attendance_status);
        $this->assertSame((int) $this->worker->getKey(), (int) $booking->staffID);
        $this->assertSame('10:00:00', $booking->bookingTime);

        $customer = $booking->customer;
        $this->assertTrue($customer->is_walkin);
        $this->assertSame('ana@example.com', $customer->email);
        $this->assertSame('ana@example.com', $customer->contact_email);
    }

    public function test_walkin_without_email_gets_no_placeholder_address(): void
    {
        $this->actingAs($this->owner, 'staff')
            ->post(route('owner.walkin.create'), $this->payload(['customer_email' => '']))
            ->assertRedirect(route('owner.walkin'));

        $customer = Customer::firstOrFail();
        $this->assertNull($customer->email);
        $this->assertNull($customer->contact_email);

        $this->actingAs($this->owner, 'staff')
            ->get(route('owner.customers'))
            ->assertOk()
            ->assertSee('No email on file')
            ->assertDontSee('invalid.local');
    }

    public function test_walkin_rejects_a_time_slot_that_has_already_passed(): void
    {
        $this->actingAs($this->owner, 'staff')
            ->post(route('owner.walkin.create'), $this->payload(['booking_time' => '09:00:00']))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('booking', 0);
    }

    public function test_walkin_fails_cleanly_when_nobody_is_free(): void
    {
        StaffAvailability::where('staffID', $this->worker->getKey())->delete();

        $this->actingAs($this->owner, 'staff')
            ->post(route('owner.walkin.create'), $this->payload(['staff_id' => null]))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('booking', 0);
    }

    public function test_available_staff_endpoint_lists_free_qualified_staff(): void
    {
        $this->actingAs($this->owner, 'staff')
            ->getJson(route('owner.walkin.staff', ['service_id' => $this->service->getKey(), 'time' => '10:30:00']))
            ->assertOk()
            ->assertJsonPath('staff.0.id', (int) $this->worker->getKey());
    }

    public function test_completing_a_partial_walkin_records_the_balance(): void
    {
        $this->actingAs($this->owner, 'staff')
            ->post(route('owner.walkin.create'), $this->payload(['payment_type' => 'partial']));

        $booking = Booking::firstOrFail();

        $this->actingAs($this->worker, 'staff')
            ->post(route('staff.walkin.complete', $booking))
            ->assertSessionHas('status');

        $booking->refresh();
        $this->assertSame('completed', $booking->bookingStatus);
        $this->assertSame('paid', $booking->paymentStatus);
        $this->assertEquals(1000, (float) $booking->payments()->sum('amount'));
    }

    public function test_walkins_cannot_be_marked_present_or_no_show(): void
    {
        $this->actingAs($this->owner, 'staff')
            ->post(route('owner.walkin.create'), $this->payload());

        $booking = Booking::firstOrFail();

        $this->actingAs($this->owner, 'staff')
            ->post(route('owner.attendance.update', $booking), ['attendance_status' => 'no-show'])
            ->assertSessionHas('error');

        $this->actingAs($this->worker, 'staff')
            ->get(route('staff.attendance'))
            ->assertOk()
            ->assertDontSee('Ana Reyes');

        $this->assertSame('approved', $booking->fresh()->bookingStatus);
    }

    public function test_booking_lists_label_walkins_and_filter_by_type(): void
    {
        $this->actingAs($this->owner, 'staff')
            ->post(route('owner.walkin.create'), $this->payload());

        $this->actingAs($this->owner, 'staff')
            ->get(route('owner.bookings', ['type' => 'walkin']))
            ->assertOk()
            ->assertSee('Walk-in')
            ->assertSee('Ana Reyes');

        $this->actingAs($this->owner, 'staff')
            ->get(route('owner.bookings', ['type' => 'online']))
            ->assertOk()
            ->assertDontSee('Ana Reyes');

        $this->actingAs($this->owner, 'staff')
            ->get(route('owner.attendance'))
            ->assertOk()
            ->assertSee('Walk-in');
    }

    public function test_a_walkin_customer_can_later_register_and_keep_their_history(): void
    {
        $this->actingAs($this->owner, 'staff')
            ->post(route('owner.walkin.create'), $this->payload());
        $walkin = Customer::firstOrFail();

        $this->mock(OtpService::class, function ($mock): void {
            $mock->shouldReceive('generate')->andReturn('123456');
            $mock->shouldReceive('send');
            $mock->shouldReceive('verify')->andReturn(['success' => true, 'message' => 'ok']);
        });

        auth('staff')->logout();

        $this->post(route('register.send-otp'), [
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'email' => 'ana@example.com',
            'contact_number' => '09170000001',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertSessionHasNoErrors();

        $this->post(route('register.verify'), ['otp_code' => '123456'])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('customer', 1);
        $account = Customer::firstOrFail();
        $this->assertSame((int) $walkin->getKey(), (int) $account->getKey());
        $this->assertFalse($account->is_walkin);
        $this->assertSame(1, $account->bookings()->count());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Ana Reyes',
            'contact_number' => '09170000001',
            'customer_email' => 'ana@example.com',
            'service_id' => $this->service->getKey(),
            'booking_time' => '10:00:00',
            'staff_id' => $this->worker->getKey(),
            'payment_method' => 'cash',
            'payment_type' => 'full',
        ], $overrides);
    }

    private function makeStaff(string $username, string $role): Staff
    {
        return Staff::create([
            'firstName' => ucfirst($username),
            'lastName' => 'Test',
            'username' => $username,
            'password' => 'password',
            'email' => $username.'@salon.test',
            'role' => $role,
            'specialization' => 'All-Around Salon Services',
            'is_active' => 1,
            'is_archived' => 0,
        ]);
    }
}
