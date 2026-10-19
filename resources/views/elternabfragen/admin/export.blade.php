@extends('layouts.app')

@section('title', '| Elternabfragen · Export')

@section('content')
@php $inputCls = 'block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm'; @endphp
<div class="container-fluid px-4 py-6 max-w-5xl">
    @include('elternabfragen.admin._nav', ['title' => 'Export', 'subtitle' => $inquiry->title])

    <a href="{{ route('elternabfragen.admin.inquiries.show', $inquiry) }}" class="text-sm text-blue-700 hover:underline"><i class="fas fa-arrow-left" aria-hidden="true"></i> Zum Rücklauf</a>

    @if(session('download'))
        <div class="my-4 p-4 rounded-lg bg-green-50 border border-green-300 text-sm">
            <a href="{{ route('elternabfragen.admin.exports.download', session('download')) }}" class="font-semibold text-green-900 underline"><i class="fas fa-download" aria-hidden="true"></i> Exportdatei herunterladen</a>
        </div>
    @endif

    <form method="POST" action="{{ route('elternabfragen.admin.exports.preview', $inquiry) }}" class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 my-4 space-y-4">
        @csrf
        <div class="grid gap-4 md:grid-cols-3">
            <div>
                <label for="group_id" class="block text-sm font-medium text-gray-700 mb-1">Ebene</label>
                <select id="group_id" name="group_id" class="{{ $inputCls }}">
                    <option value="">gesamte Abfrage</option>
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}" @selected((int) ($input['group_id'] ?? 0) === $group->id)>Klasse {{ $group->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="format" class="block text-sm font-medium text-gray-700 mb-1">Format</label>
                <select id="format" name="format" class="{{ $inputCls }}">
                    <option value="xlsx" @selected($input['format'] === 'xlsx')>Excel (XLSX)</option>
                    <option value="csv" @selected($input['format'] === 'csv')>CSV (Semikolon)</option>
                </select>
            </div>
            <label class="flex items-center gap-2 text-sm mt-6"><input type="checkbox" name="include_open" value="1" @checked($input['include_open'])> auch offene Fälle (ohne Antwort)</label>
        </div>
        <fieldset>
            <legend class="text-sm font-medium text-gray-700 mb-1">Felder</legend>
            <div class="grid gap-1 sm:grid-cols-2 lg:grid-cols-3 text-sm">
                @foreach($columns as $key => $label)
                    <label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="{{ $key }}" @checked(in_array($key, $input['columns'], true))> {{ $label }}</label>
                @endforeach
            </div>
        </fieldset>
        <div>
            <label for="target" class="block text-sm font-medium text-gray-700 mb-1">Ziel des Exports</label>
            <input id="target" name="target" maxlength="255" value="{{ $input['target'] }}" class="{{ $inputCls }}" placeholder="z. B. Übertragung in die Schulverwaltung (manuell)">
        </div>
        <button class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold">Vorschau anzeigen</button>
    </form>

    @if($preview)
        <section class="bg-white rounded-xl shadow-sm border-2 border-blue-300 p-5 mb-6">
            <h2 class="font-semibold text-gray-800 mb-3">Vorschau vor dem Herunterladen</h2>
            <dl class="grid gap-2 sm:grid-cols-2 text-sm mb-4">
                <div><dt class="text-gray-500">Datensätze</dt><dd class="text-xl font-bold">{{ $preview['records'] }}</dd></div>
                <div><dt class="text-gray-500">Antwortversion</dt><dd>{{ $preview['version'] }}</dd></div>
                <div><dt class="text-gray-500">Ausgewählte Felder</dt><dd>{{ implode(', ', $preview['columns']) }}</dd></div>
                <div><dt class="text-gray-500">Ziel / verantwortlich</dt><dd>{{ $input['target'] ?: '– (bitte angeben)' }} / {{ auth()->user()->name }}</dd></div>
            </dl>
            <ul class="text-sm space-y-1 mb-4">
                @if($preview['open'])<li class="text-amber-800">{{ $preview['open'] }} ohne Antwort</li>@endif
                @if($preview['missing'])<li class="text-red-700">{{ $preview['missing'] }} mit fehlenden Pflichtangaben</li>@endif
                @if($preview['clarification'])<li class="text-amber-800">{{ $preview['clarification'] }} mit Klärungsbedarf</li>@endif
                @if($preview['unreviewed'])<li class="text-gray-700">{{ $preview['unreviewed'] }} noch ungeprüft</li>@endif
            </ul>
            <form method="POST" action="{{ route('elternabfragen.admin.exports.store', $inquiry) }}">
                @csrf
                @foreach($input['columns'] as $column)<input type="hidden" name="columns[]" value="{{ $column }}">@endforeach
                <input type="hidden" name="group_id" value="{{ $input['group_id'] }}">
                <input type="hidden" name="format" value="{{ $input['format'] }}">
                <input type="hidden" name="include_open" value="{{ $input['include_open'] ? 1 : 0 }}">
                <input type="hidden" name="target" value="{{ $input['target'] }}">
                <button class="px-4 py-2 rounded-lg bg-green-600 text-white text-sm font-semibold" @disabled(! $input['target'])>Export erstellen (protokolliert)</button>
            </form>
        </section>
    @endif

    <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <h2 class="font-semibold text-gray-800 mb-2">Exportprotokoll</h2>
        <ul class="text-sm divide-y divide-gray-100">
            @forelse($exports as $export)
                <li class="py-2 flex flex-wrap justify-between gap-2">
                    <span>{{ $export->created_at->format('d.m.Y H:i') }} · {{ $export->creator?->name }} · {{ $export->record_count }} Datensätze · {{ strtoupper($export->format) }} · Ziel: {{ $export->target }}
                        · {{ $export->download_count }}× geladen</span>
                    @if($export->isAvailable())
                        <a href="{{ route('elternabfragen.admin.exports.download', $export) }}" class="text-blue-700 underline">Herunterladen (bis {{ $export->expires_at->format('d.m.Y') }})</a>
                    @else
                        <span class="text-gray-400">Datei gelöscht</span>
                    @endif
                </li>
            @empty
                <li class="py-2 text-gray-500">Noch keine Exporte.</li>
            @endforelse
        </ul>
    </section>
</div>
@endsection
