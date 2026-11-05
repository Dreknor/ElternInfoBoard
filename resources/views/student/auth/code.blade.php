@extends('layouts.auth')

@section('title', 'Code einlösen')

@section('content')
    <h2 class="lg-heading">Code einlösen</h2>
    <p class="lg-sub">Mit deinem Code legst du ein neues Passwort fest.</p>

    <form method="POST" action="{{ route('student.code.store') }}" class="lg-form">
        @csrf
        <div>
            <label for="username" class="lg-label">Benutzername</label>
            <div class="lg-field">
                <span class="lg-field-icon"><i class="fas fa-user"></i></span>
                <input id="username" name="username" type="text" value="{{ old('username', $username) }}" required autocomplete="username" autocapitalize="none" spellcheck="false" class="lg-input @error('username') is-invalid @enderror">
            </div>
            @error('username')<p class="lg-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="code" class="lg-label">Code</label>
            <div class="lg-field">
                <span class="lg-field-icon"><i class="fas fa-key"></i></span>
                <input id="code" name="code" type="text" value="{{ old('code', $code) }}" required autocomplete="one-time-code" autocapitalize="characters" spellcheck="false" placeholder="ABCD-EFGH" class="lg-input @error('code') is-invalid @enderror">
            </div>
            @error('code')<p class="lg-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password" class="lg-label">Neues Passwort (mindestens 8 Zeichen)</label>
            <div class="lg-field">
                <span class="lg-field-icon"><i class="fas fa-lock"></i></span>
                <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password" class="lg-input @error('password') is-invalid @enderror">
            </div>
            @error('password')<p class="lg-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password_confirmation" class="lg-label">Passwort wiederholen</label>
            <div class="lg-field">
                <span class="lg-field-icon"><i class="fas fa-lock"></i></span>
                <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password" class="lg-input">
            </div>
        </div>
        <button type="submit" class="lg-btn lg-btn--primary">Passwort speichern <i class="fas fa-arrow-right"></i></button>
    </form>
    <p class="lg-hint" style="text-align:center;margin-top:1rem"><a href="{{ route('student.login') }}" class="lg-link">Zurück zur Anmeldung</a></p>
@endsection
