@extends('layouts.app')

@section('title', '| Elternabfragen · '.$inquiry->title)

@section('content')
@php
    $status = $inquiry->status->value;
    $rules = app(\App\Settings\ElternabfragenSetting::class);
    $fourEyesBlocked = $rules->four_eyes && (int) $inquiry->created_by === auth()->id();
    $total = $statusCounts->sum();
    $answered = ($statusCounts['submitted'] ?? 0) + ($statusCounts['corrected'] ?? 0);
    $input = 'block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm';
    $kindLabels = [
        'release' => 'Benachrichtigt', 'reminder_before' => 'Erinnert', 'reminder_after' => 'Erinnert', 'reminder_manual' => 'Erinnert',
    ];
@endphp
<div class="container-fluid px-4 py-6">
    @include('elternabfragen.admin._nav', ['title' => $inquiry->title, 'subtitle' => $inquiry->templateVersion->label()])

    {{-- Aktionen --}}
    <div class="flex flex-wrap gap-2 mb-6">
        <x-elternabfragen.badge :color="$inquiry->status->color()" class="text-sm">{{ $inquiry->status->label() }}</x-elternabfragen.badge>
        @canany(['manage elternabfragen', 'approve elternabfragen'])
            <a href="{{ route('elternabfragen.admin.inquiries.preview', $inquiry) }}" class="px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-sm"><i class="fas fa-eye" aria-hidden="true"></i> Elternvorschau</a>
        @endcanany
        @can('manage elternabfragen')
            @if($status === 'draft')
                <a href="{{ route('elternabfragen.admin.inquiries.edit', $inquiry) }}" class="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-sm">Bearbeiten</a>
                <form method="POST" action="{{ route('elternabfragen.admin.inquiries.submit', $inquiry) }}">@csrf
                    <button class="px-3 py-1.5 rounded-lg bg-green-600 text-white text-sm">Zur Freigabe einreichen</button>
                </form>
                <form method="POST" action="{{ route('elternabfragen.admin.inquiries.destroy', $inquiry) }}" onsubmit="return confirm('Entwurf löschen?')">@csrf @method('DELETE')
                    <button class="px-3 py-1.5 rounded-lg border border-red-300 text-red-700 text-sm">Löschen</button>
                </form>
            @endif
            @if($status === 'running')
                <form method="POST" action="{{ route('elternabfragen.admin.inquiries.sync', $inquiry) }}">@csrf
                    <button class="px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-sm" title="Neu hinzugekommene Kinder der Zielgruppe ergänzen"><i class="fas fa-sync" aria-hidden="true"></i> Zielgruppe abgleichen</button>
                </form>
            @endif
            @if(in_array($status, ['scheduled', 'running'], true))
                <a href="#beenden" class="px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-sm"><i class="fas fa-lock" aria-hidden="true"></i> {{ $inquiry->deadlinePassed() ? 'Abschließen' : 'Vorzeitig beenden' }}</a>
            @endif
            @if(in_array($status, ['scheduled', 'running', 'closed'], true))
                <form method="POST" action="{{ route('elternabfragen.admin.inquiries.duplicate', $inquiry) }}">@csrf
                    <button class="px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-sm" title="Inhaltliche Änderungen nur als neue Abfrage"><i class="fas fa-copy" aria-hidden="true"></i> Als neue Abfrage kopieren</button>
                </form>
            @endif
        @endcan
        @can('export elternabfragen')
            @if($inquiry->isReleased() && ! $inquiry->purged_at)
                <a href="{{ route('elternabfragen.admin.exports.create', $inquiry) }}" class="px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-sm"><i class="fas fa-file-export" aria-hidden="true"></i> Export</a>
            @endif
        @endcan
    </div>

    @if($inquiry->review_note)
        <p class="mb-4 p-3 rounded-lg bg-gray-50 border text-sm"><strong>Prüfvermerk:</strong> {{ $inquiry->review_note }}</p>
    @endif
    @if($inquiry->purged_at)
        <p class="mb-4 p-3 rounded-lg bg-slate-100 text-sm">Antworten wurden am {{ $inquiry->purged_at->format('d.m.Y') }} nach Ablauf der Aufbewahrungsfrist gelöscht.</p>
    @endif

    <div class="grid gap-6 xl:grid-cols-4">
        <div class="xl:col-span-3 space-y-6">
            {{-- Kennzahlen --}}
            @if($inquiry->isReleased())
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <a href="{{ route('elternabfragen.admin.inquiries.show', $inquiry) }}" class="bg-white rounded-xl border p-3 text-center hover:border-blue-400">
                        <div class="text-2xl font-bold text-gray-800">{{ $answered }}/{{ $total }}</div><div class="text-xs text-gray-500">Rücklauf</div>
                    </a>
                    @foreach($statusOptions as $value => $text)
                        <a href="{{ route('elternabfragen.admin.inquiries.show', [$inquiry, 'status' => $value]) }}"
                           class="bg-white rounded-xl border p-3 text-center hover:border-blue-400 {{ request('status') === $value ? 'ring-2 ring-blue-500' : '' }}">
                            <div class="text-2xl font-bold {{ $value === 'problem' && ($statusCounts[$value] ?? 0) > 0 ? 'text-red-700' : 'text-gray-800' }}">{{ $statusCounts[$value] ?? 0 }}</div>
                            <div class="text-xs text-gray-500">{{ $text }}</div>
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Vorschau vor dem Start --}}
            @if($preview)
                <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <h2 class="font-semibold text-gray-800 mb-3">Erwartete Zielgruppe</h2>
                    <div class="grid grid-cols-2 gap-3 mb-3 text-center">
                        <div class="rounded-lg bg-gray-50 p-3"><div class="text-2xl font-bold">{{ $preview['children'] }}</div><div class="text-xs text-gray-500">Kinder</div></div>
                        <div class="rounded-lg bg-gray-50 p-3"><div class="text-2xl font-bold">{{ $preview['accounts'] }}</div><div class="text-xs text-gray-500">berechtigte Elternkonten</div></div>
                    </div>
                    @if($preview['by_group'])
                        <p class="text-sm text-gray-600 mb-2">Nach Klasse: {{ collect($preview['by_group'])->map(fn ($n, $g) => $g.': '.$n)->implode(' · ') }}</p>
                    @endif
                    @if($preview['without_accounts'])
                        <p class="text-sm text-red-800 bg-red-50 p-2 rounded mb-2"><strong>{{ count($preview['without_accounts']) }} Kinder ohne sorgeberechtigtes Konto</strong> (werden als technisches Problem markiert): {{ implode(', ', $preview['without_accounts']) }}</p>
                    @endif
                    @if($preview['without_email'] && $inquiry->notify_mail)
                        <p class="text-sm text-amber-800 bg-amber-50 p-2 rounded">Ohne E-Mail-Adresse: {{ implode(', ', $preview['without_email']) }}</p>
                    @endif
                </section>
            @endif

            {{-- Freigabe --}}
            @if($status === 'pending_approval')
                @can('approve elternabfragen')
                    <section id="pruefung" class="bg-white rounded-xl shadow-sm border-2 border-amber-300 p-5 scroll-mt-4">
                        <h2 class="font-semibold text-gray-800 mb-2">Verbindliche Freigabe</h2>
                        @if($fourEyesBlocked)
                            <p class="text-sm text-amber-800">Vier-Augen-Prinzip: Diese Abfrage muss von einer anderen Person freigegeben werden.</p>
                        @else
                            <form method="POST" action="{{ route('elternabfragen.admin.inquiries.approve', $inquiry) }}" class="space-y-3">
                                @csrf
                                <label class="flex items-start gap-2 text-sm">
                                    <input type="checkbox" name="confirm" value="1" class="mt-1" required>
                                    Zielgruppe, betroffene Kinder, Frist, Absender, Pflichtfelder, Hinweise und Benachrichtigungskanäle sind geprüft
                                    (<a href="{{ route('elternabfragen.admin.inquiries.preview', $inquiry) }}" class="text-blue-700 underline" target="_blank">Elternvorschau</a>).
                                </label>
                                <textarea name="note" rows="2" class="{{ $input }}" placeholder="Prüfvermerk (optional)"></textarea>
                                <button class="px-4 py-2 rounded-lg bg-green-600 text-white text-sm font-semibold">
                                    {{ $inquiry->starts_at->isPast() ? 'Freigeben und jetzt starten' : 'Freigeben (Start '.$inquiry->starts_at->format('d.m.Y H:i').')' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('elternabfragen.admin.inquiries.reject', $inquiry) }}" class="mt-4 space-y-2 border-t pt-3">
                                @csrf
                                <textarea name="note" rows="2" required class="{{ $input }}" placeholder="Was soll überarbeitet werden?"></textarea>
                                <button class="px-4 py-2 rounded-lg border border-red-300 text-red-700 text-sm">Zur Überarbeitung zurückgeben</button>
                            </form>
                        @endif
                    </section>
                @endcan
            @endif

            {{-- Rücklauf --}}
            @if($inquiry->isReleased() && ! $inquiry->purged_at)
                <section class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <form method="GET" class="p-4 border-b flex flex-wrap items-end gap-2">
                        <div>
                            <label for="f_status" class="block text-xs text-gray-600">Status</label>
                            <select id="f_status" name="status" class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
                                <option value="">alle</option>
                                @foreach($statusOptions as $value => $text)
                                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $text }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="f_klasse" class="block text-xs text-gray-600">Klasse</label>
                            <select id="f_klasse" name="klasse" class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
                                <option value="">alle</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}" @selected((int) request('klasse') === $group->id)>{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="f_frist" class="block text-xs text-gray-600">Frist</label>
                            <select id="f_frist" name="frist" class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
                                <option value="">alle</option>
                                <option value="nachfrist" @selected(request('frist') === 'nachfrist')>mit Nachfrist</option>
                            </select>
                        </div>
                        <div>
                            <label for="f_q" class="block text-xs text-gray-600">Kind oder Elternkonto</label>
                            <input id="f_q" name="q" value="{{ request('q') }}" class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
                        </div>
                        <button class="px-3 py-1.5 rounded-lg bg-gray-100 text-sm">Filtern</button>
                    </form>

                    <form method="POST" action="{{ route('elternabfragen.admin.assignments.remind', $inquiry) }}" x-data="{ selected: [] }">
                        @csrf
                        @can('manage elternabfragen')
                            @if($inquiry->isRunning())
                                <div class="p-4 border-b flex flex-wrap items-center gap-2 text-sm">
                                    <button name="scope" value="open" class="px-3 py-1.5 rounded-lg bg-blue-600 text-white" onclick="return confirm('Alle offenen Fälle erinnern? Fälle innerhalb der Wartezeit werden übersprungen.')">
                                        <i class="fas fa-bell" aria-hidden="true"></i> Alle offenen erinnern
                                    </button>
                                    <button name="scope" value="selected" class="px-3 py-1.5 rounded-lg border border-gray-300" :disabled="selected.length === 0">
                                        Ausgewählte erinnern (<span x-text="selected.length"></span>)
                                    </button>
                                    <span class="text-xs text-gray-500">Regeln: erste Erinnerung frühestens nach {{ $rules->first_reminder_after_hours }} h, danach höchstens alle {{ $rules->reminder_interval_hours }} h, max. {{ $rules->max_reminders }}×.</span>
                                </div>
                            @endif
                        @endcan
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 text-left text-gray-600">
                                    <tr>
                                        <th class="px-3 py-2"><span class="sr-only">Auswahl</span></th>
                                        <th class="px-3 py-2">Kind</th>
                                        <th class="px-3 py-2">Klasse</th>
                                        <th class="px-3 py-2">Status</th>
                                        <th class="px-3 py-2">Berechtigte Konten</th>
                                        <th class="px-3 py-2">Letzte Benachrichtigung</th>
                                        <th class="px-3 py-2">Hinweise</th>
                                        <th class="px-3 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse($assignments as $assignment)
                                        @php
                                            $deliveries = $lastDeliveries->get($assignment->id, collect());
                                            $last = $deliveries->sortByDesc('sent_at')->first();
                                            $blocked = $reminderBlocks[$assignment->id] ?? null;
                                        @endphp
                                        <tr class="{{ $assignment->has_problem ? 'bg-red-50' : '' }}">
                                            <td class="px-3 py-2">
                                                @if(! $assignment->isComplete())
                                                    <input type="checkbox" name="assignment_ids[]" value="{{ $assignment->id }}" x-model="selected" aria-label="{{ $assignment->childName() }} auswählen" @disabled($blocked !== null) title="{{ $blocked }}">
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 font-medium">{{ $assignment->childName() }}</td>
                                            <td class="px-3 py-2">{{ $assignment->group?->name ?? '–' }}</td>
                                            <td class="px-3 py-2">
                                                <x-elternabfragen.badge :color="$assignment->status->color()">{{ $assignment->status->label() }}</x-elternabfragen.badge>
                                                @if($assignment->currentResponse)
                                                    <span class="block text-xs text-gray-500">{{ $assignment->currentResponse->submitted_at->format('d.m. H:i') }} · v{{ $assignment->currentResponse->version }} · {{ $assignment->currentResponse->review_status->label() }}</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-xs">
                                                @forelse($assignment->recipients as $recipient)
                                                    <span class="block {{ $recipient->delivery_failed_at ? 'text-red-700' : '' }}" title="{{ $recipient->delivery_error }}">
                                                        {{ $recipient->user?->name }} @if($recipient->delivery_failed_at)<i class="fas fa-exclamation-triangle" aria-label="nicht zustellbar"></i>@endif
                                                    </span>
                                                @empty
                                                    <span class="text-red-700">keine</span>
                                                @endforelse
                                            </td>
                                            <td class="px-3 py-2 text-xs whitespace-nowrap">
                                                @if($last)
                                                    {{ $kindLabels[$last->kind] ?? $last->kind }} {{ \Carbon\Carbon::parse($last->sent_at)->format('d.m. H:i') }}
                                                    @if($assignment->reminder_count) <span class="block text-gray-500">{{ $assignment->reminder_count }}× erinnert</span> @endif
                                                @else – @endif
                                            </td>
                                            <td class="px-3 py-2 text-xs max-w-xs">
                                                @if($assignment->extended_until)<span class="block">Nachfrist bis {{ $assignment->extended_until->format('d.m.Y') }}</span>@endif
                                                @if($assignment->has_problem)<span class="block text-red-700">{{ \Illuminate\Support\Str::limit($assignment->problem_note, 80) }}</span>@endif
                                                @if($assignment->note)<span class="block text-gray-600">{{ \Illuminate\Support\Str::limit($assignment->note, 80) }}</span>@endif
                                            </td>
                                            <td class="px-3 py-2 text-right"><a href="{{ route('elternabfragen.admin.assignments.show', $assignment) }}" class="text-blue-700 hover:underline">Details</a></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8" class="px-3 py-6 text-center text-gray-500">Keine Fälle für diesen Filter.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </form>
                </section>
            @endif
        </div>

        <aside class="space-y-6">
            <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 text-sm space-y-2">
                <h2 class="font-semibold text-gray-800">Eckdaten</h2>
                <p><span class="text-gray-500">Vorlage:</span> <a href="{{ route('elternabfragen.admin.templates.show', $inquiry->templateVersion) }}" class="text-blue-700 hover:underline">{{ $inquiry->templateVersion->label() }}</a></p>
                <p><span class="text-gray-500">Zielgruppe:</span> {{ $inquiry->targetLabel() }}</p>
                <p><span class="text-gray-500">Start:</span> {{ $inquiry->starts_at->format('d.m.Y H:i') }}</p>
                <p><span class="text-gray-500">Frist:</span> {{ $inquiry->deadline_at->format('d.m.Y H:i') }}</p>
                <p><span class="text-gray-500">Verantwortlich:</span> {{ $inquiry->responsible?->name ?? '–' }}</p>
                <p><span class="text-gray-500">Kanäle:</span> {{ collect([$inquiry->notify_mail ? 'E-Mail' : null, $inquiry->notify_push ? 'App/Web-Push' : null])->filter()->implode(', ') ?: '–' }}</p>
                <p><span class="text-gray-500">Automatische Erinnerungen:</span> {{ $inquiry->auto_reminders ? 'ja' : 'nein' }}</p>
                <p><span class="text-gray-500">Für Mitarbeitende lesbar:</span> {{ $inquiry->staff_visible ? 'ja (nur geprüfte Antworten)' : 'nein' }}</p>
                <p><span class="text-gray-500">Selbstkorrektur:</span> {{ $inquiry->correction_window_days }} Tage</p>
                <p><span class="text-gray-500">Aufbewahrung:</span> {{ $inquiry->retention_days ? $inquiry->retention_days.' Tage nach Abschluss' : 'unbefristet' }}</p>
                @if($inquiry->approved_at)<p><span class="text-gray-500">Freigegeben:</span> {{ $inquiry->approved_at->format('d.m.Y H:i') }} von {{ $inquiry->approver?->name }}</p>@endif
                @if($inquiry->expected_children !== null)<p><span class="text-gray-500">Erwartet:</span> {{ $inquiry->expected_children }} Kinder / {{ $inquiry->expected_accounts }} Konten</p>@endif
                @if($inquiry->closed_at)<p><span class="text-gray-500">Abgeschlossen:</span> {{ $inquiry->closed_at->format('d.m.Y H:i') }}@if($inquiry->closedEarly()) <span class="text-amber-700">(vorzeitig)</span>@endif</p>@endif
            </section>

            @can('manage elternabfragen')
                @if(in_array($status, ['scheduled', 'running'], true))
                    <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 text-sm">
                        <h2 class="font-semibold text-gray-800 mb-2">Frist ändern</h2>
                        <form method="POST" action="{{ route('elternabfragen.admin.inquiries.deadline', $inquiry) }}" class="space-y-2">
                            @csrf
                            <input type="datetime-local" name="deadline_at" required value="{{ $inquiry->deadline_at->format('Y-m-d\TH:i') }}" class="{{ $input }}" aria-label="Neue Frist">
                            <input name="reason" required maxlength="1000" class="{{ $input }}" placeholder="Begründung (wird protokolliert)">
                            <button class="w-full px-3 py-2 rounded-lg border border-gray-300">Frist ändern</button>
                        </form>
                    </section>
                    <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 text-sm">
                        <h2 class="font-semibold text-gray-800 mb-2">Einstellungen</h2>
                        <form method="POST" action="{{ route('elternabfragen.admin.inquiries.settings', $inquiry) }}" class="space-y-2">
                            @csrf @method('PUT')
                            <select name="responsible_user_id" class="{{ $input }}" aria-label="Verantwortliche Person">
                                <option value="">–</option>
                                @foreach($users as $u)<option value="{{ $u->id }}" @selected($inquiry->responsible_user_id === $u->id)>{{ $u->name }}</option>@endforeach
                            </select>
                            <label class="block">Selbstkorrektur (Tage) <input type="number" name="correction_window_days" min="0" max="365" value="{{ $inquiry->correction_window_days }}" class="{{ $input }}"></label>
                            <label class="flex items-center gap-2"><input type="checkbox" name="notify_mail" value="1" @checked($inquiry->notify_mail)> E-Mail</label>
                            <label class="flex items-center gap-2"><input type="checkbox" name="notify_push" value="1" @checked($inquiry->notify_push)> App/Web-Push</label>
                            <label class="flex items-center gap-2"><input type="checkbox" name="auto_reminders" value="1" @checked($inquiry->auto_reminders)> Automatische Erinnerungen</label>
                            <label class="flex items-center gap-2"><input type="checkbox" name="staff_visible" value="1" @checked($inquiry->staff_visible)> Für Mitarbeitende lesbar</label>
                            <button class="w-full px-3 py-2 rounded-lg border border-gray-300">Speichern</button>
                        </form>
                    </section>
                    <section id="beenden" class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 text-sm scroll-mt-4">
                        <h2 class="font-semibold text-gray-800 mb-2">{{ $inquiry->deadlinePassed() ? 'Abfrage abschließen' : 'Abfrage vorzeitig beenden' }}</h2>
                        <p class="text-gray-600 mb-2">
                            @if($status === 'scheduled')
                                Die Abfrage wird nicht gestartet, Eltern werden nicht benachrichtigt.
                            @else
                                Danach sind keine Abgaben, Korrekturen, Bestätigungen und Erinnerungen mehr möglich. Bisherige Antworten bleiben erhalten.
                            @endif
                        </p>
                        <form method="POST" action="{{ route('elternabfragen.admin.inquiries.close', $inquiry) }}" class="space-y-2"
                              onsubmit="return confirm('Abfrage jetzt beenden? Das kann nicht rückgängig gemacht werden.')">
                            @csrf
                            <input name="note" maxlength="1000" class="{{ $input }}" placeholder="Begründung (optional, wird protokolliert)">
                            <button class="w-full px-3 py-2 rounded-lg border border-red-300 text-red-700"><i class="fas fa-lock" aria-hidden="true"></i> Jetzt beenden</button>
                        </form>
                    </section>
                @endif
            @endcan

            <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="font-semibold text-gray-800 mb-2">Protokoll</h2>
                <ul class="text-xs text-gray-600 space-y-2">
                    @forelse($events as $event)
                        <li>
                            <span class="text-gray-800">{{ $event->actionLabel() }}</span> · {{ $event->created_at->format('d.m.Y H:i') }} · {{ $event->user?->name ?? 'System' }}
                            @if($event->note)<span class="block italic">{{ $event->note }}</span>@endif
                        </li>
                    @empty
                        <li>–</li>
                    @endforelse
                </ul>
            </section>
        </aside>
    </div>
</div>
@endsection
