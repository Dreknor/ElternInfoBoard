@extends('layouts.app')

@section('title', '| Elternabfragen · Antworten')

@section('content')
<div class="container-fluid px-4 py-6">
    @include('elternabfragen.admin._nav', ['title' => 'Antworten', 'subtitle' => 'Lesender Zugriff auf geprüfte Antworten von Abfragen, die für Mitarbeitende freigegeben sind'])

    <form method="GET" class="flex flex-wrap items-end gap-2 mb-4">
        <div>
            <label for="abfrage" class="block text-xs text-gray-600">Abfrage</label>
            <select id="abfrage" name="abfrage" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="">alle</option>
                @foreach($inquiries as $inquiry)
                    <option value="{{ $inquiry->id }}" @selected((int) request('abfrage') === $inquiry->id)>{{ $inquiry->title }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="kind" class="block text-xs text-gray-600">Schüler/in</label>
            <input id="kind" name="kind" value="{{ request('kind') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="inhalt" class="block text-xs text-gray-600">Inhalt</label>
            <input id="inhalt" name="inhalt" value="{{ request('inhalt') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="z. B. Fahrrad">
        </div>
        <div>
            <label for="zustand" class="block text-xs text-gray-600">Zustand</label>
            <select id="zustand" name="zustand" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="">alle</option>
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected(request('zustand') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="px-3 py-2 rounded-lg bg-gray-100 text-sm">Filtern</button>
    </form>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-600">
                <tr><th class="px-4 py-3">Schüler/in</th><th class="px-4 py-3">Klasse</th><th class="px-4 py-3">Abfrage</th><th class="px-4 py-3">Eingang</th><th class="px-4 py-3"></th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($responses as $response)
                    <tr>
                        <td class="px-4 py-3">{{ $response->child?->first_name }} {{ $response->child?->last_name }}</td>
                        <td class="px-4 py-3">{{ $response->child?->class?->name ?? '–' }}</td>
                        <td class="px-4 py-3">{{ $response->inquiry?->title }}</td>
                        <td class="px-4 py-3">{{ $response->submitted_at->format('d.m.Y') }} · v{{ $response->version }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('elternabfragen.admin.responses.show', $response) }}" class="text-blue-700 hover:underline">Ansehen</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Keine freigegebenen Antworten gefunden.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $responses->links() }}</div>
</div>
@endsection
