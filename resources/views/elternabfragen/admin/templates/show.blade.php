@extends('layouts.app')

@section('title', '| Elternabfragen · '.$version->title)

@section('content')
@php
    $template = $version->template;
    $status = $version->status;
    $fourEyesBlocked = app(\App\Settings\ElternabfragenSetting::class)->four_eyes && (int) $version->created_by === auth()->id();
    $fieldTypes = \App\Enums\InquiryFieldType::options();
@endphp
<div class="container-fluid px-4 py-6 max-w-6xl">
    @include('elternabfragen.admin._nav', ['title' => $version->title, 'subtitle' => 'Vorlage · Version '.$version->version.($template->category ? ' · '.$template->category : '')])

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="flex flex-wrap items-center gap-2 mb-4">
                    <x-elternabfragen.badge :color="$status->color()">{{ $status->label() }}</x-elternabfragen.badge>
                    @if($version->is_sensitive) <x-elternabfragen.badge color="red" icon="fas fa-user-shield">sensible Inhalte</x-elternabfragen.badge> @endif
                    @if($template->isArchived()) <x-elternabfragen.badge color="slate">archiviert</x-elternabfragen.badge> @endif
                    <a href="{{ route('elternabfragen.admin.templates.preview', $version) }}" class="ml-auto px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-sm"><i class="fas fa-eye" aria-hidden="true"></i> Elternvorschau</a>
                </div>
                @if($version->review_note)
                    <p class="mb-4 p-3 rounded-lg bg-gray-50 text-sm"><strong>Prüfvermerk:</strong> {{ $version->review_note }}</p>
                @endif
                <dl class="grid gap-3 sm:grid-cols-2 text-sm">
                    <div class="sm:col-span-2"><dt class="text-gray-500">Zweck</dt><dd class="whitespace-pre-line">{{ $version->purpose }}</dd></div>
                    @if($version->parent_info)<div class="sm:col-span-2"><dt class="text-gray-500">Elterninformation</dt><dd class="whitespace-pre-line">{{ $version->parent_info }}</dd></div>@endif
                    <div><dt class="text-gray-500">Zuständige Stelle</dt><dd>{{ $version->responsible_office ?? '–' }}</dd></div>
                    <div><dt class="text-gray-500">Zielgruppe</dt><dd>{{ $version->target_hint ?? '–' }}</dd></div>
                    <div><dt class="text-gray-500">Standardfrist</dt><dd>{{ $version->default_deadline_days }} Tage</dd></div>
                    <div><dt class="text-gray-500">Aufbewahrung</dt><dd>{{ $version->retention_days ? $version->retention_days.' Tage nach Abschluss' : 'Standard' }}</dd></div>
                    @if($version->retention_note)<div class="sm:col-span-2"><dt class="text-gray-500">Hinweise zu Aufbewahrung und Löschung</dt><dd class="whitespace-pre-line">{{ $version->retention_note }}</dd></div>@endif
                </dl>
            </section>

            <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="font-semibold text-gray-800 mb-3">Formularfelder ({{ count($version->inputFields()) }} Eingaben)</h2>
                <ol class="space-y-3">
                    @foreach($version->fields as $field)
                        <li class="border border-gray-200 rounded-lg p-3 text-sm {{ $field['type'] === 'info' ? 'bg-gray-50' : '' }}">
                            <div class="flex flex-wrap items-center gap-2">
                                <strong>{{ $field['label'] }}</strong>
                                <span class="text-xs text-gray-500">{{ $fieldTypes[$field['type']] ?? $field['type'] }}</span>
                                @if($field['required'] ?? false) <x-elternabfragen.badge color="amber">Pflicht</x-elternabfragen.badge> @endif
                                @if($field['format'] ?? null) <span class="text-xs text-gray-500">Format: {{ \App\Services\Elternabfragen\FieldSchema::FORMATS[$field['format']] ?? $field['format'] }}</span> @endif
                                @if(($field['min_length'] ?? null) || ($field['max_length'] ?? null)) <span class="text-xs text-gray-500">{{ $field['min_length'] ?? 0 }}–{{ $field['max_length'] ?? '∞' }}</span> @endif
                            </div>
                            @if($field['help'] ?? null)<p class="text-gray-600 mt-1">{{ $field['help'] }}</p>@endif
                            @if(! empty($field['options']))<p class="text-gray-600 mt-1">Optionen: {{ implode(' · ', $field['options']) }}</p>@endif
                            @if($field['consent_text'] ?? null)<p class="text-gray-600 mt-1 whitespace-pre-line bg-gray-50 p-2 rounded">{{ $field['consent_text'] }}</p>@endif
                            @if($field['error_message'] ?? null)<p class="text-xs text-gray-500 mt-1">Fehlermeldung: {{ $field['error_message'] }}</p>@endif
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 space-y-3">
                <h2 class="font-semibold text-gray-800">Aktionen</h2>
                @can('manage elternabfragen')
                    @if($status->isEditable())
                        <a href="{{ route('elternabfragen.admin.templates.edit', $version) }}" class="block text-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold">Bearbeiten</a>
                        <form method="POST" action="{{ route('elternabfragen.admin.templates.submit', $version) }}">@csrf
                            <button class="w-full px-4 py-2 rounded-lg bg-green-600 text-white text-sm font-semibold">Zur Prüfung einreichen</button>
                        </form>
                    @elseif(in_array($status->value, ['approved', 'superseded'], true) && ! $template->isArchived())
                        @if($status->value === 'approved')
                            <a href="{{ route('elternabfragen.admin.inquiries.create', ['vorlage' => $version->id]) }}" class="block text-center px-4 py-2 rounded-lg bg-green-600 text-white text-sm font-semibold">Abfrage aus dieser Vorlage erstellen</a>
                        @endif
                        <form method="POST" action="{{ route('elternabfragen.admin.templates.new-version', $template) }}">@csrf
                            <button class="w-full px-4 py-2 rounded-lg border border-gray-300 text-sm">Neue Version anlegen</button>
                        </form>
                    @endif
                    @unless($template->isArchived())
                        <form method="POST" action="{{ route('elternabfragen.admin.templates.archive', $template) }}" onsubmit="return confirm('Vorlage archivieren?')">@csrf
                            <button class="w-full px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-600">Archivieren</button>
                        </form>
                    @endunless
                @endcan

                @if($status->value === 'in_review')
                    @can('approve elternabfragen')
                        @if($fourEyesBlocked)
                            <p class="text-sm text-amber-800 bg-amber-50 p-3 rounded-lg">Vier-Augen-Prinzip: Diese Version muss von einer anderen Person freigegeben werden.</p>
                        @else
                            <form method="POST" action="{{ route('elternabfragen.admin.templates.approve', $version) }}" class="space-y-2 border-t pt-3">
                                @csrf
                                <p class="text-sm text-gray-700">Pflichtfelder, Antwortoptionen und Verständlichkeit geprüft?</p>
                                @if($version->is_sensitive)
                                    <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="privacy_checked" value="1" class="mt-1" required> Datenschutzrechtliche bzw. schulinterne Zusatzprüfung ist erfolgt.</label>
                                @endif
                                <textarea name="note" rows="2" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Prüfvermerk (optional)"></textarea>
                                <button class="w-full px-4 py-2 rounded-lg bg-green-600 text-white text-sm font-semibold">Freigeben</button>
                            </form>
                            <form method="POST" action="{{ route('elternabfragen.admin.templates.reject', $version) }}" class="space-y-2 border-t pt-3">
                                @csrf
                                <textarea name="note" rows="2" required class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Was soll überarbeitet werden?"></textarea>
                                <button class="w-full px-4 py-2 rounded-lg border border-red-300 text-red-700 text-sm">Zur Überarbeitung zurückgeben</button>
                            </form>
                        @endif
                    @endcan
                @endif
            </section>

            <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="font-semibold text-gray-800 mb-2">Versionen</h2>
                <ul class="text-sm divide-y divide-gray-100">
                    @foreach($template->versions as $v)
                        <li class="py-2 flex items-center justify-between gap-2">
                            <a href="{{ route('elternabfragen.admin.templates.show', $v) }}" class="{{ $v->id === $version->id ? 'font-bold' : 'text-blue-700 hover:underline' }}">Version {{ $v->version }}</a>
                            <x-elternabfragen.badge :color="$v->status->color()">{{ $v->status->label() }}</x-elternabfragen.badge>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="font-semibold text-gray-800 mb-2">Protokoll</h2>
                <ul class="text-xs text-gray-600 space-y-2">
                    @foreach($events as $event)
                        <li>
                            <span class="text-gray-800">{{ $event->actionLabel() }}</span>
                            · {{ $event->created_at->format('d.m.Y H:i') }} · {{ $event->user?->name ?? 'System' }}
                            @if($event->note)<span class="block italic">{{ $event->note }}</span>@endif
                        </li>
                    @endforeach
                </ul>
            </section>
        </aside>
    </div>
</div>
@endsection

@if(session('templateDraftSaved'))
    @push('js')
    <script>
        try { localStorage.removeItem(@js(session('templateDraftSaved'))); } catch (e) {}
    </script>
    @endpush
@endif
