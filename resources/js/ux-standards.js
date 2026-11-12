// UI/UX standards layer. Works on every page that loads it and never replaces a page's own scripts.
import '../css/ux-standards.css';
import './confirm-dialog.js';
import './i18n.js';

const all = (selector, root = document) => [...root.querySelectorAll(selector)];
const isPost = (form) => (form.getAttribute('method') || 'get').toLowerCase() !== 'get';

// Screen reader announcements
const live = document.createElement('div');
live.className = 'ux-live';
live.setAttribute('role', 'status');
live.setAttribute('aria-live', 'polite');
document.addEventListener('DOMContentLoaded', () => document.body.appendChild(live));
const say = (text) => { live.textContent = ''; setTimeout(() => { live.textContent = text; }, 50); };

// ---------- Rule 1: Visibility of system status -----------------------------
// Top progress bar while the next page loads.
const bar = document.createElement('div');
bar.className = 'ux-progress';
bar.setAttribute('aria-hidden', 'true');
document.addEventListener('DOMContentLoaded', () => document.body.appendChild(bar));
let barTimer;
const stopBar = () => { bar.classList.remove('on'); clearTimeout(barTimer); };

document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');
    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    if (link.target === '_blank' || link.hasAttribute('download')) return;
    const href = link.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
    const url = new URL(link.href, location.href);
    if (url.origin !== location.origin) return;
    if (url.pathname === location.pathname && url.search === location.search) return;
    bar.classList.add('on');
    barTimer = setTimeout(stopBar, 12000); // a download or slow link must never leave the bar stuck
});
window.addEventListener('pageshow', stopBar);

// Submit buttons show that the request is being sent, and a second click cannot send it twice.
// (The button is not disabled, so its name/value is still sent with the form.)
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || event.defaultPrevented) return;
    if (!isPost(form) || form.hasAttribute('data-no-loading')) return;

    if (form.dataset.submitting === '1') { event.preventDefault(); return; }
    form.dataset.submitting = '1';

    const button = event.submitter || form.querySelector('button[type=submit], button:not([type])');
    let original = null;
    if (button && button.tagName === 'BUTTON') {
        original = button.innerHTML;
        button.dataset.uxBusy = '1';
        button.setAttribute('aria-disabled', 'true');
        button.innerHTML = '<span class="ux-spin" aria-hidden="true"></span>' + (button.dataset.loadingText || 'Please wait...');
    }
    say('Please wait. Your request is being sent.');

    const restore = () => {
        form.dataset.submitting = '';
        if (button && original !== null) {
            button.innerHTML = original;
            button.removeAttribute('data-ux-busy');
            button.removeAttribute('aria-disabled');
        }
    };
    window.addEventListener('pageshow', restore, { once: true });

    // Weak connection: after 15 seconds tell the person what is going on and let them try again.
    setTimeout(() => {
        if (form.dataset.submitting !== '1') return;
        let note = form.querySelector('.ux-slow');
        if (!note) {
            note = document.createElement('p');
            note.className = 'ux-slow';
            note.setAttribute('role', 'alert');
            note.textContent = 'This is taking longer than usual. Please check your internet connection. You can try again.';
            (button ? button.parentElement : form).appendChild(note);
        }
        restore();
    }, 15000);
}, false);

// ---------- Rule 5: Error prevention ----------------------------------------
// A. The submit button is grayed out until the required fields are valid, with a reason beside it.
//    It stays clickable for keyboard / screen reader users: clicking shows what is missing.
let gateCount = 0;
function setupGate(form) {
    if (form.dataset.gate || !isPost(form) || form.noValidate || form.hasAttribute('data-no-gate')) return;
    const submit = form.querySelector('button[type=submit], button:not([type]), input[type=submit]');
    if (!submit || submit.disabled || submit.hasAttribute('formnovalidate') || !form.querySelector('[required]')) return;

    form.dataset.gate = '1';
    const hint = document.createElement('span');
    hint.className = 'ux-gate-hint';
    hint.id = 'ux-gate-' + (++gateCount);
    hint.textContent = 'Fill in the required fields to continue.';
    hint.hidden = true;
    submit.insertAdjacentElement('afterend', hint);
    submit.setAttribute('aria-describedby', [submit.getAttribute('aria-describedby'), hint.id].filter(Boolean).join(' '));

    const update = () => {
        const ok = form.checkValidity();
        submit.classList.toggle('ux-gated', !ok);
        if (ok) submit.removeAttribute('aria-disabled'); else submit.setAttribute('aria-disabled', 'true');
        hint.hidden = ok;
        return ok;
    };
    form.addEventListener('input', update);
    form.addEventListener('change', update);
    submit.addEventListener('click', (event) => {
        if (!update()) { event.preventDefault(); form.reportValidity(); }
    });
    setInterval(() => { if (form.offsetParent !== null) update(); }, 1000); // modals that fill their fields with script
    update();
}

// B. Mobile numbers: only digits, the format is shown, and mistakes are flagged while typing.
function phoneProblem(raw) {
    const digits = raw.replace(/\D/g, '');
    if (!digits) return '';
    if (/^09\d*$/.test(digits)) return digits.length === 11 ? '' : 'Mobile number must have 11 digits (09XX XXX XXXX).';
    if (/^639\d*$/.test(digits)) return digits.length === 12 ? '' : 'Use 11 digits starting with 09 (09XX XXX XXXX).';
    return 'A Philippine mobile number starts with 09 (09XX XXX XXXX).';
}

function setupPhone(input) {
    if (input.dataset.phone) return;
    input.dataset.phone = '1';
    input.setAttribute('inputmode', 'tel');
    input.setAttribute('autocomplete', input.getAttribute('autocomplete') || 'tel');
    if (!input.placeholder) input.placeholder = '09XX XXX XXXX';

    const hint = document.createElement('span');
    hint.className = 'ux-field-hint';
    hint.id = 'ux-phone-' + (++gateCount);
    hint.setAttribute('aria-live', 'polite');
    input.insertAdjacentElement('afterend', hint);
    input.setAttribute('aria-describedby', [input.getAttribute('aria-describedby'), hint.id].filter(Boolean).join(' '));

    const check = () => {
        input.value = input.value.replace(/[^\d+\s-]/g, '');
        const problem = phoneProblem(input.value);
        input.setCustomValidity(problem);
        hint.className = 'ux-field-hint' + (problem ? ' bad' : input.value.trim() ? ' good' : '');
        hint.textContent = problem || (input.value.trim() ? 'Looks good.' : '');
    };
    input.addEventListener('input', check);
    check();
}

// ---------- Rule 3: User control and freedom --------------------------------
// A form marked data-warn-unsaved warns before the person leaves with unsaved typing.
function setupUnsaved(form) {
    let dirty = false;
    form.addEventListener('input', () => { dirty = true; });
    form.addEventListener('submit', () => { dirty = false; });
    window.addEventListener('beforeunload', (event) => {
        if (!dirty) return;
        event.preventDefault();
        event.returnValue = '';
    });
}

function init() {
    all('form').forEach(setupGate);
    all('input[type=tel]').forEach(setupPhone);
    all('form[data-warn-unsaved]').forEach(setupUnsaved);
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
