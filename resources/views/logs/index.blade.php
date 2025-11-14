@extends('layouts.app')
@section('title') - System-Logs @endsection

@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <!-- Header Card -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
        <div class="bg-gradient-to-r from-gray-700 to-gray-900 px-6 py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <i class="fas fa-file-alt text-white text-2xl"></i>
                    <div>
                        <h5 class="text-xl font-bold text-white mb-0">System-Logs</h5>
                        <p class="text-gray-300 text-sm mb-0">Gesamt: {{ $totalLogs }} Einträge</p>
                    </div>
                </div>

                @can('delete logs')
                <div class="flex gap-2">
                    <form action="{{ url('/logs/cleanup') }}" method="POST" onsubmit="return confirm('Möchten Sie wirklich alle Logs löschen, die älter als 30 Tage sind?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white font-medium rounded-lg transition-colors duration-200">
                            <i class="fas fa-broom"></i>
                            <span>Alte Logs löschen (>30 Tage)</span>
                        </button>
                    </form>
                </div>
                @endcan
            </div>
        </div>

        <!-- Filter Section -->
        <div class="bg-gray-50 border-b border-gray-200 px-6 py-4">
            <form method="GET" action="{{ url('/logs') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Level Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-layer-group text-gray-500 mr-1"></i>
                        Level
                    </label>
                    <select name="level" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                        <option value="">Alle Levels</option>
                        <option value="DEBUG" {{ request('level') == 'DEBUG' ? 'selected' : '' }}>DEBUG</option>
                        <option value="INFO" {{ request('level') == 'INFO' ? 'selected' : '' }}>INFO</option>
                        <option value="NOTICE" {{ request('level') == 'NOTICE' ? 'selected' : '' }}>NOTICE</option>
                        <option value="WARNING" {{ request('level') == 'WARNING' ? 'selected' : '' }}>WARNING</option>
                        <option value="ERROR" {{ request('level') == 'ERROR' ? 'selected' : '' }}>ERROR</option>
                        <option value="CRITICAL" {{ request('level') == 'CRITICAL' ? 'selected' : '' }}>CRITICAL</option>
                        <option value="ALERT" {{ request('level') == 'ALERT' ? 'selected' : '' }}>ALERT</option>
                        <option value="EMERGENCY" {{ request('level') == 'EMERGENCY' ? 'selected' : '' }}>EMERGENCY</option>
                    </select>
                </div>

                <!-- Channel Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-broadcast-tower text-gray-500 mr-1"></i>
                        Channel
                    </label>
                    <input type="text" name="channel" value="{{ request('channel') }}" placeholder="z.B. production" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                </div>

                <!-- Search -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-search text-gray-500 mr-1"></i>
                        Suche
                    </label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Nachricht durchsuchen..." class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                </div>

                <!-- Actions -->
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                        <i class="fas fa-filter"></i>
                        Filtern
                    </button>
                    <a href="{{ url('/logs') }}" class="inline-flex items-center justify-center px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium rounded-lg transition-colors duration-200">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4" role="alert">
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <!-- Logs Table -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">
                            Level
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Nachricht
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                            Channel
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-48">
                            Zeitpunkt
                        </th>
                        @can('delete logs')
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-20">
                            Aktion
                        </th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50 transition-colors duration-150" x-data="{ expanded: false }">
                            <td class="px-4 py-3 whitespace-nowrap">
                                @php
                                    $levelColors = [
                                        'DEBUG' => 'bg-gray-100 text-gray-800',
                                        'INFO' => 'bg-blue-100 text-blue-800',
                                        'NOTICE' => 'bg-cyan-100 text-cyan-800',
                                        'WARNING' => 'bg-yellow-100 text-yellow-800',
                                        'ERROR' => 'bg-red-100 text-red-800',
                                        'CRITICAL' => 'bg-red-200 text-red-900',
                                        'ALERT' => 'bg-purple-100 text-purple-800',
                                        'EMERGENCY' => 'bg-red-900 text-white',
                                    ];
                                    $levelIcons = [
                                        'DEBUG' => 'fa-bug',
                                        'INFO' => 'fa-info-circle',
                                        'NOTICE' => 'fa-sticky-note',
                                        'WARNING' => 'fa-exclamation-triangle',
                                        'ERROR' => 'fa-times-circle',
                                        'CRITICAL' => 'fa-exclamation-circle',
                                        'ALERT' => 'fa-bell',
                                        'EMERGENCY' => 'fa-fire',
                                    ];
                                @endphp
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium {{ $levelColors[$log->level_name] ?? 'bg-gray-100 text-gray-800' }}">
                                    <i class="fas {{ $levelIcons[$log->level_name] ?? 'fa-circle' }} text-xs"></i>
                                    {{ $log->level_name }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-gray-900">
                                    @php
                                        $message = is_array($log->message) ? json_encode($log->message, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $log->message;
                                        $messageLength = strlen($message);
                                    @endphp
                                    <div class="{{ $messageLength > 100 ? 'cursor-pointer' : '' }}" @if($messageLength > 100) @click="expanded = !expanded" @endif>
                                        <span x-show="!expanded">{{ Str::limit($message, 100) }}</span>
                                        <span x-show="expanded" x-cloak>{{ $message }}</span>
                                        @if($messageLength > 100)
                                            <button type="button" class="text-blue-600 hover:text-blue-800 text-xs ml-1">
                                                <span x-show="!expanded">mehr anzeigen</span>
                                                <span x-show="expanded" x-cloak>weniger anzeigen</span>
                                            </button>
                                        @endif
                                    </div>
                                    @if($log->context)
                                        <details class="mt-2">
                                            <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-700">
                                                <i class="fas fa-info-circle mr-1"></i>Kontext anzeigen
                                            </summary>
                                            @php
                                                $context = is_array($log->context) || is_object($log->context)
                                                    ? json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                                                    : $log->context;
                                            @endphp
                                            <pre class="mt-2 p-2 bg-gray-100 rounded text-xs overflow-x-auto">{{ $context }}</pre>
                                        </details>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="text-sm text-gray-500">{{ $log->channel }}</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $log->created_at->format('d.m.Y H:i:s') }}</div>
                                <div class="text-xs text-gray-500">{{ $log->created_at->diffForHumans() }}</div>
                            </td>
                            @can('delete logs')
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <form action="{{ url('/logs/' . $log->id) }}" method="POST" class="inline" onsubmit="return confirm('Diesen Log-Eintrag wirklich löschen?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 transition-colors">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-2 text-gray-300"></i>
                                <p class="text-lg">Keine Logs gefunden</p>
                                @if(request()->hasAny(['level', 'channel', 'search']))
                                    <a href="{{ url('/logs') }}" class="text-blue-600 hover:text-blue-800 text-sm mt-2 inline-block">
                                        Filter zurücksetzen
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($logs->hasPages())
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>


@endsection
