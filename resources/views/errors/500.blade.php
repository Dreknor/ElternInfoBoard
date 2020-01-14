@extends('layouts.app')

@section('content')

    <div class="content">
        <div class="title">Es ist ein Fehler aufgetreten</div>

        @if(app()->bound('sentry') && app('sentry')->getLastEventId())
            <div class="subtitle">Das Team wurde über den Fehler benachrichtigt. <br>Error ID: {{ app('sentry')->getLastEventId() }}</div>
            <script src="https://browser.sentry-cdn.com/5.11.0/bundle.min.js" integrity="sha384-jbFinqIbKkHNg+QL+yxB4VrBC0EAPTuaLGeRT0T+NfEV89YC6u1bKxHLwoo+/xxY" crossorigin="anonymous"></script>
            <script>
                Sentry.init({ dsn: 'https://908e7b8dd4294fc98a0d47b13e8da008@sentry.io/187901' });
                Sentry.showReportDialog({ eventId: '{{ app('sentry')->getLastEventId() }}' });
            </script>
        @endif
    </div>
@endsection