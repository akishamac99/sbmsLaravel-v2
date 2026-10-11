@php
    $badges = [
        'pending' => ['p-pend', 'Pending'],
        'approved' => ['p-conf', 'Approved'],
        'completed' => ['p-comp', 'Completed'],
        'declined' => ['p-dec', 'Declined'],
        'cancelled' => ['p-cancel', 'Cancelled'],
        'confirmed' => ['p-conf', 'Confirmed'],
        'paid' => ['p-conf', 'Paid'],
        'proof-submitted' => ['p-proof', 'Proof Submitted'],
        'rejected' => ['p-dec', 'Rejected'],
        'no-show' => ['p-cancel', 'No-Show'],
        'present' => ['p-conf', 'Present'],
    ];
    [$badgeClass, $badgeLabel] = $badges[$status] ?? ['', ucfirst((string) $status)];
@endphp
<span class="pill {{ $badgeClass }}">{{ $badgeLabel }}</span>
