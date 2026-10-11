@php
    $isStaff = auth('staff')->check();
    $messengerContact = \App\Models\MessengerContact::forUser(
        $isStaff ? 'staff' : 'customer',
        (int) ($isStaff ? auth('staff')->id() : auth('customer')->id())
    )->first();
    $pageHandle = config('services.messenger.page_username') ?: config('services.messenger.page_id');
@endphp
@if (config('services.messenger.page_token'))
    <section class="card">
        <div class="card-header"><span><i class="fab fa-facebook-messenger"></i> Messenger</span></div>
        <div class="card-body">
            @if ($messengerContact?->is_linked)
                <p>Connected since {{ $messengerContact->linked_at?->format('M d, Y') }}.</p>
                <p class="muted-text" style="font-size:.84rem">
                    @if ($messengerContact->can_receive)
                        You will get booking updates on Messenger.
                    @else
                        Your last message to us was over 24 hours ago, so updates will only show in the app. Send us any message on Messenger to turn them back on.
                    @endif
                </p>
                <form method="POST" action="{{ route('messenger.disconnect') }}" style="margin-top:12px">
                    @csrf
                    <button type="submit" class="btn-ghost btn-sm">Disconnect</button>
                </form>
            @elseif (session('messenger_code'))
                <p><strong>Almost done.</strong></p>
                <ol style="margin:8px 0 0 18px;font-size:.88rem;line-height:1.7">
                    <li>Open our chat: <a href="https://m.me/{{ $pageHandle }}" target="_blank" rel="noopener">m.me/{{ $pageHandle }}</a></li>
                    <li>Send this code (valid for 30 minutes): <strong style="font-size:1.1rem;letter-spacing:1px">{{ session('messenger_code') }}</strong></li>
                </ol>
            @else
                <p class="muted-text" style="font-size:.88rem">Get your booking updates in Messenger.</p>
                <form method="POST" action="{{ route('messenger.connect') }}" style="margin-top:12px">
                    @csrf
                    <button type="submit" class="btn-primary btn-sm"><i class="fab fa-facebook-messenger"></i> Connect Messenger</button>
                </form>
            @endif
        </div>
    </section>
@endif
