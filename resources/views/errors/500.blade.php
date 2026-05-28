@extends('layouts.error')

@section('title', 'Serverfehler')

@section('error-code')
    <div class="error-code">500</div>
@endsection

@section('content')
    <div class="error-icon">⚠️</div>
    <div class="error-title">Interner Serverfehler</div>
    <div class="error-message">
        Es ist ein unerwarteter Fehler aufgetreten. Das Team wurde bereits informiert.<br>
        Bitte versuchen Sie es in Kürze erneut.
    </div>

    @if(app()->bound('sentry') && app('sentry')->getLastEventId())
        <div class="sentry-box">
            Das Team wurde über den Fehler benachrichtigt.<br>
            <strong>Fehler-ID:</strong> {{ app('sentry')->getLastEventId() }}
        </div>
        <script src="https://browser.sentry-cdn.com/5.11.0/bundle.min.js"
                integrity="sha384-jbFinqIbKkHNg+QL+yxB4VrBC0EAPTuaLGeRT0T+NfEV89YC6u1bKxHLwoo+/xxY"
                crossorigin="anonymous"></script>
        <script>
            Sentry.init({ dsn: 'https://908e7b8dd4294fc98a0d47b13e8da008@sentry.io/187901' });
            Sentry.showReportDialog({ eventId: '{{ app('sentry')->getLastEventId() }}' });
        </script>
    @endif

    <div style="margin-top: 2rem;">
        <a href="{{ url('/') }}" class="btn-home">
            <i class="fas fa-home"></i> Zur Startseite
        </a>
        <a href="javascript:history.back()" class="btn-back">
            <i class="fas fa-arrow-left"></i> Zurück
        </a>
    </div>
@endsection
