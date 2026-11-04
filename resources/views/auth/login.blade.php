@extends('layouts.auth')

@section('title', 'Anmelden')

@section('content')
    <h2 class="lg-heading">Willkommen zurück</h2>
    <p class="lg-sub">Bitte melden Sie sich mit Ihren Zugangsdaten an.</p>

    {{-- Flash-Meldung --}}
    @if(session('Meldung'))
        @php
            $alertType = match (session('type')) {
                'success'         => ['success', 'fa-check-circle'],
                'danger', 'error' => ['danger', 'fa-times-circle'],
                'warning'         => ['warning', 'fa-exclamation-triangle'],
                default           => ['info', 'fa-info-circle'],
            };
        @endphp
        <div x-data="{ show: true }" x-show="show" x-transition role="alert"
             class="lg-alert lg-alert--{{ $alertType[0] }}">
            <i class="fas {{ $alertType[1] }} lg-alert-icon"></i>
            <span style="flex:1">{{ session('Meldung') }}</span>
            <button type="button" @click="show = false" aria-label="Meldung schließen">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    {{-- Formular --}}
    <form method="POST" action="{{ route('login') }}" class="lg-form" x-data="{ passwordless: false }">
        @csrf

        {{-- E-Mail --}}
        <div>
            <label for="email" class="lg-label">E-Mail-Adresse</label>
            <div class="lg-field">
                <span class="lg-field-icon"><i class="fas fa-envelope"></i></span>
                <input id="email" type="email" name="email"
                       value="{{ old('email') }}"
                       required autocomplete="email" autofocus inputmode="email"
                       placeholder="ihre@email.de"
                       class="lg-input @error('email') is-invalid @enderror"
                       @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            </div>
            @error('email')
                <p id="email-error" class="lg-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
            @enderror
        </div>

        {{-- Passwort --}}
        <div x-data="{ show: false }">
            <label for="password" class="lg-label">Kennwort</label>
            <div class="lg-field">
                <span class="lg-field-icon"><i class="fas fa-lock"></i></span>
                <input id="password" type="password" :type="show ? 'text' : 'password'"
                       name="password"
                       :required="!passwordless" autocomplete="current-password"
                       placeholder="••••••••"
                       class="lg-input lg-input--pw @error('password') is-invalid @enderror"
                       @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                <button type="button" class="lg-eye" @click="show = !show"
                        :aria-label="show ? 'Kennwort verbergen' : 'Kennwort anzeigen'">
                    <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                </button>
            </div>
            @error('password')
                <p id="password-error" class="lg-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
            @enderror
        </div>

        {{-- Optionen --}}
        <div class="lg-row">
            <label class="lg-check">
                <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                Angemeldet bleiben
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="lg-link">Passwort vergessen?</a>
            @endif
        </div>

        {{-- Anmelden --}}
        <button type="submit" class="lg-btn lg-btn--primary">
            Anmelden
            <i class="fas fa-arrow-right"></i>
        </button>

        {{-- Passwortloser Login --}}
        <button type="submit" name="submit" value="password-less"
                @click="passwordless = true"
                class="lg-btn lg-btn--ghost">
            <i class="fas fa-magic"></i>
            Login-Link per E-Mail
        </button>
        <p class="lg-hint">Ohne Kennwort – Sie erhalten einen Anmeldelink an Ihre E-Mail-Adresse.</p>
    </form>

    {{-- Keycloak / SSO (Legacy, nur ohne UCS-Integration) --}}
    @if($keycloak == true && empty($ucsEnabled))
        <div class="lg-divider"><span>oder weiter mit</span></div>
        <a href="{{ route('login.keycloak') }}" class="lg-btn lg-btn--neutral">
            <i class="fas fa-key"></i>
            {{ $keycloakButtonText ?? 'Login mit SSO' }}
        </a>
    @endif

    {{-- UCS@school OIDC-Login (Paket 06) --}}
    @if(!empty($ucsEnabled))
        <div class="lg-divider"><span>oder weiter mit</span></div>
        <a href="{{ route('auth.ucs.redirect') }}" class="lg-btn lg-btn--neutral">
            <i class="fas fa-graduation-cap"></i>
            {{ $keycloakButtonText ?: 'Anmelden mit Schul-Login' }}
        </a>
    @endif
@endsection
