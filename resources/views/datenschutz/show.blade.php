@extends('layouts.app')
@section('title') – Datenschutz & Gespeicherte Daten @endsection

@section('content')
<div class="container-fluid px-4 py-3 space-y-6">

    {{-- ===== SEITENKOPF ===== --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-md overflow-hidden">
        <div class="bg-gradient-to-r from-blue-700 to-indigo-700 px-6 py-4">
            <h4 class="text-2xl font-bold text-white mb-0 flex items-center gap-3">
                <i class="fas fa-shield-alt"></i>
                Datenschutz &amp; gespeicherte Daten
            </h4>
            <p class="text-blue-200 text-sm mt-1 mb-0">Auskunft nach Art. 15 DSGVO für {{ $user->name }}</p>
        </div>
        <div class="p-5 bg-blue-50 border-b border-blue-200">
            <div class="flex items-start gap-3">
                <i class="fas fa-info-circle text-blue-600 text-lg mt-0.5 flex-shrink-0"></i>
                <div class="text-sm text-blue-900 leading-relaxed">
                    <p class="mb-2">
                        Diese Seite zeigt alle personenbezogenen Daten, die das <strong>{{ $settings->app_name ?? config('app.name') }}</strong> über Ihr Konto in der Datenbank gespeichert hat (Art. 15 DSGVO – Recht auf Auskunft).
                    </p>
                    <p class="mb-0">
                        <strong>Hinweis:</strong> Die dargestellten Informationen beziehen sich ausschließlich auf die in dieser Anwendung gespeicherten Daten. Schulintern darüber hinausgehende Daten (z.&nbsp;B. in anderen Systemen des Schulzentrums) sind hier nicht erfasst.
                        <strong>IP-Adressen werden von dieser Anwendung nicht gespeichert.</strong>
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== DATEN EXPORTIEREN ===== --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-md overflow-hidden">
        <div class="bg-gradient-to-r from-gray-700 to-gray-800 px-5 py-3">
            <h5 class="text-lg font-bold text-white mb-0 flex items-center gap-2">
                <i class="fas fa-download"></i>
                Daten exportieren
            </h5>
        </div>
        <div class="p-5">
            <p class="text-sm text-gray-700 mb-4">
                Sie können alle gespeicherten Daten herunterladen. Der Export entspricht dem Auskunftsrecht (Art.&nbsp;15 DSGVO) sowie dem Recht auf Datenübertragbarkeit (Art.&nbsp;20 DSGVO).
            </p>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('datenschutz.export.pdf') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-semibold text-sm rounded-lg transition-colors duration-200 shadow-sm">
                    <i class="fas fa-file-pdf"></i>
                    Als PDF herunterladen
                    <span class="text-xs font-normal opacity-80">(lesbar, Art. 15)</span>
                </a>
                <a href="{{ route('datenschutz.export.json') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-700 hover:bg-gray-800 text-white font-semibold text-sm rounded-lg transition-colors duration-200 shadow-sm">
                    <i class="fas fa-file-code"></i>
                    Als JSON herunterladen
                    <span class="text-xs font-normal opacity-80">(maschinenlesbar, Art. 20)</span>
                </a>
            </div>
            <p class="text-xs text-gray-400 mt-3 mb-0">
                <i class="fas fa-lock text-gray-400 mr-1"></i>
                Nur Sie als eingeloggter Nutzer können Ihre eigenen Daten exportieren.
            </p>
        </div>
    </div>

    {{-- ===== DSGVO-RECHTE ===== --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-md overflow-hidden">
        <div class="bg-gradient-to-r from-teal-600 to-cyan-600 px-5 py-3">
            <h5 class="text-lg font-bold text-white mb-0 flex items-center gap-2">
                <i class="fas fa-balance-scale"></i>
                Ihre Rechte nach der DSGVO
            </h5>
        </div>
        <div class="p-5">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                <div class="flex items-start gap-2">
                    <i class="fas fa-eye text-teal-600 mt-0.5 flex-shrink-0"></i>
                    <div><strong>Auskunft (Art. 15)</strong><br>
                        <span class="text-gray-600">Recht auf Auskunft über gespeicherte Daten – diese Seite.</span></div>
                </div>
                <div class="flex items-start gap-2">
                    <i class="fas fa-edit text-teal-600 mt-0.5 flex-shrink-0"></i>
                    <div><strong>Berichtigung (Art. 16)</strong><br>
                        <span class="text-gray-600">Unrichtige Daten können in den <a href="{{ url('/einstellungen') }}" class="text-teal-700 underline">Einstellungen</a> oder über das Sekretariat korrigiert werden.</span></div>
                </div>
                <div class="flex items-start gap-2">
                    <i class="fas fa-trash-alt text-teal-600 mt-0.5 flex-shrink-0"></i>
                    <div><strong>Löschung (Art. 17)</strong><br>
                        <span class="text-gray-600">Kontaktieren Sie das Sekretariat oder den Datenschutzbeauftragten des Schulzentrums.</span></div>
                </div>
                <div class="flex items-start gap-2">
                    <i class="fas fa-pause-circle text-teal-600 mt-0.5 flex-shrink-0"></i>
                    <div><strong>Einschränkung (Art. 18)</strong><br>
                        <span class="text-gray-600">Recht auf Einschränkung der Verarbeitung auf Antrag.</span></div>
                </div>
                <div class="flex items-start gap-2">
                    <i class="fas fa-file-export text-teal-600 mt-0.5 flex-shrink-0"></i>
                    <div><strong>Datenübertragbarkeit (Art. 20)</strong><br>
                        <span class="text-gray-600">Auf Anfrage können Ihre Daten in maschinenlesbarem Format bereitgestellt werden.</span></div>
                </div>
                <div class="flex items-start gap-2">
                    <i class="fas fa-hand-paper text-teal-600 mt-0.5 flex-shrink-0"></i>
                    <div><strong>Widerspruch (Art. 21)</strong><br>
                        <span class="text-gray-600">Sie können der Verarbeitung Ihrer Daten jederzeit widersprechen.</span></div>
                </div>
            </div>
            <div class="mt-4 p-3 bg-gray-50 border border-gray-200 rounded-lg text-xs text-gray-600">
                <i class="fas fa-envelope text-gray-500 mr-1"></i>
                Anfragen zu Ihren Datenschutzrechten richten Sie bitte an das Sekretariat oder den zuständigen Datenschutzbeauftragten.
            </div>
        </div>
    </div>

    {{-- ===== BENUTZERDATEN ===== --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-md overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-5 py-3">
            <h5 class="text-lg font-bold text-white mb-0 flex items-center gap-2">
                <i class="fas fa-user"></i>
                Benutzerkonto-Daten
            </h5>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700 w-1/3">Datenfeld</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Gespeicherter Inhalt</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-700"><i class="fas fa-signature text-indigo-400 mr-2"></i>Name</td>
                        <td class="px-5 py-3 text-gray-900">{{ $user->name }}</td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-700"><i class="fas fa-envelope text-indigo-400 mr-2"></i>E-Mail-Adresse</td>
                        <td class="px-5 py-3 text-gray-900">{{ $user->email }}</td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-700"><i class="fas fa-envelope-open text-indigo-400 mr-2"></i>Öffentliche E-Mail</td>
                        <td class="px-5 py-3 text-gray-900">
                            @if($user->publicMail)
                                {{ $user->publicMail }}
                            @else
                                <span class="text-gray-400 italic">nicht hinterlegt</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-700"><i class="fas fa-phone text-indigo-400 mr-2"></i>Öffentliche Telefonnummer</td>
                        <td class="px-5 py-3 text-gray-900">
                            @if($user->publicPhone)
                                {{ $user->publicPhone }}
                            @else
                                <span class="text-gray-400 italic">nicht hinterlegt</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-700"><i class="fas fa-lock text-indigo-400 mr-2"></i>Kennwort</td>
                        <td class="px-5 py-3 text-gray-600 italic">Das Kennwort ist nur als bcrypt-Hash gespeichert und kann nicht zurückgerechnet werden.</td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-700"><i class="fas fa-cookie-bite text-indigo-400 mr-2"></i>„Angemeldet bleiben"-Cookie</td>
                        <td class="px-5 py-3">
                            @if($user->remember_token != "")
                                <span class="inline-flex items-center gap-1 px-2 py-1 bg-amber-100 text-amber-800 text-xs rounded-full">
                                    <i class="fas fa-check-circle"></i> Token vorhanden
                                </span>
                                <span class="text-xs text-gray-500 ml-2">Ein verschlüsselter Wert liegt vor. Ob das Cookie auf Ihrem Gerät noch existiert, ist von hier aus nicht prüfbar.</span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                                    <i class="fas fa-times-circle"></i> Kein Cookie gespeichert
                                </span>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-700"><i class="fas fa-fingerprint text-indigo-400 mr-2"></i>Eindeutige Kennung (UUID)</td>
                        <td class="px-5 py-3 text-gray-500 font-mono text-xs">{{ $user->uuid }}</td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-700"><i class="fas fa-toggle-on text-indigo-400 mr-2"></i>Konto aktiv</td>
                        <td class="px-5 py-3">
                            @if($user->is_active)
                                <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full"><i class="fas fa-check"></i> Aktiv</span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full"><i class="fas fa-times"></i> Deaktiviert</span>
                                @if($user->deactivated_at)
                                    <span class="text-xs text-gray-500 ml-2">seit {{ $user->deactivated_at->format('d.m.Y H:i') }} Uhr</span>
                                @endif
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-700"><i class="fas fa-calendar-plus text-indigo-400 mr-2"></i>Konto erstellt</td>
                        <td class="px-5 py-3 text-gray-900">{{ $user->created_at->format('d.m.Y H:i:s') }} Uhr</td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-700"><i class="fas fa-calendar-edit text-indigo-400 mr-2"></i>Konto zuletzt geändert</td>
                        <td class="px-5 py-3 text-gray-900">{{ $user->updated_at->format('d.m.Y H:i:s') }} Uhr</td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-700"><i class="fas fa-sign-in-alt text-indigo-400 mr-2"></i>Letzter gespeicherter Login</td>
                        <td class="px-5 py-3">
                            @if($user->track_login)
                                @if($user->last_online_at)
                                    {{ $user->last_online_at->format('d.m.Y H:i:s') }} Uhr
                                @else
                                    <span class="text-gray-400 italic">noch nicht aufgezeichnet</span>
                                @endif
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full">
                                    <i class="fas fa-eye-slash"></i> Kein Login-Tracking gewünscht
                                </span>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-700"><i class="fas fa-bell text-indigo-400 mr-2"></i>E-Mail-Benachrichtigungen</td>
                        <td class="px-5 py-3 text-gray-900">
                            {{ $user->benachrichtigung == 'weekly' ? 'Wöchentlich' : 'Täglich' }}
                            @if($user->sendCopy)
                                &nbsp;·&nbsp; <span class="text-gray-600">Kopie versendeter Mails gewünscht</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-700"><i class="fas fa-envelope-open-text text-indigo-400 mr-2"></i>Letzte Info-E-Mail</td>
                        <td class="px-5 py-3 text-gray-900">
                            {{ $user->lastEmail?->format('d.m.Y H:i:s') ?? '–' }}
                            <span class="text-xs text-gray-400 ml-1">(Dringende Einzelmails werden nicht gespeichert)</span>
                        </td>
                    </tr>
                    @if($user->releaseCalendar)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-700"><i class="fas fa-calendar-alt text-indigo-400 mr-2"></i>Kalender-Freigabe</td>
                        <td class="px-5 py-3 text-gray-900">
                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full"><i class="fas fa-check"></i> Aktiviert</span>
                            @if($user->calendar_prefix)
                                &nbsp;· Prefix: <code class="text-xs bg-gray-100 px-1 rounded">{{ $user->calendar_prefix }}</code>
                            @endif
                        </td>
                    </tr>
                    @endif
                    @if($user->sorg2)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-700"><i class="fas fa-link text-indigo-400 mr-2"></i>Verknüpfter Sorgeberechtigter&nbsp;2</td>
                        <td class="px-5 py-3 text-gray-900">{{ $user->sorgeberechtigter2?->name }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== GRUPPEN & ROLLEN ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white border border-gray-200 rounded-lg shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-violet-600 to-purple-600 px-5 py-3">
                <h5 class="text-lg font-bold text-white mb-0 flex items-center gap-2">
                    <i class="fas fa-users"></i>
                    Zugeordnete Gruppen
                </h5>
            </div>
            <div class="p-4">
                @forelse($user->groups()->withoutGlobalScopes()->get() as $group)
                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-violet-50 text-violet-800 border border-violet-200 text-xs rounded-full m-1">
                        <i class="fas fa-users text-violet-400"></i>
                        {{ $group->name }}
                    </span>
                @empty
                    <p class="text-gray-400 italic text-sm">Keine Gruppen zugeordnet.</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-slate-600 to-gray-700 px-5 py-3">
                <h5 class="text-lg font-bold text-white mb-0 flex items-center gap-2">
                    <i class="fas fa-user-shield"></i>
                    Rollen &amp; Berechtigungen
                </h5>
            </div>
            <div class="p-4">
                @if($user->roles->count())
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Rollen</p>
                    @foreach($user->roles as $role)
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-slate-100 text-slate-700 border border-slate-300 text-xs rounded-full m-1">
                            <i class="fas fa-user-tag text-slate-500"></i>
                            {{ $role->name }}
                        </span>
                    @endforeach
                @endif
                @php $directPermissions = $user->getDirectPermissions(); @endphp
                @if($directPermissions->count())
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mt-3 mb-2">Direkte Berechtigungen</p>
                    @foreach($directPermissions as $perm)
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-amber-50 text-amber-800 border border-amber-200 text-xs rounded-full m-1">
                            <i class="fas fa-key text-amber-500"></i>
                            {{ $perm->name }}
                        </span>
                    @endforeach
                @endif
                @if(!$user->roles->count() && !$directPermissions->count())
                    <p class="text-gray-400 italic text-sm">Keine Rollen oder Berechtigungen zugewiesen.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- ===== VERKNÜPFTE KINDER ===== --}}
    @if($user->children_rel->count())
    <div class="bg-white border border-gray-200 rounded-lg shadow-md overflow-hidden">
        <div class="bg-gradient-to-r from-pink-600 to-rose-500 px-5 py-3">
            <h5 class="text-lg font-bold text-white mb-0 flex items-center gap-2">
                <i class="fas fa-child"></i>
                Verknüpfte Kinder / Schutzbefohlene
            </h5>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Name</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Gruppe / Klasse</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($user->children_rel as $child)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-900">{{ $child->first_name }} {{ $child->last_name }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $child->group?->name ?? '–' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ===== API-TOKENS ===== --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-md overflow-hidden">
        <div class="bg-gradient-to-r from-orange-600 to-amber-500 px-5 py-3">
            <h5 class="text-lg font-bold text-white mb-0 flex items-center gap-2">
                <i class="fas fa-key"></i>
                API-Token (App-Zugriff)
            </h5>
        </div>
        <div class="p-4">
            <p class="text-sm text-gray-600 mb-3">
                <i class="fas fa-info-circle text-orange-500 mr-1"></i>
                API-Tokens ermöglichen externen Anwendungen (z.&nbsp;B. der Schul-App) den Zugriff auf Ihre Daten.
                Token-Werte selbst werden nur als Hash gespeichert und können nicht zurückgerechnet werden.
            </p>
            @if($user->tokens->count())
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Name / Gerät</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Erstellt</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Zuletzt verwendet</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($user->tokens as $token)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 font-medium text-gray-900">{{ $token->name }}</td>
                                <td class="px-4 py-2 text-gray-600">{{ $token->created_at->format('d.m.Y H:i') }} Uhr</td>
                                <td class="px-4 py-2 text-gray-600">{{ $token->last_used_at?->format('d.m.Y H:i') . ' Uhr' ?? '–' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-400 italic text-sm">Keine API-Tokens vorhanden.</p>
            @endif
        </div>
    </div>

    {{-- ===== PUSH-REGISTRIERUNGEN ===== --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-md overflow-hidden">
        <div class="bg-gradient-to-r from-rose-600 to-pink-500 px-5 py-3">
            <h5 class="text-lg font-bold text-white mb-0 flex items-center gap-2">
                <i class="fas fa-bell"></i>
                Push-Benachrichtigungen
            </h5>
        </div>
        <div class="p-4 text-sm text-gray-700">
            <i class="fas fa-mobile-alt text-rose-500 mr-2"></i>
            Es
            @if($user->pushSubscriptions->count() == 1)
                wurde <strong>1 Gerät</strong>
            @elseif($user->pushSubscriptions->count() > 1)
                wurden <strong>{{ $user->pushSubscriptions->count() }} Geräte</strong>
            @else
                wurden <strong>keine Geräte</strong>
            @endif
            für Web-Push-Benachrichtigungen registriert.
            Die gespeicherten Endpunkte enthalten keine persönlich zuordenbaren Geräteinformationen.
        </div>
    </div>

    {{-- ===== KRANKMELDUNGEN ===== --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-md overflow-hidden">
        <div class="bg-gradient-to-r from-red-600 to-orange-500 px-5 py-3">
            <h5 class="text-lg font-bold text-white mb-0 flex items-center gap-2">
                <i class="fas fa-thermometer-half"></i>
                Krankmeldungen
            </h5>
        </div>
        @if($user->krankmeldungen->count())
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Kind</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Von</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Bis</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Meldung</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Eingereicht</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($user->krankmeldungen as $krankmeldung)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-900">{{ $krankmeldung->name }}</td>
                        <td class="px-5 py-3 text-gray-700">{{ $krankmeldung->start->format('d.m.Y') }}</td>
                        <td class="px-5 py-3 text-gray-700">{{ $krankmeldung->ende->format('d.m.Y') }}</td>
                        <td class="px-5 py-3 text-gray-700">{!! $krankmeldung->kommentar !!}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $krankmeldung->created_at->format('d.m.Y H:i') }} Uhr</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
            <div class="p-4 text-sm text-gray-400 italic">Keine Krankmeldungen gespeichert.</div>
        @endif
    </div>

    {{-- ===== LISTENEINTRAGUNGEN ===== --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-md overflow-hidden">
        <div class="bg-gradient-to-r from-amber-600 to-yellow-500 px-5 py-3">
            <h5 class="text-lg font-bold text-white mb-0 flex items-center gap-2">
                <i class="fas fa-clipboard-list"></i>
                Listeneintragungen
            </h5>
        </div>
        @php $listenTermine = $user->getListenTermine(); @endphp
        @if($listenTermine && $listenTermine->count())
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Liste</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Termin</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Anmerkung</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Reserviert am</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Geändert am</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($listenTermine as $eintrag)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-900">{{ $eintrag->liste->listenname }}</td>
                        <td class="px-5 py-3 text-gray-700">{{ $eintrag->termin?->format('d.m.Y H:i') ?? '–' }} Uhr</td>
                        <td class="px-5 py-3 text-gray-600">{{ $eintrag->comment ?? '–' }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $eintrag->created_at?->format('d.m.Y H:i') ?? '–' }} Uhr</td>
                        <td class="px-5 py-3 text-gray-600">{{ $eintrag->updated_at?->format('d.m.Y H:i') ?? '–' }} Uhr</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
            <div class="p-4 text-sm text-gray-400 italic">Keine Listeneintragungen gespeichert.</div>
        @endif
    </div>

    {{-- ===== SCHICKZEITEN ===== --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-md overflow-hidden">
        <div class="bg-gradient-to-r from-lime-600 to-green-500 px-5 py-3">
            <h5 class="text-lg font-bold text-white mb-0 flex items-center gap-2">
                <i class="fas fa-clock"></i>
                Schickzeiten
            </h5>
        </div>
        @php $schickzeiten = $user->schickzeiten()->withTrashed()->get(); @endphp
        @if($schickzeiten->count())
        <div class="p-3 bg-gray-50 border-b border-gray-200 text-xs text-gray-500">
            <i class="fas fa-info-circle mr-1"></i>
            Gelöschte Einträge sind <span class="bg-red-100 text-red-700 px-1 rounded">rot</span> markiert.
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Kind</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Wochentag</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Art</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Uhrzeit</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Erstellt</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Gelöscht</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($schickzeiten as $schickzeit)
                    <tr class="hover:bg-gray-50 {{ $schickzeit->deleted_at ? 'bg-red-50' : '' }}">
                        <td class="px-5 py-3 font-medium text-gray-900">{{ $schickzeit->child_name }}</td>
                        <td class="px-5 py-3 text-gray-700">
                            @switch($schickzeit->weekday)
                                @case('1') Montag @break
                                @case('2') Dienstag @break
                                @case('3') Mittwoch @break
                                @case('4') Donnerstag @break
                                @case('5') Freitag @break
                                @default {{ $schickzeit->weekday }}
                            @endswitch
                        </td>
                        <td class="px-5 py-3 text-gray-700">{{ $schickzeit->type }}</td>
                        <td class="px-5 py-3 text-gray-700">{{ $schickzeit->time?->format('H:i') ?? '–' }} Uhr</td>
                        <td class="px-5 py-3 text-gray-600">{{ $schickzeit->created_at?->format('d.m.Y H:i') ?? '–' }} Uhr</td>
                        <td class="px-5 py-3 text-gray-600">
                            @if($schickzeit->deleted_at)
                                <span class="text-red-600">{{ $schickzeit->deleted_at->format('d.m.Y H:i') }} Uhr</span>
                            @else
                                <span class="text-gray-400">–</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
            <div class="p-4 text-sm text-gray-400 italic">Keine Schickzeiten gespeichert.</div>
        @endif
    </div>

    {{-- ===== REINIGUNGSTERMINE ===== --}}
    @if($user->Reinigung->count())
    <div class="bg-white border border-gray-200 rounded-lg shadow-md overflow-hidden">
        <div class="bg-gradient-to-r from-cyan-600 to-teal-500 px-5 py-3">
            <h5 class="text-lg font-bold text-white mb-0 flex items-center gap-2">
                <i class="fas fa-broom"></i>
                Reinigungstermine
            </h5>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Datum</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Bereich / Aufgabe</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($user->Reinigung as $reinigung)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 text-gray-700">{{ $reinigung->datum?->format('d.m.Y') ?? '–' }}</td>
                        <td class="px-5 py-3 text-gray-900">{{ $reinigung->bereich }}: {{ $reinigung->aufgabe }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ===== PFLICHTSTUNDEN ===== --}}
    @php $pflichtstunden = $user->pflichtstunden()->withTrashed()->get(); @endphp
    @if($pflichtstunden->count())
    <div class="bg-white border border-gray-200 rounded-lg shadow-md overflow-hidden">
        <div class="bg-gradient-to-r from-emerald-600 to-green-600 px-5 py-3">
            <h5 class="text-lg font-bold text-white mb-0 flex items-center gap-2">
                <i class="fas fa-user-clock"></i>
                Pflichtstunden
            </h5>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Beschreibung</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Von</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Bis</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($pflichtstunden as $ps)
                    <tr class="hover:bg-gray-50 {{ $ps->deleted_at ? 'opacity-50' : '' }}">
                        <td class="px-5 py-3 text-gray-900">{{ $ps->description ?? '–' }}</td>
                        <td class="px-5 py-3 text-gray-700">{{ $ps->start?->format('d.m.Y H:i') ?? '–' }} Uhr</td>
                        <td class="px-5 py-3 text-gray-700">{{ $ps->end?->format('d.m.Y H:i') ?? '–' }} Uhr</td>
                        <td class="px-5 py-3">
                            @if($ps->approved)
                                <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full"><i class="fas fa-check"></i> Bestätigt</span>
                            @elseif($ps->rejected)
                                <span class="inline-flex items-center gap-1 px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full"><i class="fas fa-times"></i> Abgelehnt</span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-1 bg-amber-100 text-amber-800 text-xs rounded-full"><i class="fas fa-hourglass-half"></i> Ausstehend</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ===== RÜCKMELDUNGEN ===== --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-md overflow-hidden">
        <div class="bg-gradient-to-r from-sky-600 to-blue-500 px-5 py-3">
            <h5 class="text-lg font-bold text-white mb-0 flex items-center gap-2">
                <i class="fas fa-reply"></i>
                Rückmeldungen
            </h5>
        </div>
        @if($user->userRueckmeldung->count())
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Nachricht</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Ihre Rückmeldung</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Erstellt</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Geändert</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($user->userRueckmeldung as $rueckmeldung)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-900">{{ $rueckmeldung->nachricht->header }}</td>
                        <td class="px-5 py-3 text-gray-700">{!! $rueckmeldung->text !!}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $rueckmeldung->created_at?->format('d.m.Y H:i') ?? '–' }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $rueckmeldung->updated_at?->format('d.m.Y H:i') ?? '–' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
            <div class="p-4 text-sm text-gray-400 italic">Keine Rückmeldungen gespeichert.</div>
        @endif
    </div>

    {{-- ===== EIGENE BEITRÄGE ===== --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-md overflow-hidden">
        <div class="bg-gradient-to-r from-green-600 to-emerald-500 px-5 py-3">
            <h5 class="text-lg font-bold text-white mb-0 flex items-center gap-2">
                <i class="fas fa-newspaper"></i>
                Eigene Beiträge / Nachrichten
            </h5>
        </div>
        @if($user->own_posts->count())
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Überschrift</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Erstellt</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($user->own_posts as $post)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-900">{{ $post->header }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $post->created_at?->format('d.m.Y H:i') ?? '–' }} Uhr</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
            <div class="p-4 text-sm text-gray-400 italic">Keine eigenen Beiträge vorhanden.</div>
        @endif
    </div>

    {{-- ===== KOMMENTARE ===== --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-md overflow-hidden">
        <div class="bg-gradient-to-r from-slate-600 to-gray-600 px-5 py-3">
            <h5 class="text-lg font-bold text-white mb-0 flex items-center gap-2">
                <i class="fas fa-comments"></i>
                Kommentare
            </h5>
        </div>
        @if($user->comments->count())
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Beitrag / Objekt</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Kommentar</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Erstellt</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($user->comments as $comment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-900">{{ $comment->commentable?->header ?? '–' }}</td>
                        <td class="px-5 py-3 text-gray-700">{{ $comment->body }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $comment->created_at?->format('d.m.Y H:i') ?? '–' }} Uhr</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
            <div class="p-4 text-sm text-gray-400 italic">Keine Kommentare gespeichert.</div>
        @endif
    </div>

    {{-- ===== DISKUSSIONEN ===== --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-md overflow-hidden">
        <div class="bg-gradient-to-r from-violet-700 to-indigo-600 px-5 py-3">
            <h5 class="text-lg font-bold text-white mb-0 flex items-center gap-2">
                <i class="fas fa-users-rectangle"></i>
                Diskussionen (Elternratsbereich)
            </h5>
        </div>
        @if($user->discussions->count())
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Überschrift</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Beitrag</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Erstellt</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($user->discussions as $discussion)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-900">{{ $discussion->header }}</td>
                        <td class="px-5 py-3 text-gray-700">{!! $discussion->text !!}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $discussion->created_at->format('d.m.Y H:i') }} Uhr</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
            <div class="p-4 text-sm text-gray-400 italic">Keine Diskussionsbeiträge vorhanden.</div>
        @endif
    </div>

    {{-- ===== ABFRAGEN / ABSTIMMUNGEN ===== --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-md overflow-hidden">
        <div class="bg-gradient-to-r from-fuchsia-600 to-purple-500 px-5 py-3">
            <h5 class="text-lg font-bold text-white mb-0 flex items-center gap-2">
                <i class="fas fa-poll"></i>
                Abfragen / Abstimmungen
            </h5>
        </div>
        <div class="p-3 bg-fuchsia-50 border-b border-fuchsia-100 text-xs text-fuchsia-900">
            <i class="fas fa-shield-alt text-fuchsia-500 mr-1"></i>
            Es wird gespeichert, <strong>dass</strong> Sie an einer Abfrage teilgenommen haben – jedoch <strong>nicht</strong>, welche Antwort Sie gegeben haben. Die gewählten Optionen werden anonym ohne Benutzerreferenz abgelegt.
        </div>
        @if($user->pollVotes->count())
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Nachricht / Abfrage</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Abfrage-Titel</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Teilgenommen am</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Antwort</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($user->pollVotes()->with(['poll.post', 'poll'])->get() as $vote)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-900">{{ $vote->poll?->post?->header ?? '–' }}</td>
                        <td class="px-5 py-3 text-gray-700">{{ $vote->poll?->poll_name ?? '–' }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $vote->created_at?->format('d.m.Y H:i') ?? '–' }} Uhr</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 text-gray-500 text-xs rounded-full">
                                <i class="fas fa-eye-slash"></i> anonym gespeichert
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
            <div class="p-4 text-sm text-gray-400 italic">Keine Abstimmungen gespeichert.</div>
        @endif
    </div>

    {{-- ===== LESEBESTÄTIGUNGEN ===== --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-md overflow-hidden">
        <div class="bg-gradient-to-r from-blue-500 to-sky-500 px-5 py-3">
            <h5 class="text-lg font-bold text-white mb-0 flex items-center gap-2">
                <i class="fas fa-check-double"></i>
                Lesebestätigungen
            </h5>
        </div>
        <div class="p-3 bg-sky-50 border-b border-sky-100 text-xs text-sky-800">
            <i class="fas fa-info-circle mr-1"></i>
            Es wird nur gespeichert, dass Sie einen Beitrag gelesen haben – nicht wie oft oder zu welcher Tageszeit.
        </div>
        @if($user->read_receipts->count())
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Beitrag</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-700">Bestätigt am</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($user->read_receipts as $receipt)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-900">{{ $receipt->post?->header ?? '–' }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $receipt->created_at->format('d.m.Y H:i') }} Uhr</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
            <div class="p-4 text-sm text-gray-400 italic">Keine Lesebestätigungen vorhanden.</div>
        @endif
    </div>

    {{-- ===== HINWEIS AUDIT-LOG ===== --}}
    <div class="bg-amber-50 border border-amber-200 rounded-lg p-5 text-sm text-amber-900">
        <div class="flex items-start gap-3">
            <i class="fas fa-history text-amber-600 text-lg mt-0.5 flex-shrink-0"></i>
            <div>
                <strong class="block mb-1">Hinweis: Änderungsprotokoll (Audit-Log)</strong>
                Bestimmte Aktionen (z.&nbsp;B. Änderungen an Ihrem Benutzerkonto, Schickzeiten) werden in einem internen Änderungsprotokoll festgehalten. Dieses dient der Nachvollziehbarkeit und Sicherheit und wird ausschließlich von berechtigten Administratoren eingesehen. Auf Anfrage beim Datenschutzbeauftragten erhalten Sie auch darüber Auskunft.
            </div>
        </div>
    </div>

</div>
@endsection
