@extends('layouts.error')

@section('title', 'Serverfehler')

@section('error-code')
    <div class="error-code">500</div>
@endsection

@section('content')
    <div class="error-icon">⚠️</div>
    <div class="error-title">Interner Serverfehler</div>
    <div class="error-message">
        Es ist ein unerwarteter Fehler aufgetreten.<br>
        Bitte versuchen Sie es in Kürze erneut.
    </div>

    @php
        // Nutzerfeedback-Dialog nur, wenn Sentry konfiguriert ist (DSN gesetzt) und ein Ereignis erfasst wurde.
        $sentryDsn = config('sentry.dsn');
        $sentryEventId = null;
        if (! empty($sentryDsn) && app()->bound('sentry')) {
            try {
                $sentryEventId = app('sentry')->getLastEventId();
            } catch (\Throwable $e) {
                $sentryEventId = null;
            }
        }
    @endphp
    @if(! empty($sentryDsn) && $sentryEventId)
        <div class="sentry-box">
            Das Team wurde über den Fehler benachrichtigt.<br>
            <strong>Fehler-ID:</strong> {{ $sentryEventId }}
        </div>
        <script src="https://browser.sentry-cdn.com/5.11.0/bundle.min.js"
                integrity="sha384-jbFinqIbKkHNg+QL+yxB4VrBC0EAPTuaLGeRT0T+NfEV89YC6u1bKxHLwoo+/xxY"
                crossorigin="anonymous"></script>
        <script>
            Sentry.init({ dsn: @json($sentryDsn) });
            Sentry.showReportDialog({ eventId: @json((string) $sentryEventId) });
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
