@extends('layouts.app')

@section('title', '| Moderationscenter')

@section('content')
<div class="container-fluid px-4 py-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                <i class="fas fa-shield-alt text-red-600"></i>
                Nachrichten-Moderation
            </h1>
            <p class="text-sm text-gray-600 mt-1">Gemeldete Nachrichten prüfen und bearbeiten</p>
        </div>
        <div class="text-sm text-gray-500">
            <span class="font-semibold text-gray-700">{{ $resolvedCount }}</span> bereits gelöst
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 rounded-lg text-green-800 text-sm">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    @if($reports->isEmpty())
        <div class="bg-white rounded-lg shadow-lg p-12 text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-check-circle text-3xl text-green-600"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-1">Keine offenen Meldungen</h3>
            <p class="text-gray-500 text-sm">Alle Meldungen wurden bearbeitet.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($reports as $report)
            <div class="bg-white rounded-lg shadow border border-red-100 p-5">
                <div class="flex items-start justify-between gap-4">
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

                        <!-- Gemeldete Nachricht -->
                        @if($report->message)
                        <div class="mb-3">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Gemeldete Nachricht:</p>
                            <div class="bg-gray-50 rounded px-3 py-2 border border-gray-200">
                                <p class="text-xs text-gray-500 mb-1">
                                    Von <strong>{{ $report->message->sender?->name ?? 'Unbekannt' }}</strong>
                                    · {{ $report->message->created_at?->format('d.m.Y H:i') }}
                                </p>
                                <p class="text-sm text-gray-800">{{ $report->message->trashed() ? '[Nachricht bereits gelöscht]' : $report->message->body }}</p>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Aktionen -->
                    <div class="flex flex-col gap-2 flex-shrink-0">
                        <form action="{{ route('messenger.admin.resolve', $report) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-check mr-1"></i>Erledigt
                            </button>
                        </form>

                        @if($report->message && !$report->message->trashed())
                        <form action="{{ route('messenger.delete', $report->message) }}" method="POST"
                              onsubmit="return confirm('Nachricht löschen?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-trash mr-1"></i>Löschen
                            </button>
                        </form>
                        @endif

                        @if($report->message?->sender)
                        <button type="button"
                                onclick="openMuteModal({{ $report->message->sender->id }}, '{{ addslashes($report->message->sender->name) }}')"
                                class="w-full px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-microphone-slash mr-1"></i>Stummschalten
                        </button>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-4">{{ $reports->links() }}</div>
    @endif
</div>

{{-- Modal: User stumm schalten --}}
<div id="muteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm">
        <div class="flex items-center justify-between p-4 border-b border-gray-200">
            <h3 class="font-bold text-gray-800"><i class="fas fa-microphone-slash mr-2 text-amber-500"></i>User stummschalten</h3>
            <button onclick="document.getElementById('muteModal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form id="muteForm" method="POST" class="p-4">
            @csrf
            <p id="muteName" class="text-sm text-gray-700 mb-3"></p>
            <label class="block text-sm font-medium text-gray-700 mb-2">Dauer (Stunden):</label>
            <input type="number" name="hours" value="24" min="1" max="720"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 outline-none text-sm mb-4">
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('muteModal').classList.add('hidden')"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm transition-colors">Abbrechen</button>
                <button type="submit"
                        class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-sm transition-colors">Stummschalten</button>
            </div>
        </form>
    </div>
</div>

@push('js')
<script>
function openMuteModal(userId, userName) {
    document.getElementById('muteForm').action = '/messenger/admin/user/' + userId + '/mute';
    document.getElementById('muteName').textContent = userName + ' in allen Gruppenkonversationen stummschalten';
    document.getElementById('muteModal').classList.remove('hidden');
}
</script>
@endpush
@endsection

