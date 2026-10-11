<div class="chat" id="chat"
     data-me="{{ auth('customer')->check() ? 'customer' : 'staff' }}"
     data-csrf="{{ csrf_token() }}"
     data-url-contacts="{{ route('chat.contacts') }}"
     data-url-messages="{{ route('chat.messages') }}"
     data-url-send="{{ route('chat.send') }}"
     data-url-unread="{{ route('chat.unread') }}">
    <button type="button" class="chat-fab" id="chat-fab" aria-label="Open chat" aria-expanded="false">
        <i class="fas fa-comment-dots"></i>
        <span class="chat-badge" id="chat-badge" hidden>0</span>
    </button>

    <section class="chat-panel" id="chat-panel" hidden aria-label="Chat">
        <header class="chat-head">
            <button type="button" id="chat-back" hidden aria-label="Back"><i class="fas fa-arrow-left"></i></button>
            <div class="chat-title" id="chat-title">Messages</div>
            <button type="button" id="chat-close" aria-label="Close chat"><i class="fas fa-times"></i></button>
        </header>
        <div class="chat-list" id="chat-list"></div>
        <div class="chat-thread" id="chat-thread" hidden>
            <div class="chat-messages" id="chat-messages"></div>
            <form class="chat-form" id="chat-form" autocomplete="off">
                <input type="text" id="chat-input" maxlength="1000" placeholder="Type a message..." required>
                <button type="submit" aria-label="Send"><i class="fas fa-paper-plane"></i></button>
            </form>
        </div>
    </section>
</div>
