// English / Filipino switch. The choice is remembered on this device.
// Filipino wording lives in i18n-fil.js and is loaded only when it is needed.

const KEY = 'sbms_lang';
const read = () => { try { return localStorage.getItem(KEY) === 'fil' ? 'fil' : 'en'; } catch (e) { return 'en'; } };
const write = (value) => { try { localStorage.setItem(KEY, value); } catch (e) { /* private mode: the switch still works for this page */ } };

const SKIP = new Set(['SCRIPT', 'STYLE', 'TEXTAREA', 'CODE', 'PRE', 'NOSCRIPT']);
const ATTRS = ['placeholder', 'title', 'aria-label', 'alt'];

function buildTranslator({ dict, patterns }) {
    const translate = (text) => {
        const lead = text.match(/^\s*/)[0];
        const tail = text.match(/\s*$/)[0];
        const core = text.trim().replace(/\s+/g, ' ');
        if (core.length < 2) return text;
        const hit = dict.get(core.toLowerCase());
        if (hit) return lead + hit + tail;
        for (const [regex, build] of patterns) {
            const m = core.match(regex);
            if (m) return lead + build(m) + tail;
        }
        return text;
    };

    const skipped = (el) => !el || SKIP.has(el.tagName) || el.closest('[data-no-translate]');

    const node = (n) => {
        if (n.nodeType === Node.TEXT_NODE) {
            if (skipped(n.parentElement)) return;
            const next = translate(n.nodeValue);
            if (next !== n.nodeValue) n.nodeValue = next;
            return;
        }
        if (n.nodeType !== Node.ELEMENT_NODE || skipped(n)) return;
        walk(n);
    };

    const attrs = (el) => {
        ATTRS.forEach((name) => {
            const value = el.getAttribute && el.getAttribute(name);
            if (value) { const next = translate(value); if (next !== value) el.setAttribute(name, next); }
        });
        if (el.tagName === 'INPUT' && ['submit', 'button'].includes(el.type) && el.value) {
            const next = translate(el.value);
            if (next !== el.value) el.value = next;
        }
    };

    function walk(root) {
        attrs(root);
        root.querySelectorAll('*').forEach((el) => { if (!skipped(el)) attrs(el); });
        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
        const texts = [];
        while (walker.nextNode()) texts.push(walker.currentNode);
        texts.forEach(node);
    }

    return { walk, node, attrs, translate };
}

function addToggle(current) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'ux-lang';
    button.setAttribute('data-no-translate', '');
    button.setAttribute('lang', current === 'fil' ? 'en' : 'fil');
    button.setAttribute('aria-label', current === 'fil' ? 'Language: switch to English' : 'Wika: lumipat sa Filipino');
    button.innerHTML = '<i class="fas fa-language" aria-hidden="true"></i><span class="ux-lang-label">' + (current === 'fil' ? 'English' : 'Filipino') + '</span>';
    button.addEventListener('click', () => {
        write(current === 'fil' ? 'en' : 'fil');
        window.location.reload();
    });

    const footer = document.querySelector('.sidebar-footer');
    if (footer) {
        button.classList.add('ux-lang-in-sidebar');
        footer.insertBefore(button, footer.firstElementChild && footer.querySelector('.user-info') ? footer.querySelector('.user-info').nextSibling : footer.firstChild);
    } else {
        document.body.appendChild(button);
    }
}

async function start() {
    const current = read();
    addToggle(current);
    document.documentElement.lang = current === 'fil' ? 'fil' : 'en';
    if (current !== 'fil') return;

    const t = buildTranslator(await import('./i18n-fil.js'));
    t.walk(document.body);

    new MutationObserver((mutations) => {
        mutations.forEach((m) => {
            if (m.type === 'characterData') t.node(m.target);
            m.addedNodes.forEach((n) => t.node(n));
        });
    }).observe(document.body, { childList: true, characterData: true, subtree: true });

    // Text that the page script writes after load (for example "Choose a date first").
    window.uxTranslate = (text) => t.translate(text);
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
