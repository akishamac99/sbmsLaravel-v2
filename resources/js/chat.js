const root = document.getElementById('chat');

if (root) {
    const $ = (id) => document.getElementById(id);
    const fab = $('chat-fab');
    const panel = $('chat-panel');
    const badge = $('chat-badge');
    const title = $('chat-title');
    const back = $('chat-back');
    const list = $('chat-list');
    const thread = $('chat-thread');
    const box = $('chat-messages');
    const form = $('chat-form');
    const input = $('chat-input');

    const urls = {
        contacts: root.dataset.urlContacts,
        messages: root.dataset.urlMessages,
        send: root.dataset.urlSend,
        unread: root.dataset.urlUnread,
    };

    let open = false;
    let current = null;
    let lastId = 0;
    let timer = null;

    const el = (tag, className, text) => {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined && text !== null) node.textContent = text;
        return node;
    };

    async function getJson(url) {
        const res = await fetch(url, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!res.ok) throw new Error(res.status);
        return res.json();
    }

    function setBadge(count) {
        badge.textContent = count > 99 ? '99+' : String(count);
        badge.hidden = count < 1;
    }

    function renderContacts(rows) {
        list.replaceChildren();

        if (!rows.length) {
            list.append(el('div', 'chat-empty', root.dataset.me === 'customer'
                ? 'Book a service to chat with your stylist.'
                : 'No customers to chat with yet.'));
            return;
        }

        rows.forEach((row) => {
            const button = el('button', 'chat-contact');
            button.type = 'button';

            const main = el('div', 'chat-contact-main');
            main.append(el('div', 'chat-contact-name', row.name));
            main.append(el('div', 'chat-contact-sub', row.last || row.sub || 'Start a conversation'));
            button.append(main);

            if (row.unread > 0) button.append(el('span', 'chat-unread', row.unread));

            button.addEventListener('click', () => openThread(row));
            list.append(button);
        });
    }

    async function loadContacts() {
        renderContacts((await getJson(urls.contacts)).contacts);
    }

    function addMessage(message) {
        if (message.id <= lastId) return;
        lastId = message.id;

        const bubble = el('div', 'chat-msg ' + (message.mine ? 'mine' : 'theirs'));
        bubble.append(el('span', null, message.body));

        const time = message.at
            ? new Date(message.at).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })
            : '';
        bubble.append(el('small', 'chat-time', time));
        box.append(bubble);
    }

    async function loadMessages() {
        if (!current) return;

        const who = current.id;
        const first = lastId === 0;
        const data = await getJson(`${urls.messages}?with=${who}&after=${lastId}`);

        if (!current || current.id !== who) return;

        const nearBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 60;
        data.messages.forEach(addMessage);

        if (first || nearBottom) box.scrollTop = box.scrollHeight;
    }

    function openThread(contact) {
        current = contact;
        lastId = 0;
        box.replaceChildren();
        title.textContent = contact.name;
        list.hidden = true;
        thread.hidden = false;
        back.hidden = false;
        loadMessages().catch(() => {});
        input.focus();
    }

    function showList() {
        current = null;
        title.textContent = 'Messages';
        thread.hidden = true;
        list.hidden = false;
        back.hidden = true;
        loadContacts().catch(() => {});
    }

    function setOpen(value) {
        open = value;
        panel.hidden = !value;
        fab.setAttribute('aria-expanded', String(value));
        if (value && !current) showList();
        tick();
    }

    async function tick() {
        clearTimeout(timer);

        try {
            if (open && current) await loadMessages();
            else if (open) await loadContacts();

            setBadge((await getJson(urls.unread)).unread);
        } catch (e) {
            // Offline or logged out: try again next round.
        }

        const delay = document.hidden ? 20000 : open ? (current ? 3000 : 8000) : 10000;
        timer = setTimeout(tick, delay);
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const body = input.value.trim();
        if (!body || !current) return;

        input.value = '';
        input.placeholder = 'Type a message...';

        try {
            const res = await fetch(urls.send, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': root.dataset.csrf,
                },
                body: JSON.stringify({ with: current.id, body }),
            });

            if (!res.ok) throw new Error(res.status);

            addMessage((await res.json()).message);
            box.scrollTop = box.scrollHeight;
        } catch (e) {
            input.value = body;
            input.placeholder = 'Could not send. Try again.';
        }
    });

    fab.addEventListener('click', () => setOpen(!open));
    $('chat-close').addEventListener('click', () => setOpen(false));
    back.addEventListener('click', showList);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && open) setOpen(false);
    });
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) tick();
    });

    tick();
}
