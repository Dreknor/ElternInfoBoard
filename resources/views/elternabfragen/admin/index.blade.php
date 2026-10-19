@extends('layouts.app')

@section('title', '| Elternabfragen')

@section('content')
<div class="container-fluid px-4 py-6">
    @include('elternabfragen.admin._nav', ['subtitle' => 'Abfragen vorbereiten, freigeben, Rücklauf überwachen und exportieren'])

    <div class="flex flex-wrap items-end justify-between gap-3 mb-4">
        <form method="GET" class="flex flex-wrap items-end gap-2">
            <div>
                <label for="status" class="block text-xs text-gray-600">Status</label>
                <select id="status" name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" onchange="this.form.submit()">
                    <option value="">Alle nicht abgeschlossenen</option>
                    @foreach(\App\Enums\InquiryStatus::cases() as $case)
                        <option value="{{ $case->value }}" @selected($status === $case->value)>{{ $case->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="q" class="block text-xs text-gray-600">Suche</label>
                <input id="q" name="q" value="{{ request('q') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Titel">
            </div>
            <button class="px-3 py-2 rounded-lg bg-gray-100 text-sm hover:bg-gray-200">Filtern</button>
        </form>
        @can('manage elternabfragen')
            <a href="{{ route('elternabfragen.admin.inquiries.create') }}" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">
                <i class="fas fa-plus" aria-hidden="true"></i> Neue Abfrage
            </a>
        @endcan
    </div>

    @if($inquiries->isEmpty())
        <div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-500">
            <i class="fas fa-clipboard text-4xl mb-3 text-gray-300" aria-hidden="true"></i>
            <p>Keine Abfragen gefunden.</p>
            @can('manage elternabfragen')
                <p class="mt-1 text-sm">Abfragen entstehen aus freigegebenen <a class="text-blue-700 underline" href="{{ route('elternabfragen.admin.templates.index') }}">Vorlagen</a>.</p>
            @endcan
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="px-4 py-3">Abfrage</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Zeitraum</th>
                        <th class="px-4 py-3">Rücklauf</th>
                        <th class="px-4 py-3">Verantwortlich</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($inquiries as $inquiry)
                        @php
                            $c = $counts->get($inquiry->id, collect());
                            $total = $c->sum();
                            $answered = ($c['submitted'] ?? 0) + ($c['corrected'] ?? 0);
                            $percent = $total > 0 ? round($answered / $total * 100) : 0;
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('elternabfragen.admin.inquiries.show', $inquiry) }}" class="font-semibold text-blue-700 hover:underline">{{ $inquiry->title }}</a>
                                <div class="text-xs text-gray-500">{{ $inquiry->templateVersion?->label() }}</div>
                            </td>
                            <td class="px-4 py-3"><x-elternabfragen.badge :color="$inquiry->status->color()">{{ $inquiry->status->label() }}</x-elternabfragen.badge></td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ $inquiry->starts_at->format('d.m.Y') }} – {{ $inquiry->deadline_at->format('d.m.Y') }}
                                @if($inquiry->isRunning() && $inquiry->deadlinePassed())
                                    <span class="block text-xs text-red-700">Frist abgelaufen</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 min-w-[180px]">
                                @if($total > 0)
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100" aria-label="Rücklauf">
                                            <div class="h-2 bg-green-500" style="width: {{ $percent }}%"></div>
                                        </div>
                                        <span class="text-xs text-gray-700 whitespace-nowrap">{{ $answered }}/{{ $total }}</span>
                                    </div>
                                    @if(($c['problem'] ?? 0) > 0)
                                        <span class="text-xs text-red-700"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i> {{ $c['problem'] }} Problem(e)</span>
                                    @endif
                                @else
                                    <span class="text-xs text-gray-400">noch nicht gestartet</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $inquiry->responsible?->name ?? '–' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $inquiries->links() }}</div>
    @endif
</div>
@endsection
