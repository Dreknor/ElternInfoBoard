@extends('layouts.app')
@section('title') - Hort-Verwaltung @endsection

@section('content')
    <div class="container-fluid px-4 py-6" x-data="{ activeTab: '{{ request('tab', 'anwesenheitsabfragen') }}' }">
        <!-- Header -->
        <div class="ea-hero">
            <div class="flex items-center gap-4">
                <div class="ea-hero-icon"><i class="fas fa-child" aria-hidden="true"></i></div>
                <div class="min-w-0">
                    <h1 class="ea-hero-title">Hort-Verwaltung</h1>
                    <p class="ea-hero-sub">Verwalten Sie Anwesenheitsabfragen und Schickzeiten</p>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div>
            <div>
                <nav class="ea-tabs" role="tablist">
                    <button @click="activeTab = 'anwesenheitsabfragen'"
                            :class="activeTab === 'anwesenheitsabfragen' ? 'ea-tab-active' : ''"
                            class="ea-tab flex-1 justify-center">
                        <i class="fas fa-calendar-check"></i>
                        Anwesenheitsabfragen
                    </button>
                    @can('download schickzeiten')
                    <button @click="activeTab = 'schickzeiten'"
                            :class="activeTab === 'schickzeiten' ? 'ea-tab-active' : ''"
                            class="ea-tab flex-1 justify-center">
                        <i class="fas fa-clock"></i>
                        Schickzeiten
                    </button>
                    @endcan
                    <button @click="activeTab = 'verlauf'"
                            :class="activeTab === 'verlauf' ? 'ea-tab-active' : ''"
                            class="ea-tab flex-1 justify-center">
                        <i class="fas fa-history"></i>
                        Nachrichtenverlauf
                    </button>
                    <button @click="activeTab = 'late_pickups'"
                            :class="activeTab === 'late_pickups' ? 'ea-tab-active' : ''"
                            class="ea-tab flex-1 justify-center">
                        <i class="fas fa-exclamation-triangle"></i>
                        Verspätete Abholungen
                        @if(($latePickupsByDate->flatten()->where('status', 'offen')->count() ?? 0) > 0)
                            <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-bold bg-red-600 text-white ring-1 ring-white/40">
                                {{ $latePickupsByDate->flatten()->where('status', 'offen')->count() }}
                            </span>
                        @endif
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="mt-6">
                <!-- Anwesenheitsabfragen Tab -->
                <div x-show="activeTab === 'anwesenheitsabfragen'"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100">

                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                        <!-- Download Excel -->
                        <div class="ea-card">
                            <div class="ea-card-header">
                                <div class="ea-card-icon"><i class="fas fa-download" aria-hidden="true"></i></div>
                                <h3 class="ea-card-title">Excel-Export</h3>
                            </div>
                            <div class="p-4">
                                <p class="text-sm ea-muted mb-4">
                                    Herunterladen des aktuellen Standes der Anwesenheitsabfragen für den angegebenen Zeitraum als Excel-Datei
                                </p>
                                <form action="{{route('care.abfrage.anwesenheit.download')}}" method="post">
                                    @csrf
                                    <div class="mb-4">
                                        <label class="ea-label">
                                            <i class="fas fa-calendar-alt" style="color: var(--color-primary)"></i> Datum von
                                        </label>
                                        <input type="date" name="date_start"
                                               class="ea-input"
                                               required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="ea-label">
                                            <i class="fas fa-calendar-alt" style="color: var(--color-primary)"></i> Datum bis
                                        </label>
                                        <input type="date" name="date_end"
                                               class="ea-input"
                                               required>
                                    </div>
                                    <button type="submit"
                                            class="ea-btn ea-btn-primary w-full">
                                        <i class="fas fa-file-excel"></i>
                                        Excel herunterladen
                                    </button>
                                </form>
                            </div>
                        </div>

                        @can('manage attendance queries')
                        <div class="ea-card">
                            <div class="ea-card-header">
                                <div class="ea-card-icon"><i class="fas fa-plus-circle" aria-hidden="true"></i></div>
                                <h3 class="ea-card-title">Neue Anwesenheitsabfrage</h3>
                            </div>
                            <div class="p-4">
                                <p class="text-sm ea-muted mb-4">
                                    Anwesenheitsabfragen dienen dem Erfassen von Anwesenheiten zu einzelnen Tagen (z.B. Ferientage). Hier können neue Abfragen erstellt werden.
                                    Je Kind und Tag gibt es nur eine Abfrage: Wird eine Abfrage für einen bestehenden Zeitraum erneut erstellt, werden nur fehlende Kinder nachgetragen.
                                </p>

                                <form action="{{route('care.abfrage.store')}}" method="post" class="max-w-2xl"
                                      x-data="{
                                      targetType: 'all',
                                      childSearch: '',
                                      selectedGroups: [],
                                      selectedClasses: [],
                                      selectedChildren: [],
                                      toggleId(arr, id) {
                                          const idx = arr.indexOf(id);
                                          if (idx === -1) { arr.push(id); } else { arr.splice(idx, 1); }
                                      }
                                  }">
                                    @csrf

                                    {{-- Zeitraum --}}
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                        <div>
                                            <label class="ea-label">
                                                <i class="fas fa-calendar-alt text-red-600"></i> Datum von <span class="text-red-600">*</span>
                                            </label>
                                            <input type="date" name="date_start"
                                                   class="ea-input"
                                                   min="{{now()->addDay()->format('Y-m-d')}}"
                                                   required>
                                        </div>
                                        <div>
                                            <label class="ea-label">
                                                <i class="fas fa-calendar-alt" style="color: var(--color-primary)"></i> Datum bis
                                            </label>
                                            <input type="date" name="date_end"
                                                   class="ea-input"
                                                   min="{{now()->addDay()->format('Y-m-d')}}">
                                        </div>
                                    </div>

                                    {{-- Frist --}}
                                    <div class="mb-4">
                                        <label class="ea-label">
                                            <i class="fas fa-lock text-amber-600"></i> Bis wann ist eine Anmeldung möglich
                                        </label>
                                        <input type="date" name="lock_at"
                                               class="ea-input"
                                               min="{{now()->addDay()->format('Y-m-d')}}">
                                        <p class="ea-hint">Optional: Legen Sie eine Frist fest, bis wann Eltern ihre Kinder an-/abmelden können</p>
                                    </div>

                                    {{-- Zielauswahl --}}
                                    <div class="mb-4">
                                        <label class="ea-label">
                                            <i class="fas fa-users" style="color: var(--color-primary)"></i> Für wen gilt die Abfrage? <span class="text-red-600">*</span>
                                        </label>
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-3">
                                            <label class="cursor-pointer">
                                                <input type="radio" name="target_type" value="all" x-model="targetType" class="sr-only">
                                                <div :class="targetType === 'all' ? 'ea-chip-active' : 'ea-muted ea-row-link'"
                                                     class="border-2 ea-border rounded-lg px-3 py-2 text-sm font-medium text-center transition-all duration-150">
                                                    <i class="fas fa-users block text-lg mb-1"></i>
                                                    Alle Kinder
                                                </div>
                                            </label>
                                            @if(($careGroups ?? collect())->count() > 0)
                                                <label class="cursor-pointer">
                                                    <input type="radio" name="target_type" value="groups" x-model="targetType" class="sr-only">
                                                    <div :class="targetType === 'groups' ? 'ea-chip-active' : 'ea-muted ea-row-link'"
                                                         class="border-2 ea-border rounded-lg px-3 py-2 text-sm font-medium text-center transition-all duration-150">
                                                        <i class="fas fa-layer-group block text-lg mb-1"></i>
                                                        Gruppen
                                                    </div>
                                                </label>
                                            @endif
                                            @if(($careClasses ?? collect())->count() > 0)
                                                <label class="cursor-pointer">
                                                    <input type="radio" name="target_type" value="classes" x-model="targetType" class="sr-only">
                                                    <div :class="targetType === 'classes' ? 'ea-chip-active' : 'ea-muted ea-row-link'"
                                                         class="border-2 ea-border rounded-lg px-3 py-2 text-sm font-medium text-center transition-all duration-150">
                                                        <i class="fas fa-school block text-lg mb-1"></i>
                                                        Klassen
                                                    </div>
                                                </label>
                                            @endif
                                            <label class="cursor-pointer">
                                                <input type="radio" name="target_type" value="children" x-model="targetType" class="sr-only">
                                                <div :class="targetType === 'children' ? 'ea-chip-active' : 'ea-muted ea-row-link'"
                                                     class="border-2 ea-border rounded-lg px-3 py-2 text-sm font-medium text-center transition-all duration-150">
                                                    <i class="fas fa-child block text-lg mb-1"></i>
                                                    Einzelne Kinder
                                                </div>
                                            </label>
                                        </div>

                                        {{-- Gruppen-Auswahl --}}
                                        <div x-show="targetType === 'groups'" x-cloak class="mt-2 p-3 ea-subtle rounded-lg border ea-border">
                                            <p class="text-xs font-semibold mb-2" style="color: var(--color-primary)">Gruppen auswählen:</p>
                                            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                                @foreach($careGroups ?? [] as $group)
                                                    <label class="flex items-center gap-2 cursor-pointer p-2 rounded ea-row-link">
                                                        <input type="checkbox"
                                                               name="target_ids[]"
                                                               value="{{ $group->id }}"
                                                               @change="toggleId(selectedGroups, {{ $group->id }})"
                                                               class="ea-check">
                                                        <span class="text-sm ea-text">{{ $group->name }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            <p x-show="selectedGroups.length === 0" class="text-xs text-amber-600 dark:text-amber-400 mt-2 flex items-center gap-1">
                                                <i class="fas fa-exclamation-circle"></i> Bitte mindestens eine Gruppe wählen.
                                            </p>
                                        </div>

                                        {{-- Klassen-Auswahl --}}
                                        <div x-show="targetType === 'classes'" x-cloak class="mt-2 p-3 ea-subtle rounded-lg border ea-border">
                                            <p class="text-xs font-semibold mb-2" style="color: var(--color-primary)">Klassen auswählen:</p>
                                            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                                @foreach($careClasses ?? [] as $class)
                                                    <label class="flex items-center gap-2 cursor-pointer p-2 rounded ea-row-link">
                                                        <input type="checkbox"
                                                               name="target_ids[]"
                                                               value="{{ $class->id }}"
                                                               @change="toggleId(selectedClasses, {{ $class->id }})"
                                                               class="ea-check">
                                                        <span class="text-sm ea-text">{{ $class->name }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            <p x-show="selectedClasses.length === 0" class="text-xs text-amber-600 dark:text-amber-400 mt-2 flex items-center gap-1">
                                                <i class="fas fa-exclamation-circle"></i> Bitte mindestens eine Klasse wählen.
                                            </p>
                                        </div>

                                        {{-- Kinder-Auswahl --}}
                                        <div x-show="targetType === 'children'" x-cloak class="mt-2 p-3 ea-subtle rounded-lg border ea-border">
                                            <p class="text-xs font-semibold mb-2" style="color: var(--color-primary)">Kinder auswählen:</p>
                                            <div class="mb-2">
                                                <input type="text"
                                                       x-model="childSearch"
                                                       placeholder="Suchen…"
                                                       class="ea-input !py-1.5">
                                            </div>
                                            <div class="max-h-48 overflow-y-auto grid grid-cols-1 md:grid-cols-2 gap-1">
                                                @foreach($children as $child)
                                                    <label class="flex items-center gap-2 cursor-pointer p-2 rounded ea-row-link"
                                                           data-childname="{{ strtolower($child->last_name . ' ' . $child->first_name) }}"
                                                           x-show="!childSearch || $el.dataset.childname.includes(childSearch.toLowerCase())">
                                                        <input type="checkbox"
                                                               name="target_ids[]"
                                                               value="{{ $child->id }}"
                                                               @change="toggleId(selectedChildren, {{ $child->id }})"
                                                               class="ea-check">
                                                        <span class="text-sm ea-text">{{ $child->last_name }}, {{ $child->first_name }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            <p class="text-xs ea-muted mt-2">
                                                <span x-text="selectedChildren.length"></span> Kind(er) ausgewählt
                                            </p>
                                            <p x-show="selectedChildren.length === 0" class="text-xs text-amber-600 dark:text-amber-400 flex items-center gap-1">
                                                <i class="fas fa-exclamation-circle"></i> Bitte mindestens ein Kind wählen.
                                            </p>
                                        </div>
                                    </div>

                                    {{-- Anwesenheitsstatus --}}
                                    <div class="mb-4">
                                        <label class="ea-label">
                                            <i class="fas fa-user-check text-green-600"></i> Anwesenheitsstatus
                                        </label>
                                        <select name="should_be"
                                                class="ea-input">
                                            <option value="">Keine Angabe (Eltern werden gefragt)</option>
                                            <option value="1">Kind kommt</option>
                                            <option value="0">Kind kommt nicht</option>
                                        </select>
                                        <p class="ea-hint">Optional: Legen Sie direkt fest, ob das Kind anwesend sein wird. Nur wenn hier ein Wert gewählt ist, werden bestehende Einträge entsprechend aktualisiert – sonst bleiben vorhandene Rückmeldungen unverändert.</p>
                                    </div>

                                    <button type="submit"
                                            :disabled="(targetType === 'groups' && selectedGroups.length === 0) ||
                                                   (targetType === 'classes' && selectedClasses.length === 0) ||
                                                   (targetType === 'children' && selectedChildren.length === 0)"
                                            class="ea-btn ea-btn-success ea-btn-lg w-full">
                                        <i class="fas fa-paper-plane"></i>
                                        Anwesenheit abfragen
                                    </button>
                                </form>
                            </div>
                        </div>
                        @endcan

                        </div>

                    <!-- Aktuelle Abfragen mit Rücklauf-Dashboard -->
                    <div class="mt-6 ea-card">
                        <div class="ea-card-header">
                            <div class="ea-card-icon"><i class="fas fa-chart-bar" aria-hidden="true"></i></div>
                            <h3 class="ea-card-title">Aktuelle Anwesenheitsabfragen – Rücklauf</h3>
                        </div>
                        <div class="p-4">
                            <p class="text-sm ea-muted mb-4">
                                Übersicht aller offenen Anwesenheitsabfragen mit Rücklaufquoten. Klicken Sie auf „Fehlende Antworten", um zu sehen, welche Eltern noch nicht geantwortet haben.
                            </p>

                            @forelse($abfragen as $date => $detail)
                                <div class="rounded-lg border ea-border p-4 mb-4 hover:shadow-md transition-shadow">
                                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-3 gap-2">
                                        <h6 class="font-bold ea-text flex items-center gap-2">
                                            <i class="fas fa-calendar" style="color: var(--color-primary)"></i>
                                            {{ \Carbon\Carbon::parse($date)->locale('de')->isoFormat('dddd, D. MMMM YYYY') }}
                                        </h6>
                                        <div class="flex items-center gap-3">
                                            <span class="text-lg font-bold {{ $detail['response_rate'] >= 80 ? 'text-green-600 dark:text-green-400' : ($detail['response_rate'] >= 50 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                                                {{ $detail['response_rate'] }}% beantwortet
                                            </span>
                                            @if(\Carbon\Carbon::parse($date)->isFuture())
                                                <div class="flex items-center gap-1">
                                                    <button type="button"
                                                            class="edit-comment-button ea-icon-btn"
                                                            data-date="{{ $date }}"
                                                            data-comment="{{ $detail['comment'] ?? '' }}">
                                                        <i class="fa fa-edit"></i>
                                                    </button>
                                                    @can('manage attendance queries')
                                                    <form action="{{ route('care.abfrage.destroy', ['date' => $date]) }}" method="post" class="delete-form inline">
                                                        @csrf
                                                        @method('delete')
                                                        <button type="button"
                                                                class="delete-button ea-icon-btn !text-red-600 dark:!text-red-400">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                    @endcan
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    @if($detail['comment'])
                                        <p class="text-sm ea-muted mb-3 italic">
                                            <i class="fas fa-comment-alt opacity-75 mr-1"></i> {{ $detail['comment'] }}
                                        </p>
                                    @endif

                                    {{-- Fortschrittsbalken --}}
                                    @if($detail['total'] > 0)
                                        <div class="ea-progress !h-4 mb-3">
                                            <div class="flex h-4">
                                                <div class="bg-green-500 transition-all" style="width: {{ $detail['coming'] / $detail['total'] * 100 }}%"
                                                     title="{{ $detail['coming'] }} kommen"></div>
                                                <div class="bg-red-400 transition-all" style="width: {{ $detail['not_coming'] / $detail['total'] * 100 }}%"
                                                     title="{{ $detail['not_coming'] }} kommen nicht"></div>
                                                <div class="transition-all" style="background-color: color-mix(in srgb, var(--color-text-secondary) 35%, transparent); width: {{ $detail['pending'] / $detail['total'] * 100 }}%"
                                                     title="{{ $detail['pending'] }} offen"></div>
                                            </div>
                                        </div>

                                        <div class="flex flex-wrap gap-4 text-sm mb-3">
                                            <span class="text-green-700 dark:text-green-400 font-medium"><i class="fas fa-check"></i> {{ $detail['coming'] }} kommen</span>
                                            <span class="text-red-600 dark:text-red-400 font-medium"><i class="fas fa-times"></i> {{ $detail['not_coming'] }} kommen nicht</span>
                                            <span class="ea-muted font-medium"><i class="fas fa-question-circle"></i> {{ $detail['pending'] }} offen</span>
                                            <span class="ea-muted opacity-75 text-xs">| Gesamt: {{ $detail['total'] }}</span>
                                            @if($detail['lock_at'])
                                                <span class="ea-muted opacity-75 text-xs">| Frist: {{ $detail['lock_at']->format('d.m.Y') }}</span>
                                            @endif
                                        </div>
                                    @endif

                                    {{-- Fehlende Eltern aufklappbar --}}
                                    @if($detail['pending'] > 0 && $detail['pending_children']->count() > 0)
                                        <details class="mt-2 border border-orange-200 dark:border-orange-500/40 rounded-lg">
                                            <summary class="cursor-pointer text-sm text-orange-700 dark:text-orange-300 font-semibold px-3 py-2 bg-orange-50 dark:bg-orange-900/30 rounded-t-lg hover:bg-orange-100 dark:hover:bg-orange-900/40 transition-colors">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                {{ $detail['pending_parents']->count() }} {{ $detail['pending_parents']->count() === 1 ? 'Elternteil hat' : 'Eltern haben' }} noch nicht geantwortet
                                                ({{ $detail['pending_children']->count() }} {{ $detail['pending_children']->count() === 1 ? 'Kind' : 'Kinder' }})
                                            </summary>
                                            <div class="px-3 py-2 space-y-1">
                                                @foreach($detail['pending_children'] as $child)
                                                    <div class="flex justify-between items-center py-1.5 px-2 ea-subtle rounded text-sm border-b ea-border last:border-b-0">
                                                        <span class="font-medium ea-text">
                                                            <i class="fas fa-child ea-muted mr-1"></i>
                                                            {{ $child->first_name }} {{ $child->last_name }}
                                                        </span>
                                                        <span class="ea-muted text-xs">
                                                            {{ $child->parents->pluck('name')->join(', ') }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </details>
                                    @elseif($detail['pending'] === 0 && $detail['total'] > 0)
                                        <div class="ea-alert ea-alert-success mt-2 !px-3 !py-2 !gap-2">
                                            <i class="fas fa-check-circle"></i>
                                            <span class="font-medium">Alle Eltern haben geantwortet!</span>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="flex flex-col items-center gap-2 ea-muted py-8">
                                    <i class="fas fa-inbox text-4xl"></i>
                                    <p class="text-sm">Keine zukünftigen Anwesenheitsabfragen vorhanden</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Statistiken & Planungshilfe  -->
                    <div class="mt-6 ea-card"
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
                        <div class="ea-card-header">
                            <div class="ea-card-icon"><i class="fas fa-chart-bar" aria-hidden="true"></i></div>
                            <h3 class="ea-card-title">Statistiken & Planungshilfe</h3>
                        </div>
                        <div class="p-4">
                            <p class="text-sm ea-muted mb-4">
                                Analysieren Sie Anwesenheitsabfragen eines beliebigen Zeitraums und exportieren Sie einen Ferienplan als PDF.
                            </p>

                            {{-- Zeitraum-Auswahl --}}
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div>
                                    <label class="ea-label">
                                        <i class="fas fa-calendar-alt" style="color: var(--color-primary)"></i> Datum von
                                    </label>
                                    <input type="date" x-model="dateStart"
                                           class="ea-input">
                                </div>
                                <div>
                                    <label class="ea-label">
                                        <i class="fas fa-calendar-alt" style="color: var(--color-primary)"></i> Datum bis
                                    </label>
                                    <input type="date" x-model="dateEnd"
                                           class="ea-input">
                                </div>
                                <div class="flex items-end">
                                    <button @click="loadStats()"
                                            :disabled="loading || !dateStart || !dateEnd"
                                            class="ea-btn ea-btn-primary w-full">
                                        <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-chart-line'"></i>
                                        <span x-text="loading ? 'Lädt...' : 'Statistiken laden'"></span>
                                    </button>
                                </div>
                            </div>

                            {{-- Fehlermeldung --}}
                            <div x-show="error" x-cloak class="ea-alert ea-alert-danger mb-4">
                                <i class="fas fa-exclamation-triangle"></i> <span x-text="error"></span>
                            </div>

                            {{-- Statistik-Karten --}}
                            <div x-show="stats !== null" x-cloak>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                    <div class="ea-stat flex-col justify-center text-center !gap-0">
                                        <div class="text-3xl font-bold" style="color: var(--color-primary)" x-text="stats?.forecast_max_children ?? 0"></div>
                                        <div class="text-sm ea-muted mt-1">Max. Kinder an einem Tag</div>
                                    </div>
                                    <div class="ea-stat flex-col justify-center text-center !gap-0">
                                        <div class="text-3xl font-bold text-green-600 dark:text-green-400" x-text="(stats?.response_rate ?? 0) + '%'"></div>
                                        <div class="text-sm ea-muted mt-1">Rücklaufquote</div>
                                    </div>
                                    <div class="ea-stat flex-col justify-center text-center !gap-0">
                                        <div class="text-3xl font-bold" style="color: var(--color-secondary)" x-text="stats?.avg_per_day ?? 0"></div>
                                        <div class="text-sm ea-muted mt-1">⌀ Kinder pro Tag</div>
                                    </div>
                                    <div class="ea-stat flex-col justify-center text-center !gap-0">
                                        <div class="text-3xl font-bold text-amber-600 dark:text-amber-400" x-text="stats?.pending_count ?? 0"></div>
                                        <div class="text-sm ea-muted mt-1">Noch offen</div>
                                    </div>
                                </div>

                                {{-- Balkendiagramm nach Datum --}}
                                <div class="mb-4">
                                    <h6 class="font-semibold ea-text mb-3 flex items-center gap-2">
                                        <i class="fas fa-calendar-week" style="color: var(--color-primary)"></i>
                                        Erwartete Kinder pro Tag
                                    </h6>
                                    <template x-for="(dayStat, date) in (stats ? stats.by_date : {})" :key="date">
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="text-xs font-mono ea-muted whitespace-nowrap"
                                                  style="min-width: 100px;"
                                                  x-text="new Date(date).toLocaleDateString('de-DE', { weekday: 'short', day: '2-digit', month: '2-digit' })">
                                            </span>
                                            <div class="flex-1 ea-subtle border ea-border rounded h-7 relative overflow-hidden">
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
                                                    <div class="flex items-center justify-center transition-all"
                                                         style="background-color: color-mix(in srgb, var(--color-text-secondary) 35%, transparent);"
                                                         :style="'width:' + (dayStat.total > 0 ? dayStat.pending/dayStat.total*100 : 0) + '%'"
                                                         :title="dayStat.pending + ' offen'">
                                                        <span x-show="dayStat.pending > 0" class="text-xs ea-text font-bold px-1" x-text="dayStat.pending"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <span class="text-xs ea-muted whitespace-nowrap" x-text="dayStat.total + ' ges.'"></span>
                                        </div>
                                    </template>
                                </div>

                                {{-- Legende --}}
                                <div class="flex flex-wrap gap-4 text-xs ea-muted mb-4">
                                    <span><span class="inline-block w-3 h-3 bg-green-500 rounded mr-1"></span>kommt</span>
                                    <span><span class="inline-block w-3 h-3 bg-red-400 rounded mr-1"></span>kommt nicht</span>
                                    <span><span class="inline-block w-3 h-3 rounded mr-1" style="background-color: color-mix(in srgb, var(--color-text-secondary) 35%, transparent);"></span>offen</span>
                                </div>

                                {{-- PDF-Export --}}
                                <div class="border-t ea-border pt-4 mt-2">
                                    <h6 class="font-semibold ea-text mb-3 flex items-center gap-2">
                                        <i class="fas fa-file-pdf text-red-500"></i>
                                        Ferienplan als PDF exportieren
                                    </h6>
                                    <p class="text-sm ea-muted mb-3">
                                        Exportiert alle Kinder, die sich für den gewählten Zeitraum angemeldet haben (inkl. Schickzeiten).
                                    </p>
                                    <form action="{{ route('care.abfrage.ferienplan.pdf') }}" method="post" class="inline">
                                        @csrf
                                        <input type="hidden" name="date_start" :value="dateStart">
                                        <input type="hidden" name="date_end" :value="dateEnd">
                                        <button type="submit"
                                                :disabled="!dateStart || !dateEnd"
                                                class="ea-btn ea-btn-primary">
                                            <i class="fas fa-file-pdf"></i>
                                            Ferienplan herunterladen
                                        </button>
                                    </form>
                                </div>
                            </div>

                            {{-- Hinweis vor dem ersten Laden --}}
                            <div x-show="stats === null && !loading && !error" class="flex flex-col items-center gap-2 ea-muted py-6">
                                <i class="fas fa-chart-bar text-3xl"></i>
                                <p class="text-sm">Zeitraum wählen und auf „Statistiken laden" klicken.</p>
                            </div>
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
                    <div class="mb-6 ea-card">
                        <div class="ea-card-header">
                            <div class="ea-card-icon"><i class="fas fa-download" aria-hidden="true"></i></div>
                            <h3 class="ea-card-title">Schickzeiten Export</h3>
                        </div>
                        <div class="p-4">
                            <p class="text-sm ea-muted mb-4">
                                Herunterladen der eingetragenen Schickzeiten als Excel-Datei
                            </p>
                            <a href="{{url('schickzeiten/download')}}"
                               class="ea-btn ea-btn-primary ea-btn-lg">
                                <i class="fas fa-file-excel"></i>
                                Excel herunterladen
                            </a>
                        </div>
                    </div>


                    <!-- Neue Abfrage erstellen -->
                    <div class="mt-6 ea-card">
                        <div class="ea-card-header">
                            <div class="ea-card-icon"><i class="fas fa-users-clock" aria-hidden="true"></i></div>
                            <h3 class="ea-card-title">Dauer-Schickzeiten der Kinder</h3>
                        </div>
                        <div class="p-4">
                            <p class="text-sm ea-muted mb-4">
                                Hier können für jedes Kind die hinterlegten Dauerschickzeiten angezeigt werden
                            </p>

                            <div class="space-y-2">
                                @foreach($children as $child)
                                    <div class="border ea-border rounded-lg overflow-hidden" x-data="{ open: false }">
                                        <button @click="open = !open"
                                                class="w-full flex items-center justify-between p-3 ea-row-link">
                                            <span class="font-medium ea-text">
                                                {{$child->last_name}}, {{$child->first_name}}
                                            </span>
                                            <div class="flex items-center gap-3">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: color-mix(in srgb, var(--color-primary) 12%, transparent); color: var(--color-primary);">
                                                    {{$child->schickzeiten->count()}} Zeiten
                                                </span>
                                                <i class="fas fa-chevron-down ea-muted transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                                            </div>
                                        </button>

                                        <div x-show="open"
                                             x-transition:enter="transition ease-out duration-200"
                                             x-transition:enter-start="opacity-0 max-h-0"
                                             x-transition:enter-end="opacity-100 max-h-screen"
                                             style="display: none;"
                                             class="border-t ea-border">
                                            <div class="p-4 ea-subtle">
                                                <div class="overflow-x-auto rounded-lg border ea-border" style="background-color: var(--color-card-bg);">
                                                    <table class="ea-table">
                                                        <thead>
                                                            <tr>
                                                                <th class="px-3 py-2 text-left text-xs font-medium uppercase">Tag</th>
                                                                <th class="px-3 py-2 text-left text-xs font-medium uppercase">Ab</th>
                                                                <th class="px-3 py-2 text-left text-xs font-medium uppercase">Genau</th>
                                                                <th class="px-3 py-2 text-left text-xs font-medium uppercase">Spätestens</th>
                                                                <th class="px-3 py-2 text-right text-xs font-medium uppercase">Aktionen</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @for($x=1;$x<6;$x++)
                                                                <tr>
                                                                    <td class="px-3 py-2 whitespace-nowrap text-sm font-medium">{{$weekdays[$x]}}</td>
                                                                    <td class="px-3 py-2 whitespace-nowrap text-sm" style="color: var(--color-text-secondary)">
                                                                        @if($child->schickzeiten->where('weekday', $x)->count() > 0)
                                                                            {{$child->schickzeiten->where('weekday', $x)->first()->time_ab?->format('H:i')}}
                                                                        @else
                                                                            <span class="ea-muted opacity-75">-</span>
                                                                        @endif
                                                                    </td>
                                                                    <td class="px-3 py-2 whitespace-nowrap text-sm" style="color: var(--color-text-secondary)">
                                                                        @if($child->schickzeiten->where('weekday', $x)->count() > 0)
                                                                            {{ $child->schickzeiten->where('weekday', $x)->first()?->time?->format('H:i') }}
                                                                        @else
                                                                            <span class="ea-muted opacity-75">-</span>
                                                                        @endif
                                                                    </td>
                                                                    <td class="px-3 py-2 whitespace-nowrap text-sm" style="color: var(--color-text-secondary)">
                                                                        @if($child->schickzeiten->where('weekday', $x)->count() > 0)
                                                                            {{$child->schickzeiten->where('weekday', $x)->first()?->time_spaet?->format('H:i')}}
                                                                        @else
                                                                            <span class="ea-muted opacity-75">-</span>
                                                                        @endif
                                                                    </td>
                                                                    <td class="px-3 py-2 whitespace-nowrap text-right text-sm">
                                                                        <div class="flex items-center justify-end gap-2">
                                                                            <a href="{{route('schickzeiten.edit',['child' => $child->id, 'day' => $x])}}"
                                                                               class="ea-icon-btn">
                                                                                <i class="fa fa-edit"></i>
                                                                            </a>
                                                                            @if($child->schickzeiten->where('weekday', $x)->first())
                                                                                <form action="{{route('schickzeiten.destroy', ['schickzeit' => $child->schickzeiten->where('weekday', $x)->first()->id])}}"
                                                                                      method="post">
                                                                                    @csrf
                                                                                    @method('delete')
                                                                                    <button type="submit"
                                                                                            onclick="return confirm('Wirklich löschen?')"
                                                                                            class="ea-icon-btn !text-red-600 dark:!text-red-400">
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
                                                            <tfoot class="border-t ea-border" style="background-color: color-mix(in srgb, var(--color-primary) 6%, transparent);">
                                                                <tr>
                                                                    <td colspan="5" class="px-3 py-2 text-xs font-semibold ea-text">Tagesaktuelle Schickzeiten</td>
                                                                </tr>
                                                                @foreach($child->schickzeiten->where('weekday', null) as $schickzeit)
                                                                    <tr class="ea-row-link">
                                                                        <td class="px-3 py-2 whitespace-nowrap text-sm font-medium ea-text">
                                                                            {{$schickzeit->specific_date?->format('d.m.Y')}}
                                                                        </td>
                                                                        <td colspan="3" class="px-3 py-2 text-sm ea-muted">
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
                                                                                        class="ea-icon-btn !text-red-600 dark:!text-red-400">
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

                <div x-show="activeTab === 'verlauf'"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     style="display: none;"
                     x-data="{
                         childFilter: '',
                         contentFilter: ''
                     }">
                    <div class="ea-card">
                        <div class="ea-card-header">
                            <div class="ea-card-icon"><i class="fas fa-history" aria-hidden="true"></i></div>
                            <h3 class="ea-card-title">Nachrichten und Schickzeiten</h3>
                        </div>
                        <div class="p-4">
                            <p class="mb-5 text-sm ea-muted">
                                Vergangene Einträge der letzten 4 Wochen sowie alle zukünftigen Nachrichten.
                                Zukünftige Nachrichten sind farblich hervorgehoben.
                            </p>
                            <div class="flex flex-col lg:flex-row gap-4 mb-5">
                                <div class="flex-1">
                                    <label class="ea-label">Kind suchen</label>
                                    <input type="text"
                                           x-model="childFilter"
                                           placeholder="Nach Kind, Vor- oder Nachname..."
                                           class="ea-input">
                                </div>
                                <div class="flex-1">
                                    <label class="ea-label">Inhalt suchen</label>
                                    <input type="text"
                                           x-model="contentFilter"
                                           placeholder="Nach Nachricht, Uhrzeit oder Hinweis..."
                                           class="ea-input">
                                </div>
                            </div>

                            @if($futureHistoryEntries->isEmpty() && $pastHistoryEntries->isEmpty())
                                <div class="flex flex-col items-center ea-muted py-10">
                                    <i class="fas fa-inbox text-4xl mb-3"></i>
                                    <p class="text-sm">Es wurden keine passenden Nachrichten oder Schickzeiten erfasst.</p>
                                </div>
                            @else
                                @foreach([
                                    [
                                        'title' => 'Zukünftige Nachrichten',
                                        'description' => 'Geplante Nachrichten, beginnend mit dem nächsten Termin.',
                                        'icon' => 'fa-calendar-plus',
                                        'entries' => $futureHistoryEntries,
                                        'future' => true,
                                    ],
                                    [
                                        'title' => 'Vergangene Einträge',
                                        'description' => 'Nachrichten und Schickzeiten der vergangenen 4 Wochen.',
                                        'icon' => 'fa-history',
                                        'entries' => $pastHistoryEntries,
                                        'future' => false,
                                    ],
                                ] as $historySection)
                                    <section class="{{ !$loop->first ? 'mt-8 border-t ea-border pt-6' : '' }}">
                                        <div class="mb-3 flex items-start justify-between gap-3">
                                            <div>
                                                <h4 class="flex items-center gap-2 text-base font-bold {{ $historySection['future'] ? '' : 'ea-text' }}" @if($historySection['future']) style="color: var(--color-primary)" @endif>
                                                    <i class="fas {{ $historySection['icon'] }}"></i>
                                                    {{ $historySection['title'] }}
                                                </h4>
                                                <p class="mt-1 text-sm ea-muted">{{ $historySection['description'] }}</p>
                                            </div>
                                            <span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $historySection['future'] ? '' : 'ea-subtle ea-muted border ea-border' }}" @if($historySection['future']) style="background-color: color-mix(in srgb, var(--color-primary) 12%, transparent); color: var(--color-primary);" @endif>
                                                {{ $historySection['entries']->count() }}
                                            </span>
                                        </div>

                                        @if($historySection['entries']->isEmpty())
                                            <div class="rounded-lg border border-dashed ea-border px-4 py-6 text-center text-sm ea-muted">
                                                {{ $historySection['future'] ? 'Keine zukünftigen Nachrichten vorhanden.' : 'Keine vergangenen Einträge vorhanden.' }}
                                            </div>
                                        @else
                                            <div class="space-y-3">
                                                @foreach($historySection['entries'] as $historyEntry)
                                                    <div class="rounded-lg border p-4 shadow-sm {{ $historyEntry['is_future'] ? '' : 'ea-border ea-subtle' }}"
                                                         @if($historyEntry['is_future']) style="border-color: var(--color-primary); background-color: color-mix(in srgb, var(--color-primary) 7%, var(--color-card-bg)); box-shadow: 0 0 0 1px color-mix(in srgb, var(--color-primary) 30%, transparent), 0 1px 2px rgba(0,0,0,0.05);" @endif
                                                         data-child-name="{{ strtolower($historyEntry['child_name']) }}"
                                                         data-content="{{ strtolower($historyEntry['content'] ?? '') }}"
                                                         x-show="(!childFilter || $el.dataset.childName.includes(childFilter.toLowerCase())) && (!contentFilter || $el.dataset.content.includes(contentFilter.toLowerCase()))">
                                                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-2">
                                                            <div class="flex items-center gap-3 flex-wrap">
                                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $historyEntry['kind'] === 'notice' ? '' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' }}" @if($historyEntry['kind'] === 'notice') style="background-color: color-mix(in srgb, var(--color-primary) 12%, transparent); color: var(--color-primary);" @endif>
                                                                    {{ $historyEntry['type'] }}
                                                                </span>
                                                                @if($historyEntry['is_future'])
                                                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold text-white" style="background-color: var(--color-primary);">
                                                                        <i class="fas fa-calendar-plus"></i>
                                                                        Zukünftig
                                                                    </span>
                                                                @endif
                                                                <h4 class="font-semibold ea-text">{{ $historyEntry['child_name'] }}</h4>
                                                            </div>
                                                            <div class="text-xs {{ $historyEntry['is_future'] ? 'font-semibold' : 'ea-muted' }}" @if($historyEntry['is_future']) style="color: var(--color-primary)" @endif>
                                                                {{ \Carbon\Carbon::parse($historyEntry['date'])->locale('de')->isoFormat('DD.MM.YYYY, HH:mm') }} Uhr
                                                            </div>
                                                        </div>

                                                        <p class="text-sm ea-text whitespace-pre-line leading-relaxed">{{ $historyEntry['content'] }}</p>

                                                        <div class="mt-3 flex items-center justify-between flex-wrap gap-2 text-xs ea-muted">
                                                            <span class="inline-flex items-center gap-1">
                                                                <i class="fas fa-user"></i>
                                                                {{ $historyEntry['author'] }}
                                                            </span>
                                                            @if($historyEntry['kind'] === 'notice')
                                                                <span class="inline-flex items-center gap-1" style="color: var(--color-primary)">
                                                                    <i class="fas fa-comment-dots"></i>
                                                                    Nachricht
                                                                </span>
                                                            @else
                                                                <span class="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400">
                                                                    <i class="fas fa-clock"></i>
                                                                    Schickzeit
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </section>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Verspätete Abholungen Tab -->
                <div x-show="activeTab === 'late_pickups'"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     style="display: none;">

                    <div class="ea-card mb-6">
                        <div class="ea-card-header">
                            <div class="ea-card-icon !bg-red-100 !text-red-600 dark:!bg-red-900/40 dark:!text-red-300"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i></div>
                            <h3 class="ea-card-title">Verspätete Abholungen</h3>
                        </div>
                        <div class="p-4">
                            <p class="text-sm ea-muted mb-4">
                                Übersicht über Kinder, die nach der hinterlegten Schickzeit abgeholt wurden. Verspätete Abholungen müssen
                                bestätigt oder verworfen werden (z. B. wenn das Austragen im System vergessen wurde, das Kind aber
                                rechtzeitig abgeholt wurde).
                            </p>

                            {{-- Filter --}}
                            <form method="get" action="{{ url('verwaltung/schickzeiten') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6 items-end">
                                <input type="hidden" name="tab" value="late_pickups">
                                <div>
                                    <label class="ea-label">
                                        <i class="fas fa-calendar-week text-red-600 dark:text-red-400"></i> Woche
                                    </label>
                                    <input type="week" name="late_week"
                                           value="{{ $lateWeekStart->format('o-\WW') }}"
                                           class="ea-input">
                                </div>
                                <div>
                                    <label class="ea-label">Gruppe</label>
                                    <select name="late_group_id" class="ea-input">
                                        <option value="">Alle Gruppen</option>
                                        @foreach($careGroups as $group)
                                            <option value="{{ $group->id }}" @selected((string) $lateGroupId === (string) $group->id)>{{ $group->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="ea-label">Klasse</label>
                                    <select name="late_class_id" class="ea-input">
                                        <option value="">Alle Klassen</option>
                                        @foreach($careClasses as $class)
                                            <option value="{{ $class->id }}" @selected((string) $lateClassId === (string) $class->id)>{{ $class->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="ea-label">Kind (Einzelansicht)</label>
                                    <select name="late_child_id" class="ea-input">
                                        <option value="">Wochenansicht (alle Kinder)</option>
                                        @foreach($latePickupChildren as $lpChild)
                                            <option value="{{ $lpChild->id }}" @selected((string) $lateChildId === (string) $lpChild->id)>{{ $lpChild->last_name }}, {{ $lpChild->first_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex gap-2">
                                    <button type="submit"
                                            class="ea-btn ea-btn-primary flex-1">
                                        <i class="fas fa-filter"></i> Filtern
                                    </button>
                                </div>
                            </form>

                            @if(!$selectedLateChild)
                                {{-- Wochennavigation --}}
                                <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                                    <a href="{{ request()->fullUrlWithQuery(['late_week' => $lateWeekStart->copy()->subWeek()->format('o-\WW'), 'tab' => 'late_pickups']) }}"
                                       class="ea-btn ea-btn-secondary ea-btn-sm">
                                        <i class="fas fa-chevron-left"></i> Vorherige Woche
                                    </a>
                                    <span class="font-medium ea-text text-center">
                                        {{ $lateWeekStart->format('d.m.Y') }} – {{ $lateWeekEnd->format('d.m.Y') }}
                                    </span>
                                    <a href="{{ request()->fullUrlWithQuery(['late_week' => $lateWeekStart->copy()->addWeek()->format('o-\WW'), 'tab' => 'late_pickups']) }}"
                                       class="ea-btn ea-btn-secondary ea-btn-sm">
                                        Nächste Woche <i class="fas fa-chevron-right"></i>
                                    </a>
                                </div>

                                @if($latePickupsByDate->isEmpty())
                                    <div class="ea-alert ea-alert-success">
                                        <i class="fas fa-check-circle"></i> Keine verspäteten Abholungen in dieser Woche.
                                    </div>
                                @else
                                    <div class="space-y-6">
                                        @foreach($latePickupsByDate as $date => $entries)
                                            <div class="border ea-border rounded-lg overflow-hidden">
                                                <div class="ea-subtle border-b ea-border px-4 py-2 font-semibold ea-text">
                                                    {{ \Carbon\Carbon::parse($date)->translatedFormat('l, d.m.Y') }}
                                                </div>
                                                <div class="overflow-x-auto">
                                                    <table class="ea-table">
                                                        <thead>
                                                        <tr>
                                                            <th class="px-3 py-2 text-left text-xs font-medium uppercase">Kind</th>
                                                            <th class="px-3 py-2 text-left text-xs font-medium uppercase">Erwartet bis</th>
                                                            <th class="px-3 py-2 text-left text-xs font-medium uppercase">Abgeholt um</th>
                                                            <th class="px-3 py-2 text-left text-xs font-medium uppercase">Verspätung</th>
                                                            <th class="px-3 py-2 text-left text-xs font-medium uppercase">Status</th>
                                                            @if($canManageLatePickups)
                                                                <th class="px-3 py-2 text-right text-xs font-medium uppercase">Aktionen</th>
                                                            @endif
                                                        </tr>
                                                        </thead>
                                                        <tbody>
                                                        @foreach($entries as $entry)
                                                            @include('schickzeiten.partials.late_pickup_row', ['entry' => $entry, 'canManageLatePickups' => $canManageLatePickups])
                                                        @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            @else
                                {{-- Einzelkind-Ansicht --}}
                                <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                                    <h4 class="text-lg font-semibold ea-text mb-0">
                                        Verspätete Abholungen: {{ $selectedLateChild->last_name }}, {{ $selectedLateChild->first_name }}
                                    </h4>
                                    <a href="{{ request()->fullUrlWithQuery(['late_child_id' => '', 'tab' => 'late_pickups']) }}"
                                       class="ea-btn ea-btn-secondary ea-btn-sm">
                                        <i class="fas fa-arrow-left"></i> Zur Wochenansicht
                                    </a>
                                </div>

                                @if($childLatePickups->isEmpty())
                                    <div class="ea-alert ea-alert-success">
                                        <i class="fas fa-check-circle"></i> Keine verspäteten Abholungen für dieses Kind (letzte 90 Tage).
                                    </div>
                                @else
                                    <div class="border ea-border rounded-lg overflow-hidden">
                                        <div class="overflow-x-auto">
                                            <table class="ea-table">
                                                <thead>
                                                <tr>
                                                    <th class="px-3 py-2 text-left text-xs font-medium uppercase">Datum</th>
                                                    <th class="px-3 py-2 text-left text-xs font-medium uppercase">Erwartet bis</th>
                                                    <th class="px-3 py-2 text-left text-xs font-medium uppercase">Abgeholt um</th>
                                                    <th class="px-3 py-2 text-left text-xs font-medium uppercase">Verspätung</th>
                                                    <th class="px-3 py-2 text-left text-xs font-medium uppercase">Status</th>
                                                    @if($canManageLatePickups)
                                                        <th class="px-3 py-2 text-right text-xs font-medium uppercase">Aktionen</th>
                                                    @endif
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($childLatePickups as $entry)
                                                    @include('schickzeiten.partials.late_pickup_row', ['entry' => $entry, 'canManageLatePickups' => $canManageLatePickups, 'showDate' => true])
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
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

            <div class="relative ea-card shadow-xl max-w-md w-full"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100">

                <div class="ea-card-header">
                    <div class="ea-card-icon"><i class="fas fa-comment-alt" aria-hidden="true"></i></div>
                    <h3 class="ea-card-title">Kommentar bearbeiten</h3>
                </div>

                <form id="commentForm" method="POST" action="{{route('anwesenheit.comment.update')}}">
                    @csrf
                    <div class="p-6">
                        <div class="mb-4">
                            <label class="ea-label">Kommentar</label>
                            <textarea name="comment"
                                      x-model="comment"
                                      class="ea-input resize-none"
                                      rows="3"
                                      maxlength="256"
                                      required></textarea>
                        </div>
                        <input type="hidden" name="date" x-model="date">
                    </div>

                    <div class="ea-card-footer flex items-center justify-between gap-3">
                        <button type="button"
                                @click="show = false"
                                class="ea-btn ea-btn-secondary">
                            Abbrechen
                        </button>
                        <div class="flex gap-2">
                            <button type="button"
                                    @click="deleteComment(date)"
                                    class="ea-btn ea-btn-danger">
                                Löschen
                            </button>
                            <button type="submit"
                                    class="ea-btn ea-btn-primary">
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
