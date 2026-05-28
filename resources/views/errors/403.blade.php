@extends('layouts.error')

@section('title', 'Zugriff verweigert')

@section('error-code')
    <div class="error-code">403</div>
@endsection

@section('content')
    <div class="error-icon">🚫</div>
    <div class="error-title">Zugriff verweigert</div>
    <div class="error-message">
        Sie haben keine Berechtigung, diese Seite aufzurufen.<br>
        Bitte wenden Sie sich an einen Administrator, falls Sie der Meinung sind, dass dies ein Fehler ist.
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
