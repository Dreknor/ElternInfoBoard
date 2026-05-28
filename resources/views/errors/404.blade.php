@extends('layouts.error')

@section('title', 'Seite nicht gefunden')

@section('error-code')
    <div class="error-code">404</div>
@endsection

@section('content')
    <div class="error-icon">🔍</div>
    <div class="error-title">Seite nicht gefunden</div>
    <div class="error-message">
        Die gesuchte Seite existiert nicht oder wurde verschoben.<br>
        Bitte überprüfen Sie die Adresse oder kehren Sie zur Startseite zurück.
    </div>
    <div style="margin-top: 2rem;">
        <a href="{{ url('/') }}" class="btn-home">
            <i class="fas fa-home"></i> Zur Startseite
        </a>
        <a href="javascript:history.back()" class="btn-back">
            <i class="fas fa-arrow-left"></i> Zurück
        </a>
    </div>
@endsection

