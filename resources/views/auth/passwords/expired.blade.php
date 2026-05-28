@extends('layouts.app')
@section('title') - Passwort ändern @endsection

@push('css')
<style>
    .pw-input:focus {
        border-color: var(--color-primary, #2563eb) !important;
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary, #2563eb) 15%, transparent) !important;
    }
    .pw-toggle:hover { color: var(--color-primary, #2563eb); }
</style>
@endpush

@section('content')
<div class="container-fluid px-4 py-8">
    <div class="max-w-lg mx-auto">

        <!-- Header-Karte -->
        <div class="bg-gradient-to-r from-[var(--color-primary,#2563eb)] to-[var(--color-secondary,#6366f1)] rounded-2xl shadow-lg px-6 py-5 mb-6 flex items-center gap-4">
            <div class="w-12 h-12 flex-shrink-0 bg-white/20 rounded-xl flex items-center justify-center">
                <i class="fas fa-key text-white text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-white">Passwort ändern</h1>
                <p class="text-white/75 text-sm mt-0.5">Bitte vergeben Sie ein neues, sicheres Passwort.</p>
            </div>
        </div>

        <!-- Haupt-Karte -->
        <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
            <div class="p-6">

                @if (session('status'))
                <!-- Erfolgs-Meldung -->
                <div x-data="{ show: true }" x-show="show" x-cloak x-transition
                     class="mb-6 flex items-start gap-3 p-4 rounded-xl text-sm bg-green-50 border border-green-200 text-green-800">
                    <i class="fas fa-check-circle flex-shrink-0 mt-0.5 text-green-500"></i>
                    <span class="flex-1">{{ session('status') }}</span>
                    <button @click="show = false" class="opacity-50 hover:opacity-100 transition-opacity leading-none">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
                <a href="{{ url('/') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-all duration-200 active:scale-[0.98]"
                   style="background: linear-gradient(to right, var(--color-primary, #2563eb), var(--color-secondary, #6366f1))">
                    <i class="fas fa-home"></i>
                    Zur Startseite
                </a>

                @else
                <!-- Hinweis-Banner -->
                <div class="mb-6 flex items-center gap-3 p-4 rounded-xl text-sm bg-amber-50 border border-amber-200 text-amber-800">
                    <i class="fas fa-exclamation-triangle flex-shrink-0 text-amber-500"></i>
                    <span>Ihr Passwort muss geändert werden, bevor Sie fortfahren können.</span>
                </div>

                <!-- Formular -->
                <form class="space-y-5" method="POST" action="{{ route('password.post_expired') }}"
                      x-data="{ showCurrent: false, showNew: false, showConfirm: false }">
                    @csrf

                    <!-- Aktuelles Passwort -->
                    <div>
                        <label for="current_password" class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Aktuelles Passwort
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                <i class="fas fa-lock text-gray-400 text-sm"></i>
                            </span>
                            <input id="current_password"
                                   :type="showCurrent ? 'text' : 'password'"
                                   name="current_password"
                                   required
                                   autocomplete="current-password"
                                   placeholder="••••••••"
                                   class="pw-input w-full pl-10 pr-12 py-3 rounded-xl border text-sm transition-all duration-200 outline-none
                                          {{ $errors->has('current_password') ? 'border-red-400 bg-red-50 text-red-900' : 'border-gray-300 bg-white text-gray-900' }}">
                            <button type="button" @click="showCurrent = !showCurrent"
                                    class="pw-toggle absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 transition-colors"
                                    :aria-label="showCurrent ? 'Verbergen' : 'Anzeigen'">
                                <i :class="showCurrent ? 'fas fa-eye-slash' : 'fas fa-eye'" class="text-sm"></i>
                            </button>
                        </div>
                        @if ($errors->has('current_password'))
                        <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i> {{ $errors->first('current_password') }}
                        </p>
                        @endif
                    </div>

                    <!-- Neues Passwort -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Neues Passwort
                            <span class="text-xs text-gray-400 font-normal ml-1">Mindestens 8 Zeichen</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                <i class="fas fa-lock text-gray-400 text-sm"></i>
                            </span>
                            <input id="password"
                                   :type="showNew ? 'text' : 'password'"
                                   name="password"
                                   required
                                   autocomplete="new-password"
                                   placeholder="••••••••"
                                   class="pw-input w-full pl-10 pr-12 py-3 rounded-xl border text-sm transition-all duration-200 outline-none
                                          {{ $errors->has('password') ? 'border-red-400 bg-red-50 text-red-900' : 'border-gray-300 bg-white text-gray-900' }}">
                            <button type="button" @click="showNew = !showNew"
                                    class="pw-toggle absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 transition-colors"
                                    :aria-label="showNew ? 'Verbergen' : 'Anzeigen'">
                                <i :class="showNew ? 'fas fa-eye-slash' : 'fas fa-eye'" class="text-sm"></i>
                            </button>
                        </div>
                        @if ($errors->has('password'))
                        <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i> {{ $errors->first('password') }}
                        </p>
                        @endif
                    </div>

                    <!-- Passwort bestätigen -->
                    <div>
                        <label for="password-confirm" class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Neues Passwort bestätigen
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                <i class="fas fa-lock text-gray-400 text-sm"></i>
                            </span>
                            <input id="password-confirm"
                                   :type="showConfirm ? 'text' : 'password'"
                                   name="password_confirmation"
                                   required
                                   autocomplete="new-password"
                                   placeholder="••••••••"
                                   class="pw-input w-full pl-10 pr-12 py-3 rounded-xl border border-gray-300 bg-white text-gray-900 text-sm transition-all duration-200 outline-none
                                          {{ $errors->has('password_confirmation') ? 'border-red-400 bg-red-50 text-red-900' : '' }}">
                            <button type="button" @click="showConfirm = !showConfirm"
                                    class="pw-toggle absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 transition-colors"
                                    :aria-label="showConfirm ? 'Verbergen' : 'Anzeigen'">
                                <i :class="showConfirm ? 'fas fa-eye-slash' : 'fas fa-eye'" class="text-sm"></i>
                            </button>
                        </div>
                        @if ($errors->has('password_confirmation'))
                        <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i> {{ $errors->first('password_confirmation') }}
                        </p>
                        @endif
                    </div>

                    <!-- Submit -->
                    <button type="submit"
                            class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl
                                   active:scale-[0.98] text-white font-semibold text-sm transition-all duration-200"
                            style="background: linear-gradient(to right, var(--color-primary, #2563eb), var(--color-secondary, #6366f1))">
                        <i class="fas fa-save"></i>
                        Passwort ändern
                    </button>
                </form>
                @endif

            </div>
        </div>

    </div>
</div>
@endsection
