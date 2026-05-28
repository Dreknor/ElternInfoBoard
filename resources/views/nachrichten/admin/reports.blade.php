@extends('layouts.app')

@section('title', '| Gemeldete Beiträge')

@section('content')
<div class="container-fluid px-4 py-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                <i class="fas fa-flag text-red-600"></i>
                Gemeldete Beiträge
            </h1>
            <p class="text-sm text-gray-600 mt-1">Gemeldete Beiträge prüfen und bearbeiten</p>
        </div>
        <div class="text-sm text-gray-500">
            <span class="font-semibold text-gray-700">{{ $resolvedCount }}</span> bereits gelöst
        </div>
    </div>

    @if(session('Meldung'))
        <div class="mb-4 p-4 @if(session('type') == 'success') bg-green-50 border-l-4 border-green-500 text-green-800 @elseif(session('type') == 'danger') bg-red-50 border-l-4 border-red-500 text-red-800 @else bg-blue-50 border-l-4 border-blue-500 text-blue-800 @endif rounded-lg text-sm">
            {{ session('Meldung') }}
        </div>
    @endif

    @if($reports->isEmpty())
        <div class="bg-white rounded-xl shadow-md p-8 text-center">
            <i class="fas fa-check-circle text-green-400 text-5xl mb-4"></i>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Keine offenen Meldungen</h3>
            <p class="text-gray-500">Aktuell sind keine Beiträge gemeldet.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($reports as $report)
                <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-200">
                    <div class="p-5 flex flex-col md:flex-row md:items-start gap-4">
                        <div class="flex-1 min-w-0">
                            <!-- Gemeldet von -->
                            <div class="flex items-center gap-2 mb-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <i class="fas fa-flag mr-1"></i>Gemeldet
                                </span>
                                <span class="text-sm text-gray-600">
                                    von <strong>{{ $report->reporter?->name ?? 'Unbekannt' }}</strong>
                                    · {{ $report->created_at->diffForHumans() }}
                                </span>
                            </div>

                            <!-- Grund -->
                            <div class="mb-3">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Grund:</p>
                                <p class="text-sm text-gray-800 bg-orange-50 rounded px-3 py-2">{{ $report->reason }}</p>
                            </div>

                            <!-- Gemeldeter Beitrag -->
                            @if($report->post)
                                <div class="mb-3">
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Gemeldeter Beitrag:</p>
                                    <div class="bg-gray-50 rounded px-3 py-2 border border-gray-200">
                                        <p class="text-xs text-gray-500 mb-1">
                                            Von <strong>{{ $report->post->autor?->name ?? 'Unbekannt' }}</strong>
                                            · {{ $report->post->created_at?->format('d.m.Y H:i') }}
                                            @if($report->post->groups->isNotEmpty())
                                                · Gruppen: {{ $report->post->groups->pluck('name')->join(', ') }}
                                            @endif
                                        </p>
                                        <p class="font-semibold text-sm text-gray-800 mb-1">{{ $report->post->header }}</p>
                                        <p class="text-sm text-gray-700">{{ Str::limit(strip_tags($report->post->news), 200) }}</p>
                                        @if($report->post->trashed())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-200 text-gray-600 mt-1">
                                                <i class="fas fa-trash mr-1"></i> Bereits gelöscht
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="mb-3">
                                    <p class="text-sm text-gray-400 italic">Beitrag nicht mehr vorhanden.</p>
                                </div>
                            @endif
                        </div>

                        <!-- Aktionen -->
                        <div class="flex flex-col gap-2 flex-shrink-0">
                            <form action="{{ route('post-reports.resolve', $report) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                                    <i class="fas fa-check"></i> Erledigt
                                </button>
                            </form>

                            @if($report->post && !$report->post->trashed())
                                <a href="{{ url('/posts/edit/' . $report->post_id) }}"
                                   class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-lg transition-colors">
                                    <i class="fas fa-edit"></i> Bearbeiten
                                </a>

                                <a href="{{ route('post.find', $report->post_id) }}"
                                   class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors">
                                    <i class="fas fa-eye"></i> Anzeigen
                                </a>

                                <form action="{{ route('post-reports.destroy-post', $report) }}" method="POST"
                                      onsubmit="return confirm('Beitrag wirklich löschen? Alle offenen Meldungen werden als erledigt markiert.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                                        <i class="fas fa-trash"></i> Beitrag löschen
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $reports->links() }}
        </div>
    @endif
</div>
@endsection

