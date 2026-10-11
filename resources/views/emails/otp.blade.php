<div style="font-family:Arial,sans-serif;max-width:480px;margin:auto;padding:24px;color:#1e1014">
    <h2 style="color:#8B3A52;margin:0 0 12px">{{ config('app.name') }}</h2>
    <p>Your verification code is:</p>
    <p style="font-size:32px;letter-spacing:6px;font-weight:700;color:#8B3A52;margin:12px 0">{{ $code }}</p>
    <p>This code is valid for {{ $minutes }} minutes. If you didn't request it, you can ignore this email.</p>
</div>
