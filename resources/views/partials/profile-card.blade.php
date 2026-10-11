{{-- Left-hand summary card on every profile page. --}}
@php
    $initials = collect(preg_split('/\s+/', trim($name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
@endphp
<section class="card profile-card">
    <div class="profile-avatar" aria-hidden="true">{{ $initials }}</div>
    <h2>{{ $name }}</h2>
    <div class="profile-role">{{ $roleLabel }}</div>
    <dl class="kv">
        <dt>Email</dt><dd>{{ $email ?: '—' }}</dd>
        <dt>Contact number</dt><dd>{{ $contact ?: '—' }}</dd>
        @isset($extra)
            @foreach ($extra as $label => $value)
                <dt>{{ $label }}</dt><dd>{{ $value }}</dd>
            @endforeach
        @endisset
    </dl>
</section>
