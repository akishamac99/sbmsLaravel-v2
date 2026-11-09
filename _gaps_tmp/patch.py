import sys, pathlib
root = pathlib.Path(sys.argv[1])
def edit(rel, pairs):
    p = root / rel
    s = p.read_text(encoding='utf-8')
    for old, new in pairs:
        if s.count(old) != 1:
            sys.exit(f"ANCHOR PROBLEM in {rel}: found {s.count(old)} times: {old[:70]!r}")
        s = s.replace(old, new)
    p.write_text(s, encoding='utf-8')
    print("patched", rel)

# ---------------- routes ----------------
use_old = "use App\\Http\\Controllers\\Portal\\ViewFeedbackController;"
use_new = use_old + """
use App\\Http\\Controllers\\Portal\\EditBookingController;
use App\\Http\\Controllers\\Portal\\RecordPaymentController;
use App\\Http\\Controllers\\Portal\\ViewCustomerRecordController;
use App\\Http\\Controllers\\Portal\\ViewScheduleController;"""

staff_old = "        Route::post('/bookings/{booking}/approve', [StaffBookingController::class, 'approve'])->name('bookings.approve');"
staff_new = staff_old + """

        // Update Booking (own bookings), View Schedule, View Customer Records
        Route::get('/bookings/{booking}/edit', [EditBookingController::class, 'edit'])->name('bookings.edit');
        Route::put('/bookings/{booking}', [EditBookingController::class, 'update'])->name('bookings.update');
        Route::get('/schedule', ViewScheduleController::class)->name('schedule');
        Route::get('/customers', [ViewCustomerRecordController::class, 'index'])->name('customers');
        Route::get('/customers/{customer}', [ViewCustomerRecordController::class, 'show'])->name('customers.show');"""

mgr_old = "->middleware('perm:bookings,edit')->name('bookings.decline');"
mgr_new = mgr_old + """

        // Update Booking / Assign Staff, Record Payment, Schedule, Customer records, Staff availability
        Route::get('/bookings/{booking}/edit', [EditBookingController::class, 'edit'])->middleware('perm:bookings,edit')->name('bookings.edit');
        Route::put('/bookings/{booking}', [EditBookingController::class, 'update'])->middleware('perm:bookings,edit')->name('bookings.update');
        Route::post('/bookings/{booking}/payments', RecordPaymentController::class)->middleware(['perm:payments,edit', 'throttle:30,1'])->name('bookings.payments.record');
        Route::get('/schedule', ViewScheduleController::class)->middleware('perm:bookings')->name('schedule');
        Route::get('/customers/{customer}', [ViewCustomerRecordController::class, 'show'])->middleware('perm:customers')->name('customers.show');
        Route::get('/staff-availability', [AccountController::class, 'manageAvailability'])->middleware('perm:staff')->name('staff-availability');
        Route::put('/staff-availability', [AccountController::class, 'updateStaffAvailability'])->middleware('perm:staff,edit')->name('staff-availability.update');
        Route::post('/staff-availability/requests/{availabilityRequest}/approve', [AccountController::class, 'approveAvailabilityRequest'])->middleware('perm:staff,edit')->name('staff-availability.approve');
        Route::post('/staff-availability/requests/{availabilityRequest}/reject', [AccountController::class, 'rejectAvailabilityRequest'])->middleware('perm:staff,edit')->name('staff-availability.reject');"""

own_old = "        Route::post('/bookings/{booking}/decline', [OperationsController::class, 'declineBooking'])->name('bookings.decline');"
own_new = own_old + """

        // Update Booking / Assign Staff, Record Payment, Schedule, Customer records
        Route::get('/bookings/{booking}/edit', [EditBookingController::class, 'edit'])->name('bookings.edit');
        Route::put('/bookings/{booking}', [EditBookingController::class, 'update'])->name('bookings.update');
        Route::post('/bookings/{booking}/payments', RecordPaymentController::class)->middleware('throttle:30,1')->name('bookings.payments.record');
        Route::get('/schedule', ViewScheduleController::class)->name('schedule');
        Route::get('/customers/{customer}', [ViewCustomerRecordController::class, 'show'])->name('customers.show');"""

edit('routes/web.php', [(use_old, use_new), (staff_old, staff_new), (mgr_old, mgr_new), (own_old, own_new)])

# ---------------- nav ----------------
edit('app/Support/Nav.php', [
 ("                    ['Manage Bookings', 'bookings', 'fa-calendar', null],",
  "                    ['Manage Bookings', 'bookings', 'fa-calendar', null],\n                    ['Schedule', 'schedule', 'fa-calendar-days', null],"),
 ("                    ['Manage Bookings', 'bookings', 'fa-calendar', 'bookings'],",
  "                    ['Manage Bookings', 'bookings', 'fa-calendar', 'bookings'],\n                    ['Schedule', 'schedule', 'fa-calendar-days', 'bookings'],"),
 ("                    ['Staff Directory', 'staff', 'fa-id-card', 'staff'],",
  "                    ['Staff Directory', 'staff', 'fa-id-card', 'staff'],\n                    ['Staff Availability', 'staff-availability', 'fa-clock', 'staff'],"),
 ("                    ['My Feedback', 'feedback', 'fa-star', null],",
  "                    ['My Feedback', 'feedback', 'fa-star', null],\n                    ['Schedule', 'schedule', 'fa-calendar-days', null],\n                    ['My Customers', 'customers', 'fa-users', null],"),
])

# ---------------- availability shared by owner and manager ----------------
p = root / 'app/Http/Controllers/Staff/AccountController.php'
s = p.read_text(encoding='utf-8')
n = s.count("SystemLog::record('owner', (int) Auth::guard('staff')->id(),")
assert n == 3, n
s = s.replace("SystemLog::record('owner', (int) Auth::guard('staff')->id(),", "SystemLog::record(Auth::guard('staff')->user()->role_slug, (int) Auth::guard('staff')->id(),")
old = "        return view('owner.staff-availability', [\n            'staffMembers' => $staffMembers,"
assert s.count(old) == 1
s = s.replace(old, "        return view('owner.staff-availability', [\n            'role' => Auth::guard('staff')->user()->role_slug,\n            'staffMembers' => $staffMembers,")
p.write_text(s, encoding='utf-8'); print("patched AccountController")

p = root / 'resources/views/owner/staff-availability.blade.php'
s = p.read_text(encoding='utf-8')
c = s.count("route('owner.staff-availability")
assert c >= 4, c
s = s.replace("route('owner.staff-availability", "route($role . '.staff-availability")
p.write_text(s, encoding='utf-8'); print("patched staff-availability view", c)

# ---------------- booking detail controller ----------------
edit('app/Http/Controllers/Portal/BookingDetailController.php', [
 ("use App\\Models\\Booking;\n", "use App\\Models\\Booking;\nuse App\\Support\\BookingAccess;\nuse App\\Support\\PaymentLedger;\n"),
 ("            'canCancel' => $this->canCancel($role, $booking),\n        ]);",
  "            'canCancel' => $this->canCancel($role, $booking),\n            'canEdit' => BookingAccess::canEdit($user, $booking),\n            'canRecordPayment' => BookingAccess::canRecordPayment($user) && in_array($booking->bookingStatus, ['pending', 'approved', 'completed'], true),\n            'paid' => PaymentLedger::paid($booking),\n            'outstanding' => PaymentLedger::outstanding($booking),\n        ]);"),
])

# ---------------- booking details view ----------------
edit('resources/views/portal/booking-details.blade.php', [
 ("        @include('partials.status-badge', ['status' => $booking->bookingStatus])\n    </div>",
  "        @include('partials.status-badge', ['status' => $booking->bookingStatus])\n        @if ($canEdit)\n            <a class=\"btn-ghost btn-sm\" href=\"{{ route($role . '.bookings.edit', $booking) }}\"><i class=\"fas fa-pen\"></i> Edit booking</a>\n        @endif\n    </div>"),
 ("                    <dt>Balance on the day</dt><dd>&#8369;{{ number_format($booking->remaining_balance, 2) }}</dd>\n",
  "                    <dt>Balance on the day</dt><dd>&#8369;{{ number_format($booking->remaining_balance, 2) }}</dd>\n                    <dt>Paid so far</dt><dd>&#8369;{{ number_format($paid, 2) }}</dd>\n                    <dt>Still to pay</dt><dd>&#8369;{{ number_format($outstanding, 2) }}</dd>\n"),
 ("            @endif\n        </section>\n\n        @if ($booking->feedback)",
  """            @endif
            @if ($canRecordPayment && $outstanding > 0)
                <form method="POST" action="{{ route($role . '.bookings.payments.record', $booking) }}" class="card-body" style="border-top:1px solid rgba(139,58,82,0.08);display:grid;gap:12px">
                    @csrf
                    <strong>Record a payment</strong>
                    <label class="field"><span class="field-label">Amount (PHP)</span>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0.01" max="{{ $outstanding }}" value="{{ old('amount', number_format($outstanding, 2, '.', '')) }}" required>
                    </label>
                    @error('amount')<div class="field-error" role="alert">{{ $message }}</div>@enderror
                    <label class="field"><span class="field-label">Method</span>
                        <select name="method" class="form-control" required>
                            <option value="cash">Cash</option>
                            <option value="gcash">GCash</option>
                            <option value="bank_transfer">Bank transfer</option>
                        </select>
                    </label>
                    <label class="field"><span class="field-label">Reference number <span class="field-hint">Optional</span></span>
                        <input type="text" name="reference" class="form-control" maxlength="50" value="{{ old('reference') }}">
                    </label>
                    <div><button type="submit" class="btn-primary"><i class="fas fa-receipt"></i> Record payment</button></div>
                </form>
            @endif
        </section>

        @if ($booking->feedback)"""),
])

# ---------------- customers list: link to the record ----------------
edit('resources/views/portal/customers.blade.php', [
 ("<td><span class=\"cell-main\">{{ $customer->full_name }}</span></td>",
  "<td><a class=\"cell-main\" href=\"{{ route($role . '.customers.show', $customer) }}\">{{ $customer->full_name }}</a></td>"),
])
print("ALL PATCHES OK")
