<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffPortalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_approve_a_booking_assigned_to_another_staff_member(): void
    {
        $staff = $this->staff();
        $otherStaff = $this->staff('other');
        $booking = $this->booking($otherStaff);

        $this->actingAs($staff, 'staff')
            ->post(route('staff.bookings.approve', $booking))
            ->assertRedirect(route('staff.bookings'));

        $this->assertDatabaseHas('booking', [
            'bookingID' => $booking->getKey(),
            'bookingStatus' => 'pending',
        ]);
    }

    public function test_assigned_staff_can_approve_a_pending_booking(): void
    {
        $staff = $this->staff();
        $booking = $this->booking($staff);

        $this->actingAs($staff, 'staff')
            ->post(route('staff.bookings.approve', $booking))
            ->assertRedirect(route('staff.bookings'));

        $this->assertDatabaseHas('booking', [
            'bookingID' => $booking->getKey(),
            'bookingStatus' => 'approved',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_type' => 'customer',
            'userID' => $booking->customerID,
            'title' => 'Booking Approved',
        ]);
    }

    public function test_assigned_staff_can_mark_attendance_and_complete_a_booking(): void
    {
        $staff = $this->staff();
        $booking = $this->booking($staff, 'approved');

        $this->actingAs($staff, 'staff')
            ->post(route('staff.attendance.mark', $booking), ['attendance_status' => 'present'])
            ->assertRedirect(route('staff.attendance'));

        $this->assertDatabaseHas('booking', [
            'bookingID' => $booking->getKey(),
            'bookingStatus' => 'completed',
            'attendance_status' => 'present',
        ]);
    }

    public function test_staff_cannot_mark_attendance_for_another_staff_members_booking(): void
    {
        $staff = $this->staff();
        $otherStaff = $this->staff('other');
        $booking = $this->booking($otherStaff, 'approved');

        $this->actingAs($staff, 'staff')
            ->post(route('staff.attendance.mark', $booking), ['attendance_status' => 'present'])
            ->assertRedirect(route('staff.attendance'));

        $this->assertDatabaseHas('booking', [
            'bookingID' => $booking->getKey(),
            'bookingStatus' => 'approved',
            'attendance_status' => 'pending',
        ]);
    }

    public function test_walkin_uses_service_price_instead_of_a_client_supplied_amount(): void
    {
        $this->travelTo(today()->setTime(9, 5));
        $owner = $this->staff('owner', 'Owner');
        $worker = $this->staff();
        StaffAvailability::openAllWeek((int) $worker->getKey());
        $service = Service::create([
            'serviceName' => 'Haircut',
            'servicePrice' => 800,
            'duration' => '01:00:00',
            'is_active' => 1,
        ]);

        $this->actingAs($owner, 'staff')
            ->post(route('owner.walkin.create'), [
                'customer_name' => 'Walk In Guest',
                'contact_number' => '09123456789',
                'service_id' => $service->getKey(),
                'booking_time' => '09:00:00',
                'payment_method' => 'cash',
                'payment_type' => 'partial',
                'total_amount' => 1,
            ])
            ->assertRedirect(route('owner.walkin'));

        $booking = Booking::firstOrFail();
        $this->assertSame('800.00', $booking->total_amount);
        $this->assertSame('160.00', $booking->downpayment_amount);
        $this->assertDatabaseHas('payment', [
            'bookingID' => $booking->getKey(),
            'amount' => 160,
            'payStatus' => 'paid',
        ]);
    }

    public function test_manager_needs_the_configured_module_permission_to_open_bookings(): void
    {
        $manager = $this->staff('manager', 'Manager');

        $this->actingAs($manager, 'staff')
            ->get(route('manager.bookings'))
            ->assertRedirect(route('manager.dashboard'));

        Permission::create([
            'role_name' => 'Manager',
            'permission_key' => 'bookings',
            'can_view' => 1,
            'can_create' => 0,
            'can_edit' => 0,
            'can_delete' => 0,
        ]);

        $this->withoutVite();

        $this->get(route('manager.bookings'))
            ->assertOk()
            ->assertViewIs('portal.bookings');
    }

    public function test_manager_cannot_open_owner_administration(): void
    {
        $manager = $this->staff('manager', 'Manager');

        $this->actingAs($manager, 'staff')
            ->get(route('owner.promos'))
            ->assertRedirect(route('manager.dashboard'));
    }

    public function test_owner_login_redirects_to_owner_dashboard(): void
    {
        $owner = $this->staff('janet', 'Owner');

        $this->post(route('login.store'), [
            'email' => $owner->email,
            'password' => 'password',
        ])->assertRedirect(route('owner.dashboard'));
    }

    public function test_manager_login_redirects_to_manager_dashboard(): void
    {
        $manager = $this->staff('hace', 'Manager');

        $this->post(route('login.store'), [
            'email' => $manager->email,
            'password' => 'password',
        ])->assertRedirect(route('manager.dashboard'));
    }

    private function staff(string $username = 'worker', string $role = 'Staff'): Staff
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

    private function booking(Staff $staff, string $status = 'pending'): Booking
    {
        $customer = Customer::create([
            'firstName' => 'Test',
            'lastName' => 'Customer',
            'email' => 'customer@salon.test',
            'password' => 'password',
            'contactNumber' => '09123456789',
            'accountStatus' => 'active',
            'is_verified' => 1,
        ]);

        $service = Service::create([
            'serviceName' => 'Haircut',
            'servicePrice' => 800,
            'duration' => '01:00:00',
            'is_active' => 1,
        ]);

        return Booking::create([
            'bookingRef' => 'BK-TEST-001',
            'customerID' => $customer->getKey(),
            'serviceID' => $service->getKey(),
            'service' => $service->serviceName,
            'staffID' => $staff->getKey(),
            'bookingDate' => today()->toDateString(),
            'bookingTime' => '09:00:00',
            'bookingStatus' => $status,
            'paymentStatus' => 'pending',
            'total_amount' => 800,
            'downpayment_amount' => 160,
        ]);
    }
}
