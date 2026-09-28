<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ config('app.name') }} – In der App anmelden</title>
    <style>
        body { font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; background: #f3f4f6; color: #111827;
               margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 16px; }
        .card { background: #fff; border-radius: 12px; padding: 32px 24px; max-width: 420px; width: 100%;
                box-shadow: 0 2px 8px rgba(0,0,0,.06); text-align: center; }
        h1 { font-size: 22px; margin: 0 0 8px; }
        p { color: #6b7280; line-height: 1.5; }
        a.button { display: block; margin-top: 24px; padding: 14px; border-radius: 8px; background: var(--color-primary, #2563eb);
                   color: #fff; text-decoration: none; font-weight: 600; }
    </style>
    <x-theme-vars />
</head>
<body>
<div class="card">
    <h1>In der App anmelden</h1>
    <p>Tippen Sie auf den Knopf, um die ElternInfo-App zu öffnen. Der Link ist nur kurze Zeit gültig und funktioniert einmal.</p>
    <a class="button" href="{{ $appUrl }}">ElternInfo-App öffnen</a>
    <p style="font-size: 13px; margin-top: 24px">Die App ist nicht installiert? Dann melden Sie sich bitte im Browser an.</p>
</div>
<script>window.location.href = @json($appUrl);</script>
</body>
</html>
