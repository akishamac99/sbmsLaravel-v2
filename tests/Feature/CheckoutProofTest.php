<?php

namespace Tests\Feature;

use App\Models\BookingCart;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CheckoutProofTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private BookingCart $item;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $staff = Staff::create([
            'firstName' => 'Aurelia', 'lastName' => 'A', 'username' => 'aurelia', 'password' => 'password',
            'email' => 'aurelia@salon.test', 'role' => 'Staff', 'specialization' => 'All-Around Salon Services',
            'is_active' => 1, 'is_archived' => 0,
        ]);
        StaffAvailability::openAllWeek((int) $staff->getKey());
        $service = Service::create(['serviceName' => 'Classic Facial', 'servicePrice' => 1000, 'duration' => '01:00:00', 'is_active' => 1]);

        $this->customer = $this->makeCustomer('maria@example.com', '09170000001');
        $this->item = BookingCart::create([
            'customerID' => $this->customer->getKey(),
            'session_id' => 'test',
            'serviceID' => $service->getKey(),
            'staffID' => $staff->getKey(),
            'bookingDate' => today()->addDays(2)->toDateString(),
            'bookingTime' => '10:00:00',
        ]);
    }

    public function test_cash_on_arrival_is_no_longer_accepted(): void
    {
        $this->checkout(['payment_method' => 'cash', 'payment_proof' => UploadedFile::fake()->image('proof.jpg')])
            ->assertSessionHasErrors('payment_method');

        $this->assertDatabaseCount('booking', 0);

        $this->actingAs($this->customer, 'customer')
            ->withSession(['checkout_selection' => ['cart_ids' => [$this->item->cartID], 'service_modes' => []]])
            ->get(route('customer.checkout'))
            ->assertOk()
            ->assertDontSee('Cash on Arrival');
    }

    public function test_a_payment_screenshot_is_required(): void
    {
        $this->checkout([])->assertSessionHasErrors(['payment_proof' => 'Please attach the screenshot of your payment.']);
        $this->assertDatabaseCount('booking', 0);
    }

    public function test_uploaded_proof_is_saved_with_the_booking_and_viewable_by_the_salon(): void
    {
        $this->checkout(['payment_proof' => UploadedFile::fake()->image('gcash.png', 1080, 1920)])
            ->assertRedirect(route('customer.bookings'));

        $payment = Payment::firstOrFail();
        $this->assertSame('proof-submitted', $payment->payStatus);
        $this->assertSame('proof-submitted', $payment->booking->paymentStatus);
        Storage::disk('public')->assertExists($payment->payment_proof);

        // The customer and the owner can open it, even without the storage link.
        $this->actingAs($this->customer, 'customer')->get(route('customer.payments.proof', $payment))->assertOk();

        $owner = Staff::create([
            'firstName' => 'Janet', 'lastName' => 'O', 'username' => 'janet', 'password' => 'password',
            'email' => 'janet@salon.test', 'role' => 'Owner', 'is_active' => 1, 'is_archived' => 0,
        ]);
        $this->actingAs($owner, 'staff')->get(route('payment-proof', $payment))->assertOk();
    }

    public function test_another_customer_cannot_open_someone_elses_proof(): void
    {
        $this->checkout(['payment_proof' => UploadedFile::fake()->image('gcash.png')]);
        $payment = Payment::firstOrFail();

        $stranger = $this->makeCustomer('stranger@example.com', '09170000002');
        $this->actingAs($stranger, 'customer')->get(route('customer.payments.proof', $payment))->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function checkout(array $overrides): TestResponse
    {
        return $this->actingAs($this->customer, 'customer')
            ->withSession(['checkout_selection' => ['cart_ids' => [$this->item->cartID], 'service_modes' => []]])
            ->post(route('customer.checkout.place'), array_merge([
                'payment_type' => 'partial',
                'payment_method' => 'gcash',
            ], $overrides));
    }

    private function makeCustomer(string $email, string $phone): Customer
    {
        return Customer::create([
            'firstName' => 'Test', 'lastName' => 'Customer', 'email' => $email, 'password' => 'secret123',
            'contactNumber' => $phone, 'accountStatus' => 'active', 'is_verified' => 1,
        ]);
    }
}
