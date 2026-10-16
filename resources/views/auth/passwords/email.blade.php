@extends('layouts.auth')

@section('title', 'Passwort zurücksetzen')

@section('brand')
    <div class="lg-info">
        <div class="lg-info-icon"><i class="fas fa-lock-open"></i></div>
        <div>
            <strong>Passwort vergessen?</strong>
            <span>Wir senden Ihnen einen Link zum Zurücksetzen per E-Mail.</span>
        </div>
    </div>
@endsection

@section('content')
    <h2 class="lg-heading">Passwort vergessen?</h2>
    <p class="lg-sub">Geben Sie Ihre E-Mail-Adresse ein. Wir senden Ihnen einen Link, mit dem Sie ein neues Passwort vergeben können.</p>

    {{-- Erfolgs-Meldung --}}
    @if (session('status'))
        <div x-data="{ show: true }" x-show="show" x-transition role="alert" class="lg-alert lg-alert--success">
            <i class="fas fa-check-circle lg-alert-icon"></i>
            <span style="flex:1">{{ session('status') }}</span>
            <button type="button" @click="show = false" aria-label="Meldung schließen">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    {{-- Formular --}}
    <form method="POST" action="{{ route('password.email') }}" class="lg-form">
        @csrf

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

        <button type="submit" class="lg-btn lg-btn--primary">
            <i class="fas fa-paper-plane"></i>
            Link anfordern
        </button>
    </form>

    <div class="lg-back">
        <a href="{{ route('login') }}" class="lg-link">
            <i class="fas fa-arrow-left"></i>
            Zurück zur Anmeldung
        </a>
    </div>
@endsection
