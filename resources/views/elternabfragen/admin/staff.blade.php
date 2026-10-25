@extends('layouts.app')

@section('title', '| Elternabfragen · Antworten')

@section('content')
<div class="container-fluid px-4 py-6">
    @include('elternabfragen.admin._nav', ['title' => 'Antworten', 'subtitle' => 'Lesender Zugriff auf geprüfte Antworten von Abfragen, die für Mitarbeitende freigegeben sind', 'icon' => 'fas fa-eye'])

    <form method="GET" class="ea-card p-4 mb-5 flex flex-wrap items-end gap-3">
        <div class="min-w-[200px]">
            <label for="abfrage" class="ea-label">Abfrage</label>
            <select id="abfrage" name="abfrage" class="ea-input">
                <option value="">alle</option>
                @foreach($inquiries as $inquiry)
                    <option value="{{ $inquiry->id }}" @selected((int) request('abfrage') === $inquiry->id)>{{ $inquiry->title }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="kind" class="ea-label">Schüler/in</label>
            <input id="kind" name="kind" value="{{ request('kind') }}" class="ea-input" placeholder="Vor- oder Nachname">
        </div>
        <div>
            <label for="inhalt" class="ea-label">Inhalt</label>
            <input id="inhalt" name="inhalt" value="{{ request('inhalt') }}" class="ea-input" placeholder="z. B. Fahrrad">
        </div>
        <div>
            <label for="zustand" class="ea-label">Zustand</label>
            <select id="zustand" name="zustand" class="ea-input">
                <option value="">alle</option>
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected(request('zustand') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="ea-btn ea-btn-secondary"><i class="fas fa-filter" aria-hidden="true"></i> Filtern</button>
    </form>

    <div class="ea-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="ea-table">
                <thead>
                    <tr><th>Schüler/in</th><th>Klasse</th><th>Abfrage</th><th>Eingang</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($responses as $response)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="ea-avatar !w-8 !h-8 !text-xs">{{ mb_strtoupper(mb_substr($response->child?->first_name ?? '?', 0, 1).mb_substr($response->child?->last_name ?? '', 0, 1)) }}</span>
                                    <span class="font-medium">{{ $response->child?->first_name }} {{ $response->child?->last_name }}</span>
                                </div>
                            </td>
                            <td>{{ $response->child?->class?->name ?? '–' }}</td>
                            <td>{{ $response->inquiry?->title }}</td>
                            <td class="whitespace-nowrap">{{ $response->submitted_at->format('d.m.Y') }} · v{{ $response->version }}</td>
                            <td class="text-right"><a href="{{ route('elternabfragen.admin.responses.show', $response) }}" class="ea-btn ea-btn-ghost ea-btn-sm">Ansehen <i class="fas fa-chevron-right" aria-hidden="true"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="ea-empty"><div class="ea-empty-icon"><i class="fas fa-search" aria-hidden="true"></i></div>Keine freigegebenen Antworten gefunden.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-5">{{ $responses->links() }}</div>
</div>
@endsection
