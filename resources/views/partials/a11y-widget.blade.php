{{-- Accessibility panel for every page (portal, login and landing).
     Visual: text size, high contrast, readable font, spacing, underlined links, large cursor, read aloud.
     Motor: bigger click targets, keyboard shortcut (Alt+A), skip link, strong focus ring.
     Cognitive / vestibular: pause animations, keep messages on screen.
     Settings are saved in this browser only. --}}
@verbatim
<style id="a11y-style">
    .sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
    .skip-link{position:absolute;left:8px;top:-80px;z-index:100000;background:#1a1a1a;color:#fff;padding:12px 18px;border-radius:8px;font:600 16px Arial,sans-serif;text-decoration:underline}
    .skip-link:focus{top:8px}
    :focus-visible{outline:3px solid #8B3A52 !important;outline-offset:2px !important;box-shadow:0 0 0 5px #fff !important}

    #a11y-fab:hover{filter:brightness(1.15)}
    #a11y-fab{position:fixed;left:16px;bottom:16px;z-index:99998;width:52px;height:52px;border-radius:50%;border:3px solid #EDD9A3;background:linear-gradient(135deg,#8B3A52,#6B2A3E);color:#EDD9A3;font-size:24px;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 10px rgba(0,0,0,.35)}
    /* On portal pages the button lives inside the sidebar footer, so it never covers the menu. */
    #a11y-fab.a11y-in-sidebar{position:static;width:100%;height:auto;border-radius:8px;margin:0 0 8px;padding:9px 14px;border:1px solid rgba(255,255,255,.25);background:rgba(255,255,255,.10);color:#fff;font:600 .85rem 'DM Sans','Inter',Arial,sans-serif;gap:8px;justify-content:flex-start;box-shadow:none}
    #a11y-fab.a11y-in-sidebar i{color:#EDD9A3;font-size:1rem}
    #a11y-fab.a11y-in-sidebar:hover{background:rgba(255,255,255,.2);filter:none}
    #a11y-panel.a11y-side{left:calc(var(--sidebar-w,250px) + 12px);bottom:16px}
    @media (max-width:900px){#a11y-fab.a11y-in-sidebar{justify-content:center;padding:9px 0}#a11y-fab.a11y-in-sidebar .a11y-label{display:none}#a11y-panel.a11y-side{left:84px}}
    #a11y-panel{position:fixed;left:16px;bottom:80px;z-index:99999;width:min(92vw,350px);max-height:min(78vh,78dvh);overflow-y:auto;background:#fff8f4;color:#1a1a1a;border:2px solid #8B3A52;border-radius:14px;padding:16px;font:15px/1.45 Arial,sans-serif;box-shadow:0 8px 30px rgba(0,0,0,.3)}
    #a11y-panel[hidden]{display:none}
    #a11y-panel h2{margin:0 0 4px;font:700 18px Arial,sans-serif;color:#1a1a1a}
    #a11y-panel p.a11y-hint{margin:0 0 12px;color:#444;font-size:13px}
    #a11y-panel fieldset{border:0;margin:0 0 12px;padding:0}
    #a11y-panel legend{font-weight:700;margin-bottom:6px;padding:0}
    #a11y-panel .a11y-sizes{display:flex;gap:6px}
    #a11y-panel .a11y-sizes button{flex:1;min-height:44px;border:2px solid #8B3A52;background:#fff;color:#8B3A52;font-weight:700;border-radius:8px;cursor:pointer}
    #a11y-panel .a11y-sizes button[aria-pressed="true"]{background:#8B3A52;color:#fff}
    #a11y-panel label.a11y-opt{display:flex;align-items:center;gap:10px;min-height:44px;padding:4px 0;cursor:pointer}
    #a11y-panel label.a11y-opt input{width:22px;height:22px;flex:none}
    #a11y-panel .a11y-row{display:flex;gap:8px;flex-wrap:wrap}
    #a11y-panel .a11y-row button{min-height:44px;padding:8px 12px;border-radius:8px;border:2px solid #8B3A52;background:#fff;color:#8B3A52;font-weight:700;cursor:pointer}
    #a11y-panel .a11y-row button.a11y-close{background:#8B3A52;color:#fff}

    html.a11y-t125 body{zoom:1.25}
    html.a11y-t150 body{zoom:1.5}
    /* Zoom also multiplies vh/vw, so the panel's limits are divided by the zoom to keep it inside the screen and scrollable. */
    html.a11y-t125 #a11y-panel{max-height:calc(78vh / 1.25);width:min(calc(92vw / 1.25),350px)}
    html.a11y-t150 #a11y-panel{max-height:calc(78vh / 1.5);width:min(calc(92vw / 1.5),350px)}
    #a11y-panel{overscroll-behavior:contain;-webkit-overflow-scrolling:touch}
    html.a11y-font body, html.a11y-font body *:not(.fas):not(.far):not(.fab):not(.fa):not([class^="fa-"]){font-family:"Atkinson Hyperlegible",Verdana,Arial,sans-serif !important}
    html.a11y-spacing body *{line-height:1.8 !important;letter-spacing:.05em !important;word-spacing:.12em !important}
    html.a11y-links a{text-decoration:underline !important;text-underline-offset:3px}
    html.a11y-targets a.nav-item,html.a11y-targets .btn,html.a11y-targets button,html.a11y-targets select,html.a11y-targets summary,
    html.a11y-targets .form-control,html.a11y-targets input:not([type=checkbox]):not([type=radio]),html.a11y-targets .btn-menu,html.a11y-targets .icon-btn{min-height:48px;min-width:48px}
    html.a11y-targets input[type=checkbox],html.a11y-targets input[type=radio]{width:24px;height:24px}
    html.a11y-targets .menu-dropdown button,html.a11y-targets .menu-dropdown a{padding-top:14px;padding-bottom:14px}
    html.a11y-motion *,html.a11y-motion *::before,html.a11y-motion *::after{animation:none !important;transition:none !important;scroll-behavior:auto !important}
    @media (prefers-reduced-motion: reduce){*,*::before,*::after{animation:none !important;transition:none !important;scroll-behavior:auto !important}}
    html.a11y-cursor,html.a11y-cursor *{cursor:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='36' height='36'><path d='M4 2 L4 28 L11 21 L16 33 L21 31 L16 19 L26 19 Z' fill='black' stroke='white' stroke-width='2'/></svg>") 4 2, auto !important}

    html.a11y-contrast body *:not(img):not(svg):not(video):not(.fas):not(.far):not(.fab):not(.fa){background-color:#000 !important;background-image:none !important;color:#fff !important;border-color:#fff !important;box-shadow:none !important;text-shadow:none !important}
    html.a11y-contrast body{background:#000 !important;color:#fff !important}
    html.a11y-contrast a,html.a11y-contrast a *{color:#ffe600 !important;text-decoration:underline !important}
    html.a11y-contrast button,html.a11y-contrast .btn,html.a11y-contrast input,html.a11y-contrast select,html.a11y-contrast textarea{border:2px solid #fff !important}
    html.a11y-contrast button:not(.toast-close):not([aria-pressed]),html.a11y-contrast .btn-primary{background:#ffe600 !important;color:#000 !important}
    html.a11y-contrast .fas,html.a11y-contrast .far,html.a11y-contrast .fab,html.a11y-contrast .fa{color:inherit}
    html.a11y-contrast :focus-visible{outline-color:#ffe600 !important;box-shadow:none !important}
    html.a11y-contrast #a11y-panel button[aria-pressed="true"]{background:#fff !important;color:#000 !important}
</style>
<script>
(function () {
    var KEY = 'sbms_a11y', root = document.documentElement;
    var DEFAULTS = { size: 100, contrast: 0, font: 0, spacing: 0, links: 0, targets: 0, motion: 0, cursor: 0, sticky: 0 };
    var prefs = Object.assign({}, DEFAULTS);
    try { prefs = Object.assign(prefs, JSON.parse(localStorage.getItem(KEY) || '{}')); } catch (e) {}

    function apply() {
        root.classList.toggle('a11y-t125', prefs.size === 125);
        root.classList.toggle('a11y-t150', prefs.size === 150);
        ['contrast', 'font', 'spacing', 'links', 'targets', 'motion', 'cursor', 'sticky'].forEach(function (k) {
            root.classList.toggle('a11y-' + k, !!prefs[k]);
        });
        if (prefs.font && !document.getElementById('a11y-font-link')) {
            var l = document.createElement('link');
            l.id = 'a11y-font-link'; l.rel = 'stylesheet';
            l.href = 'https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:wght@400;700&display=swap';
            document.head.appendChild(l);
        }
    }
    function save() { try { localStorage.setItem(KEY, JSON.stringify(prefs)); } catch (e) {} }
    apply(); // runs before the page is drawn, so there is no flash

    document.addEventListener('DOMContentLoaded', function () {
        var body = document.body;

        // 1. Skip link: first thing a keyboard or screen reader user meets.
        var main = document.querySelector('main') || document.querySelector('[role="main"]');
        if (main) {
            if (!main.id) main.id = 'main-content';
            main.setAttribute('tabindex', '-1');
            var skip = document.createElement('a');
            skip.className = 'skip-link'; skip.href = '#' + main.id; skip.textContent = 'Skip to main content';
            body.insertBefore(skip, body.firstChild);
        }

        // 2. Small fixes for assistive technology, applied to every page.
        document.querySelectorAll('i.fas,i.far,i.fab,i.fa').forEach(function (i) {
            if (!i.hasAttribute('aria-hidden')) i.setAttribute('aria-hidden', 'true');
        });
        document.querySelectorAll('.nav-item.active').forEach(function (a) { a.setAttribute('aria-current', 'page'); });
        document.querySelectorAll('.nav-badge').forEach(function (b) {
            var s = document.createElement('span'); s.className = 'sr-only'; s.textContent = ' unread'; b.appendChild(s);
        });
        document.querySelectorAll('.table-scroll').forEach(function (t) {
            if (t.scrollWidth > t.clientWidth) { t.setAttribute('tabindex', '0'); t.setAttribute('role', 'region'); t.setAttribute('aria-label', 'Scrollable table'); }
        });

        // 3. The panel.
        var fab = document.createElement('button');
        fab.id = 'a11y-fab'; fab.type = 'button';
        fab.setAttribute('aria-label', 'Accessibility settings (Alt+A)');
        fab.setAttribute('aria-expanded', 'false'); fab.setAttribute('aria-controls', 'a11y-panel');
        var sidebarFooter = document.querySelector('.sidebar-footer');
        fab.innerHTML = '<i class="fas fa-universal-access" aria-hidden="true"></i>' + (sidebarFooter ? '<span class="a11y-label">Accessibility</span>' : '');

        var panel = document.createElement('div');
        panel.id = 'a11y-panel'; panel.hidden = true;
        panel.setAttribute('role', 'dialog'); panel.setAttribute('aria-labelledby', 'a11y-title');
        panel.innerHTML =
            '<h2 id="a11y-title">Accessibility</h2>' +
            '<p class="a11y-hint">Changes apply at once and are remembered on this device.</p>' +
            '<fieldset><legend>Text size</legend><div class="a11y-sizes">' +
              '<button type="button" data-size="100">Normal</button>' +
              '<button type="button" data-size="125">Large</button>' +
              '<button type="button" data-size="150">Largest</button></div></fieldset>' +
            '<fieldset><legend>Seeing</legend>' +
              opt('contrast', 'High contrast (black and yellow)') +
              opt('font', 'Easy-to-read font') +
              opt('spacing', 'More space between lines and letters') +
              opt('links', 'Underline all links') +
              opt('cursor', 'Large mouse pointer') + '</fieldset>' +
            '<fieldset><legend>Moving and clicking</legend>' +
              opt('targets', 'Bigger buttons and click areas') + '</fieldset>' +
            '<fieldset><legend>Focus and reading</legend>' +
              opt('motion', 'Stop animations') +
              opt('sticky', 'Keep messages on screen until I close them') + '</fieldset>' +
            '<div class="a11y-row">' +
              '<button type="button" id="a11y-read">Read page aloud</button>' +
              '<button type="button" id="a11y-reset">Reset all</button>' +
              '<button type="button" id="a11y-close" class="a11y-close">Close</button></div>';
        function opt(k, label) { return '<label class="a11y-opt"><input type="checkbox" data-key="' + k + '"> <span>' + label + '</span></label>'; }

        if (sidebarFooter) {
            fab.classList.add('a11y-in-sidebar'); panel.classList.add('a11y-side');
            sidebarFooter.insertBefore(fab, sidebarFooter.querySelector('form') || null);
        } else {
            body.appendChild(fab);
        }
        body.appendChild(panel);

        function sync() {
            panel.querySelectorAll('[data-size]').forEach(function (b) { b.setAttribute('aria-pressed', String(prefs.size === Number(b.dataset.size))); });
            panel.querySelectorAll('[data-key]').forEach(function (c) { c.checked = !!prefs[c.dataset.key]; });
        }
        function setOpen(open) {
            panel.hidden = !open; fab.setAttribute('aria-expanded', String(open));
            if (open) { sync(); var first = panel.querySelector('button'); if (first) first.focus(); } else { fab.focus(); }
        }
        fab.addEventListener('click', function () { setOpen(panel.hidden); });
        panel.querySelector('#a11y-close').addEventListener('click', function () { setOpen(false); });
        panel.addEventListener('click', function (e) {
            var b = e.target.closest('[data-size]'); if (!b) return;
            prefs.size = Number(b.dataset.size); apply(); save(); sync();
        });
        panel.addEventListener('change', function (e) {
            var c = e.target.closest('[data-key]'); if (!c) return;
            prefs[c.dataset.key] = c.checked ? 1 : 0; apply(); save();
        });
        panel.querySelector('#a11y-reset').addEventListener('click', function () { prefs = Object.assign({}, DEFAULTS); apply(); save(); sync(); window.speechSynthesis && speechSynthesis.cancel(); });

        var reading = false;
        panel.querySelector('#a11y-read').addEventListener('click', function () {
            if (!('speechSynthesis' in window)) { alert('Your browser cannot read pages aloud.'); return; }
            if (reading) { speechSynthesis.cancel(); reading = false; this.textContent = 'Read page aloud'; return; }
            var src = document.querySelector('main') || body;
            var u = new SpeechSynthesisUtterance((src.innerText || '').replace(/\s+/g, ' ').slice(0, 8000));
            var btn = this; u.onend = u.onerror = function () { reading = false; btn.textContent = 'Read page aloud'; };
            reading = true; this.textContent = 'Stop reading'; speechSynthesis.speak(u);
        });

        document.addEventListener('keydown', function (e) {
            if (e.altKey && (e.key === 'a' || e.key === 'A')) { e.preventDefault(); setOpen(panel.hidden); }
            else if (e.key === 'Escape' && !panel.hidden) { setOpen(false); }
        });
    });
})();
</script>
@endverbatim
