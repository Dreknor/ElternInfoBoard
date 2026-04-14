@extends('layouts.app')
@section('title') - Einstellungen @endsection

@section('content')

    <div class="container-fluid px-4 py-3">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-4 py-3 border-b border-indigo-800">
                <h5 class="text-xl font-bold text-white mb-0 flex items-center gap-2">
                    <i class="fas fa-user-cog"></i>
                    {{$user->name}}
                </h5>
            </div>
            <div class="p-4">
                @if(isset($changelog))
                    <div class="mb-6">
                        <div class="bg-gradient-to-br from-amber-50 to-orange-50 border-2 border-amber-300 rounded-xl shadow-lg overflow-hidden">
                            <div class="bg-gradient-to-r from-amber-500 to-orange-500 px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center justify-center w-12 h-12 bg-white/20 backdrop-blur-sm rounded-lg">
                                        <i class="fas fa-bullhorn text-2xl text-white"></i>
                                    </div>
                                    <div>
                                        <h5 class="text-xl font-bold text-white mb-0">
                                            {{$changelog->header}}
                                        </h5>
                                        <p class="text-xs text-amber-100 mb-0 mt-0.5">
                                            <i class="fas fa-clock mr-1"></i>
                                            Neuigkeiten & Änderungen
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="px-6 py-5">
                                <div class="prose prose-sm max-w-none text-gray-700 leading-relaxed changelog-content">
                                    {!! $changelog->text !!}
                                </div>
                            </div>
                            <div class="px-6 py-3 bg-gradient-to-r from-amber-100 to-orange-100 border-t border-amber-200">
                                <p class="text-xs text-amber-800 mb-0 flex items-center gap-2">
                                    <i class="fas fa-info-circle"></i>
                                    <span>Bitte beachten Sie die oben genannten Änderungen und Hinweise.</span>
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Hauptbereich: Einstellungen -->
                <div class="bg-white border border-gray-200 rounded-lg shadow-md overflow-hidden mb-6">
                    <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 border-b border-blue-800">
                        <h5 class="text-xl font-bold text-white mb-0 flex items-center gap-2">
                            <i class="fas fa-cog"></i>
                            Einstellungen
                        </h5>
                    </div>

                    <div class="p-8">
                                @if ($errors->any())
                                    <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 rounded">
                                        <div class="flex items-start gap-2">
                                            <i class="fas fa-exclamation-circle text-red-600 mt-1"></i>
                                            <ul class="text-sm text-red-700 space-y-1">
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                @endif
                                <form action="{{url('/einstellungen/')}}" method="post" class="space-y-8">
                                    @csrf
                                    @method('PUT')

                                    <!-- Persönliche Daten -->
                                    <div>
                                        <h6 class="text-base font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                                            <i class="fas fa-user-circle text-blue-600 mr-2"></i>
                                            Persönliche Daten
                                        </h6>
                                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                    <i class="fas fa-user text-blue-600 mr-1"></i>
                                                    Name
                                                </label>
                                                <input type="text"
                                                       class="w-full px-4 py-3 text-sm border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                                       placeholder="Ihr vollständiger Name"
                                                       name="name"
                                                       value="{{$user->name}}"
                                                       required>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                    <i class="fas fa-envelope text-blue-600 mr-1"></i>
                                                    E-Mail
                                                </label>
                                                <input type="email"
                                                       class="w-full px-4 py-3 text-sm border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                                       placeholder="ihre.email@beispiel.de"
                                                       name="email"
                                                       value="{{$user->email}}"
                                                       required>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                    <i class="fas fa-envelope-open text-blue-600 mr-1"></i>
                                                    Öffentliche E-Mail
                                                    <span class="block text-xs text-gray-500 font-normal mt-0.5">Für andere Eltern sichtbar</span>
                                                </label>
                                                <input type="email"
                                                       class="w-full px-4 py-3 text-sm border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                                       placeholder="öffentliche.email@beispiel.de"
                                                       name="publicMail"
                                                       value="{{$user->publicMail}}">
                                            </div>

                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                    <i class="fas fa-phone text-blue-600 mr-1"></i>
                                                    Öffentliche Telefonnummer
                                                    <span class="block text-xs text-gray-500 font-normal mt-0.5">Für andere Eltern in gleichen Gruppen sichtbar</span>
                                                </label>
                                                <input type="tel"
                                                       class="w-full px-4 py-3 text-sm border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                                       placeholder="+49 123 456789"
                                                       name="publicPhone"
                                                       value="{{$user->publicPhone}}"
                                                       autocomplete="off">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Sicherheit -->
                                    <div>
                                        <h6 class="text-base font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200 mt-4">
                                            <i class="fas fa-lock text-blue-600 mr-2"></i>
                                            Sicherheit & Passwort
                                        </h6>
                                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                    <i class="fas fa-lock text-blue-600 mr-1"></i>
                                                    Aktuelles Passwort
                                                    <span class="block text-xs text-gray-500 font-normal mt-0.5">Zur Bestätigung erforderlich</span>
                                                </label>
                                                <input class="w-full px-4 py-3 text-sm border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                                       name="current_password"
                                                       type="password"
                                                       autocomplete="current-password"
                                                       placeholder="••••••••">
                                                @error('current_password')
                                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                    <i class="fas fa-key text-blue-600 mr-1"></i>
                                                    Neues Passwort
                                                    <span class="block text-xs text-gray-500 font-normal mt-0.5">Mindestens 10 Zeichen, Groß-/Kleinbuchstaben und Zahl</span>
                                                </label>
                                                <input class="w-full px-4 py-3 text-sm border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                                       name="password"
                                                       type="password"
                                                       minlength="10"
                                                       autocomplete="new-password"
                                                       placeholder="••••••••">
                                                @error('password')
                                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                    <i class="fas fa-check-double text-blue-600 mr-1"></i>
                                                    Passwort bestätigen
                                                    <span class="block text-xs text-gray-500 font-normal mt-0.5">Passwort wiederholen</span>
                                                </label>
                                                <input id="password-confirm"
                                                       type="password"
                                                       class="w-full px-4 py-3 text-sm border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                                       name="password_confirmation"
                                                       autocomplete="new-password"
                                                       placeholder="••••••••">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Benachrichtigungen -->
                                    <div>
                                        <h6 class="text-base font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200 mt-4">
                                            <i class="fas fa-bell text-blue-600 mr-2"></i>
                                            Benachrichtigungen
                                        </h6>
                                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                    <i class="fas fa-envelope-circle-check text-blue-600 mr-1"></i>
                                                    E-Mail Benachrichtigungen
                                                    <span class="block text-xs text-gray-500 font-normal mt-0.5">Zuletzt: {{$user->lastEmail?->format('d.m.Y H:i') ?? 'Nie'}}</span>
                                                </label>
                                                <select class="w-full px-4 py-3 text-sm border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                                        name="benachrichtigung">
                                                    <option value="daily" @if($user->benachrichtigung == 'daily') selected @endif>Täglich (bei neuen Nachrichten)</option>
                                                    <option value="weekly" @if($user->benachrichtigung == 'weekly') selected @endif>Wöchentlich (Freitags)</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                    <i class="fas fa-copy text-blue-600 mr-1"></i>
                                                    Kopie von Rückmeldungen
                                                    <span class="block text-xs text-gray-500 font-normal mt-0.5">Erhalten Sie eine Kopie Ihrer Rückmeldungen</span>
                                                </label>
                                                <select class="w-full px-4 py-3 text-sm border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                                        name="sendCopy">
                                                    <option value="1" @if($user->sendCopy == 1) selected @endif>Kopie erhalten</option>
                                                    <option value="0" @if($user->sendCopy == 0) selected @endif>Keine Kopie senden</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Erweiterte Einstellungen -->
                                    <div>
                                        <h6 class="text-base font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200 mt-4">
                                            <i class="fas fa-sliders-h text-blue-600 mr-2"></i>
                                            Erweiterte Einstellungen
                                        </h6>
                                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                    <i class="fas fa-sign-in-alt text-blue-600 mr-1"></i>
                                                    Login aufzeichnen
                                                    <span class="block text-xs text-gray-500 font-normal mt-0.5">Speicherung des letzten Logins</span>
                                                </label>
                                                <select class="w-full px-4 py-3 text-sm border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                                        name="track_login">
                                                    <option value="1" @if($user->track_login == true) selected @endif>Aufzeichnen</option>
                                                    <option value="0" @if($user->track_login == false) selected @endif>Nicht speichern</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                    <i class="fas fa-calendar-share text-blue-600 mr-1"></i>
                                                    Termine freigeben
                                                    <span class="block text-xs text-gray-500 font-normal mt-0.5">Link für externe Kalender</span>
                                                </label>
                                                <select class="w-full px-4 py-3 text-sm border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                                        name="releaseCalendar">
                                                    <option value="1" @if($user->releaseCalendar == true) selected @endif>Ja, freigeben</option>
                                                    <option value="0" @if($user->releaseCalendar == false) selected @endif>Nein</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                    <i class="fas fa-tag text-blue-600 mr-1"></i>
                                                    Termin-Prefix
                                                    <span class="block text-xs text-gray-500 font-normal mt-0.5">Vorangestelltes Kürzel (max. 8 Zeichen)</span>
                                                </label>
                                                <input type="text"
                                                       class="w-full px-4 py-3 text-sm border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                                       name="calendar_prefix"
                                                       value="{{$user->calendar_prefix}}"
                                                       maxlength="8"
                                                       placeholder="z.B. KITA">
                                            </div>
                                        </div>

                                        {{-- Messenger-Sichtbarkeit --}}
                                        @if($user->can('use messenger'))
                                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-4">
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                    <i class="fas fa-user-secret text-blue-600 mr-1"></i>
                                                    In Messenger-Suche sichtbar
                                                    <span class="block text-xs text-gray-500 font-normal mt-0.5">Entscheiden Sie, ob andere Eltern Sie per Direktnachricht finden können</span>
                                                </label>
                                                <select class="w-full px-4 py-3 text-sm border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                                        name="messenger_discoverable">
                                                    <option value="1" @if($user->messenger_discoverable) selected @endif>Ja, auffindbar für Direktnachrichten</option>
                                                    <option value="0" @if(!$user->messenger_discoverable) selected @endif>Nein, nicht in der Suche anzeigen</option>
                                                </select>
                                                <p class="text-xs text-gray-400 mt-1">
                                                    <i class="fas fa-info-circle mr-0.5"></i>
                                                    Gruppen-Chats sind davon nicht betroffen. Diese Einstellung gilt nur für neue Direktnachrichten.
                                                </p>
                                            </div>
                                        </div>
                                        @endif
                                    </div>

                                    <div class="pt-4 border-t border-gray-200">
                                        <button type="submit"
                                                class="w-full lg:w-auto inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-colors duration-200 shadow-md hover:shadow-lg hidden"
                                                id="btn-save">
                                            <i class="fas fa-save"></i>
                                            <span>Änderungen speichern</span>
                                        </button>
                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Zweite Reihe: Kinder und Gruppen nebeneinander -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6 mt-4">
                    @if(auth()->user()->groups->count() > 0 || auth()->user()->children()?->count() > 0)
                            <div class="bg-white border border-gray-200 rounded-lg shadow-md overflow-hidden">
                                <div class="bg-gradient-to-r from-green-600 to-green-700 px-4 py-3 border-b border-green-800">
                                    <h5 class="text-lg font-bold text-white mb-0 flex items-center gap-2">
                                        <i class="fas fa-child"></i>
                                        Kinder
                                    </h5>
                                </div>
                                <div class="p-5">
                                    <p class="text-xs text-gray-600 mb-4 leading-relaxed">
                                        <i class="fas fa-info-circle text-blue-600 mr-1"></i>
                                        Hier werden die Kinder angezeigt, die mit Ihrem Konto verknüpft sind. Sollte Ihr Kind im Hort betreut werden, können Sie mit der Glocke die Benachrichtigung aktivieren.
                                    </p>
                                    <div class="space-y-3">
                                        @foreach($user->children() as $child)
                                            <div class="border border-gray-200 rounded-lg p-4 hover:border-green-500 hover:shadow-sm transition-all duration-200">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="flex-1 min-w-0">
                                                        <div class="font-semibold text-gray-800 mb-2">
                                                            {{$child->first_name}} {{$child->last_name}}
                                                        </div>
                                                        <div class="flex flex-wrap gap-2">
                                                            <span class="inline-flex items-center px-2.5 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded">
                                                                <i class="fas fa-users mr-1.5 text-[10px]"></i>
                                                                {{$child->group->name ?? ''}}
                                                            </span>
                                                            <span class="inline-flex items-center px-2.5 py-1 bg-purple-100 text-purple-700 text-xs font-medium rounded">
                                                                <i class="fas fa-graduation-cap mr-1.5 text-[10px]"></i>
                                                                {{$child->class->name ?? ''}}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        @if($child->notification)
                                                            <button class="inline-flex items-center justify-center w-9 h-9 bg-teal-500 hover:bg-teal-600 text-white rounded-lg cursor-pointer child-notification transition-colors"
                                                                  title="Benachrichtigung aktiv"
                                                                  data-child_id="{{$child->id}}"
                                                                  data-notification="1">
                                                                <i class="fas fa-bell text-sm"></i>
                                                            </button>
                                                        @else
                                                            <button class="inline-flex items-center justify-center w-9 h-9 bg-amber-500 hover:bg-amber-600 text-white rounded-lg cursor-pointer child-notification transition-colors"
                                                                  title="Benachrichtigung deaktiviert"
                                                                  data-child_id="{{$child->id}}"
                                                                  data-notification="0">
                                                                <i class="fas fa-bell-slash text-sm"></i>
                                                            </button>
                                                        @endif
                                                        <a href="#"
                                                           class="inline-flex items-center justify-center w-9 h-9 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors edit-child-btn"
                                                           data-child-id="{{ $child->id }}"
                                                           data-child="{{ json_encode($child) }}">
                                                            <i class="fas fa-edit text-sm"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="border-t border-gray-200 p-5 bg-gray-50">
                                    <h6 class="text-sm font-semibold text-gray-800 mb-4">
                                        <i class="fas fa-plus-circle text-green-600 mr-1"></i>
                                        Kind hinzufügen
                                    </h6>
                                    <form action="{{url('/child')}}" method="post" class="space-y-3">
                                        @csrf
                                        <div>
                                            <label for="first_name" class="block text-xs font-semibold text-gray-700 mb-1.5">Vorname <span class="text-red-600">*</span></label>
                                            <input id="first_name"
                                                   type="text"
                                                   class="w-full px-3 py-2.5 text-sm border-2 border-gray-300 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-200 outline-none"
                                                   name="first_name"
                                                   placeholder="Vorname des Kindes"
                                                   required>
                                        </div>
                                        <div>
                                            <label for="last_name" class="block text-xs font-semibold text-gray-700 mb-1.5">Nachname <span class="text-red-600">*</span></label>
                                            <input id="last_name"
                                                   type="text"
                                                   class="w-full px-3 py-2.5 text-sm border-2 border-gray-300 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-200 outline-none"
                                                   name="last_name"
                                                   placeholder="Nachname des Kindes"
                                                   required>
                                        </div>
                                        <div>
                                            <label for="group" class="block text-xs font-semibold text-gray-700 mb-1.5">Gruppe <span class="text-red-600">*</span></label>
                                            <select id="group"
                                                    class="w-full px-3 py-2.5 text-sm border-2 border-gray-300 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-200 outline-none"
                                                    name="group_id"
                                                    required>
                                                <option value="">Bitte wählen...</option>
                                                @foreach(auth()->user()->groups as $group)
                                                    <option value="{{$group->id}}">{{$group->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label for="class" class="block text-xs font-semibold text-gray-700 mb-1.5">Klassenstufe <span class="text-red-600">*</span></label>
                                            <select id="class"
                                                    class="w-full px-3 py-2.5 text-sm border-2 border-gray-300 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-200 outline-none"
                                                    name="class_id"
                                                    required>
                                                <option value="">Bitte wählen...</option>
                                                @foreach(auth()->user()->groups as $group)
                                                    <option value="{{$group->id}}">{{$group->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button type="submit"
                                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold text-sm rounded-lg transition-colors duration-200 shadow-sm hover:shadow-md">
                                            <i class="fas fa-plus"></i>
                                            <span>Kind hinzufügen</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endif

                        <div class="bg-white border border-gray-200 rounded-lg shadow-md overflow-hidden">
                            <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-4 py-3 border-b border-purple-800">
                                <h5 class="text-lg font-bold text-white mb-0 flex items-center gap-2">
                                    <i class="fas fa-users"></i>
                                    Gruppen
                                </h5>
                            </div>
                            <div class="p-5">
                                <div class="flex flex-wrap gap-2.5">
                                    @foreach($user->groups as $gruppe)
                                        <span class="inline-flex items-center px-3.5 py-2.5 bg-purple-100 text-purple-700 font-medium text-sm rounded-lg border border-purple-300 hover:bg-purple-200 transition-colors">
                                            <i class="fas fa-user-friends mr-2"></i>
                                            {{$gruppe->name}}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                            <div class="border-t border-gray-200 p-5 bg-gray-50">
                                <p class="text-xs text-gray-600 leading-relaxed">
                                    <i class="fas fa-info-circle text-purple-600 mr-1"></i>
                                    Sollte die Lerngruppe und/oder Alterststufe Ihres Kindes nicht korrekt in den Gruppen abgebildet sein, wenden Sie sich bitte an das Sekretariat.
                                </p>
                            </div>
                        </div>
                </div>

                <!-- Dritte Reihe: ICAL und API-Token -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    @if($user->releaseCalendar == 1)
                        <div class="bg-white border border-gray-200 rounded-lg shadow-md overflow-hidden">
                            <div class="bg-gradient-to-r from-cyan-600 to-cyan-700 px-4 py-3 border-b border-cyan-800">
                                <h6 class="text-lg font-bold text-white mb-0 flex items-center gap-2">
                                    <i class="fas fa-calendar-alt"></i>
                                    ICAL-Kalender
                                </h6>
                            </div>
                            <div class="p-4">
                                <p class="text-sm text-gray-700 mb-3">
                                    <i class="fas fa-info-circle text-cyan-600 mr-1"></i>
                                    Die angegebene URL kann in den meisten Kalender-Anwendungen hinzugefügt werden, um die Termine direkt einzubinden.
                                </p>
                                <div class="p-3 bg-gray-50 border border-gray-300 rounded-lg">
                                    <code class="text-xs text-gray-800 break-all">{{config('app.url')."/".$user->uuid.'/ical'}}</code>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="bg-white border border-gray-200 rounded-lg shadow-md overflow-hidden">
                        <div class="bg-gradient-to-r from-orange-600 to-orange-700 px-4 py-3 border-b border-orange-800">
                            <h6 class="text-lg font-bold text-white mb-0 flex items-center gap-2">
                                <i class="fas fa-key"></i>
                                API-Token
                            </h6>
                        </div>

                        @if(session()->has('token'))
                            <div class="p-4 bg-green-50 border-b border-green-200">
                                <div class="flex items-start gap-2">
                                    <i class="fas fa-check-circle text-green-600 mt-1"></i>
                                    <div class="flex-1">
                                        <p class="text-sm text-green-800 font-medium mb-2">
                                            Das Token wurde erfolgreich erstellt. Bitte speichern Sie das Token an einem sicheren Ort. Es kann nicht noch einmal angezeigt werden.
                                        </p>
                                        <div class="p-3 bg-white border border-green-300 rounded-lg">
                                            <code class="text-xs text-gray-800 break-all">{{session('token')}}</code>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="p-4">
                            <p class="text-sm text-gray-700 mb-3">
                                <i class="fas fa-shield-alt text-orange-600 mr-1"></i>
                                Mit dem API-Token können externe Anwendungen auf die Daten-Schnittstellen zugreifen.
                            </p>

                            @if(auth()->user()->tokens->count() > 0)
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead class="bg-gray-100 border-b border-gray-300">
                                            <tr>
                                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700">Name</th>
                                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700">Erstellt am</th>
                                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-700">Aktionen</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            @foreach(auth()->user()->tokens as $token)
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-3 py-2 text-sm text-gray-800">{{$token->name}}</td>
                                                    <td class="px-3 py-2 text-sm text-gray-600">{{$token->created_at->format('d.m.Y')}}</td>
                                                    <td class="px-3 py-2 text-right">
                                                        <form action="{{url('/einstellungen/token/'.$token->id)}}" method="post" class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                    class="inline-flex items-center gap-1 px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg transition-colors">
                                                                <i class="fas fa-trash"></i>
                                                                <span>Löschen</span>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="flex items-start gap-3 p-3 bg-gray-50 border-l-4 border-gray-400 rounded mb-4">
                                    <i class="fas fa-info-circle text-gray-600 mt-1"></i>
                                    <p class="text-gray-700 text-sm mb-0">Keine API-Tokens vorhanden</p>
                                </div>
                            @endif
                        </div>

                        <div class="border-t border-gray-200 p-4 bg-gray-50">
                            <h6 class="text-sm font-semibold text-gray-800 mb-3">
                                <i class="fas fa-plus-circle text-orange-600 mr-1"></i>
                                Neues Token erstellen
                            </h6>
                            <form action="{{url('/einstellungen/token')}}" method="post" class="space-y-3">
                                @csrf
                                <div>
                                    <label for="name" class="block text-xs font-medium text-red-600 mb-1">Name*</label>
                                    <input id="name"
                                           type="text"
                                           class="w-full px-3 py-2 text-sm border-2 border-gray-300 rounded-lg focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition-all duration-200 outline-none"
                                           name="name"
                                           required>
                                </div>
                                <button type="submit"
                                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white font-semibold text-sm rounded-lg transition-colors duration-200">
                                    <i class="fas fa-plus"></i>
                                    <span>Token erstellen</span>
                                </button>
                            </form>
                        </div>
                        </div>
                    </div>
                </div>

                @if($user->sorg2 != null)
                    <div class="p-4 bg-amber-50 border-l-4 border-amber-500 rounded">
                        <div class="flex items-start gap-2">
                            <i class="fas fa-link text-amber-600 mt-1"></i>
                            <p class="text-sm text-amber-800 mb-0">
                                Das Konto ist verknüpft mit <strong>{{$user->sorgeberechtigter2?->name}}</strong>. Dadurch sind die Rückmeldungen in beiden Konten sichtbar.
                            </p>
                        </div>
                    </div>
                @endif
        </div> <!-- Ende p-4 -->
    </div> <!-- Ende card -->
</div> <!-- Ende container-fluid -->

    <!-- Modal zum Bearbeiten von Kindern -->
    <div class="modal fade" id="editChildModal" tabindex="-1" role="dialog" aria-labelledby="editChildModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content rounded-lg shadow-xl">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-4 py-3 rounded-t-lg">
                    <div class="flex items-center justify-between">
                        <h5 class="text-lg font-bold text-white mb-0 flex items-center gap-2" id="editChildModalLabel">
                            <i class="fas fa-edit"></i>
                            Kind bearbeiten
                        </h5>
                        <button type="button" class="text-white hover:text-gray-200 transition-colors" data-dismiss="modal" aria-label="Close">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>
                <div class="p-4">
                    <form id="editChildForm" method="POST" class="space-y-3">
                        @csrf
                        @method('PUT')
                        <div>
                            <label for="editFirstName" class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-user text-blue-600 mr-1"></i>
                                Vorname
                            </label>
                            <input type="text"
                                   class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                   id="editFirstName"
                                   name="first_name"
                                   required>
                        </div>
                        <div>
                            <label for="editLastName" class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-user text-blue-600 mr-1"></i>
                                Nachname
                            </label>
                            <input type="text"
                                   class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                   id="editLastName"
                                   name="last_name"
                                   required>
                        </div>
                        <div>
                            <label for="editGroup" class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-users text-blue-600 mr-1"></i>
                                Gruppe
                            </label>
                            <select class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                    id="editGroup"
                                    name="group_id"
                                    required>
                                @foreach(auth()->user()->groups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="editClass" class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-graduation-cap text-blue-600 mr-1"></i>
                                Klassenstufe
                            </label>
                            <select class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                    id="editClass"
                                    name="class_id"
                                    required>
                                @foreach(auth()->user()->groups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex gap-2 pt-3">
                            <button type="submit"
                                    class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors duration-200">
                                <i class="fas fa-save"></i>
                                <span>Speichern</span>
                            </button>
                            <button type="button"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg transition-colors duration-200"
                                    data-dismiss="modal">
                                Abbrechen
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')

    <script>
        $(document).ready(function () {
            $("input").keyup(function() {
                checkChanged();
            });
            $("select").change(function() {
                checkChanged();
            });

            function checkChanged() {
                if (!$('input').val()) {
                    $("#btn-save").hide();
                } else {
                    $("#btn-save").show();
                }
            }
        });

        $('.child-notification').click(function () {
            let child_id = $(this).data('child_id');
            let notification = $(this).data('notification');

            $.ajax({
                url: '/child/' + child_id + '/notification',
                type: 'POST',
                data: {
                    _token: '{{csrf_token()}}',
                    'child_id': child_id,
                    'notification': notification == 1 ? 0 : 1
                },
                success: function (data) {
                    if (data.notification == 1) {
                        $('.child-notification[data-child_id=' + child_id + ']')
                            .removeClass('bg-amber-500 hover:bg-amber-600')
                            .addClass('bg-teal-500 hover:bg-teal-600')
                            .html('<i class="fas fa-bell text-sm"></i>')
                            .attr('title', 'Benachrichtigung aktiv');
                        $('.child-notification[data-child_id=' + child_id + ']').data('notification', 1);
                    } else {
                        $('.child-notification[data-child_id=' + child_id + ']')
                            .removeClass('bg-teal-500 hover:bg-teal-600')
                            .addClass('bg-amber-500 hover:bg-amber-600')
                            .html('<i class="fas fa-bell-slash text-sm"></i>')
                            .attr('title', 'Benachrichtigung deaktiviert');
                        $('.child-notification[data-child_id=' + child_id + ']').data('notification', 0);
                    }
                },
                error: function (data) {
                    alert('Fehler beim Speichern');
                }
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            // Open the modal and populate the form with the child's data
            $('.edit-child-btn').click(function(e) {
                e.preventDefault();
                var childId = $(this).data('child-id');
                var child = $(this).data('child');

                $('#editChildForm').attr('action', '/child/' + childId);
                $('#editFirstName').val(child.first_name);
                $('#editLastName').val(child.last_name);
                $('#editGroup').val(child.group_id);
                $('#editClass').val(child.class_id);

                $('#editChildModal').modal('show');
            });
        });
    </script>
@endpush

@push('css')
    <style>
        .changelog-content {
            line-height: 1.75;
        }
        .changelog-content p {
            margin-bottom: 1rem;
        }
        .changelog-content p:last-child {
            margin-bottom: 0;
        }
        .changelog-content ul,
        .changelog-content ol {
            margin: 0.75rem 0;
            padding-left: 1.5rem;
        }
        .changelog-content li {
            margin-bottom: 0.5rem;
            line-height: 1.6;
        }
        .changelog-content li::marker {
            color: #f59e0b;
        }
        .changelog-content strong {
            color: #92400e;
            font-weight: 600;
        }
        .changelog-content a {
            color: #2563eb;
            text-decoration: underline;
            transition: color 0.2s;
        }
        .changelog-content a:hover {
            color: #1d4ed8;
        }
        .changelog-content h1,
        .changelog-content h2,
        .changelog-content h3,
        .changelog-content h4 {
            color: #92400e;
            font-weight: 700;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
        }
        .changelog-content h1:first-child,
        .changelog-content h2:first-child,
        .changelog-content h3:first-child,
        .changelog-content h4:first-child {
            margin-top: 0;
        }
        .changelog-content code {
            background: #fef3c7;
            padding: 0.125rem 0.375rem;
            border-radius: 0.25rem;
            font-size: 0.875em;
            color: #92400e;
        }
        .changelog-content blockquote {
            border-left: 4px solid #fbbf24;
            padding-left: 1rem;
            margin: 1rem 0;
            color: #78350f;
            font-style: italic;
        }
    </style>
@endpush

