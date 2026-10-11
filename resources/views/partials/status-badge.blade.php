@php
    $map = [
        'pending' => 'p-pend', 'proof-submitted' => 'p-pend',
        'approved' => 'p-conf', 'confirmed' => 'p-conf', 'paid' => 'p-conf', 'present' => 'p-conf',
        'completed' => 'p-comp',
        'declined' => 'p-dec', 'rejected' => 'p-dec', 'no-show' => 'p-dec',
        'cancelled' => 'p-cancel',
    ];
@endphp
<span class="pill {{ $map[$status] ?? 'p-pend' }}">{{ ucfirst(str_replace('-', ' ', $status)) }}</span>
