@extends('layouts.auth')

@section('title', 'Anmelden – Schülerinnen und Schüler')

@section('content')
    <h2 class="lg-heading">Hallo!</h2>
    <p class="lg-sub">Melde dich mit deinem Benutzernamen an.</p>

    @if(session('Meldung'))
        <div role="alert" class="lg-alert lg-alert--{{ session('type') === 'success' ? 'success' : 'warning' }}">
            <i class="fas fa-info-circle lg-alert-icon"></i><span style="flex:1">{{ session('Meldung') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('student.login.store') }}" class="lg-form">
        @csrf
        <div>
            <label for="username" class="lg-label">Benutzername</label>
            <div class="lg-field">
                <span class="lg-field-icon"><i class="fas fa-user"></i></span>
                <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus
                       autocomplete="username" autocapitalize="none" spellcheck="false" placeholder="vorname.nachname"
                       class="lg-input @error('username') is-invalid @enderror">
            </div>
            @error('username')<p class="lg-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>@enderror
        </div>
        <div x-data="{ show: false }">
            <label for="password" class="lg-label">Passwort</label>
            <div class="lg-field">
                <span class="lg-field-icon"><i class="fas fa-lock"></i></span>
                <input id="password" name="password" :type="show ? 'text' : 'password'" type="password" required autocomplete="current-password" class="lg-input lg-input--pw">
                <button type="button" class="lg-eye" @click="show = !show" :aria-label="show ? 'Passwort verbergen' : 'Passwort anzeigen'"><i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i></button>
            </div>
        </div>
        <button type="submit" class="lg-btn lg-btn--primary">Anmelden <i class="fas fa-arrow-right"></i></button>
    </form>

    <p class="lg-hint" style="margin-top:1rem">
        Erstes Mal hier oder Passwort vergessen? <a href="{{ route('student.code') }}" class="lg-link">Code einlösen</a>.
        Den Code bekommst du im Sekretariat oder von deinen Eltern.
    </p>
    <p class="lg-hint" style="text-align:center"><a href="{{ route('login') }}" class="lg-link">Anmeldung für Eltern und Mitarbeitende</a></p>
@endsection
