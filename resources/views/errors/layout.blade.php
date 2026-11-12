<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Janet's Angel Beauty Salon</title>
    {{-- Inline styles only: this page must still work when the build files are missing. --}}
    <style>
        :root { color-scheme: light dark; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px 16px; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: #fbf5f2; color: #1e1014; }
        .box { max-width: 520px; width: 100%; background: #fff; border: 1px solid #ead9de; border-radius: 18px; padding: 36px 28px; text-align: center; box-shadow: 0 14px 34px rgba(139, 58, 82, .12); }
        .brand { font-size: .85rem; letter-spacing: .08em; text-transform: uppercase; color: #6b4a54; margin-bottom: 18px; }
        .icon { font-size: 2.6rem; margin-bottom: 6px; }
        h1 { font-size: 1.6rem; margin: 0 0 10px; }
        p { line-height: 1.6; margin: 0 0 8px; color: #4a3238; }
        .hint { font-size: .9rem; color: #6b4a54; margin-top: 14px; }
        .actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin-top: 24px; }
        a.btn, button.btn { display: inline-flex; align-items: center; min-height: 46px; padding: 0 22px; border-radius: 10px; font: inherit; font-weight: 600; text-decoration: none; cursor: pointer; border: 2px solid #8b3a52; }
        .primary { background: #8b3a52; color: #fff; }
        .secondary { background: #fff; color: #8b3a52; }
        a.btn:focus-visible, button.btn:focus-visible { outline: 3px solid #c5a15b; outline-offset: 2px; }
        .code { font-size: .8rem; color: #7a5560; margin-top: 18px; }
        @media (prefers-color-scheme: dark) {
            body { background: #1e1014; color: #f7eef1; }
            .box { background: #2a1920; border-color: #4a2f39; }
            p { color: #e6d3d9; } .hint, .brand, .code { color: #d4b9c1; }
            .secondary { background: transparent; color: #f2c9d6; border-color: #f2c9d6; }
        }
    </style>
</head>
<body>
<main class="box" role="main">
    <div class="brand">Janet's Angel Beauty Salon</div>
    <div class="icon" aria-hidden="true">@yield('icon', '⚠')</div>
    <h1>@yield('heading')</h1>
    <p>@yield('message')</p>
    <p class="hint">@yield('hint')</p>
    <div class="actions">
        @hasSection('retry')
            <button type="button" class="btn primary" onclick="@if (request()->isMethod('GET')) location.reload() @else history.back() @endif">{{ request()->isMethod('GET') ? 'Try again' : 'Go back and try again' }}</button>
            <a class="btn secondary" href="{{ url('/') }}">Go to home page</a>
        @else
            <a class="btn primary" href="{{ url('/') }}">Go to home page</a>
            <button type="button" class="btn secondary" onclick="if (history.length > 1) { history.back(); } else { location.href = '{{ url('/') }}'; }">Go back</button>
        @endif
    </div>
    <p class="code">Error {{ $code ?? '' }}</p>
</main>
</body>
</html>
