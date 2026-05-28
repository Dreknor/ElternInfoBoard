<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Fehler') – {{ config('app.name', 'ElternInfo') }}</title>
    <link href="{{ asset('/css/all.css') }}?v=1" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .error-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            padding: 3rem 2.5rem;
            max-width: 520px;
            width: 100%;
            text-align: center;
            animation: fadeInUp 0.5s ease forwards;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .error-code {
            font-size: 6rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        .error-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .error-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 0.75rem;
        }
        .error-message {
            color: #718096;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        .btn-home {
            display: inline-block;
            padding: 0.75rem 2rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: opacity 0.2s, transform 0.2s;
        }
        .btn-home:hover { opacity: 0.9; transform: translateY(-1px); color: #fff; }
        .btn-back {
            display: inline-block;
            padding: 0.75rem 2rem;
            background: #edf2f7;
            color: #4a5568;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            margin-left: 0.75rem;
            transition: background 0.2s;
        }
        .btn-back:hover { background: #e2e8f0; color: #2d3748; }
        .sentry-box {
            margin-top: 1.5rem;
            padding: 1rem;
            background: #fff5f5;
            border: 1px solid #fed7d7;
            border-radius: 8px;
            color: #c53030;
            font-size: 0.85rem;
        }
        .app-logo {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            margin: 0 auto 1rem;
            display: block;
            object-fit: contain;
        }
    </style>
</head>
<body>
<div class="error-card">
    <img src="{{ asset('img/app_logo.png') }}" alt="Logo" class="app-logo"
         onerror="this.style.display='none'">

    @yield('error-code')

    @yield('content')
</div>
</body>
</html>

