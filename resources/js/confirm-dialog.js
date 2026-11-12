// A styled confirmation dialog that replaces the browser's confirm() box.
// - Works with onsubmit/onclick="return confirm('...')" already in the views, and with data-confirm.
// - "No, go back" is the focused default, so pressing Enter by accident does not do the risky thing.
// - Esc, the backdrop and the Back button all cancel.

const RISKY = /\b(delete|archive|decline|reject|cancel|block|remove|no-show|permanently)\b/i;
const CONFIRM_CALL = /confirm\(\s*(['"])((?:\\[\s\S]|(?!\1)[^\\])*)\1\s*\)/;

let root; let titleEl; let messageEl; let yesBtn; let noBtn; let resolver; let lastFocus;

function build() {
    root = document.createElement('div');
    root.className = 'ux-confirm';
    root.hidden = true;
    root.innerHTML =
        '<div class="ux-confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="ux-confirm-title" aria-describedby="ux-confirm-message">' +
        '<h2 id="ux-confirm-title">Please confirm</h2>' +
        '<p id="ux-confirm-message"></p>' +
        '<div class="ux-confirm-actions"><button type="button" class="ux-confirm-no">No, go back</button>' +
        '<button type="button" class="ux-confirm-yes">Yes, continue</button></div></div>';
    document.body.appendChild(root);
    titleEl = root.querySelector('#ux-confirm-title');
    messageEl = root.querySelector('#ux-confirm-message');
    yesBtn = root.querySelector('.ux-confirm-yes');
    noBtn = root.querySelector('.ux-confirm-no');

    const finish = (answer) => {
        root.hidden = true;
        document.documentElement.classList.remove('ux-confirm-open');
        if (lastFocus && document.contains(lastFocus)) lastFocus.focus();
        const done = resolver; resolver = null;
        if (done) done(answer);
    };
    yesBtn.addEventListener('click', () => finish(true));
    noBtn.addEventListener('click', () => finish(false));
    root.addEventListener('click', (event) => { if (event.target === root) finish(false); });
    root.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') { event.preventDefault(); event.stopPropagation(); finish(false); return; }
        if (event.key !== 'Tab') return;
        const first = noBtn; const last = yesBtn;
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    });
}

export function uxConfirm(message, { yes = 'Yes, continue', title = 'Please confirm', danger } = {}) {
    if (!root) build();
    if (resolver) return Promise.resolve(false);
    lastFocus = document.activeElement;

    const tr = (text) => (window.uxTranslate ? window.uxTranslate(text) : text);
    titleEl.textContent = tr(title);
    messageEl.textContent = tr(message);
    yesBtn.textContent = tr(yes);
    noBtn.textContent = tr('No, go back');
    yesBtn.classList.toggle('danger', danger ?? RISKY.test(message + ' ' + yes));

    root.hidden = false;
    document.documentElement.classList.add('ux-confirm-open');
    noBtn.focus();
    return new Promise((resolve) => { resolver = resolve; });
}
window.uxConfirm = uxConfirm;

function messageFor(el) {
    if (el.dataset && el.dataset.confirm) return el.dataset.confirm;
    for (const attr of ['onsubmit', 'onclick']) {
        const code = el.getAttribute && el.getAttribute(attr);
        const m = code && code.match(CONFIRM_CALL);
        if (m) return m[2].replace(/\\([\s\S])/g, '$1');
    }
    return null;
}

const labelOf = (button) => {
    const text = (button && button.textContent || '').replace(/\s+/g, ' ').trim();
    return text && text.length <= 26 ? text : 'Yes, continue';
};

// Let the real action through once, without the old browser question.
function proceed(form, submitter) {
    const stash = { onsubmit: form.getAttribute('onsubmit'), confirm: form.dataset.confirm };
    form.removeAttribute('onsubmit');
    delete form.dataset.confirm;
    const buttonStash = submitter ? { onclick: submitter.getAttribute('onclick'), confirm: submitter.dataset.confirm } : null;
    if (submitter) { submitter.removeAttribute('onclick'); delete submitter.dataset.confirm; }
    try {
        if (submitter && submitter.form === form) form.requestSubmit(submitter); else form.requestSubmit();
    } finally {
        if (stash.onsubmit) form.setAttribute('onsubmit', stash.onsubmit);
        if (stash.confirm) form.dataset.confirm = stash.confirm;
        if (buttonStash) {
            if (buttonStash.onclick) submitter.setAttribute('onclick', buttonStash.onclick);
            if (buttonStash.confirm) submitter.dataset.confirm = buttonStash.confirm;
        }
    }
}

// Buttons that carry the question on themselves (onclick / data-confirm).
document.addEventListener('click', (event) => {
    const el = event.target.closest('button, a[data-confirm], input[type=submit]');
    if (!el || event.defaultPrevented) return;
    const message = messageFor(el);
    if (!message) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    uxConfirm(message, { yes: labelOf(el) }).then((ok) => {
        if (!ok) return;
        if (el.tagName === 'A') { window.location.href = el.href; return; }
        if (el.form) proceed(el.form, el);
    });
}, true);

// Forms that carry the question (onsubmit / data-confirm).
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    const message = messageFor(form);
    if (!message) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    const submitter = event.submitter;
    uxConfirm(message, { yes: labelOf(submitter) }).then((ok) => { if (ok) proceed(form, submitter); });
}, true);
