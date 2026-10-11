@extends('layouts.auth')

@section('title', 'Neues Passwort vergeben')

@section('brand')
    <div class="lg-info">
        <div class="lg-info-icon"><i class="fas fa-key"></i></div>
        <div>
            <strong>Neues Passwort</strong>
            <span>Vergeben Sie ein sicheres, neues Passwort.</span>
        </div>
    </div>
@endsection

@section('content')
    <h2 class="lg-heading">Neues Passwort vergeben</h2>
    <p class="lg-sub">Wählen Sie ein sicheres, neues Passwort für Ihr Konto.</p>

    {{-- Formular --}}
    <form method="POST" action="{{ route('password.update') }}" class="lg-form"
          x-data="{ showPw: false, showPwConfirm: false }">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        {{-- E-Mail --}}
        <div>
            <label for="email" class="lg-label">E-Mail-Adresse</label>
            <div class="lg-field">
                <span class="lg-field-icon"><i class="fas fa-envelope"></i></span>
                <input id="email" type="email" name="email"
                       value="{{ $email ?? old('email') }}"
                       required autocomplete="email" inputmode="email"
                       @if(empty($email ?? old('email'))) autofocus @endif
                       placeholder="ihre@email.de"
                       class="lg-input @error('email') is-invalid @enderror"
                       @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            </div>
            @error('email')
                <p id="email-error" class="lg-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
            @enderror
        </div>

        {{-- Neues Passwort --}}
        <div>
            <label for="password" class="lg-label">Neues Passwort</label>
            <div class="lg-field">
                <span class="lg-field-icon"><i class="fas fa-lock"></i></span>
                <input id="password" type="password" :type="showPw ? 'text' : 'password'"
                       name="password"
                       required autocomplete="new-password"
                       @if(!empty($email ?? old('email'))) autofocus @endif
                       placeholder="••••••••"
                       class="lg-input lg-input--pw @error('password') is-invalid @enderror"
                       aria-describedby="@error('password') password-error @else password-hint @enderror">
                <button type="button" class="lg-eye" @click="showPw = !showPw"
                        :aria-label="showPw ? 'Passwort verbergen' : 'Passwort anzeigen'">
                    <i class="fas" :class="showPw ? 'fa-eye-slash' : 'fa-eye'"></i>
                </button>
            </div>
            @error('password')
                <p id="password-error" class="lg-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
            @else
                <p id="password-hint" class="lg-field-hint">Mindestens 8 Zeichen.</p>
            @enderror
        </div>

        {{-- Passwort bestätigen --}}
        <div>
            <label for="password-confirm" class="lg-label">Passwort bestätigen</label>
            <div class="lg-field">
                <span class="lg-field-icon"><i class="fas fa-lock"></i></span>
                <input id="password-confirm" type="password" :type="showPwConfirm ? 'text' : 'password'"
                       name="password_confirmation"
                       required autocomplete="new-password"
                       placeholder="••••••••"
                       class="lg-input lg-input--pw">
                <button type="button" class="lg-eye" @click="showPwConfirm = !showPwConfirm"
                        :aria-label="showPwConfirm ? 'Passwort verbergen' : 'Passwort anzeigen'">
                    <i class="fas" :class="showPwConfirm ? 'fa-eye-slash' : 'fa-eye'"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="lg-btn lg-btn--primary">
            <i class="fas fa-check"></i>
            Passwort speichern
        </button>
    </form>

    <div class="lg-back">
        <a href="{{ route('login') }}" class="lg-link">
            <i class="fas fa-arrow-left"></i>
            Zurück zur Anmeldung
        </a>
    </div>
@endsection
