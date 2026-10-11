<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Customer\BookingController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\FeedbackController;
use App\Http\Controllers\Customer\NotificationController;
use App\Http\Controllers\Customer\OnlinePaymentController;
use App\Http\Controllers\Customer\PaymentProofController;
use App\Http\Controllers\Customer\ProfileController;
use App\Http\Controllers\Customer\ServiceController;
use App\Http\Controllers\Customer\StaffAvailabilityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\MessengerLinkController;
use App\Http\Controllers\MessengerWebhookController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Owner\AdministrationController;
use App\Http\Controllers\PaymentProofFileController;
use App\Http\Controllers\PayMongoWebhookController;
use App\Http\Controllers\Portal\BookingDetailController;
use App\Http\Controllers\Portal\OperationsController;
use App\Http\Controllers\Portal\WalkinController;
use App\Http\Controllers\Staff\AccountController;
use App\Http\Controllers\Staff\BookingController as StaffBookingController;
use Illuminate\Support\Facades\Route;

// The public landing page (your old index.php) comes later. For now, go to the login page.
Route::get('/', LandingController::class)->name('home');

// PayMongo calls this when an online payment happens. It has no login; the signature proves it is real.
Route::post('/webhooks/paymongo', PayMongoWebhookController::class)->name('webhooks.paymongo');

// Meta calls these. No login: the signature and verify token prove they are real.
Route::get('/webhooks/messenger', [MessengerWebhookController::class, 'verify'])->name('webhooks.messenger.verify');
Route::post('/webhooks/messenger', [MessengerWebhookController::class, 'receive'])->name('webhooks.messenger');

// Connect or disconnect Messenger, for a logged-in customer or staff member.
Route::middleware('auth:customer,staff')->group(function () {
    Route::post('/messenger/connect', [MessengerLinkController::class, 'connect'])->name('messenger.connect');
    Route::post('/messenger/disconnect', [MessengerLinkController::class, 'disconnect'])->name('messenger.disconnect');
    // Chat bubble, for a logged-in customer or staff member.
    Route::middleware('auth:customer,staff')->prefix('chat')->name('chat.')->group(function () {
    Route::get('/contacts', [ChatController::class, 'contacts'])->name('contacts');
    Route::get('/messages', [ChatController::class, 'messages'])->name('messages');
    Route::post('/messages', [ChatController::class, 'send'])->middleware('throttle:30,1')->name('send');
    Route::get('/unread', [ChatController::class, 'unread'])->name('unread');
});
});

/*
|--------------------------------------------------------------------------
| Guests only (logged-in users are sent to their own dashboard)
|--------------------------------------------------------------------------
*/
Route::middleware('guest:customer,staff')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'sendOtp'])->name('register.send-otp');
    Route::post('/register/verify', [RegisterController::class, 'verify'])->name('register.verify');
    Route::post('/register/resend', [RegisterController::class, 'resend'])->name('register.resend');
    Route::post('/register/reset', [RegisterController::class, 'reset'])->name('register.reset');
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->name('password.email');
    Route::get('/reset-password/{broker}/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password/{broker}', [PasswordResetController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

// Uploaded service pictures, served without needing the public/storage link.
Route::get('/media/{path}', MediaController::class)->where('path', 'services/.+')->name('media');

/*
|--------------------------------------------------------------------------
| Customer area
|--------------------------------------------------------------------------
*/
Route::middleware('auth:customer')->prefix('customer')->name('customer.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'customer'])->name('dashboard');

    Route::get('/services', [ServiceController::class, 'index'])->name('services');
    Route::post('/cart', [ServiceController::class, 'addToCart'])->name('cart.add');
    Route::put('/cart/{cart}', [ServiceController::class, 'updateCart'])->name('cart.update');
    Route::delete('/cart/{cart}', [ServiceController::class, 'removeCart'])->name('cart.remove');
    Route::post('/cart/checkout', [ServiceController::class, 'selectForCheckout'])->name('cart.checkout');
    Route::get('/staff-availability', StaffAvailabilityController::class)->name('staff-availability');
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'place'])->name('checkout.place');
    Route::post('/checkout/promo', [CheckoutController::class, 'promo'])->name('checkout.promo');

    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings');
    Route::get('/bookings/{ref}', [BookingController::class, 'show'])->name('bookings.show');
    Route::post('/bookings/{ref}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::get('/bookings/{ref}/reschedule', [BookingController::class, 'reschedule'])->name('bookings.reschedule');
    Route::post('/bookings/{ref}/reschedule', [BookingController::class, 'rescheduleStore'])->name('bookings.reschedule.store');
    Route::get('/bookings/{ref}/payment', [PaymentProofController::class, 'create'])->name('bookings.payment');
    Route::post('/bookings/{ref}/payment', [PaymentProofController::class, 'store'])->name('bookings.payment.store');
    Route::post('/bookings/{ref}/pay-online', [OnlinePaymentController::class, 'start'])->name('bookings.pay-online');
    Route::get('/bookings/{ref}/pay-online/done', [OnlinePaymentController::class, 'done'])->name('bookings.pay-online.done');
    Route::get('/payments/{payment}/proof', [PaymentProofFileController::class, 'customer'])->name('payments.proof');
    Route::get('/feedback', [FeedbackController::class, 'index'])->name('feedback');
    Route::get('/feedback/{ref}', [FeedbackController::class, 'focus'])->name('feedback.create');
    Route::post('/feedback/{ref}', [FeedbackController::class, 'store'])->name('feedback.store');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
});

/*
|--------------------------------------------------------------------------
| Staff-side areas (all use the "staff" login)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:staff')->group(function () {
    Route::get('/payment-proofs/{payment}', [PaymentProofFileController::class, 'staff'])->name('payment-proof');

    Route::middleware('role:staff')->prefix('staff')->name('staff.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'staff'])->name('dashboard');
        Route::get('/bookings', [StaffBookingController::class, 'index'])->name('bookings');
        Route::get('/bookings/{booking}', [BookingDetailController::class, 'show'])->name('bookings.show');
        Route::post('/bookings/{booking}/approve', [StaffBookingController::class, 'approve'])->name('bookings.approve');
        Route::get('/attendance', [StaffBookingController::class, 'attendance'])->name('attendance');
        Route::post('/attendance/{booking}', [StaffBookingController::class, 'markAttendance'])->name('attendance.mark');
        Route::get('/walkin', [WalkinController::class, 'index'])->name('walkin');
        Route::get('/walkin/staff', [WalkinController::class, 'availableStaff'])->name('walkin.staff');
        Route::post('/walkin', [WalkinController::class, 'store'])->name('walkin.create');
        Route::post('/walkin/{booking}/complete', [WalkinController::class, 'complete'])->name('walkin.complete');
        Route::get('/availability', [AccountController::class, 'availability'])->name('availability');
        Route::post('/availability', [AccountController::class, 'updateAvailability'])->name('availability.update');
        Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
        Route::put('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
        Route::put('/profile/password', [AccountController::class, 'updatePassword'])->name('profile.password');
    });

    Route::middleware('role:manager')->prefix('manager')->name('manager.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'manager'])->name('dashboard');
        Route::get('/bookings', [OperationsController::class, 'bookings'])->middleware('perm:bookings')->name('bookings');
        Route::get('/bookings/{booking}', [BookingDetailController::class, 'show'])->middleware('perm:bookings')->name('bookings.show');
        Route::post('/bookings/{booking}/cancel', [BookingDetailController::class, 'cancel'])->middleware('perm:bookings,edit')->name('bookings.cancel');
        Route::post('/bookings/{booking}/approve', [OperationsController::class, 'approveBooking'])->middleware('perm:bookings,edit')->name('bookings.approve');
        Route::post('/bookings/{booking}/decline', [OperationsController::class, 'declineBooking'])->middleware('perm:bookings,edit')->name('bookings.decline');
        Route::get('/payments', [OperationsController::class, 'payments'])->middleware('perm:payments')->name('payments');
        Route::post('/payments/{payment}/confirm', [OperationsController::class, 'confirmPayment'])->middleware('perm:payments,edit')->name('payments.confirm');
        Route::post('/payments/{payment}/reject', [OperationsController::class, 'rejectPayment'])->middleware('perm:payments,edit')->name('payments.reject');
        Route::get('/walkin', [WalkinController::class, 'index'])->middleware('perm:walkin')->name('walkin');
        Route::get('/walkin/staff', [WalkinController::class, 'availableStaff'])->middleware('perm:walkin')->name('walkin.staff');
        Route::post('/walkin', [WalkinController::class, 'store'])->middleware('perm:walkin,create')->name('walkin.create');
        Route::post('/walkin/{booking}/complete', [WalkinController::class, 'complete'])->middleware('perm:walkin,create')->name('walkin.complete');
        Route::get('/attendance', [OperationsController::class, 'attendance'])->middleware('perm:attendance')->name('attendance');
        Route::post('/attendance/{booking}', [OperationsController::class, 'updateAttendance'])->middleware('perm:attendance,edit')->name('attendance.update');
        Route::get('/customers', [OperationsController::class, 'customers'])->middleware('perm:customers')->name('customers');
        Route::get('/staff', [OperationsController::class, 'staffDirectory'])->middleware('perm:staff')->name('staff');
        Route::get('/services', [OperationsController::class, 'services'])->middleware('perm:services')->name('services');
        Route::get('/reports', [OperationsController::class, 'reports'])->middleware('perm:reports')->name('reports');
        Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
        Route::put('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
        Route::put('/profile/password', [AccountController::class, 'updatePassword'])->name('profile.password');
    });

    Route::middleware('role:owner')->prefix('owner')->name('owner.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'owner'])->name('dashboard');
        Route::get('/bookings', [OperationsController::class, 'bookings'])->name('bookings');
        Route::get('/bookings/{booking}', [BookingDetailController::class, 'show'])->name('bookings.show');
        Route::post('/bookings/{booking}/cancel', [BookingDetailController::class, 'cancel'])->name('bookings.cancel');
        Route::post('/bookings/{booking}/approve', [OperationsController::class, 'approveBooking'])->name('bookings.approve');
        Route::post('/bookings/{booking}/decline', [OperationsController::class, 'declineBooking'])->name('bookings.decline');
        Route::get('/payments', [OperationsController::class, 'payments'])->name('payments');
        Route::post('/payments/{payment}/confirm', [OperationsController::class, 'confirmPayment'])->name('payments.confirm');
        Route::post('/payments/{payment}/reject', [OperationsController::class, 'rejectPayment'])->name('payments.reject');
        Route::get('/walkin', [WalkinController::class, 'index'])->name('walkin');
        Route::get('/walkin/staff', [WalkinController::class, 'availableStaff'])->name('walkin.staff');
        Route::post('/walkin', [WalkinController::class, 'store'])->name('walkin.create');
        Route::post('/walkin/{booking}/complete', [WalkinController::class, 'complete'])->name('walkin.complete');
        Route::get('/attendance', [OperationsController::class, 'attendance'])->name('attendance');
        Route::post('/attendance/{booking}', [OperationsController::class, 'updateAttendance'])->name('attendance.update');
        Route::get('/customers', [OperationsController::class, 'customers'])->name('customers');
        Route::post('/customers/{customer}/status', [OperationsController::class, 'updateCustomerStatus'])->name('customers.status');
        Route::get('/staff', [OperationsController::class, 'staffDirectory'])->name('staff');
        Route::get('/services', [OperationsController::class, 'services'])->name('services');
        Route::post('/services', [AdministrationController::class, 'createService'])->name('services.create');
        Route::put('/services/{service}', [AdministrationController::class, 'updateService'])->name('services.update');
        Route::post('/services/{service}/toggle', [AdministrationController::class, 'toggleService'])->name('services.toggle');
        Route::get('/reports', [OperationsController::class, 'reports'])->name('reports');
        Route::get('/staff-availability', [AccountController::class, 'manageAvailability'])->name('staff-availability');
        Route::put('/staff-availability', [AccountController::class, 'updateStaffAvailability'])->name('staff-availability.update');
        Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
        Route::put('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
        Route::put('/profile/password', [AccountController::class, 'updatePassword'])->name('profile.password');
        Route::get('/promos', [AdministrationController::class, 'promos'])->name('promos');
        Route::post('/promos', [AdministrationController::class, 'createPromo'])->name('promos.create');
        Route::put('/promos/{promo}', [AdministrationController::class, 'updatePromo'])->name('promos.update');
        Route::post('/promos/{promo}/toggle', [AdministrationController::class, 'togglePromo'])->name('promos.toggle');
        Route::delete('/promos/{promo}', [AdministrationController::class, 'deletePromo'])->name('promos.delete');
        Route::get('/policies', [AdministrationController::class, 'policies'])->name('policies');
        Route::put('/policies', [AdministrationController::class, 'updatePolicies'])->name('policies.update');
        Route::get('/permissions', [AdministrationController::class, 'permissions'])->name('permissions');
        Route::put('/permissions', [AdministrationController::class, 'updatePermissions'])->name('permissions.update');
        Route::get('/logs', [AdministrationController::class, 'logs'])->name('logs');
        Route::get('/staff/manage', [AdministrationController::class, 'staff'])->name('staff.manage');
        Route::post('/staff/manage', [AdministrationController::class, 'createStaff'])->name('staff.create');
        Route::put('/staff/{staff}', [AdministrationController::class, 'updateStaff'])->name('staff.update');
        Route::post('/staff/{staff}/archive', [AdministrationController::class, 'archiveStaff'])->name('staff.archive');
        Route::post('/staff/{staff}/toggle', [AdministrationController::class, 'toggleStaff'])->name('staff.toggle');
    });
});
