@extends('layouts.app')
@section('title') - Hort-Verwaltung @endsection

@section('content')
    <div class="container-fluid px-4 py-6" x-data="{ activeTab: 'anwesenheitsabfragen' }">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Hort-Verwaltung</h1>
            <p class="text-gray-600">Verwalten Sie Anwesenheitsabfragen und Schickzeiten</p>
        </div>

        <!-- Tab Navigation -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
            <div class="border-b border-gray-200">
                <nav class="flex flex-wrap -mb-px" role="tablist">
                    <button @click="activeTab = 'anwesenheitsabfragen'"
                            :class="activeTab === 'anwesenheitsabfragen' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-600 hover:text-gray-800 hover:border-gray-300'"
                            class="flex-1 px-6 py-3 border-b-2 font-medium text-sm transition-all duration-200 flex items-center justify-center gap-2">
                        <i class="fas fa-calendar-check"></i>
                        Anwesenheitsabfragen
                    </button>
                    @can('download schickzeiten')
                    <button @click="activeTab = 'schickzeiten'"
                            :class="activeTab === 'schickzeiten' ? 'border-green-600 text-green-600' : 'border-transparent text-gray-600 hover:text-gray-800 hover:border-gray-300'"
                            class="flex-1 px-6 py-3 border-b-2 font-medium text-sm transition-all duration-200 flex items-center justify-center gap-2">
                        <i class="fas fa-clock"></i>
                        Schickzeiten
                    </button>
                    @endcan
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-6">
                <!-- Anwesenheitsabfragen Tab -->
                <div x-show="activeTab === 'anwesenheitsabfragen'"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100">

                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                        <!-- Download Excel -->
                        <div class="bg-white rounded-lg shadow border border-gray-200">
                            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3">
                                <h3 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                                    <i class="fas fa-download"></i>
                                    Excel-Export
                                </h3>
                            </div>
                            <div class="p-4">
                                <p class="text-sm text-gray-600 mb-4">
                                    Herunterladen des aktuellen Standes der Anwesenheitsabfragen für den angegebenen Zeitraum als Excel-Datei
                                </p>
                                <form action="{{route('care.abfrage.anwesenheit.download')}}" method="post">
                                    @csrf
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-calendar-alt text-blue-600"></i> Datum von
                                        </label>
                                        <input type="date" name="date_start"
                                               class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                               required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-calendar-alt text-blue-600"></i> Datum bis
                                        </label>
                                        <input type="date" name="date_end"
                                               class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                               required>
                                    </div>
                                    <button type="submit"
                                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                                        <i class="fas fa-file-excel"></i>
                                        Excel herunterladen
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Anwesenheit für Kind eintragen -->
                        <div class="bg-white rounded-lg shadow border border-gray-200">
                            <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-4 py-3">
                                <h3 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                                    <i class="fas fa-user-plus"></i>
                                    Anwesenheit eintragen
                                </h3>
                            </div>
                            <div class="p-4">
                                <p class="text-sm text-gray-600 mb-4">
                                    Das Erfassen von geplanten Anwesenheiten einzelner Kinder ist hier möglich.
                                </p>
                                <form action="{{route('care.abfrage.anwesenheit.store')}}" method="post">
                                    @csrf
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-calendar-alt text-blue-600"></i> Datum von
                                        </label>
                                        <input type="date" name="date_start"
                                               class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                               min="{{now()->addDay()->format('Y-m-d')}}"
                                               required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-calendar-alt text-blue-600"></i> Datum bis
                                        </label>
                                        <input type="date" name="date_end"
                                               class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                               min="{{now()->addDay()->format('Y-m-d')}}">
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-child text-blue-600"></i> Kind
                                        </label>
                                        <select name="child_id"
                                                class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                                required>
                                            <option value="">Bitte wählen</option>
                                            @foreach($children as $child)
                                                <option value="{{$child->id}}">{{$child->last_name}}, {{$child->first_name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="submit"
                                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors duration-200">
                                        <i class="fas fa-save"></i>
                                        Abwesenheit eintragen
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Aktuelle Abfragen mit Rücklauf-Dashboard -->
                    <div class="mt-6 bg-white rounded-lg shadow border border-gray-200">
                        <div class="bg-gradient-to-r from-teal-600 to-teal-700 px-4 py-3">
                            <h3 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                                <i class="fas fa-chart-bar"></i>
                                Aktuelle Anwesenheitsabfragen – Rücklauf
                            </h3>
                        </div>
                        <div class="p-4">
                            <p class="text-sm text-gray-600 mb-4">
                                Übersicht aller offenen Anwesenheitsabfragen mit Rücklaufquoten. Klicken Sie auf „Fehlende Antworten", um zu sehen, welche Eltern noch nicht geantwortet haben.
                            </p>

                            @forelse($abfragen as $date => $detail)
                                <div class="bg-white rounded-lg border border-gray-200 p-4 mb-4 hover:shadow-md transition-shadow">
                                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-3 gap-2">
                                        <h6 class="font-bold text-gray-800 flex items-center gap-2">
                                            <i class="fas fa-calendar text-blue-600"></i>
                                            {{ \Carbon\Carbon::parse($date)->locale('de')->isoFormat('dddd, D. MMMM YYYY') }}
                                        </h6>
                                        <div class="flex items-center gap-3">
                                            <span class="text-lg font-bold {{ $detail['response_rate'] >= 80 ? 'text-green-600' : ($detail['response_rate'] >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                                {{ $detail['response_rate'] }}% beantwortet
                                            </span>
                                            @if(\Carbon\Carbon::parse($date)->isFuture())
                                                <div class="flex items-center gap-1">
                                                    <button type="button"
                                                            class="edit-comment-button inline-flex items-center gap-1 px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors"
                                                            data-date="{{ $date }}"
                                                            data-comment="{{ $detail['comment'] ?? '' }}">
                                                        <i class="fa fa-edit"></i>
                                                    </button>
                                                    <form action="{{ route('care.abfrage.destroy', ['date' => $date]) }}" method="post" class="delete-form inline">
                                                        @csrf
                                                        @method('delete')
                                                        <button type="button"
                                                                class="delete-button inline-flex items-center gap-1 px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    @if($detail['comment'])
                                        <p class="text-sm text-gray-500 mb-3 italic">
                                            <i class="fas fa-comment-alt text-gray-400 mr-1"></i> {{ $detail['comment'] }}
                                        </p>
                                    @endif

                                    {{-- Fortschrittsbalken --}}
                                    @if($detail['total'] > 0)
                                        <div class="w-full bg-gray-200 rounded-full h-4 mb-3 overflow-hidden">
                                            <div class="flex h-4">
                                                <div class="bg-green-500 transition-all" style="width: {{ $detail['coming'] / $detail['total'] * 100 }}%"
                                                     title="{{ $detail['coming'] }} kommen"></div>
                                                <div class="bg-red-400 transition-all" style="width: {{ $detail['not_coming'] / $detail['total'] * 100 }}%"
                                                     title="{{ $detail['not_coming'] }} kommen nicht"></div>
                                                <div class="bg-gray-300 transition-all" style="width: {{ $detail['pending'] / $detail['total'] * 100 }}%"
                                                     title="{{ $detail['pending'] }} offen"></div>
                                            </div>
                                        </div>

                                        <div class="flex flex-wrap gap-4 text-sm mb-3">
                                            <span class="text-green-700 font-medium"><i class="fas fa-check"></i> {{ $detail['coming'] }} kommen</span>
                                            <span class="text-red-600 font-medium"><i class="fas fa-times"></i> {{ $detail['not_coming'] }} kommen nicht</span>
                                            <span class="text-gray-500 font-medium"><i class="fas fa-question-circle"></i> {{ $detail['pending'] }} offen</span>
                                            <span class="text-gray-400 text-xs">| Gesamt: {{ $detail['total'] }}</span>
                                            @if($detail['lock_at'])
                                                <span class="text-gray-400 text-xs">| Frist: {{ $detail['lock_at']->format('d.m.Y') }}</span>
                                            @endif
                                        </div>
                                    @endif

                                    {{-- Fehlende Eltern aufklappbar --}}
                                    @if($detail['pending'] > 0 && $detail['pending_children']->count() > 0)
                                        <details class="mt-2 border border-orange-200 rounded-lg">
                                            <summary class="cursor-pointer text-sm text-orange-700 font-semibold px-3 py-2 bg-orange-50 rounded-t-lg hover:bg-orange-100 transition-colors">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                {{ $detail['pending_parents']->count() }} {{ $detail['pending_parents']->count() === 1 ? 'Elternteil hat' : 'Eltern haben' }} noch nicht geantwortet
                                                ({{ $detail['pending_children']->count() }} {{ $detail['pending_children']->count() === 1 ? 'Kind' : 'Kinder' }})
                                            </summary>
                                            <div class="px-3 py-2 space-y-1">
                                                @foreach($detail['pending_children'] as $child)
                                                    <div class="flex justify-between items-center py-1.5 px-2 bg-orange-50 rounded text-sm border-b border-orange-100 last:border-b-0">
                                                        <span class="font-medium text-gray-800">
                                                            <i class="fas fa-child text-gray-400 mr-1"></i>
                                                            {{ $child->first_name }} {{ $child->last_name }}
                                                        </span>
                                                        <span class="text-gray-500 text-xs">
                                                            {{ $child->parents->pluck('name')->join(', ') }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </details>
                                    @elseif($detail['pending'] === 0 && $detail['total'] > 0)
                                        <div class="flex items-center gap-2 text-sm text-green-700 mt-2 bg-green-50 rounded px-3 py-2">
                                            <i class="fas fa-check-circle"></i>
                                            <span class="font-medium">Alle Eltern haben geantwortet!</span>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="flex flex-col items-center gap-2 text-gray-500 py-8">
                                    <i class="fas fa-inbox text-4xl"></i>
                                    <p class="text-sm">Keine zukünftigen Anwesenheitsabfragen vorhanden</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Neue Abfrage erstellen -->
                            <h3 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                                <i class="fas fa-plus-circle"></i>
                                Neue Anwesenheitsabfrage erstellen
                            </h3>
                        </div>
                        <div class="p-4">
                            <p class="text-sm text-gray-600 mb-4">
                                Anwesenheitsabfragen dienen dem Erfassen von Anwesenheiten zu einzelnen Tagen (z.B. Ferientage). Hier können neue Abfragen erstellt werden.
                            </p>
                            <form action="{{route('care.abfrage.store')}}" method="post" class="max-w-2xl">
                                @csrf
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-calendar-alt text-red-600"></i> Datum von <span class="text-red-600">*</span>
                                        </label>
                                        <input type="date" name="date_start"
                                               class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                               min="{{now()->addDay()->format('Y-m-d')}}"
                                               required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-calendar-alt text-blue-600"></i> Datum bis
                                        </label>
                                        <input type="date" name="date_end"
                                               class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                               min="{{now()->addDay()->format('Y-m-d')}}">
                                    </div>
                                    <div class="mb-4 md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-lock text-amber-600"></i> Bis wann ist eine Anmeldung möglich
                                        </label>
                                        <input type="date" name="lock_at"
                                               class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                               min="{{now()->addDay()->format('Y-m-d')}}">
                                        <p class="mt-1 text-xs text-gray-500">Optional: Legen Sie eine Frist fest, bis wann Eltern ihre Kinder an-/abmelden können</p>
                                    </div>
                                </div>
                                <button type="submit"
                                        class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-colors duration-200">
                                    <i class="fas fa-paper-plane"></i>
                                    Anwesenheit abfragen
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Schickzeiten Tab -->
                @can('download schickzeiten')
                <div x-show="activeTab === 'schickzeiten'"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     style="display: none;">

                    <!-- Download Button -->
                    <div class="mb-6 bg-white rounded-lg shadow border border-gray-200">
                        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3">
                            <h3 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                                <i class="fas fa-download"></i>
                                Schickzeiten Export
                            </h3>
                        </div>
                        <div class="p-4">
                            <p class="text-sm text-gray-600 mb-4">
                                Herunterladen der eingetragenen Schickzeiten als Excel-Datei
                            </p>
                            <a href="{{url('schickzeiten/download')}}"
                               class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                                <i class="fas fa-file-excel"></i>
                                Excel herunterladen
                            </a>
                        </div>
                    </div>

                    <!-- Statistiken & Planungshilfe  -->
                    <div class="mt-6 bg-white rounded-lg shadow border border-gray-200"
                         x-data="{
                             dateStart: '',
                             dateEnd: '',
                             stats: null,
                             loading: false,
                             error: null,
                             loadStats() {
                                 if (!this.dateStart || !this.dateEnd) return;
                                 this.loading = true;
                                 this.error = null;
                                 const url = '{{ route('care.abfrage.stats') }}?date_start=' + this.dateStart + '&date_end=' + this.dateEnd;
                                 fetch(url, {
                                     headers: {
                                         'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                         'Accept': 'application/json',
                                     }
                                 })
                                 .then(r => r.json())
                                 .then(data => { this.stats = data; this.loading = false; })
                                 .catch(() => { this.error = 'Fehler beim Laden der Statistiken.'; this.loading = false; });
                             }
                         }">
                        <div class="bg-gradient-to-r from-orange-500 to-amber-600 px-4 py-3">
                            <h3 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                                <i class="fas fa-chart-bar"></i>
                                Statistiken & Planungshilfe
                            </h3>
                        </div>
                        <div class="p-4">
                            <p class="text-sm text-gray-600 mb-4">
                                Analysieren Sie Anwesenheitsabfragen eines beliebigen Zeitraums und exportieren Sie einen Ferienplan als PDF.
                            </p>

                            {{-- Zeitraum-Auswahl --}}
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calendar-alt text-orange-600"></i> Datum von
                                    </label>
                                    <input type="date" x-model="dateStart"
                                           class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition-all duration-200 outline-none">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calendar-alt text-orange-600"></i> Datum bis
                                    </label>
                                    <input type="date" x-model="dateEnd"
                                           class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition-all duration-200 outline-none">
                                </div>
                                <div class="flex items-end">
                                    <button @click="loadStats()"
                                            :disabled="loading || !dateStart || !dateEnd"
                                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-orange-600 hover:bg-orange-700 disabled:opacity-50 text-white font-medium rounded-lg transition-colors duration-200">
                                        <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-chart-line'"></i>
                                        <span x-text="loading ? 'Lädt...' : 'Statistiken laden'"></span>
                                    </button>
                                </div>
                            </div>

                            {{-- Fehlermeldung --}}
                            <div x-show="error" x-cloak class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded mb-4 text-sm">
                                <i class="fas fa-exclamation-triangle"></i> <span x-text="error"></span>
                            </div>

                            {{-- Statistik-Karten --}}
                            <div x-show="stats !== null" x-cloak>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                    <div class="bg-blue-50 rounded-lg p-4 text-center border border-blue-200">
                                        <div class="text-3xl font-bold text-blue-600" x-text="stats?.forecast_max_children ?? 0"></div>
                                        <div class="text-sm text-gray-600 mt-1">Max. Kinder an einem Tag</div>
                                    </div>
                                    <div class="bg-green-50 rounded-lg p-4 text-center border border-green-200">
                                        <div class="text-3xl font-bold text-green-600" x-text="(stats?.response_rate ?? 0) + '%'"></div>
                                        <div class="text-sm text-gray-600 mt-1">Rücklaufquote</div>
                                    </div>
                                    <div class="bg-purple-50 rounded-lg p-4 text-center border border-purple-200">
                                        <div class="text-3xl font-bold text-purple-600" x-text="stats?.avg_per_day ?? 0"></div>
                                        <div class="text-sm text-gray-600 mt-1">⌀ Kinder pro Tag</div>
                                    </div>
                                    <div class="bg-amber-50 rounded-lg p-4 text-center border border-amber-200">
                                        <div class="text-3xl font-bold text-amber-600" x-text="stats?.pending_count ?? 0"></div>
                                        <div class="text-sm text-gray-600 mt-1">Noch offen</div>
                                    </div>
                                </div>

                                {{-- Balkendiagramm nach Datum --}}
                                <div class="mb-4">
                                    <h6 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                        <i class="fas fa-calendar-week text-orange-600"></i>
                                        Erwartete Kinder pro Tag
                                    </h6>
                                    <template x-for="(dayStat, date) in stats.by_date" :key="date">
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="text-xs font-mono text-gray-500 whitespace-nowrap"
                                                  style="min-width: 100px;"
                                                  x-text="new Date(date).toLocaleDateString('de-DE', { weekday: 'short', day: '2-digit', month: '2-digit' })">
                                            </span>
                                            <div class="flex-1 bg-gray-100 rounded h-7 relative overflow-hidden">
                                                <div class="flex h-7 rounded overflow-hidden">
                                                    <div class="bg-green-500 flex items-center justify-center transition-all"
                                                         :style="'width:' + (dayStat.total > 0 ? dayStat.coming/dayStat.total*100 : 0) + '%'"
                                                         :title="dayStat.coming + ' kommen'">
                                                        <span x-show="dayStat.coming > 0" class="text-xs text-white font-bold px-1" x-text="dayStat.coming"></span>
                                                    </div>
                                                    <div class="bg-red-400 flex items-center justify-center transition-all"
                                                         :style="'width:' + (dayStat.total > 0 ? dayStat.not_coming/dayStat.total*100 : 0) + '%'"
                                                         :title="dayStat.not_coming + ' kommen nicht'">
                                                        <span x-show="dayStat.not_coming > 0" class="text-xs text-white font-bold px-1" x-text="dayStat.not_coming"></span>
                                                    </div>
                                                    <div class="bg-gray-300 flex items-center justify-center transition-all"
                                                         :style="'width:' + (dayStat.total > 0 ? dayStat.pending/dayStat.total*100 : 0) + '%'"
                                                         :title="dayStat.pending + ' offen'">
                                                        <span x-show="dayStat.pending > 0" class="text-xs text-gray-600 font-bold px-1" x-text="dayStat.pending"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <span class="text-xs text-gray-500 whitespace-nowrap" x-text="dayStat.total + ' ges.'"></span>
                                        </div>
                                    </template>
                                </div>

                                {{-- Legende --}}
                                <div class="flex flex-wrap gap-4 text-xs text-gray-600 mb-4">
                                    <span><span class="inline-block w-3 h-3 bg-green-500 rounded mr-1"></span>kommt</span>
                                    <span><span class="inline-block w-3 h-3 bg-red-400 rounded mr-1"></span>kommt nicht</span>
                                    <span><span class="inline-block w-3 h-3 bg-gray-300 rounded mr-1"></span>offen</span>
                                </div>

                                {{-- PDF-Export --}}
                                <div class="border-t border-gray-200 pt-4 mt-2">
                                    <h6 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                        <i class="fas fa-file-pdf text-red-500"></i>
                                        Ferienplan als PDF exportieren
                                    </h6>
                                    <p class="text-sm text-gray-500 mb-3">
                                        Exportiert alle Kinder, die sich für den gewählten Zeitraum angemeldet haben (inkl. Schickzeiten).
                                    </p>
                                    <form action="{{ route('care.abfrage.ferienplan.pdf') }}" method="post" class="inline">
                                        @csrf
                                        <input type="hidden" name="date_start" :value="dateStart">
                                        <input type="hidden" name="date_end" :value="dateEnd">
                                        <button type="submit"
                                                :disabled="!dateStart || !dateEnd"
                                                class="inline-flex items-center gap-2 px-5 py-2 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white font-medium rounded-lg transition-colors duration-200">
                                            <i class="fas fa-file-pdf"></i>
                                            Ferienplan herunterladen
                                        </button>
                                    </form>
                                </div>
                            </div>

                            {{-- Hinweis vor dem ersten Laden --}}
                            <div x-show="stats === null && !loading && !error" class="flex flex-col items-center gap-2 text-gray-400 py-6">
                                <i class="fas fa-chart-bar text-3xl"></i>
                                <p class="text-sm">Zeitraum wählen und auf „Statistiken laden" klicken.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Neue Abfrage erstellen -->
                    <div class="mt-6 bg-white rounded-lg shadow border border-gray-200">
                        <div class="bg-gradient-to-r from-green-600 to-green-700 px-4 py-3">
                            <h3 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                                <i class="fas fa-users-clock"></i>
                                Dauer-Schickzeiten der Kinder
                            </h3>
                        </div>
                        <div class="p-4">
                            <p class="text-sm text-gray-600 mb-4">
                                Hier können für jedes Kind die hinterlegten Dauerschickzeiten angezeigt werden
                            </p>

                            <div class="space-y-2">
                                @foreach($children as $child)
                                    <div class="border border-gray-200 rounded-lg overflow-hidden" x-data="{ open: false }">
                                        <button @click="open = !open"
                                                class="w-full flex items-center justify-between p-3 hover:bg-gray-50 transition-colors duration-200">
                                            <span class="font-medium text-gray-800">
                                                {{$child->last_name}}, {{$child->first_name}}
                                            </span>
                                            <div class="flex items-center gap-3">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{$child->schickzeiten->count()}} Zeiten
                                                </span>
                                                <i class="fas fa-chevron-down transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                                            </div>
                                        </button>

                                        <div x-show="open"
                                             x-transition:enter="transition ease-out duration-200"
                                             x-transition:enter-start="opacity-0 max-h-0"
                                             x-transition:enter-end="opacity-100 max-h-screen"
                                             style="display: none;"
                                             class="border-t border-gray-200">
                                            <div class="p-4 bg-gray-50">
                                                <div class="overflow-x-auto">
                                                    <table class="min-w-full divide-y divide-gray-200">
                                                        <thead class="bg-white">
                                                            <tr>
                                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Tag</th>
                                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Ab</th>
                                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Genau</th>
                                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Spätestens</th>
                                                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-700 uppercase">Aktionen</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="bg-white divide-y divide-gray-200">
                                                            @for($x=1;$x<6;$x++)
                                                                <tr class="hover:bg-gray-50">
                                                                    <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900">{{$weekdays[$x]}}</td>
                                                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-600">
                                                                        @if($child->schickzeiten->where('weekday', $x)->count() > 0)
                                                                            {{$child->schickzeiten->where('weekday', $x)->first()->time_ab?->format('H:i')}}
                                                                        @else
                                                                            <span class="text-gray-400">-</span>
                                                                        @endif
                                                                    </td>
                                                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-600">
                                                                        @if($child->schickzeiten->where('weekday', $x)->count() > 0)
                                                                            {{ $child->schickzeiten->where('weekday', $x)->first()?->time?->format('H:i') }}
                                                                        @else
                                                                            <span class="text-gray-400">-</span>
                                                                        @endif
                                                                    </td>
                                                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-600">
                                                                        @if($child->schickzeiten->where('weekday', $x)->count() > 0)
                                                                            {{$child->schickzeiten->where('weekday', $x)->first()?->time_spaet?->format('H:i')}}
                                                                        @else
                                                                            <span class="text-gray-400">-</span>
                                                                        @endif
                                                                    </td>
                                                                    <td class="px-3 py-2 whitespace-nowrap text-right text-sm">
                                                                        <div class="flex items-center justify-end gap-2">
                                                                            <a href="{{route('schickzeiten.edit',['child' => $child->id, 'day' => $x])}}"
                                                                               class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors duration-200">
                                                                                <i class="fa fa-edit"></i>
                                                                            </a>
                                                                            @if($child->schickzeiten->where('weekday', $x)->first())
                                                                                <form action="{{route('schickzeiten.destroy', ['schickzeit' => $child->schickzeiten->where('weekday', $x)->first()->id])}}"
                                                                                      method="post">
                                                                                    @csrf
                                                                                    @method('delete')
                                                                                    <button type="submit"
                                                                                            onclick="return confirm('Wirklich löschen?')"
                                                                                            class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors duration-200">
                                                                                        <i class="fa fa-trash"></i>
                                                                                    </button>
                                                                                </form>
                                                                            @endif
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            @endfor
                                                        </tbody>
                                                        @if($child->schickzeiten->where('weekday', null)->count() > 0)
                                                            <tfoot class="bg-blue-50">
                                                                <tr>
                                                                    <td colspan="5" class="px-3 py-2 text-xs font-semibold text-gray-700">Tagesaktuelle Schickzeiten</td>
                                                                </tr>
                                                                @foreach($child->schickzeiten->where('weekday', null) as $schickzeit)
                                                                    <tr class="hover:bg-blue-100">
                                                                        <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900">
                                                                            {{$schickzeit->specific_date?->format('d.m.Y')}}
                                                                        </td>
                                                                        <td colspan="3" class="px-3 py-2 text-sm text-gray-600">
                                                                            @if($schickzeit->type == 'genau')
                                                                                Genau {{$schickzeit->time?->format('H:i')}} Uhr
                                                                            @else
                                                                                Ab {{$schickzeit->time_ab?->format('H:i')}} Uhr
                                                                                @if($schickzeit->time_spaet)
                                                                                    bis {{$schickzeit->time_spaet?->format('H:i')}} Uhr
                                                                                @endif
                                                                            @endif
                                                                        </td>
                                                                        <td class="px-3 py-2 whitespace-nowrap text-right">
                                                                            <form action="{{route('schickzeiten.destroy', ['schickzeit' => $schickzeit->id])}}"
                                                                                  method="post">
                                                                                @csrf
                                                                                @method('delete')
                                                                                <button type="submit"
                                                                                        onclick="return confirm('Wirklich löschen?')"
                                                                                        class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors duration-200">
                                                                                    <i class="fa fa-trash"></i>
                                                                                </button>
                                                                            </form>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tfoot>
                                                        @endif
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                @endcan
            </div>
        </div>
    </div>

    <!-- Modal für Kommentar bearbeiten -->
    <div x-data="{ show: false, date: '', comment: '' }"
         @open-comment-modal.window="show = true; date = $event.detail.date; comment = $event.detail.comment"
         x-show="show"
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75" @click="show = false"></div>

            <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100">

                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 rounded-t-lg">
                    <h3 class="text-lg font-bold text-white">Kommentar bearbeiten</h3>
                </div>

                <form id="commentForm" method="POST" action="{{route('anwesenheit.comment.update')}}">
                    @csrf
                    <div class="p-6">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Kommentar</label>
                            <textarea name="comment"
                                      x-model="comment"
                                      class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none resize-none"
                                      rows="3"
                                      maxlength="256"
                                      required></textarea>
                        </div>
                        <input type="hidden" name="date" x-model="date">
                    </div>

                    <div class="flex items-center justify-between gap-3 px-6 py-4 bg-gray-50 rounded-b-lg">
                        <button type="button"
                                @click="show = false"
                                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-lg transition-colors duration-200">
                            Abbrechen
                        </button>
                        <div class="flex gap-2">
                            <button type="button"
                                    @click="deleteComment(date)"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors duration-200">
                                Löschen
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                                Speichern
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Bestätigungsmodals für Schickzeiten --}}
    @include('components.schickzeiten-confirmation-modals')

@endsection

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Delete Button Handler
            const deleteButtons = document.querySelectorAll('.delete-button');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const form = this.closest('form');
                    Swal.fire({
                        title: 'Sind Sie sicher?',
                        text: 'Diese Aktion kann nicht rückgängig gemacht werden!',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Ja, löschen!',
                        cancelButtonText: 'Abbrechen'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });

            // Edit Comment Button Handler
            document.querySelectorAll('.edit-comment-button').forEach(button => {
                button.addEventListener('click', function () {
                    const date = this.dataset.date;
                    const comment = this.dataset.comment || '';

                    window.dispatchEvent(new CustomEvent('open-comment-modal', {
                        detail: { date, comment }
                    }));
                });
            });
        });

        // Delete Comment Function
        function deleteComment(date) {
            Swal.fire({
                title: 'Sind Sie sicher?',
                text: 'Der Kommentar wird gelöscht!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ja, löschen!',
                cancelButtonText: 'Abbrechen'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch("{{route('anwesenheit.comment.remove')}}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ date })
                    }).then(response => {
                        if (response.ok) {
                            Swal.fire('Gelöscht!', 'Der Kommentar wurde gelöscht.', 'success');
                            location.reload();
                        }
                    });
                }
            });
        }
    </script>
@endpush

