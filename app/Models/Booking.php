<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Booking extends Model
{
    protected $table = 'booking';

    protected $primaryKey = 'bookingID';

    protected $guarded = ['bookingID'];

    protected function casts(): array
    {
        return [
            'bookingDate' => 'date',
            'total_amount' => 'decimal:2',
            'downpayment_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customerID', 'customerID');
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staffID', 'staffID');
    }

    // Named serviceItem because the booking table already has a text column called "service".
    public function serviceItem()
    {
        return $this->belongsTo(Service::class, 'serviceID', 'serviceID');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'bookingID', 'bookingID');
    }

    public function feedback()
    {
        return $this->hasOne(Feedback::class, 'bookingID', 'bookingID');
    }

    public const SOURCE_ONLINE = 'online';

    public const SOURCE_WALKIN = 'walkin';

    /**
     * Bookings made at the front desk.
     */
    public function scopeWalkins($query)
    {
        return $query->where('booking_source', self::SOURCE_WALKIN);
    }

    public function getIsWalkinAttribute(): bool
    {
        return $this->booking_source === self::SOURCE_WALKIN;
    }

    // Only this customer's bookings.
    public function scopeOwnedBy($query, int $customerId)
    {
        return $query->where('customerID', $customerId);
    }

    // The name to show: the text saved with the booking, or the service's current name.
    public function getServiceNameAttribute(): string
    {
        return $this->service ?: (string) $this->serviceItem?->serviceName;
    }

    public function getStaffNameAttribute(): ?string
    {
        return $this->staff?->full_name;
    }

    // What the customer owes up front: the downpayment, or everything for a full payment.
    public function getAmountDueAttribute(): float
    {
        return (float) ($this->payment_type === 'partial' ? $this->downpayment_amount : $this->total_amount);
    }

    // What is still unpaid after the up-front payment (nothing, for a full payment).
    public function getRemainingBalanceAttribute(): float
    {
        return $this->payment_type === 'partial'
            ? max(0.0, (float) $this->total_amount - (float) $this->downpayment_amount)
            : 0.0;
    }

    /**
     * The appointment's start as a date-time in the salon's time zone.
     */
    public function getAppointmentAtAttribute(): Carbon
    {
        return Carbon::parse($this->bookingDate->toDateString().' '.$this->bookingTime);
    }

    /**
     * The last moment the customer can cancel or reschedule (the "cancellation window" policy).
     */
    public function getChangeDeadlineAttribute(): Carbon
    {
        return $this->appointment_at->copy()->subHours(SystemPolicy::cancellationWindowHours());
    }

    /**
     * Still open (pending or approved), booked online, and before the change deadline.
     */
    public function getIsChangeableByCustomerAttribute(): bool
    {
        return in_array($this->bookingStatus, ['pending', 'approved'], true)
            && ! $this->is_walkin
            && now()->lt($this->change_deadline);
    }

    public function getCanCancelAttribute(): bool
    {
        return $this->is_changeable_by_customer;
    }

    public function getReschedulesLeftAttribute(): int
    {
        return max(0, SystemPolicy::maxReschedules() - (int) $this->reschedule_count);
    }

    public function getCanRescheduleAttribute(): bool
    {
        return $this->is_changeable_by_customer && $this->reschedules_left > 0;
    }

    // Payment proof can be sent while the booking is open and not yet confirmed as paid.
    public function getCanUploadProofAttribute(): bool
    {
        return in_array($this->bookingStatus, ['pending', 'approved'], true)
            && ! in_array($this->paymentStatus, ['confirmed', 'paid'], true);
    }

    // The "Pay Now" shortcut in the bookings list: approved, and not paid yet.
    public function getCanPayNowAttribute(): bool
    {
        return $this->bookingStatus === 'approved'
            && ! in_array($this->paymentStatus, ['confirmed', 'paid'], true);
    }
}
