@extends('layouts.app')
@section('title') - Einstellungen @endsection

@section('content')

<div class="container-fluid px-4 py-3"
     x-data="settingsPage()"
     x-init="init()">

    {{-- Changelog-Banner --}}
    @if(isset($changelog))
        <div class="mb-4">
            <div class="border-2 border-amber-300 dark:border-amber-700 rounded-xl shadow-lg overflow-hidden" style="background-color: var(--color-card-bg);">
                <div class="px-6 py-4" style="background: linear-gradient(to right, #f59e0b, #f97316);">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-12 h-12 bg-white/20 backdrop-blur-sm rounded-lg">
                            <i class="fas fa-bullhorn text-2xl text-white"></i>
                        </div>
                        <div>
                            <h5 class="text-xl font-bold text-white mb-0">{{$changelog->header}}</h5>
                            <p class="text-xs text-amber-100 mb-0 mt-0.5">
                                <i class="fas fa-clock mr-1"></i>Neuigkeiten & Änderungen
                            </p>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-5">
                    <div class="prose prose-sm max-w-none leading-relaxed changelog-content" style="color: var(--color-text-secondary);">
                        {!! $changelog->text !!}
                    </div>
                </div>
                <div class="px-6 py-3 border-t border-amber-200 dark:border-amber-800" style="background-color: rgba(254,243,199,0.15);">
                    <p class="text-xs text-amber-800 dark:text-amber-300 mb-0 flex items-center gap-2">
                        <i class="fas fa-info-circle"></i>
                        <span>Bitte beachten Sie die oben genannten Änderungen und Hinweise.</span>
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Hauptkarte mit Sidebar-Tab-Layout --}}
    <div class="rounded-xl shadow-lg overflow-hidden" style="background-color: var(--color-card-bg); border: 1px solid var(--color-card-border);">

        {{-- Header --}}
        <div class="px-5 py-3 border-b flex items-center gap-3" style="background-color: var(--color-primary); border-color: var(--color-primary-dark);">
            <div class="flex items-center justify-center w-9 h-9 bg-white/20 rounded-lg">
                <i class="fas fa-user-cog text-white text-lg"></i>
            </div>
            <div>
                <h5 class="text-lg font-bold text-white mb-0">{{$user->name}}</h5>
                <p class="text-xs text-white/70 mb-0">Persönliche Einstellungen</p>
            </div>
        </div>

        {{-- Sidebar + Inhalt --}}
        <div class="flex" style="min-height: 520px;">

            {{-- Sidebar-Navigation --}}
            <nav class="flex-shrink-0 border-r" style="width: 220px; background-color: var(--color-body-bg); border-color: var(--color-card-border);">
                <div class="p-2 space-y-0.5">
                    <template x-for="tab in tabs" :key="tab.id">
                        <button
                            @click="activeTab = tab.id"
                            :class="activeTab === tab.id ? 'font-semibold' : 'font-medium hover:bg-black/5 dark:hover:bg-white/5'"
                            :style="activeTab === tab.id ? 'background-color: var(--color-primary); color: white;' : 'color: var(--color-text-primary);'"
                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-left text-sm transition-all duration-150">
                            <i :class="tab.icon" class="w-4 text-center text-sm flex-shrink-0"
                               :style="activeTab === tab.id ? 'color: white;' : 'color: var(--color-primary);'"></i>
                            <span x-text="tab.label" class="leading-tight"></span>
                        </button>
                    </template>
                </div>

                @if($user->sorg2 != null)
                    <div class="mx-2 mb-2 p-2.5 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700">
                        <p class="text-xs text-amber-800 dark:text-amber-300 flex items-start gap-1.5">
                            <i class="fas fa-link mt-0.5 flex-shrink-0"></i>
                            <span>Verknüpft mit <strong>{{$user->sorgeberechtigter2?->name}}</strong></span>
                        </p>
                    </div>
                @endif
            </nav>

            {{-- Tab-Inhalte --}}
            <div class="flex-1 overflow-y-auto" style="max-height: calc(100vh - 180px);">

                @if ($errors->any())
                    <div class="mx-6 mt-5 p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded">
                        <div class="flex items-start gap-2">
                            <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400 mt-1"></i>
                            <ul class="text-sm text-red-700 dark:text-red-300 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                {{-- Gemeinsames Formular für Profil-Tabs --}}
                <form action="{{url('/einstellungen/')}}" method="post" id="settingsForm">
                    @csrf
                    @method('PUT')

                    {{-- TAB: Persönliche Daten --}}
                    <div x-show="activeTab === 'profil'" x-cloak class="p-8">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="flex items-center justify-center w-10 h-10 rounded-xl" style="background-color: var(--color-widget-primary-bg);">
                                <i class="fas fa-user-circle" style="color: var(--color-primary);"></i>
                            </div>
                            <div>
                                <h2 class="text-base font-bold mb-0" style="color: var(--color-text-primary);">Persönliche Daten</h2>
                                <p class="text-xs mb-0" style="color: var(--color-text-secondary);">Namen, E-Mail und Telefonnummer</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-7">
                            <div>
                                <label class="block text-sm font-semibold mb-2" style="color: var(--color-text-primary);">
                                    <i class="fas fa-user text-blue-600 mr-1"></i>Name
                                </label>
                                <input type="text"
                                       class="w-full px-4 py-3 text-sm border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all outline-none"
                                       placeholder="Ihr vollständiger Name" name="name" value="{{$user->name}}" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-2" style="color: var(--color-text-primary);">
                                    <i class="fas fa-envelope text-blue-600 mr-1"></i>E-Mail
                                </label>
                                <input type="email"
                                       class="w-full px-4 py-3 text-sm border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all outline-none"
                                       placeholder="ihre.email@beispiel.de" name="email" value="{{$user->email}}" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-2" style="color: var(--color-text-primary);">
                                    <i class="fas fa-envelope-open text-blue-600 mr-1"></i>
                                    Öffentliche E-Mail
                                    <span class="block text-xs font-normal mt-0.5" style="color: var(--color-text-secondary);">Für andere Eltern sichtbar</span>
                                </label>
                                <input type="email"
                                       class="w-full px-4 py-3 text-sm border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all outline-none"
                                       placeholder="öffentliche.email@beispiel.de" name="publicMail" value="{{$user->publicMail}}">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-2" style="color: var(--color-text-primary);">
                                    <i class="fas fa-phone text-blue-600 mr-1"></i>
                                    Öffentliche Telefonnummer
                                    <span class="block text-xs font-normal mt-0.5" style="color: var(--color-text-secondary);">Für andere Eltern in gleichen Gruppen sichtbar</span>
                                </label>
                                <input type="tel"
                                       class="w-full px-4 py-3 text-sm border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all outline-none"
                                       placeholder="+49 123 456789" name="publicPhone" value="{{$user->publicPhone}}" autocomplete="off">
                            </div>
                        </div>
                        <div class="mt-6 pt-4 border-t" style="border-color: var(--color-card-border);">
                            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold text-sm rounded-lg transition-colors shadow-sm">
                                <i class="fas fa-save"></i>Änderungen speichern
                            </button>
                        </div>
                    </div>

                    {{-- TAB: Sicherheit --}}
                    <div x-show="activeTab === 'sicherheit'" x-cloak class="p-8">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="flex items-center justify-center w-10 h-10 rounded-xl" style="background-color: var(--color-widget-primary-bg);">
                                <i class="fas fa-lock" style="color: var(--color-primary);"></i>
                            </div>
                            <div>
                                <h2 class="text-base font-bold mb-0" style="color: var(--color-text-primary);">Sicherheit & Passwort</h2>
                                <p class="text-xs mb-0" style="color: var(--color-text-secondary);">Passwort ändern und Zugangsdaten verwalten</p>
                            </div>
                        </div>
                        <div>
                            <div class="p-4 rounded-lg mb-5 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700">
                                <p class="text-sm text-blue-800 dark:text-blue-300 mb-0">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Lassen Sie die Passwort-Felder leer, wenn Sie das Passwort nicht ändern möchten.
                                </p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-7">
                                <div>
                                    <label class="block text-sm font-semibold mb-2" style="color: var(--color-text-primary);">
                                        <i class="fas fa-lock text-blue-600 mr-1"></i>
                                        Aktuelles Passwort
                                        <span class="block text-xs font-normal mt-0.5" style="color: var(--color-text-secondary);">Zur Bestätigung erforderlich</span>
                                    </label>
                                    <input class="w-full px-4 py-3 text-sm border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all outline-none"
                                           name="current_password" type="password" autocomplete="current-password" placeholder="••••••••">
                                    @error('current_password')
                                        <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-2" style="color: var(--color-text-primary);">
                                        <i class="fas fa-key text-blue-600 mr-1"></i>
                                        Neues Passwort
                                        <span class="block text-xs font-normal mt-0.5" style="color: var(--color-text-secondary);">Mind. 10 Zeichen, Groß-/Kleinbuchst. & Zahl</span>
                                    </label>
                                    <input class="w-full px-4 py-3 text-sm border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all outline-none"
                                           name="password" type="password" minlength="10" autocomplete="new-password" placeholder="••••••••">
                                    @error('password')
                                        <p class="text-red-500 dark:text-red-400 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-2" style="color: var(--color-text-primary);">
                                        <i class="fas fa-check-double text-blue-600 mr-1"></i>
                                        Passwort bestätigen
                                        <span class="block text-xs font-normal mt-0.5" style="color: var(--color-text-secondary);">Passwort wiederholen</span>
                                    </label>
                                    <input id="password-confirm" type="password"
                                           class="w-full px-4 py-3 text-sm border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all outline-none"
                                           name="password_confirmation" autocomplete="new-password" placeholder="••••••••">
                                </div>
                            </div>
                            <div class="mt-6 pt-4 border-t" style="border-color: var(--color-card-border);">
                                <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold text-sm rounded-lg transition-colors shadow-sm">
                                    <i class="fas fa-save"></i>Passwort speichern
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- TAB: Benachrichtigungen --}}
                    <div x-show="activeTab === 'benachrichtigungen'" x-cloak class="p-8">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="flex items-center justify-center w-10 h-10 rounded-xl" style="background-color: var(--color-widget-primary-bg);">
                                <i class="fas fa-bell" style="color: var(--color-primary);"></i>
                            </div>
                            <div>
                                <h2 class="text-base font-bold mb-0" style="color: var(--color-text-primary);">Benachrichtigungen</h2>
                                <p class="text-xs mb-0" style="color: var(--color-text-secondary);">E-Mail-Benachrichtigungen und Kopien</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-7">
                            <div>
                                <label class="block text-sm font-semibold mb-2" style="color: var(--color-text-primary);">
                                    <i class="fas fa-envelope-circle-check text-blue-600 mr-1"></i>
                                    E-Mail Benachrichtigungen
                                    <span class="block text-xs font-normal mt-0.5" style="color: var(--color-text-secondary);">Zuletzt: {{$user->lastEmail?->format('d.m.Y H:i') ?? 'Nie'}}</span>
                                </label>
                                <select class="w-full px-4 py-3 text-sm border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all outline-none"
                                        name="benachrichtigung">
                                    <option value="daily" @if($user->benachrichtigung == 'daily') selected @endif>Täglich (bei neuen Nachrichten)</option>
                                    <option value="weekly" @if($user->benachrichtigung == 'weekly') selected @endif>Wöchentlich (Freitags)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-2" style="color: var(--color-text-primary);">
                                    <i class="fas fa-copy text-blue-600 mr-1"></i>
                                    Kopie von Rückmeldungen
                                    <span class="block text-xs font-normal mt-0.5" style="color: var(--color-text-secondary);">Erhalten Sie eine Kopie Ihrer Rückmeldungen</span>
                                </label>
                                <select class="w-full px-4 py-3 text-sm border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all outline-none"
                                        name="sendCopy">
                                    <option value="1" @if($user->sendCopy == 1) selected @endif>Kopie erhalten</option>
                                    <option value="0" @if($user->sendCopy == 0) selected @endif>Keine Kopie senden</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-6 pt-4 border-t" style="border-color: var(--color-card-border);">
                            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold text-sm rounded-lg transition-colors shadow-sm">
                                <i class="fas fa-save"></i>Einstellungen speichern
                            </button>
                        </div>
                    </div>

                    {{-- TAB: Erweiterte Einstellungen --}}
                    <div x-show="activeTab === 'erweitert'" x-cloak class="p-8">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="flex items-center justify-center w-10 h-10 rounded-xl" style="background-color: var(--color-widget-primary-bg);">
                                <i class="fas fa-sliders-h" style="color: var(--color-primary);"></i>
                            </div>
                            <div>
                                <h2 class="text-base font-bold mb-0" style="color: var(--color-text-primary);">Erweiterte Einstellungen</h2>
                                <p class="text-xs mb-0" style="color: var(--color-text-secondary);">Login, Kalender und weitere Optionen</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-7">
                            <div>
                                <label class="block text-sm font-semibold mb-2" style="color: var(--color-text-primary);">
                                    <i class="fas fa-sign-in-alt text-blue-600 mr-1"></i>
                                    Login aufzeichnen
                                    <span class="block text-xs font-normal mt-0.5" style="color: var(--color-text-secondary);">Speicherung des letzten Logins</span>
                                </label>
                                <select class="w-full px-4 py-3 text-sm border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all outline-none"
                                        name="track_login">
                                    <option value="1" @if($user->track_login == true) selected @endif>Aufzeichnen</option>
                                    <option value="0" @if($user->track_login == false) selected @endif>Nicht speichern</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-2" style="color: var(--color-text-primary);">
                                    <i class="fas fa-calendar-share text-blue-600 mr-1"></i>
                                    Termine freigeben
                                    <span class="block text-xs font-normal mt-0.5" style="color: var(--color-text-secondary);">Link für externe Kalender</span>
                                </label>
                                <select class="w-full px-4 py-3 text-sm border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all outline-none"
                                        name="releaseCalendar">
                                    <option value="1" @if($user->releaseCalendar == true) selected @endif>Ja, freigeben</option>
                                    <option value="0" @if($user->releaseCalendar == false) selected @endif>Nein</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-2" style="color: var(--color-text-primary);">
                                    <i class="fas fa-tag text-blue-600 mr-1"></i>
                                    Termin-Prefix
                                    <span class="block text-xs font-normal mt-0.5" style="color: var(--color-text-secondary);">Vorangestelltes Kürzel (max. 8 Zeichen)</span>
                                </label>
                                <input type="text"
                                       class="w-full px-4 py-3 text-sm border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all outline-none"
                                       name="calendar_prefix" value="{{$user->calendar_prefix}}" maxlength="8" placeholder="z.B. KITA">
                            </div>
                        </div>
                        @if($user->can('use messenger'))
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-7 mt-5">
                            <div>
                                <label class="block text-sm font-semibold mb-2" style="color: var(--color-text-primary);">
                                    <i class="fas fa-user-secret text-blue-600 mr-1"></i>
                                    In Messenger-Suche sichtbar
                                    <span class="block text-xs font-normal mt-0.5" style="color: var(--color-text-secondary);">Entscheiden Sie, ob andere Eltern Sie per Direktnachricht finden können</span>
                                </label>
                                <select class="w-full px-4 py-3 text-sm border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all outline-none"
                                        name="messenger_discoverable">
                                    <option value="1" @if($user->messenger_discoverable) selected @endif>Ja, auffindbar für Direktnachrichten</option>
                                    <option value="0" @if(!$user->messenger_discoverable) selected @endif>Nein, nicht in der Suche anzeigen</option>
                                </select>
                                <p class="text-xs mt-1" style="color: var(--color-text-secondary);">
                                    <i class="fas fa-info-circle mr-0.5"></i>
                                    Gruppen-Chats sind davon nicht betroffen.
                                </p>
                            </div>
                        </div>
                        @endif
                        <div class="mt-6 pt-4 border-t" style="border-color: var(--color-card-border);">
                            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold text-sm rounded-lg transition-colors shadow-sm">
                                <i class="fas fa-save"></i>Einstellungen speichern
                            </button>
                        </div>
                    </div>

                </form>{{-- Ende gemeinsames Formular --}}

                {{-- TAB: Kinder & Gruppen --}}
                <div x-show="activeTab === 'kinder'" x-cloak class="p-8">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="flex items-center justify-center w-10 h-10 rounded-xl" style="background-color: var(--color-widget-primary-bg);">
                            <i class="fas fa-child" style="color: var(--color-primary);"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold mb-0" style="color: var(--color-text-primary);">Kinder & Gruppen</h2>
                            <p class="text-xs mb-0" style="color: var(--color-text-secondary);">Verknüpfte Kinder und Gruppenmitgliedschaften</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        @if(auth()->user()->groups->count() > 0 || auth()->user()->children()?->count() > 0)
                        <div>
                            <h3 class="text-sm font-bold mb-3 flex items-center gap-2" style="color: var(--color-text-primary);">
                                <i class="fas fa-child text-green-600"></i>Meine Kinder
                            </h3>
                            <p class="text-xs mb-3 leading-relaxed" style="color: var(--color-text-secondary);">
                                <i class="fas fa-info-circle text-blue-600 mr-1"></i>
                                Hier werden die Kinder angezeigt, die mit Ihrem Konto verknüpft sind. Mit der Glocke können Sie Benachrichtigungen aktivieren.
                            </p>
                            <div class="space-y-2 mb-5">
                                @foreach($user->children() as $child)
                                    <div class="border rounded-lg p-3 hover:border-green-500 hover:shadow-sm transition-all duration-200" style="border-color: var(--color-card-border);">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex-1 min-w-0">
                                                <div class="font-semibold text-sm mb-1.5" style="color: var(--color-text-primary);">
                                                    {{$child->first_name}} {{$child->last_name}}
                                                </div>
                                                <div class="flex flex-wrap gap-1.5">
                                                    <span class="inline-flex items-center px-2 py-0.5 bg-blue-100 text-blue-700 text-xs font-medium rounded">
                                                        <i class="fas fa-users mr-1 text-[10px]"></i>{{$child->group->name ?? ''}}
                                                    </span>
                                                    <span class="inline-flex items-center px-2 py-0.5 bg-purple-100 text-purple-700 text-xs font-medium rounded">
                                                        <i class="fas fa-graduation-cap mr-1 text-[10px]"></i>{{$child->class->name ?? ''}}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                @if($child->notification)
                                                    <button class="inline-flex items-center justify-center w-8 h-8 bg-teal-500 hover:bg-teal-600 text-white rounded-lg cursor-pointer child-notification transition-colors"
                                                            title="Benachrichtigung aktiv" data-child_id="{{$child->id}}" data-notification="1">
                                                        <i class="fas fa-bell text-xs"></i>
                                                    </button>
                                                @else
                                                    <button class="inline-flex items-center justify-center w-8 h-8 bg-amber-500 hover:bg-amber-600 text-white rounded-lg cursor-pointer child-notification transition-colors"
                                                            title="Benachrichtigung deaktiviert" data-child_id="{{$child->id}}" data-notification="0">
                                                        <i class="fas fa-bell-slash text-xs"></i>
                                                    </button>
                                                @endif
                                                <a href="#"
                                                   class="inline-flex items-center justify-center w-8 h-8 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors edit-child-btn"
                                                   data-child-id="{{ $child->id }}" data-child="{{ json_encode($child) }}">
                                                    <i class="fas fa-edit text-xs"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="border-t pt-4" style="border-color: var(--color-card-border);">
                                <h3 class="text-sm font-bold mb-3 flex items-center gap-2" style="color: var(--color-text-primary);">
                                    <i class="fas fa-plus-circle text-green-600"></i>Kind hinzufügen
                                </h3>
                                <form action="{{url('/child')}}" method="post" class="space-y-3">
                                    @csrf
                                    <div>
                                        <label class="block text-xs font-semibold mb-1" style="color: var(--color-text-primary);">Vorname <span class="text-red-600">*</span></label>
                                        <input type="text" class="w-full px-3 py-2 text-sm border-2 border-gray-300 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all outline-none"
                                               name="first_name" placeholder="Vorname des Kindes" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold mb-1" style="color: var(--color-text-primary);">Nachname <span class="text-red-600">*</span></label>
                                        <input type="text" class="w-full px-3 py-2 text-sm border-2 border-gray-300 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all outline-none"
                                               name="last_name" placeholder="Nachname des Kindes" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold mb-1" style="color: var(--color-text-primary);">Gruppe <span class="text-red-600">*</span></label>
                                        <select class="w-full px-3 py-2 text-sm border-2 border-gray-300 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all outline-none"
                                                name="group_id" required>
                                            <option value="">Bitte wählen...</option>
                                            @foreach(auth()->user()->groups as $group)
                                                <option value="{{$group->id}}">{{$group->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold mb-1" style="color: var(--color-text-primary);">Klassenstufe <span class="text-red-600">*</span></label>
                                        <select class="w-full px-3 py-2 text-sm border-2 border-gray-300 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all outline-none"
                                                name="class_id" required>
                                            <option value="">Bitte wählen...</option>
                                            @foreach(auth()->user()->groups as $group)
                                                <option value="{{$group->id}}">{{$group->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold text-sm rounded-lg transition-colors shadow-sm">
                                        <i class="fas fa-plus"></i>Kind hinzufügen
                                    </button>
                                </form>
                            </div>
                        </div>
                        @endif

                        <div>
                            <h3 class="text-sm font-bold mb-3 flex items-center gap-2" style="color: var(--color-text-primary);">
                                <i class="fas fa-users text-purple-600"></i>Meine Gruppen
                            </h3>
                            <div class="flex flex-wrap gap-2 mb-4">
                                @foreach($user->groups as $gruppe)
                                    <span class="inline-flex items-center px-3 py-2 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 font-medium text-sm rounded-lg border border-purple-300 dark:border-purple-700">
                                        <i class="fas fa-user-friends mr-2"></i>{{$gruppe->name}}
                                    </span>
                                @endforeach
                            </div>
                            <p class="text-xs leading-relaxed p-3 rounded-lg" style="color: var(--color-text-secondary); background-color: var(--color-body-bg); border: 1px solid var(--color-card-border);">
                                <i class="fas fa-info-circle text-purple-600 mr-1"></i>
                                Sollte die Lerngruppe und/oder Altersstufe Ihres Kindes nicht korrekt in den Gruppen abgebildet sein, wenden Sie sich bitte an das Sekretariat.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- TAB: Integrationen --}}
                <div x-show="activeTab === 'integrationen'" x-cloak class="p-8">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="flex items-center justify-center w-10 h-10 rounded-xl" style="background-color: var(--color-widget-primary-bg);">
                            <i class="fas fa-plug" style="color: var(--color-primary);"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold mb-0" style="color: var(--color-text-primary);">Integrationen</h2>
                            <p class="text-xs mb-0" style="color: var(--color-text-secondary);">ICAL-Kalender und API-Zugang</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        @if($user->releaseCalendar == 1)
                        <div class="rounded-lg overflow-hidden border" style="border-color: var(--color-card-border);">
                            <div class="px-4 py-3 bg-gradient-to-r from-cyan-600 to-cyan-700">
                                <h6 class="text-sm font-bold text-white mb-0 flex items-center gap-2">
                                    <i class="fas fa-calendar-alt"></i>ICAL-Kalender
                                </h6>
                            </div>
                            <div class="p-4">
                                <p class="text-sm mb-3" style="color: var(--color-text-secondary);">
                                    <i class="fas fa-info-circle text-cyan-600 mr-1"></i>
                                    Diese URL kann in Kalender-Apps hinzugefügt werden, um Termine direkt einzubinden.
                                </p>
                                <div class="p-3 rounded-lg" style="background-color: var(--color-body-bg); border: 1px solid var(--color-card-border);">
                                    <code class="text-xs break-all" style="color: var(--color-text-primary);">{{config('app.url')."/".$user->uuid.'/ical'}}</code>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="rounded-lg p-4 border" style="border-color: var(--color-card-border); background-color: var(--color-body-bg);">
                            <p class="text-sm" style="color: var(--color-text-secondary);">
                                <i class="fas fa-calendar-times text-gray-400 mr-2"></i>
                                ICAL-Kalender ist nicht freigegeben. Aktivieren Sie die Option unter
                                <button @click="activeTab='erweitert'" class="text-blue-600 hover:underline font-medium">Erweiterte Einstellungen</button>.
                            </p>
                        </div>
                        @endif

                        <div class="rounded-lg overflow-hidden border" style="border-color: var(--color-card-border);">
                            <div class="px-4 py-3 bg-gradient-to-r from-orange-600 to-orange-700">
                                <h6 class="text-sm font-bold text-white mb-0 flex items-center gap-2">
                                    <i class="fas fa-key"></i>API-Token
                                </h6>
                            </div>

                            @if(session()->has('token'))
                                <div class="p-4 border-b bg-green-50 dark:bg-green-900/20" style="border-color: var(--color-card-border);">
                                    <div class="flex items-start gap-2">
                                        <i class="fas fa-check-circle text-green-600 dark:text-green-400 mt-1"></i>
                                        <div class="flex-1">
                                            <p class="text-sm text-green-800 dark:text-green-300 font-medium mb-2">
                                                Token erfolgreich erstellt. Bitte jetzt sichern – es wird nicht erneut angezeigt!
                                            </p>
                                            <div class="p-2 rounded" style="background-color: var(--color-input-bg); border: 1px solid var(--color-input-border);">
                                                <code class="text-xs break-all" style="color: var(--color-text-primary);">{{session('token')}}</code>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="p-4">
                                <p class="text-sm mb-3" style="color: var(--color-text-secondary);">
                                    <i class="fas fa-shield-alt text-orange-600 mr-1"></i>
                                    Mit dem API-Token können externe Anwendungen auf die Daten-Schnittstellen zugreifen.
                                </p>

                                @if(auth()->user()->tokens->count() > 0)
                                    <div class="overflow-x-auto mb-4">
                                        <table class="w-full text-sm">
                                            <thead class="border-b" style="background-color: var(--color-body-bg); border-color: var(--color-card-border);">
                                                <tr>
                                                    <th class="px-3 py-2 text-left text-xs font-semibold" style="color: var(--color-text-secondary);">Name</th>
                                                    <th class="px-3 py-2 text-left text-xs font-semibold" style="color: var(--color-text-secondary);">Erstellt am</th>
                                                    <th class="px-3 py-2 text-right text-xs font-semibold" style="color: var(--color-text-secondary);">Aktionen</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach(auth()->user()->tokens as $token)
                                                    <tr class="border-b hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors" style="border-color: var(--color-card-border);">
                                                        <td class="px-3 py-2" style="color: var(--color-text-primary);">{{$token->name}}</td>
                                                        <td class="px-3 py-2 text-xs" style="color: var(--color-text-secondary);">{{$token->created_at->format('d.m.Y')}}</td>
                                                        <td class="px-3 py-2 text-right">
                                                            <form action="{{url('/einstellungen/token/'.$token->id)}}" method="post" class="inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg transition-colors">
                                                                    <i class="fas fa-trash"></i>Löschen
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="flex items-center gap-2 p-3 rounded border-l-4 border-gray-400 mb-4" style="background-color: var(--color-body-bg);">
                                        <i class="fas fa-info-circle text-gray-500"></i>
                                        <p class="text-sm mb-0" style="color: var(--color-text-secondary);">Keine API-Tokens vorhanden</p>
                                    </div>
                                @endif

                                <h6 class="text-sm font-semibold mb-2 flex items-center gap-1.5" style="color: var(--color-text-primary);">
                                    <i class="fas fa-plus-circle text-orange-600"></i>Neues Token erstellen
                                </h6>
                                <form action="{{url('/einstellungen/token')}}" method="post" class="flex gap-2">
                                    @csrf
                                    <input type="text"
                                           class="flex-1 px-3 py-2 text-sm border-2 border-gray-300 rounded-lg focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition-all outline-none"
                                           name="name" placeholder="Token-Name" required>
                                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white font-semibold text-sm rounded-lg transition-colors flex-shrink-0">
                                        <i class="fas fa-plus"></i>Erstellen
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TAB: Design --}}
                @if(($generalSettings->allow_user_theme ?? true) && isset($themes))
                <div x-show="activeTab === 'design'" x-cloak class="p-8">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="flex items-center justify-center w-10 h-10 rounded-xl" style="background-color: var(--color-widget-primary-bg);">
                            <i class="fas fa-palette" style="color: var(--color-primary);"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold mb-0" style="color: var(--color-text-primary);">Design-Theme</h2>
                            <p class="text-xs mb-0" style="color: var(--color-text-secondary);">Ihr persönliches Erscheinungsbild</p>
                        </div>
                    </div>
                    <p class="text-sm mb-5" style="color: var(--color-text-secondary);">
                        Wählen Sie ein Design für Ihre persönliche Ansicht. Das Standard-Design wird vom Administrator festgelegt.
                    </p>
                    <form action="{{ route('user.theme.update') }}" method="post" id="themeForm">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-5" id="themeGrid">
                            <label class="cursor-pointer theme-card-label" data-theme-value="">
                                <input type="radio" name="theme" value="" class="sr-only" @if(empty($userTheme)) checked @endif>
                                <div class="theme-card border-2 rounded-xl p-3 text-center transition-all duration-200 relative"
                                     style="border-color: {{ empty($userTheme) ? 'var(--color-primary)' : 'var(--color-card-border)' }};
                                            background-color: {{ empty($userTheme) ? 'var(--color-widget-primary-bg)' : 'var(--color-card-bg)' }}">
                                    <div class="absolute top-2 right-2 w-5 h-5 rounded-full items-center justify-center text-white text-xs active-check {{ empty($userTheme) ? 'flex' : 'hidden' }}"
                                         style="background-color: var(--color-primary)">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <i class="fas fa-cog text-2xl mb-2 block" style="color: var(--color-text-secondary);"></i>
                                    <span class="text-xs font-semibold block" style="color: var(--color-text-primary);">System-Standard</span>
                                    <span class="text-[10px] block mt-1" style="color: var(--color-text-secondary);">Admin-Vorgabe</span>
                                </div>
                            </label>

                            @foreach($themes as $theme)
                                @php $vars = $theme->variables(); $isActive = $userTheme === $theme->id(); @endphp
                                <label class="cursor-pointer theme-card-label" data-theme-value="{{ $theme->id() }}">
                                    <input type="radio" name="theme" value="{{ $theme->id() }}" class="sr-only" @if($isActive) checked @endif>
                                    <div class="theme-card border-2 rounded-xl p-3 text-center transition-all duration-200 relative"
                                         style="border-color: {{ $isActive ? ($vars['--color-primary'] ?? '#2563eb') : 'var(--color-card-border)' }};
                                                background-color: var(--color-card-bg)">
                                        <div class="absolute top-2 right-2 w-5 h-5 rounded-full items-center justify-center text-white text-xs active-check {{ $isActive ? 'flex' : 'hidden' }}"
                                             style="background-color: {{ $vars['--color-primary'] ?? '#2563eb' }}">
                                            <i class="fas fa-check"></i>
                                        </div>
                                        <div class="flex justify-center gap-1 mb-2">
                                            <span class="w-5 h-5 rounded-full border border-gray-200 inline-block" style="background: {{ $vars['--color-primary'] ?? '#000' }}"></span>
                                            <span class="w-5 h-5 rounded-full border border-gray-200 inline-block" style="background: {{ $vars['--color-sidebar-bg'] ?? '#000' }}"></span>
                                            <span class="w-5 h-5 rounded-full border border-gray-200 inline-block" style="background: {{ $vars['--color-widget-primary-from'] ?? '#000' }}"></span>
                                        </div>
                                        <span class="text-xs font-semibold block" style="color: var(--color-text-primary);">{{ $theme->name() }}</span>
                                        <span class="text-[10px] block mt-1" style="color: var(--color-text-secondary);">{{ $theme->description() }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        <button type="submit" id="themeSaveBtn"
                                class="inline-flex items-center gap-2 px-6 py-2.5 text-white font-semibold rounded-lg transition-colors"
                                style="background-color: var(--color-primary)"
                                onmouseover="this.style.backgroundColor=getComputedStyle(document.documentElement).getPropertyValue('--color-primary-dark')"
                                onmouseout="this.style.backgroundColor=getComputedStyle(document.documentElement).getPropertyValue('--color-primary')">
                            <i class="fas fa-save"></i>Theme speichern
                        </button>
                    </form>
                </div>
                @endif

            </div>{{-- Ende Tab-Inhalte --}}
        </div>{{-- Ende flex --}}
    </div>{{-- Ende Hauptkarte --}}
</div>

{{-- Alpine.js Modal: Kind bearbeiten --}}
<div x-data="{
        open: false,
        childId: null,
        firstName: '',
        lastName: '',
        groupId: '',
        classId: '',
        openModal(child) {
            this.childId = child.id;
            this.firstName = child.first_name;
            this.lastName = child.last_name;
            this.groupId = child.group_id;
            this.classId = child.class_id;
            this.open = true;
            document.body.style.overflow = 'hidden';
        },
        closeModal() {
            this.open = false;
            document.body.style.overflow = '';
        }
     }"
     id="editChildModalWrapper"
     @open-edit-child.window="openModal($event.detail)">

    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/50 z-40"
         @click="closeModal()">
    </div>

    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         @keydown.escape.window="closeModal()">

        <div class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-xl shadow-2xl overflow-hidden" @click.stop>
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-4 py-3">
                <div class="flex items-center justify-between">
                    <h5 class="text-lg font-bold text-white mb-0 flex items-center gap-2">
                        <i class="fas fa-edit"></i>Kind bearbeiten
                    </h5>
                    <button type="button" @click="closeModal()" class="text-white hover:text-gray-200 transition-colors rounded-lg p-1 hover:bg-white/10">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <div class="p-5">
                <form id="editChildForm" method="POST" class="space-y-4" :action="'/child/' + childId">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="editFirstName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <i class="fas fa-user text-blue-600 mr-1"></i>Vorname
                        </label>
                        <input type="text" class="form-control" id="editFirstName" name="first_name" x-model="firstName" required>
                    </div>
                    <div>
                        <label for="editLastName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <i class="fas fa-user text-blue-600 mr-1"></i>Nachname
                        </label>
                        <input type="text" class="form-control" id="editLastName" name="last_name" x-model="lastName" required>
                    </div>
                    <div>
                        <label for="editGroup" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <i class="fas fa-users text-blue-600 mr-1"></i>Gruppe
                        </label>
                        <select class="form-control" id="editGroup" name="group_id" x-model="groupId" required>
                            @foreach(auth()->user()->groups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="editClass" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <i class="fas fa-graduation-cap text-blue-600 mr-1"></i>Klassenstufe
                        </label>
                        <select class="form-control" id="editClass" name="class_id" x-model="classId" required>
                            @foreach(auth()->user()->groups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="btn btn-primary flex-1">
                            <i class="fas fa-save"></i> Speichern
                        </button>
                        <button type="button" @click="closeModal()" class="btn btn-light">Abbrechen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('js')
<script>
function settingsPage() {
    return {
        activeTab: 'profil',
        tabs: [
            { id: 'profil',             icon: 'fas fa-user-circle', label: 'Persönliche Daten' },
            { id: 'sicherheit',         icon: 'fas fa-lock',        label: 'Sicherheit' },
            { id: 'benachrichtigungen', icon: 'fas fa-bell',        label: 'Benachrichtigungen' },
            { id: 'erweitert',          icon: 'fas fa-sliders-h',   label: 'Erweiterte Einstell.' },
            { id: 'kinder',             icon: 'fas fa-child',       label: 'Kinder & Gruppen' },
            { id: 'integrationen',      icon: 'fas fa-plug',        label: 'Integrationen' },
            @if(($generalSettings->allow_user_theme ?? true) && isset($themes))
            { id: 'design',             icon: 'fas fa-palette',     label: 'Design-Theme' },
            @endif
        ],
        init() {
            const hash = window.location.hash?.slice(1);
            const validIds = this.tabs.map(t => t.id);
            if (hash && validIds.includes(hash)) {
                this.activeTab = hash;
            }
            @if ($errors->has('current_password') || $errors->has('password'))
                this.activeTab = 'sicherheit';
            @endif
            this.$watch('activeTab', val => {
                history.replaceState(null, '', '#' + val);
            });
        }
    };
}

// Theme-Formular: kein Doppelsubmit
document.getElementById('themeForm')?.addEventListener('submit', function(e) {
    e.stopImmediatePropagation();
});

// Theme-Karten: visuelles Feedback
document.querySelectorAll('.theme-card-label').forEach(function(label) {
    label.addEventListener('click', function() {
        document.querySelectorAll('.theme-card').forEach(function(card) {
            card.style.borderColor = getComputedStyle(document.documentElement).getPropertyValue('--color-card-border').trim();
            card.style.backgroundColor = getComputedStyle(document.documentElement).getPropertyValue('--color-card-bg').trim();
            var check = card.querySelector('.active-check');
            if (check) { check.classList.add('hidden'); check.classList.remove('flex'); }
        });
        var card = this.querySelector('.theme-card');
        var radio = this.querySelector('input[type=radio]');
        var activeColor = radio.getAttribute('data-primary') ||
            getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim();
        card.style.borderColor = activeColor;
        card.style.backgroundColor = getComputedStyle(document.documentElement).getPropertyValue('--color-widget-primary-bg').trim();
        var check = card.querySelector('.active-check');
        if (check) { check.classList.remove('hidden'); check.classList.add('flex'); }
    });
});

document.querySelectorAll('#themeGrid input[type=radio]').forEach(function(radio) {
    var card = radio.closest('label')?.querySelector('.theme-card');
    var existingBorder = card?.style.borderColor;
    if (existingBorder && existingBorder !== '' && !existingBorder.includes('var(')) {
        radio.setAttribute('data-primary', existingBorder);
    }
});

$(document).ready(function() {
    $('.child-notification').click(function () {
        let child_id = $(this).data('child_id');
        let notification = $(this).data('notification');
        $.ajax({
            url: '/child/' + child_id + '/notification',
            type: 'POST',
            data: { _token: '{{csrf_token()}}', child_id: child_id, notification: notification == 1 ? 0 : 1 },
            success: function(data) {
                if (data.notification == 1) {
                    $('.child-notification[data-child_id=' + child_id + ']')
                        .removeClass('bg-amber-500 hover:bg-amber-600').addClass('bg-teal-500 hover:bg-teal-600')
                        .html('<i class="fas fa-bell text-xs"></i>').attr('title', 'Benachrichtigung aktiv').data('notification', 1);
                } else {
                    $('.child-notification[data-child_id=' + child_id + ']')
                        .removeClass('bg-teal-500 hover:bg-teal-600').addClass('bg-amber-500 hover:bg-amber-600')
                        .html('<i class="fas fa-bell-slash text-xs"></i>').attr('title', 'Benachrichtigung deaktiviert').data('notification', 0);
                }
            },
            error: function() { alert('Fehler beim Speichern'); }
        });
    });

    $('.edit-child-btn').click(function(e) {
        e.preventDefault();
        var child = $(this).data('child');
        window.dispatchEvent(new CustomEvent('open-edit-child', { detail: child }));
    });
});
</script>
@endpush

@push('css')
<style>
    [x-cloak] { display: none !important; }

    .changelog-content { line-height: 1.75; }
    .changelog-content p { margin-bottom: 1rem; }
    .changelog-content p:last-child { margin-bottom: 0; }
    .changelog-content ul, .changelog-content ol { margin: 0.75rem 0; padding-left: 1.5rem; }
    .changelog-content li { margin-bottom: 0.5rem; line-height: 1.6; }
    .changelog-content li::marker { color: #f59e0b; }
    .changelog-content strong { color: #92400e; font-weight: 600; }
    .changelog-content a { color: #2563eb; text-decoration: underline; transition: color 0.2s; }
    .changelog-content a:hover { color: #1d4ed8; }
    .changelog-content h1, .changelog-content h2,
    .changelog-content h3, .changelog-content h4 {
        color: #92400e; font-weight: 700; margin-top: 1.5rem; margin-bottom: 0.75rem;
    }
    .changelog-content h1:first-child, .changelog-content h2:first-child,
    .changelog-content h3:first-child, .changelog-content h4:first-child { margin-top: 0; }
    .changelog-content code { background: #fef3c7; padding: 0.125rem 0.375rem; border-radius: 0.25rem; font-size: 0.875em; color: #92400e; }
    .changelog-content blockquote { border-left: 4px solid #fbbf24; padding-left: 1rem; margin: 1rem 0; color: #78350f; font-style: italic; }
</style>
@endpush



