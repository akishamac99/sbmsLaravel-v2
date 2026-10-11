<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    @vite(['resources/css/auth.css'])
</head>
<body>
<div class="shell">
    <div class="side-left">
        <div class="brand">
            <div class="brand-name">Janet's<em>Beauty Salon</em></div>
            <div class="brand-line"></div>
            <div class="brand-loc">Baguio City, Philippines</div>
        </div>
        <div class="hero-copy">@yield('hero')</div>
    </div>
    <div class="side-right">
        <a href="{{ route('home') }}" class="back-lnk"><i class="fas fa-arrow-left"></i> Back to home</a>
        <div class="form-hd">
            <h3>@yield('heading')</h3>
            <p>@yield('subheading')</p>
        </div>

        @if (session('status'))
            <div class="alert alert-ok"><i class="fas fa-check-circle"></i> {{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-err"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
        @endif

        @yield('content')
    </div>
</div>
</body>
</html>
