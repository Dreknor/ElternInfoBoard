@extends('layouts.app')
@section('title') - Sicherheitsprotokoll @endsection

@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
        <div class="bg-gradient-to-r from-gray-700 to-gray-900 px-6 py-4">
            <div class="flex items-center gap-3">
                <i class="fas fa-shield-alt text-white text-2xl"></i>
                <div>
                    <h5 class="text-xl font-bold text-white mb-0">Sicherheitsprotokoll</h5>
                    <p class="text-gray-300 text-sm mb-0">Nur Ansicht &ndash; {{ $events->total() }} {{ $events->total() === 1 ? 'Eintrag' : 'Einträge' }}</p>
                </div>
            </div>
        </div>

        <div class="bg-amber-50 border-b border-amber-200 px-6 py-4 text-sm text-amber-900">
            <div class="flex items-start gap-3">
                <i class="fas fa-info-circle text-amber-600 mt-0.5"></i>
                <div>
                    <strong>Zweckbindung:</strong>
                    Dieses Protokoll darf ausschließlich der Aufklärung von Sicherheitsvorfällen dienen (Art. 32 DSGVO).
                    Eine Auswertung zur Leistungs- oder Verhaltenskontrolle ist nicht zulässig.
                    Jeder Aufruf dieser Seite wird selbst protokolliert.
                    Einträge werden nach {{ $retentionDays }} Tagen gelöscht, IP-Adressen und Browserkennungen nach {{ $anonymizeAfterDays }} Tagen gekürzt bzw. entfernt.
                    Das Protokoll kann über die Oberfläche weder gelöscht noch exportiert werden.
                </div>
            </div>
        </div>

        <div class="bg-gray-50 border-b border-gray-200 px-6 py-4">
            <form method="GET" action="{{ url('/security-log') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-tags text-gray-500 mr-1"></i> Ereignis
                    </label>
                    <select name="event" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                        <option value="">Alle Ereignisse</option>
                        @foreach($eventOptions as $value => $label)
                            <option value="{{ $value }}" {{ request('event') === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-user text-gray-500 mr-1"></i> Konto-ID
                    </label>
                    <input type="number" min="1" name="user_id" value="{{ request('user_id') }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-calendar text-gray-500 mr-1"></i> Von
                    </label>
                    <input type="date" name="from" value="{{ request('from') }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-calendar text-gray-500 mr-1"></i> Bis
                    </label>
                    <input type="date" name="to" value="{{ request('to') }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-network-wired text-gray-500 mr-1"></i> IP-Adresse
                    </label>
                    <input type="text" name="ip" value="{{ request('ip') }}" placeholder="Anfang der IP ..." class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                </div>
                <div class="flex items-end gap-2 lg:col-span-6">
                    <button type="submit" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                        <i class="fas fa-filter"></i> Filtern
                    </button>
                    <a href="{{ url('/security-log') }}" title="Filter zurücksetzen" class="inline-flex items-center justify-center px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium rounded-lg transition-colors duration-200">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Zeitpunkt</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ereignis</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Konto</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ausgelöst durch</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Browser</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kontext</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($events as $event)
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">{{ $event->created_at?->format('d.m.Y H:i:s') }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                <div class="font-medium">{{ \App\Http\Controllers\SecurityLogController::eventLabel($event->event) }}</div>
                                <div class="text-xs text-gray-500">{{ $event->event }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                @if($event->user_id)
                                    {{ $names[$event->user_id] ?? 'gelöschtes Konto' }} <span class="text-gray-500">(ID {{ $event->user_id }})</span>
                                @else
                                    <span class="text-gray-400">&ndash;</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                @if($event->actor_id && $event->actor_id !== $event->user_id)
                                    {{ $names[$event->actor_id] ?? 'gelöschtes Konto' }} <span class="text-gray-500">(ID {{ $event->actor_id }})</span>
                                @else
                                    <span class="text-gray-400">&ndash;</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $event->ip_address ?? '–' }}</td>
                            <td class="px-4 py-3 text-xs text-gray-600" title="{{ $event->user_agent }}">{{ $event->user_agent ? \Illuminate\Support\Str::limit($event->user_agent, 40) : '–' }}</td>
                            <td class="px-4 py-3 text-xs text-gray-700">
                                @forelse((array) $event->context as $key => $value)
                                    <div>{{ $key }}={{ is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (is_bool($value) ? ($value ? 'true' : 'false') : $value) }}</div>
                                @empty
                                    <span class="text-gray-400">&ndash;</span>
                                @endforelse
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">Keine Einträge gefunden.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $events->links() }}
        </div>
    </div>
</div>

@endsection
