@extends('layouts.app')

@section('title', '| Elternabfragen · '.$assignment->childName())

@section('content')
@php $input = 'block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm'; @endphp
<div class="container-fluid px-4 py-6 max-w-6xl">
    @include('elternabfragen.admin._nav', ['title' => $assignment->childName(), 'subtitle' => $inquiry->title.' · '.($assignment->group?->name ?? 'ohne Klasse')])

    <a href="{{ route('elternabfragen.admin.inquiries.show', $inquiry) }}" class="text-sm text-blue-700 hover:underline"><i class="fas fa-arrow-left" aria-hidden="true"></i> Zum Rücklauf</a>

    <div class="grid gap-6 lg:grid-cols-3 mt-4">
        <div class="lg:col-span-2 space-y-6">
            <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 text-sm space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                    <x-elternabfragen.badge :color="$assignment->status->color()">{{ $assignment->status->label() }}</x-elternabfragen.badge>
                    <span>Frist: {{ $assignment->effectiveDeadline()->format('d.m.Y H:i') }}{{ $assignment->extended_until ? ' (Nachfrist)' : '' }}</span>
                    @if($assignment->correction_until)<span>· Korrektur freigegeben bis {{ $assignment->correction_until->format('d.m.Y H:i') }}</span>@endif
                </div>
                @if($assignment->has_problem)<p class="p-2 rounded bg-red-50 text-red-800"><strong>Technisches Problem:</strong> {{ $assignment->problem_note }}</p>@endif
                @if($assignment->note)<p class="p-2 rounded bg-gray-50"><strong>Bearbeitungshinweis:</strong> {{ $assignment->note }}</p>@endif
                <div>
                    <p class="text-gray-500">Berechtigte Konten (Sorgerecht zum Startzeitpunkt)</p>
                    <ul>
                        @forelse($assignment->recipients as $recipient)
                            <li>{{ $recipient->user?->name }} · {{ $recipient->user?->email ?: 'keine E-Mail' }}
                                @if($recipient->delivery_failed_at)<span class="text-red-700">– nicht zustellbar: {{ $recipient->delivery_error }}</span>@endif
                            </li>
                        @empty
                            <li class="text-red-700">Keine berechtigten Konten.</li>
                        @endforelse
                    </ul>
                </div>
                @if($assignment->drafts->isNotEmpty())
                    <p class="text-gray-600">Entwurf begonnen von: {{ $assignment->drafts->pluck('user.name')->filter()->implode(', ') }}</p>
                @endif
            </section>

            <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="font-semibold text-gray-800 mb-3">Antwortversionen</h2>
                @forelse($assignment->responses as $response)
                    <div class="border rounded-lg p-3 mb-2 text-sm flex flex-wrap items-center justify-between gap-2 {{ $response->is_current ? 'border-green-300' : '' }}">
                        <div>
                            <strong>Version {{ $response->version }}</strong>
                            @if($response->is_current) <x-elternabfragen.badge color="green">gültig</x-elternabfragen.badge> @endif
                            <x-elternabfragen.badge :color="$response->review_status->color()">{{ $response->review_status->label() }}</x-elternabfragen.badge>
                            <span class="block text-gray-600">{{ $response->submitted_at->format('d.m.Y H:i') }} · {{ $response->user?->name ?? 'unbekannt' }} · {{ $response->channel === 'app' ? 'App' : 'Web' }}</span>
                            @if($response->correction_reason)<span class="block text-gray-500">Korrekturgrund: {{ $response->correction_reason }}</span>@endif
                        </div>
                        @if($canSeeAnswers)
                            <a href="{{ route('elternabfragen.admin.responses.show', $response) }}" class="px-3 py-1.5 rounded-lg border border-gray-300">Antwort ansehen</a>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Noch keine Antwort eingereicht.</p>
                @endforelse
                @unless($canSeeAnswers)
                    <p class="text-xs text-gray-500 mt-2">Antwortinhalte sind nur mit dem Recht „Antwortinhalte einsehen“ sichtbar.</p>
                @endunless
            </section>

            <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="font-semibold text-gray-800 mb-3">Zustellungen und Erinnerungen</h2>
                <ul class="text-sm divide-y divide-gray-100">
                    @forelse($assignment->deliveries as $delivery)
                        <li class="py-1.5 {{ $delivery->success ? '' : 'text-red-700' }}">
                            {{ $delivery->sent_at->format('d.m.Y H:i') }} · {{ $delivery->kindLabel() }} · {{ $delivery->channelLabel() }} · {{ $delivery->user?->name }}
                            {{ $delivery->trigger === 'manual' ? '(manuell)' : '' }}
                            @if(! $delivery->success) – {{ $delivery->error }} @endif
                        </li>
                    @empty
                        <li class="text-gray-500">–</li>
                    @endforelse
                </ul>
            </section>
        </div>

        <aside class="space-y-6">
            @can('manage elternabfragen')
                @if($inquiry->isRunning())
                    <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 text-sm space-y-4">
                        <h2 class="font-semibold text-gray-800">Bearbeiten</h2>
                        @unless($assignment->isAnswered())
                            <form method="POST" action="{{ route('elternabfragen.admin.assignments.remind', $inquiry) }}">@csrf
                                <input type="hidden" name="scope" value="selected"><input type="hidden" name="assignment_ids[]" value="{{ $assignment->id }}">
                                <button class="w-full px-3 py-2 rounded-lg bg-blue-600 text-white"><i class="fas fa-bell" aria-hidden="true"></i> Erinnern</button>
                            </form>
                            <form method="POST" action="{{ route('elternabfragen.admin.assignments.extend', $assignment) }}" class="space-y-2">@csrf
                                <label class="block text-gray-600">Individuelle Nachfrist</label>
                                <input type="datetime-local" name="extended_until" required class="{{ $input }}" value="{{ now()->addWeek()->setTime(23, 59)->format('Y-m-d\TH:i') }}">
                                <input name="reason" required class="{{ $input }}" placeholder="Grund / Rückfrage (Pflicht)">
                                <button class="w-full px-3 py-2 rounded-lg border border-gray-300">Nachfrist setzen</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('elternabfragen.admin.assignments.correction', $assignment) }}" class="space-y-2">@csrf
                                <label class="block text-gray-600">Korrektur durch Eltern freigeben bis</label>
                                <input type="datetime-local" name="correction_until" required class="{{ $input }}" value="{{ now()->addWeek()->setTime(23, 59)->format('Y-m-d\TH:i') }}">
                                <input name="reason" required class="{{ $input }}" placeholder="Grund (Pflicht)">
                                <button class="w-full px-3 py-2 rounded-lg border border-gray-300">Korrektur freigeben</button>
                            </form>
                        @endunless
                        @if($assignment->has_problem)
                            <form method="POST" action="{{ route('elternabfragen.admin.assignments.resolve', $assignment) }}" class="space-y-2">@csrf
                                <input name="note" class="{{ $input }}" placeholder="Lösung (optional)">
                                <button class="w-full px-3 py-2 rounded-lg border border-gray-300">Problem gelöst</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('elternabfragen.admin.assignments.problem', $assignment) }}" class="space-y-2">@csrf
                                <input name="note" required class="{{ $input }}" placeholder="z. B. E-Mail-Adresse ungültig">
                                <button class="w-full px-3 py-2 rounded-lg border border-red-300 text-red-700">Technisches Problem melden</button>
                            </form>
                        @endif
                    </section>
                @endif
                <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 text-sm">
                    <form method="POST" action="{{ route('elternabfragen.admin.assignments.note', $assignment) }}" class="space-y-2">@csrf
                        <label class="block font-semibold text-gray-800">Bearbeitungshinweis</label>
                        <textarea name="note" rows="3" required class="{{ $input }}" placeholder="z. B. Klärung widersprüchlicher Angaben mit beiden Elternteilen">{{ $assignment->note }}</textarea>
                        <button class="w-full px-3 py-2 rounded-lg border border-gray-300">Speichern</button>
                    </form>
                </section>
            @endcan

            <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="font-semibold text-gray-800 mb-2">Protokoll</h2>
                <ul class="text-xs text-gray-600 space-y-2">
                    @forelse($assignment->events as $event)
                        <li><span class="text-gray-800">{{ $event->actionLabel() }}</span> · {{ $event->created_at->format('d.m.Y H:i') }} · {{ $event->user?->name ?? 'System' }}
                            @if($event->note)<span class="block italic">{{ $event->note }}</span>@endif</li>
                    @empty
                        <li>–</li>
                    @endforelse
                </ul>
            </section>
        </aside>
    </div>
</div>
@endsection
