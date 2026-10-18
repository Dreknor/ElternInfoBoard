@extends('layouts.app')

@section('title', '| Elternabfragen · Vorlagen')

@section('content')
<div class="container-fluid px-4 py-6">
    @include('elternabfragen.admin._nav', ['title' => 'Vorlagen', 'subtitle' => 'Geprüfte, versionierte Vorlagen für wiederkehrende Abfragen'])

    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div class="flex gap-2 text-sm">
            <a href="{{ route('elternabfragen.admin.templates.index') }}" class="px-3 py-1.5 rounded-lg {{ $archive ? 'bg-white border' : 'bg-blue-600 text-white' }}">Aktiv</a>
            <a href="{{ route('elternabfragen.admin.templates.index', ['archiv' => 1]) }}" class="px-3 py-1.5 rounded-lg {{ $archive ? 'bg-blue-600 text-white' : 'bg-white border' }}">Archiv</a>
        </div>
        @can('manage elternabfragen')
            <a href="{{ route('elternabfragen.admin.templates.create') }}" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">
                <i class="fas fa-plus" aria-hidden="true"></i> Neue Vorlage
            </a>
        @endcan
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-600">
                <tr>
                    <th class="px-4 py-3">Vorlage</th>
                    <th class="px-4 py-3">Kategorie</th>
                    <th class="px-4 py-3">Freigegeben</th>
                    <th class="px-4 py-3">Bearbeitung</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($templates as $template)
                    @php $latest = $template->latestVersion; $approved = $template->approvedVersion; @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('elternabfragen.admin.templates.show', $latest) }}" class="font-semibold text-blue-700 hover:underline">{{ $template->title }}</a>
                            @if($latest?->is_sensitive) <x-elternabfragen.badge color="red" icon="fas fa-user-shield">sensibel</x-elternabfragen.badge> @endif
                        </td>
                        <td class="px-4 py-3">{{ $template->category ?? '–' }}</td>
                        <td class="px-4 py-3">{{ $approved ? 'Version '.$approved->version : '–' }}</td>
                        <td class="px-4 py-3">
                            @if($latest && $latest->id !== $approved?->id)
                                <x-elternabfragen.badge :color="$latest->status->color()">v{{ $latest->version }}: {{ $latest->status->label() }}</x-elternabfragen.badge>
                            @else
                                <span class="text-gray-400">–</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if($approved && ! $archive)
                                @can('manage elternabfragen')
                                    <a href="{{ route('elternabfragen.admin.inquiries.create', ['vorlage' => $approved->id]) }}" class="px-3 py-1.5 rounded-lg bg-green-600 text-white text-xs font-semibold hover:bg-green-700">Abfrage erstellen</a>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Keine Vorlagen vorhanden.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
