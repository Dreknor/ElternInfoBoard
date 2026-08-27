@extends('layouts.app')

@section('content')
@php
    // Defensive Fallbacks – werden normalerweise vom Controller befüllt
    $overlappingIds = $overlappingIds ?? [];
    $overlapGroups  = $overlapGroups  ?? collect();
    $entryGroupMap  = $entryGroupMap  ?? [];
@endphp
    <div class="container-fluid px-4 py-6">
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                {{ session('error') }}
            </div>
        @endif
        <!-- Statistik-Dashboard -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <!-- Gesamt Familien -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Gesamt Familien</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $stats['totalFamilies'] }}</p>
                    </div>
                    <div class="bg-blue-100 rounded-full p-3">
                        <i class="fas fa-users text-2xl text-blue-600"></i>
                    </div>
                </div>
            </div>

            <!-- Geleistete Stunden -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Geleistete Stunden</p>
                        <p class="text-3xl font-bold text-blue-600">{{ round($stats['totalHoursCompleted'], 1) }}h</p>
                        <p class="text-xs text-gray-500 mt-1">
                            von {{ round($stats['totalHoursRequired'], 1) }}h erforderlich
                        </p>
                    </div>
                    <div class="bg-blue-100 rounded-full p-3">
                        <i class="fas fa-clock text-2xl text-blue-600"></i>
                    </div>
                </div>
            </div>

            <!-- Vollständig erfüllt -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Erfüllt (100%)</p>
                        <p class="text-3xl font-bold text-green-600">{{ $stats['completed'] }}</p>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ $stats['totalFamilies'] > 0 ? round(($stats['completed'] / $stats['totalFamilies']) * 100) : 0 }}% aller Familien
                        </p>
                    </div>
                    <div class="bg-green-100 rounded-full p-3">
                        <i class="fas fa-check-circle text-2xl text-green-600"></i>
                    </div>
                </div>
            </div>

            <!-- Teilweise erfüllt -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">In Arbeit</p>
                        <p class="text-3xl font-bold text-yellow-600">{{ $stats['partial'] }}</p>

                        <p class="text-xs text-gray-500 mt-1" title="Aktuell bestätigt: Ø {{ $stats['avgPercent'] }}%">
                            Erwartet: Ø {{ $stats['expectedAvgPercent'] }}% Erfüllung
                        </p>
                        <p class="text-xs text-gray-500 mt-1" title="Aktuell bestätigt: Ø {{ $stats['avgPercent'] }}%">
                            Enthält geplante zukünftige Stunden
                        </p>
                    </div>
                    <div class="bg-yellow-100 rounded-full p-3">
                        <i class="fas fa-hourglass-half text-2xl text-yellow-600" aria-hidden="true"></i>
                    </div>
                </div>
            </div>

            <!-- Zu zahlender Beitrag -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Zu zahlen</p>
                        <p class="text-3xl font-bold text-red-600">{{ number_format($stats['totalBeitrag'], 2, ',', '.') }} €</p>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ round($stats['totalHoursMissing'], 1) }}h offen
                        </p>
                    </div>
                    <div class="bg-red-100 rounded-full p-3">
                        <i class="fas fa-euro-sign text-2xl text-red-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Freigabe & Schnellzugriff -->
        <div class="grid grid-cols-1 xl:grid-cols-[1.55fr_0.85fr] gap-6 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden" x-data="{
                selectedIds: [],
                filterBereich: '',
                init() {
                    const urlParams = new URLSearchParams(window.location.search);
                    const urlFilter = urlParams.get('bereich_filter');
                    const storageFilter = localStorage.getItem('pflichtstunden_bereich_filter');

                    if (urlFilter) {
                        this.filterBereich = urlFilter;
                    } else if (storageFilter) {
                        this.filterBereich = storageFilter;
                    }

                    if (this.filterBereich) {
                        this.$nextTick(() => this.filterByBereich());
                    }
                },
                toggleAll() {
                    const visibleIds = this.getVisiblePflichtstunden();
                    if (this.selectedIds.length === visibleIds.length && visibleIds.length > 0) {
                        this.selectedIds = [];
                    } else {
                        this.selectedIds = visibleIds;
                    }
                },
                getVisiblePflichtstunden() {
                    const rows = document.querySelectorAll('[data-pflichtstunde-row]');
                    const visibleIds = [];
                    rows.forEach(row => {
                        if (row.style.display !== 'none') {
                            visibleIds.push(parseInt(row.getAttribute('data-pflichtstunde-id')));
                        }
                    });
                    return visibleIds;
                },
                get allSelected() {
                    const visibleIds = this.getVisiblePflichtstunden();
                    return this.selectedIds.length === visibleIds.length && visibleIds.length > 0;
                },
                get someSelected() {
                    const visibleIds = this.getVisiblePflichtstunden();
                    return this.selectedIds.length > 0 && this.selectedIds.length < visibleIds.length;
                },
                filterByBereich() {
                    const rows = document.querySelectorAll('[data-pflichtstunde-row]');
                    rows.forEach(row => {
                        const bereich = row.getAttribute('data-bereich') || '';
                        row.style.display = (this.filterBereich === '' || bereich === this.filterBereich) ? '' : 'none';
                    });

                    if (this.filterBereich) {
                        localStorage.setItem('pflichtstunden_bereich_filter', this.filterBereich);
                    } else {
                        localStorage.removeItem('pflichtstunden_bereich_filter');
                    }

                    const url = new URL(window.location.href);
                    if (this.filterBereich) {
                        url.searchParams.set('bereich_filter', this.filterBereich);
                    } else {
                        url.searchParams.delete('bereich_filter');
                    }
                    window.history.replaceState({}, '', url.toString());
                },
                approveSelected() {
                    if (this.selectedIds.length === 0) {
                        alert('Bitte wählen Sie mindestens eine Pflichtstunde aus.');
                        return;
                    }

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route('pflichtstunden.approveMultiple') }}';

                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = '{{ csrf_token() }}';
                    form.appendChild(csrfToken);

                    const methodField = document.createElement('input');
                    methodField.type = 'hidden';
                    methodField.name = '_method';
                    methodField.value = 'PUT';
                    form.appendChild(methodField);

                    const idsInput = document.createElement('input');
                    idsInput.type = 'hidden';
                    idsInput.name = 'ids';
                    idsInput.value = JSON.stringify(this.selectedIds);
                    form.appendChild(idsInput);

                    document.body.appendChild(form);
                    form.submit();
                }
            }">
                <div class="px-6 py-4 border-b border-slate-200 bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 text-white">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-3">
                            <div class="h-11 w-11 rounded-xl bg-white/15 flex items-center justify-center">
                                <i class="fas fa-clock text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold">Freigabe von Pflichtstunden</h3>
                                <p class="text-sm text-white/80">Wartende Einträge im Fokus – schnell genehmigen, ablehnen oder bearbeiten.</p>
                            </div>
                        </div>
                        <div x-show="selectedIds.length > 0" x-cloak class="flex items-center gap-3">
                            <span class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-sm font-medium">
                                <span x-text="selectedIds.length"></span> ausgewählt
                            </span>
                            <button @click="approveSelected()" class="inline-flex items-center gap-2 rounded-lg bg-emerald-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-400">
                                <i class="fas fa-check-double"></i>
                                Ausgewählte bestätigen
                            </button>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    @if($pflichtstunden->isEmpty())
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-8 text-center text-emerald-700">
                            <i class="fas fa-check-circle text-4xl mb-3"></i>
                            <p class="text-lg font-semibold">Keine unbestätigten Pflichtstunden vorhanden</p>
                            <p class="mt-2 text-sm text-emerald-600">Der Freigabeprozess ist aktuell sauber.</p>
                        </div>
                    @else
                        <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            @if(!empty($pflichtstunden_settings->pflichtstunden_bereiche) && count($pflichtstunden_settings->pflichtstunden_bereiche) > 0)
                                <div class="flex items-center gap-3">
                                    <label class="text-sm font-medium text-slate-700"><i class="fas fa-filter text-blue-500 mr-2"></i>Bereich</label>
                                    <select x-model="filterBereich" @change="filterByBereich()" class="rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                        <option value="">Alle Bereiche</option>
                                        @foreach($pflichtstunden_settings->pflichtstunden_bereiche as $bereich)
                                            <option value="{{ $bereich }}">{{ $bereich }}</option>
                                        @endforeach
                                        <option value="__KEIN_BEREICH__">Ohne Bereich</option>
                                    </select>
                                </div>
                            @endif
                            <div class="text-sm text-slate-500">{{ $pflichtstunden->count() }} Einträge warten auf Freigabe</div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left"><input type="checkbox" @click="toggleAll()" :checked="allSelected" :indeterminate="someSelected" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"></th>
                                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600">Datum</th>
                                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600">Dauer</th>
                                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600">Person</th>
                                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600">Grund</th>
                                        @if(!empty($pflichtstunden_settings->pflichtstunden_bereiche) && count($pflichtstunden_settings->pflichtstunden_bereiche) > 0)
                                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600">Bereich</th>
                                        @endif
                                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600">Aktionen</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white">
                                    @foreach ($pflichtstunden as $pflichtstunde)
                                        @php $hasOverlap = in_array($pflichtstunde->id, $overlappingIds); @endphp
                                        <tr class="transition-colors hover:bg-slate-50 {{ $hasOverlap ? 'bg-orange-50' : '' }}"
                                            data-pflichtstunde-row
                                            data-pflichtstunde-id="{{ $pflichtstunde->id }}"
                                            data-bereich="{{ $pflichtstunde->bereich ?? '__KEIN_BEREICH__' }}"
                                            :class="{ 'bg-blue-50': selectedIds.includes({{ $pflichtstunde->id }}) }"
                                            x-data="{
                                                showEdit: false,
                                                editData: {
                                                    start: '{{ $pflichtstunde->start->format('Y-m-d\TH:i') }}',
                                                    end: '{{ $pflichtstunde->end->format('Y-m-d\TH:i') }}',
                                                    description: {{ Js::from($pflichtstunde->description) }}
                                                }
                                            }">
                                            <td class="px-4 py-3"><input type="checkbox" value="{{ $pflichtstunde->id }}" x-model="selectedIds" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"></td>
                                            <td class="px-4 py-3 text-sm text-slate-700">
                                                <span x-show="!showEdit">
                                                    @if($pflichtstunde->start->isSameDay($pflichtstunde->end))
                                                        <div class="font-semibold text-slate-800">{{ $pflichtstunde->start->format('d.m.Y') }}</div>
                                                        <div class="text-xs text-slate-500">{{ $pflichtstunde->start->format('H:i') }} - {{ $pflichtstunde->end->format('H:i') }}</div>
                                                    @else
                                                        <div class="text-xs text-slate-500">{{ $pflichtstunde->start->format('d.m.Y H:i') }}</div>
                                                        <div class="text-xs text-slate-500">{{ $pflichtstunde->end->format('d.m.Y H:i') }}</div>
                                                    @endif
                                                </span>
                                                <div x-show="showEdit" x-cloak class="space-y-2">
                                                    <input type="datetime-local" x-model="editData.start" class="w-full rounded border border-slate-300 px-2 py-1 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-200">
                                                    <input type="datetime-local" x-model="editData.end" class="w-full rounded border border-slate-300 px-2 py-1 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-200">
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                                    @if($pflichtstunde->duration > 60)
                                                        {{ floor($pflichtstunde->duration / 60) }}h {{ $pflichtstunde->duration % 60 }}m
                                                    @else
                                                        {{ $pflichtstunde->duration }}m
                                                    @endif
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm font-medium text-slate-900">
                                                {{ $pflichtstunde->user?->name ?? 'Unbekannt / gelöschter Benutzer' }}
                                                @if($hasOverlap)
                                                    @php $groupId = $entryGroupMap[$pflichtstunde->id] ?? null; @endphp
                                                    <span class="ml-2 inline-flex items-center gap-1 rounded-full border border-orange-200 bg-orange-100 px-2 py-0.5 text-[10px] font-semibold text-orange-700" title="Überlappungsgruppe {{ $groupId }}">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                        Überlappung{!! $groupId ? ' (Gr. '.$groupId.')' : '' !!}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-sm text-slate-700">
                                                <span x-show="!showEdit">{{ $pflichtstunde->description }}</span>
                                                <textarea x-show="showEdit" x-cloak x-model="editData.description" rows="2" class="w-full rounded border border-slate-300 px-2 py-1 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-200"></textarea>
                                            </td>
                                            @if(!empty($pflichtstunden_settings->pflichtstunden_bereiche) && count($pflichtstunden_settings->pflichtstunden_bereiche) > 0)
                                                <td class="px-4 py-3 text-sm">
                                                    @if($pflichtstunde->bereich)
                                                        <span class="inline-flex items-center gap-1 rounded bg-violet-100 px-2 py-1 text-[11px] font-medium text-violet-700"><i class="fas fa-folder"></i>{{ $pflichtstunde->bereich }}</span>
                                                    @else
                                                        <span class="text-xs text-slate-400">-</span>
                                                    @endif
                                                </td>
                                            @endif
                                            <td class="px-4 py-3 text-sm">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <button @click="showEdit = !showEdit" type="button" class="inline-flex items-center gap-1 rounded bg-blue-600 px-2.5 py-1.5 text-[11px] font-medium text-white hover:bg-blue-500">
                                                        <i class="fas" :class="showEdit ? 'fa-times' : 'fa-edit'"></i>
                                                        <span x-text="showEdit ? 'Abbrechen' : 'Bearbeiten'"></span>
                                                    </button>
                                                    <form x-show="showEdit" x-cloak :action="`{{ route('pflichtstunden.update', $pflichtstunde) }}`" method="POST" class="inline">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="start" :value="editData.start">
                                                        <input type="hidden" name="end" :value="editData.end">
                                                        <input type="hidden" name="description" :value="editData.description">
                                                        <button type="submit" onclick="return confirm('Änderungen speichern?');" class="inline-flex items-center gap-1 rounded bg-indigo-600 px-2.5 py-1.5 text-[11px] font-medium text-white hover:bg-indigo-500"><i class="fas fa-save"></i>Speichern</button>
                                                    </form>
                                                    <form action="{{ route('pflichtstunden.approve', $pflichtstunde) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="bereich_filter" x-bind:value="filterBereich">
                                                        <button type="submit" class="inline-flex items-center gap-1 rounded bg-emerald-600 px-2.5 py-1.5 text-[11px] font-medium text-white hover:bg-emerald-500"><i class="fas fa-check"></i>Bestätigen</button>
                                                    </form>
                                                    <div x-data="{ showReject: false }" class="inline-flex items-center gap-2">
                                                        <button @click="showReject = !showReject" type="button" class="inline-flex items-center gap-1 rounded bg-red-600 px-2.5 py-1.5 text-[11px] font-medium text-white hover:bg-red-500"><i class="fas fa-times"></i>Ablehnen</button>
                                                        <form x-show="showReject" x-transition action="{{ route('pflichtstunden.reject', $pflichtstunde) }}" method="POST" class="inline-flex items-center gap-2">
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="hidden" name="bereich_filter" x-bind:value="filterBereich">
                                                            <input name="rejection_reason" type="text" class="rounded border border-slate-300 px-2 py-1 text-xs focus:border-red-500 focus:ring-1 focus:ring-red-200" placeholder="Grund..." required>
                                                            <button type="submit" onclick="return confirm('Möchten Sie diese Pflichtstunde wirklich ablehnen?');" class="rounded bg-red-600 px-2 py-1 text-white hover:bg-red-500"><i class="fas fa-check"></i></button>
                                                        </form>
                                                    </div>
                                                    <form action="{{ route('pflichtstunden.destroy', $pflichtstunde) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" onclick="return confirm('Möchten Sie diese Pflichtstunde wirklich löschen?');" class="inline-flex items-center gap-1 rounded bg-slate-600 px-2.5 py-1.5 text-[11px] font-medium text-white hover:bg-slate-500"><i class="fas fa-trash"></i>Löschen</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div x-show="selectedIds.length > 0" x-cloak class="mt-5 flex items-center justify-end gap-3">
                            <span class="text-sm font-medium text-slate-700"><span x-text="selectedIds.length"></span> ausgewählt</span>
                            <button @click="approveSelected()" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500"><i class="fas fa-check-double"></i>Ausgewählte bestätigen</button>
                        </div>
                    @endif
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-lg font-bold text-slate-900"><i class="fas fa-file-pdf text-red-500 mr-2"></i>PDF-Report</h3>
                    </div>
                    <form method="GET" action="{{ route('pflichtstunden.report.pdf') }}" class="mt-4 space-y-3">
                        <div>
                            <label for="report_year" class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600">Schuljahr</label>
                            <select id="report_year" name="year" class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                @foreach($availableYears as $year)
                                    <option value="{{ $year }}" {{ (int) $selectedYear === (int) $year ? 'selected' : '' }}>{{ $year }} / {{ $year + 1 }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="report_start" class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600">Start</label>
                                <input type="date" id="report_start" name="start" class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                            </div>
                            <div>
                                <label for="report_end" class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600">Ende</label>
                                <input type="date" id="report_end" name="end" class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600">Sortierung</label>
                            <select name="sort" class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                <option value="family_name">Nach Nachname</option>
                                <option value="highest_debt">Nach höchster Stundenschuld</option>
                            </select>
                        </div>
                        <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                            <input type="checkbox" name="anonymized" value="1">
                            Anonymisiert
                        </label>
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-500"><i class="fas fa-download"></i>PDF generieren</button>
                    </form>
                </div>

                @can('edit Pflichtstunden')
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200">
                        <div class="rounded-t-2xl bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 text-white">
                            <h3 class="text-lg font-bold"><i class="fas fa-user-clock mr-2"></i>Pflichtstunden erfassen</h3>
                        </div>
                        <div class="p-6">
                            <form method="POST" action="{{ route('pflichtstunden.verwaltung.store') }}" class="space-y-4">
                                @csrf
                                <div>
                                    <label for="user_id" class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600">Nutzer</label>
                                    <select name="user_id" id="user_id" class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 @error('user_id') border-red-500 @enderror" required>
                                        <option value="">-- Nutzer auswählen --</option>
                                        @foreach($allGroupedUsers as $group)
                                            <option value="{{ $group['user']->id }}">{{ $group['user']->name }} @if($group['partner']) / {{ $group['partner']->name }} @endif</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label for="admin_start" class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600">Start</label>
                                        <input type="datetime-local" id="admin_start" name="start" value="{{ old('start') }}" required class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 @error('start') border-red-500 @enderror">
                                    </div>
                                    <div>
                                        <label for="admin_end" class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600">Ende</label>
                                        <input type="datetime-local" id="admin_end" name="end" value="{{ old('end') }}" required class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 @error('end') border-red-500 @enderror">
                                    </div>
                                </div>
                                <div>
                                    <label for="admin_description" class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600">Grund / Beschreibung</label>
                                    <textarea id="admin_description" name="description" rows="3" placeholder="Beschreiben Sie den Grund für die Pflichtstunden..." required class="w-full resize-none rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                                </div>
                                @if(!empty($pflichtstunden_settings->pflichtstunden_bereiche) && count($pflichtstunden_settings->pflichtstunden_bereiche) > 0)
                                    <div>
                                        <label for="admin_bereich" class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600">Bereich</label>
                                        <select id="admin_bereich" name="bereich" class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 @error('bereich') border-red-500 @enderror">
                                            <option value="">-- Bitte wählen --</option>
                                            @foreach($pflichtstunden_settings->pflichtstunden_bereiche as $bereich)
                                                <option value="{{ $bereich }}" {{ old('bereich') == $bereich ? 'selected' : '' }}>{{ $bereich }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500"><i class="fas fa-save"></i>Pflichtstunden erfassen</button>
                            </form>
                        </div>
                    </div>
                @endcan
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 mb-6"
             x-data="{
                 activeTab: 'overview',
                 search: '',
                 currentPage: 1,
                 perPage: 20,
                 allUsers: {{ \Illuminate\Support\Js::from($overviewUsers ?? []) }},
                 get filteredUsers() {
                     const source = Array.isArray(this.allUsers) ? this.allUsers : [];
                     if (!this.search || !this.search.trim()) return source;

                     const searchLower = this.search.toLowerCase();
                     return source.filter(group => {
                         const userName = String(group?.userName ?? '').toLowerCase();
                         const partnerName = String(group?.partnerName ?? '').toLowerCase();
                         return userName.includes(searchLower) || partnerName.includes(searchLower);
                     });
                 },
                 get paginatedUsers() {
                     const filtered = this.filteredUsers;
                     const start = (this.currentPage - 1) * this.perPage;
                     return filtered.slice(start, start + this.perPage);
                 },
                 get totalPages() {
                     return Math.max(1, Math.ceil(this.filteredUsers.length / this.perPage));
                 },
                 nextPage() {
                     if (this.currentPage < this.totalPages) {
                         this.currentPage++;
                         this.scrollToTable();
                     }
                 },
                 prevPage() {
                     if (this.currentPage > 1) {
                         this.currentPage--;
                         this.scrollToTable();
                     }
                 },
                 goToPage(page) {
                     this.currentPage = page;
                     this.scrollToTable();
                 },
                 scrollToTable() {
                     const table = document.querySelector('#userTable');
                     if (table) {
                         table.scrollIntoView({ behavior: 'smooth', block: 'start' });
                     }
                 }
             }"
             x-init="$watch('search', () => { currentPage = 1; })">
            <div class="border-b border-slate-200 bg-slate-50 px-6 py-3">
                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="activeTab = 'overview'" :class="activeTab === 'overview' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-100'" class="rounded-lg px-4 py-2 text-sm font-semibold transition-colors">
                        <i class="fas fa-chart-bar mr-2"></i>Familienübersicht
                    </button>
                    <button type="button" @click="activeTab = 'rules'" :class="activeTab === 'rules' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-100'" class="rounded-lg px-4 py-2 text-sm font-semibold transition-colors">
                        <i class="fas fa-sliders-h mr-2"></i>Sollstunden-Zuweisung
                    </button>
                </div>
            </div>

            <div x-show="activeTab === 'overview'" x-transition>
                <div class="rounded-t-2xl bg-gradient-to-r from-emerald-500 to-teal-600 px-6 py-4 text-white">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h3 class="text-xl font-bold"><i class="fas fa-chart-bar mr-2"></i>Übersicht der Pflichtstunden</h3>
                            <p class="mt-1 text-sm text-white/80">
                                Zeitraum: {{ $periodStart->format('d.m.Y') }} - {{ $periodEnd->format('d.m.Y') }}
                                @if ($selectedYear)
                                    <span class="ml-2 inline-flex items-center rounded-full bg-white/15 px-2 py-0.5 text-[10px] font-medium uppercase tracking-[0.12em]">vergangener Zeitraum</span>
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <div x-data="{ showPeriodMenu: false }" class="relative">
                                <button @click="showPeriodMenu = !showPeriodMenu" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-emerald-700 shadow-sm hover:bg-emerald-50"><i class="fas fa-calendar-alt"></i>Zeitraum <i class="fas fa-chevron-down text-[10px]"></i></button>
                                <div x-show="showPeriodMenu" @click.away="showPeriodMenu = false" class="absolute right-0 z-50 mt-2 w-64 rounded-xl border border-slate-200 bg-white shadow-xl" style="display:none;">
                                    <div class="py-2">
                                        <a href="{{ route('pflichtstunden.indexVerwaltung') }}" class="flex items-center gap-3 px-4 py-3 {{ ! $selectedYear ? 'bg-emerald-50' : '' }} text-slate-700 hover:bg-emerald-50">
                                            <i class="fas fa-calendar-day text-emerald-600"></i>
                                            <div><div class="font-medium">Aktueller Zeitraum</div><div class="text-xs text-slate-500">{{ \Carbon\Carbon::createFromFormat('m-d', $pflichtstunden_settings->pflichtstunden_start)->format('d.m.') }} - {{ \Carbon\Carbon::createFromFormat('m-d', $pflichtstunden_settings->pflichtstunden_ende)->format('d.m.Y') }}</div></div>
                                        </a>
                                        @if (count($availableYears) > 0)
                                            <div class="my-1 border-t border-slate-200"></div>
                                            @foreach ($availableYears as $availableYear)
                                                <a href="{{ route('pflichtstunden.indexVerwaltung', ['year' => $availableYear]) }}" class="flex items-center gap-3 px-4 py-3 {{ $selectedYear === $availableYear ? 'bg-blue-50' : '' }} text-slate-700 hover:bg-blue-50">
                                                    <i class="fas fa-history text-blue-600"></i>
                                                    <div><div class="font-medium">{{ $availableYear }} / {{ $availableYear + 1 }}</div><div class="text-xs text-slate-500">Zeitraum {{ $availableYear }}-{{ $availableYear + 1 }}</div></div>
                                                </a>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div x-data="{ showExportMenu: false }" class="relative">
                                <button @click="showExportMenu = !showExportMenu" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-emerald-700 shadow-sm hover:bg-emerald-50"><i class="fas fa-file-excel"></i>Excel-Export <i class="fas fa-chevron-down text-[10px]"></i></button>
                                <div x-show="showExportMenu" @click.away="showExportMenu = false" class="absolute right-0 z-50 mt-2 w-64 rounded-xl border border-slate-200 bg-white shadow-xl" style="display:none;">
                                    <div class="py-2">
                                        <a href="{{ route('pflichtstunden.export') }}" class="flex items-center gap-3 px-4 py-3 text-slate-700 hover:bg-emerald-50"><i class="fas fa-calendar-day text-emerald-600"></i><div><div class="font-medium">Aktueller Zeitraum</div><div class="text-xs text-slate-500">{{ \Carbon\Carbon::createFromFormat('m-d', $pflichtstunden_settings->pflichtstunden_start)->format('d.m.') }} - {{ \Carbon\Carbon::createFromFormat('m-d', $pflichtstunden_settings->pflichtstunden_ende)->format('d.m.Y') }}</div></div></a>
                                        <div class="my-1 border-t border-slate-200"></div>
                                        <a href="{{ route('pflichtstunden.export', ['year' => date('Y') - 1]) }}" class="flex items-center gap-3 px-4 py-3 text-slate-700 hover:bg-blue-50"><i class="fas fa-calendar-alt text-blue-600"></i><div><div class="font-medium">Vorjahr</div><div class="text-xs text-slate-500">Zeitraum {{ date('Y') - 1 }}</div></div></a>
                                        <a href="{{ route('pflichtstunden.export', ['year' => date('Y') - 2]) }}" class="flex items-center gap-3 px-4 py-3 text-slate-700 hover:bg-blue-50"><i class="fas fa-calendar-alt text-blue-600"></i><div><div class="font-medium">{{ date('Y') - 2 }}</div><div class="text-xs text-slate-500">Zeitraum {{ date('Y') - 2 }}</div></div></a>
                                        <div class="my-1 border-t border-slate-200"></div>
                                        <form action="{{ route('pflichtstunden.export') }}" method="get" class="space-y-2 px-4 py-3">
                                            <div class="font-medium text-slate-700">Individueller Zeitraum</div>
                                            <div class="grid grid-cols-2 gap-2">
                                                <input type="date" name="start" class="rounded border border-slate-300 px-2 py-1 text-sm" value="{{ request('start') }}">
                                                <input type="date" name="end" class="rounded border border-slate-300 px-2 py-1 text-sm" value="{{ request('end') }}">
                                            </div>
                                            <button type="submit" class="w-full rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500">Export starten</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 pt-6" id="userTable">
                    <div class="flex items-center gap-4">
                        <div class="relative flex-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4"><i class="fas fa-search text-slate-400"></i></div>
                            <input type="text" x-model="search" class="w-full rounded-xl border border-slate-300 bg-slate-50 py-3 pl-11 pr-4 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="Nutzer durchsuchen (Name)...">
                        </div>
                        <div class="whitespace-nowrap text-sm text-slate-600"><i class="fas fa-info-circle mr-1 text-blue-500"></i><span x-text="filteredUsers.length"></span> von <span x-text="allUsers.length"></span> Einträgen</div>
                    </div>
                </div>

                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600">Familie</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600">Modus</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600">Geleistet</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600">Konto</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600">Offen</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600">Beitrag</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600">Erfüllung</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600">Details</th>
                                </tr>
                            </thead>
                            <template x-for="group in paginatedUsers" :key="group.userName">
                                <tbody class="divide-y divide-slate-200 bg-white">
                                        <tr class="align-top hover:bg-slate-50">
                                            <td class="px-4 py-3">
                                                <div class="font-semibold text-slate-900" x-text="group.userName"></div>
                                                <div x-show="group.partnerName" class="text-xs text-slate-500">+ <span x-text="group.partnerName"></span></div>
                                            </td>
                                            <td class="px-4 py-3"><span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700" x-text="group.modeLabel"></span></td>
                                            <td class="px-4 py-3"><span class="inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700"><template x-if="group.totalMinutes >= 60"><span x-text="Math.floor(group.totalMinutes / 60) + 'h ' + (group.totalMinutes % 60) + 'm'"></span></template><template x-if="group.totalMinutes < 60"><span x-text="group.totalMinutes + 'm'"></span></template></span></td>
                                            <td class="px-4 py-3">
                                                <div>
                                                    <span :class="group.closingBalanceMinutes < 0 ? 'text-red-600' : 'text-green-700'" class="font-semibold" x-text="(group.closingBalanceMinutes < 0 ? '-' : '') + Math.floor(Math.abs(group.closingBalanceMinutes) / 60) + 'h ' + (Math.abs(group.closingBalanceMinutes) % 60) + 'm'"></span>
                                                    <div class="text-[11px] text-slate-500" x-text="'Übertrag: ' + Math.floor(group.carryoverMinutes / 60) + 'h ' + (group.carryoverMinutes % 60) + 'm'"></div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <template x-if="group.openMinutes > 0">
                                                    <span class="inline-flex rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700"><template x-if="group.openMinutes >= 60"><span x-text="Math.floor(group.openMinutes / 60) + 'h ' + (group.openMinutes % 60) + 'm'"></span></template><template x-if="group.openMinutes < 60"><span x-text="group.openMinutes + 'm'"></span></template></span>
                                                </template>
                                                <template x-if="group.openMinutes === 0"><span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">0 Min.</span></template>
                                            </td>
                                            <td class="px-4 py-3 font-semibold">
                                                <template x-if="group.beitrag > 0"><span class="text-red-600" x-text="group.beitrag.toFixed(2).replace('.', ',') + ' €'"></span></template>
                                                <template x-if="group.beitrag === 0"><span class="text-emerald-600">0,00 €</span></template>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-3">
                                                    <div class="h-6 w-28 overflow-hidden rounded-full bg-slate-200">
                                                        <div class="flex h-full items-center justify-center text-[10px] font-semibold text-white transition-all duration-300" :class="{ 'bg-green-500': group.percent >= 100, 'bg-yellow-500': group.percent >= 50 && group.percent < 100, 'bg-red-500': group.percent < 50 }" :style="'width: ' + Math.min(100, group.percent) + '%'">
                                                            <span x-text="group.percent + '%'"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <button type="button" @click="group.showDetails = !group.showDetails" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-100">
                                                    <i class="fas" :class="group.showDetails ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                                    <span x-text="group.showDetails ? 'Weniger' : 'Alle anzeigen'"></span>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr x-show="group.showDetails" x-cloak>
                                            <td colspan="8" class="bg-slate-50 px-4 py-3">
                                                <div class="rounded-xl border border-slate-200 bg-white p-3">
                                                    <div class="mb-3 flex items-center justify-between">
                                                        <div class="text-sm font-semibold text-slate-800">Pflichtstunden von <span x-text="group.userName"></span></div>
                                                        <div class="text-[11px] text-slate-500" x-text="(group.entries || []).length + ' Einträge'"></div>
                                                    </div>
                                                    <template x-if="(group.entries || []).length > 0">
                                                        <div class="max-h-64 overflow-auto">
                                                            <table class="min-w-full text-xs text-slate-700">
                                                                <thead class="bg-slate-50">
                                                                    <tr>
                                                                        <th class="px-2 py-2 text-left font-semibold">Datum</th>
                                                                        <th class="px-2 py-2 text-left font-semibold">Zeit</th>
                                                                        <th class="px-2 py-2 text-left font-semibold">Dauer</th>
                                                                        <th class="px-2 py-2 text-left font-semibold">Bereich</th>
                                                                        <th class="px-2 py-2 text-left font-semibold">Beschreibung</th>
                                                                        <th class="px-2 py-2 text-left font-semibold">Status</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <template x-for="entry in group.entries" :key="entry.id">
                                                                        <tr class="border-t border-slate-200">
                                                                            <td class="px-2 py-2" x-text="entry.start"></td>
                                                                            <td class="px-2 py-2" x-text="entry.start + ' – ' + entry.end"></td>
                                                                            <td class="px-2 py-2" x-text="entry.duration"></td>
                                                                            <td class="px-2 py-2" x-text="entry.bereich"></td>
                                                                            <td class="px-2 py-2" x-text="entry.description"></td>
                                                                            <td class="px-2 py-2"><span class="inline-flex rounded-full px-2 py-1 text-[10px] font-semibold" :class="entry.approved ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'" x-text="entry.approved ? 'freigegeben' : 'wartend'"></span></td>
                                                                        </tr>
                                                                    </template>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </template>
                                                    <template x-if="(group.entries || []).length === 0">
                                                        <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 p-3 text-xs text-slate-500">Keine Pflichtstunden für diese Familie im aktuellen Zeitraum.</div>
                                                    </template>
                                                </div>
                                            </td>
                                        </tr>
                                </tbody>
                            </template>
                        </table>
                    </div>

                    <div x-show="filteredUsers.length > 0" class="mt-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div class="text-sm text-slate-600">Zeige <span class="font-medium" x-text="((currentPage - 1) * perPage) + 1"></span> bis <span class="font-medium" x-text="Math.min(currentPage * perPage, filteredUsers.length)"></span> von <span class="font-medium" x-text="filteredUsers.length"></span> Einträgen</div>
                        <div class="flex items-center gap-2">
                            <button @click="prevPage()" :disabled="currentPage === 1" :class="currentPage === 1 ? 'cursor-not-allowed opacity-50' : 'hover:bg-blue-700'" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors">Zurück</button>
                            <div class="flex items-center gap-1">
                                <template x-for="page in totalPages" :key="page">
                                    <button @click="goToPage(page)" x-show="page === 1 || page === totalPages || (page >= currentPage - 1 && page <= currentPage + 1)" :class="page === currentPage ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" class="h-9 w-9 rounded-lg text-sm font-medium" x-text="page"></button>
                                </template>
                            </div>
                            <button @click="nextPage()" :disabled="currentPage === totalPages" :class="currentPage === totalPages ? 'cursor-not-allowed opacity-50' : 'hover:bg-blue-700'" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors">Weiter</button>
                        </div>
                    </div>

                    <div x-show="filteredUsers.length === 0" class="py-8 text-center text-slate-500"><i class="fas fa-search text-4xl mb-3"></i><p class="text-lg font-medium">Keine Nutzer gefunden</p><p class="text-sm">Versuchen Sie eine andere Suche</p></div>
                </div>
            </div>

            <div x-show="activeTab === 'rules'" x-transition class="p-6">
                <form method="POST" action="{{ route('pflichtstunden.family-rule.bulk') }}" class="mb-6 grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-6">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="period_year" value="{{ $periodYear }}">
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600">Modus für markierte Familien</label>
                        <select name="mode" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                            <option value="standard">Standard</option>
                            <option value="reduced">Ermäßigt</option>
                            <option value="custom">Individuell</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600">Individuell (h)</label>
                        <input type="number" step="0.5" min="0" name="custom_required_hours" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="z. B. 12.5">
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600">Begründung</label>
                        <input type="text" name="reason" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="z. B. Sonderregelung">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Bulk übernehmen</button>
                    </div>
                    <div class="md:col-span-6 grid grid-cols-2 gap-2 md:grid-cols-4 lg:grid-cols-6">
                        @foreach($groupedUsers as $group)
                            <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white p-2 text-sm text-slate-700">
                                <input type="checkbox" name="family_keys[]" value="{{ $group['family_key'] }}">
                                <span>{{ $group['family_name'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </form>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-3 py-3 text-left font-semibold text-slate-600">Familie</th>
                                <th class="px-3 py-3 text-left font-semibold text-slate-600">Modus</th>
                                <th class="px-3 py-3 text-left font-semibold text-slate-600">Sollstunden</th>
                                <th class="px-3 py-3 text-left font-semibold text-slate-600">Begründung</th>
                                <th class="px-3 py-3 text-left font-semibold text-slate-600">Aktion</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @foreach($groupedUsers as $group)
                                @php $ruleFormId = 'family-rule-'.$group['family_key']; @endphp
                                <tr>
                                    <td class="px-3 py-3 font-medium text-slate-900">{{ $group['family_name'] }}</td>
                                    <td class="px-3 py-3"><select name="mode" form="{{ $ruleFormId }}" class="w-full max-w-xs rounded-lg border border-slate-300 bg-slate-50 px-2 py-1.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200"><option value="standard" @selected($group['rule_mode'] === 'standard')>Standard</option><option value="reduced" @selected($group['rule_mode'] === 'reduced')>Ermäßigt</option><option value="custom" @selected($group['rule_mode'] === 'custom')>Individuell</option></select></td>
                                    <td class="px-3 py-3"><input type="number" step="0.5" min="0" name="custom_required_hours" form="{{ $ruleFormId }}" value="{{ $group['custom_required_hours'] }}" class="w-28 rounded-lg border border-slate-300 bg-slate-50 px-2 py-1.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200"></td>
                                    <td class="px-3 py-3"><input type="text" name="reason" form="{{ $ruleFormId }}" value="{{ $group['rule_reason'] }}" class="w-full rounded-lg border border-slate-300 bg-slate-50 px-2 py-1.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="Begründung"></td>
                                    <td class="px-3 py-3">
                                        <form method="POST" action="{{ route('pflichtstunden.family-rule.update') }}" id="{{ $ruleFormId }}" class="inline">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="family_key" value="{{ $group['family_key'] }}">
                                            <input type="hidden" name="period_year" value="{{ $periodYear }}">
                                        </form>
                                        <button type="submit" form="{{ $ruleFormId }}" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-500">Speichern</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Überlappungsgruppen: gruppierte Darstellung aller Zeitkonflikte -->
        @if($overlapGroups->isNotEmpty())
            <div class="bg-white rounded-xl shadow-md border-2 border-orange-300 mb-6">
                <div class="px-6 py-4 rounded-t-xl text-white"
                     style="background: linear-gradient(to right, #f97316, #ef4444)">
                    <h3 class="text-xl font-bold flex items-center gap-3">
                        <i class="fas fa-exclamation-triangle text-2xl"></i>
                        Hinweis: Überlappende Pflichtstunden
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-white/30 text-white text-sm font-bold">
                            {{ $overlapGroups->count() }}
                        </span>
                    </h3>
                    <p class="text-sm mt-1 text-white/90">
                        Die folgenden Einträge überlappen sich zeitlich innerhalb derselben Familie.
                        Abgelehnte Einträge werden nicht berücksichtigt. Jede Gruppe zeigt die konkreten Überschneidungen.
                    </p>
                </div>

                <div class="p-6 space-y-5">
                    @foreach($overlapGroups as $group)
                        @php
                            $confirmedCount = $group['entries']->where('approved', true)->count();
                        @endphp
                        <div class="border border-orange-200 rounded-xl overflow-hidden shadow-sm"
                             x-data="{ showConfirmed: false }">
                            <!-- Gruppen-Header -->
                            <div class="bg-orange-50 px-4 py-3 flex items-center justify-between border-b border-orange-200">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-orange-500 text-white text-xs font-bold">
                                        {{ $group['group_id'] }}
                                    </span>
                                    <span class="font-semibold text-orange-900">
                                        <i class="fas fa-users text-orange-600 mr-1"></i>
                                        {{ $group['family_name'] }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-3">
                                    @if($confirmedCount > 0)
                                        <button @click="showConfirmed = !showConfirmed" type="button"
                                                class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded-full border transition-colors duration-200"
                                                :class="showConfirmed
                                                    ? 'bg-green-100 text-green-700 border-green-300 hover:bg-green-200'
                                                    : 'bg-white text-orange-700 border-orange-300 hover:bg-orange-100'">
                                            <i class="fas" :class="showConfirmed ? 'fa-eye-slash' : 'fa-eye'"></i>
                                            <span x-text="showConfirmed ? 'Bestätigte ausblenden' : 'Bestätigte einblenden ({{ $confirmedCount }})'"></span>
                                        </button>
                                    @endif
                                    <span class="text-xs text-orange-600 font-medium bg-orange-100 px-2 py-0.5 rounded-full border border-orange-200">
                                        {{ $group['entries']->count() }} überlappende Einträge
                                    </span>
                                </div>
                            </div>

                            <!-- Einträge der Gruppe, nach Startzeit sortiert -->
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50 border-b border-gray-200">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Datum / Uhrzeit</th>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Dauer</th>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Person</th>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Grund</th>
                                            @if(!empty($pflichtstunden_settings->pflichtstunden_bereiche) && count($pflichtstunden_settings->pflichtstunden_bereiche) > 0)
                                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Bereich</th>
                                            @endif
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aktionen</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($group['entries'] as $entry)
                                            <tr class="{{ $entry->approved ? 'bg-green-50' : 'bg-yellow-50' }}"
                                                x-show="{{ $entry->approved ? 'showConfirmed' : 'true' }}"
                                                x-data="{
                                                    showEdit: false,
                                                    editData: {
                                                        start: '{{ $entry->start->format('Y-m-d\TH:i') }}',
                                                        end: '{{ $entry->end->format('Y-m-d\TH:i') }}',
                                                        description: {{ Js::from($entry->description) }}
                                                    }
                                                }">
                                                <td class="px-4 py-3">
                                                    @if($entry->approved)
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700 border border-green-300">
                                                            <i class="fas fa-check-circle"></i> Bestätigt
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700 border border-yellow-300">
                                                            <i class="fas fa-clock"></i> Ausstehend
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 text-gray-700 font-medium">
                                                    <span x-show="!showEdit">
                                                        @if($entry->start->isSameDay($entry->end))
                                                            <div>{{ $entry->start->format('d.m.Y') }}</div>
                                                            <div class="text-xs text-gray-500">{{ $entry->start->format('H:i') }} – {{ $entry->end->format('H:i') }}</div>
                                                        @else
                                                            <div class="text-xs">{{ $entry->start->format('d.m.Y H:i') }}</div>
                                                            <div class="text-xs">{{ $entry->end->format('d.m.Y H:i') }}</div>
                                                        @endif
                                                    </span>
                                                    <div x-show="showEdit" x-cloak class="space-y-2">
                                                        <input type="datetime-local" x-model="editData.start" class="w-full px-2 py-1 text-xs border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-200">
                                                        <input type="datetime-local" x-model="editData.end" class="w-full px-2 py-1 text-xs border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-200">
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        @if($entry->duration > 60)
                                                            {{ floor($entry->duration / 60) }}h {{ $entry->duration % 60 }}m
                                                        @else
                                                            {{ $entry->duration }}m
                                                        @endif
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 font-medium text-gray-900">{{ $entry->user?->name ?? 'Unbekannt / gelöschter Benutzer' }}</td>
                                                <td class="px-4 py-3 text-gray-700">
                                                    <span x-show="!showEdit">{{ $entry->description }}</span>
                                                    <textarea x-show="showEdit" x-cloak x-model="editData.description" rows="2" class="w-full px-2 py-1 text-xs border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-200"></textarea>
                                                </td>
                                                @if(!empty($pflichtstunden_settings->pflichtstunden_bereiche) && count($pflichtstunden_settings->pflichtstunden_bereiche) > 0)
                                                    <td class="px-4 py-3">
                                                        @if($entry->bereich)
                                                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-purple-100 text-purple-700 text-xs font-medium rounded">
                                                                <i class="fas fa-folder"></i> {{ $entry->bereich }}
                                                            </span>
                                                        @else
                                                            <span class="text-gray-400 text-xs">–</span>
                                                        @endif
                                                    </td>
                                                @endif
                                                <td class="px-4 py-3 text-sm">
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <!-- Bearbeiten -->
                                                        <button @click="showEdit = !showEdit" type="button"
                                                                class="inline-flex items-center gap-1 px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded transition-colors duration-200">
                                                            <i class="fas" :class="showEdit ? 'fa-times' : 'fa-edit'"></i>
                                                            <span x-text="showEdit ? 'Abbrechen' : 'Bearbeiten'"></span>
                                                        </button>
                                                        <!-- Speichern (nur bei Edit) -->
                                                        <form x-show="showEdit" x-cloak :action="`{{ route('pflichtstunden.update', $entry) }}`" method="POST" class="inline">
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="hidden" name="start" :value="editData.start">
                                                            <input type="hidden" name="end" :value="editData.end">
                                                            <input type="hidden" name="description" :value="editData.description">
                                                            <button type="submit"
                                                                    class="inline-flex items-center gap-1 px-2 py-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded transition-colors duration-200"
                                                                    onclick="return confirm('Änderungen speichern?');">
                                                                <i class="fas fa-save"></i>
                                                                Speichern
                                                            </button>
                                                        </form>
                                                        @if(!$entry->approved)
                                                            <!-- Bestätigen -->
                                                            <form action="{{ route('pflichtstunden.approve', $entry) }}" method="POST" class="inline">
                                                                @csrf
                                                                @method('PUT')
                                                                <button type="submit"
                                                                        class="inline-flex items-center gap-1 px-2 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-lg transition-colors duration-200">
                                                                    <i class="fas fa-check"></i>
                                                                    Bestätigen
                                                                </button>
                                                            </form>
                                                            <!-- Ablehnen -->
                                                            <div x-data="{ showReject: false }" class="inline-flex items-center gap-2">
                                                                <button @click="showReject = !showReject" type="button"
                                                                        class="inline-flex items-center gap-1 px-2 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg transition-colors duration-200">
                                                                    <i class="fas fa-times"></i>
                                                                    Ablehnen
                                                                </button>
                                                                <form x-show="showReject"
                                                                      x-transition
                                                                      action="{{ route('pflichtstunden.reject', $entry) }}"
                                                                      method="POST"
                                                                      class="inline-flex items-center gap-2">
                                                                    @csrf
                                                                    @method('PUT')
                                                                    <input name="rejection_reason"
                                                                           type="text"
                                                                           class="px-2 py-1 text-xs border border-gray-300 rounded-lg focus:border-red-500 focus:ring-1 focus:ring-red-200"
                                                                           placeholder="Grund..."
                                                                           required>
                                                                    <button type="submit"
                                                                            class="px-2 py-1 bg-red-600 hover:bg-red-700 text-white text-xs rounded-lg"
                                                                            onclick="return confirm('Möchten Sie diese Pflichtstunde wirklich ablehnen?');">
                                                                        <i class="fas fa-check"></i>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        @endif
                                                        <!-- Löschen -->
                                                        <form action="{{ route('pflichtstunden.destroy', $entry) }}" method="POST" class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                    class="inline-flex items-center gap-1 px-2 py-1 bg-gray-600 hover:bg-gray-700 text-white text-xs font-medium rounded transition-colors duration-200"
                                                                    onclick="return confirm('Möchten Sie diese Pflichtstunde wirklich löschen?');">
                                                                <i class="fas fa-trash"></i>
                                                                Löschen
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif


        @if($ruleHistoryEntries->isNotEmpty())
            <div class="bg-white rounded-xl shadow-md border border-gray-200 mb-6">
                <div class="px-6 py-4 rounded-t-xl text-white"
                     style="background: linear-gradient(to right, #6b7280, #4b5563)">
                    <h3 class="text-xl font-bold flex items-center gap-3">
                        <i class="fas fa-history text-2xl"></i>
                        Historie der Soll-Regeln (letzte 20 Änderungen)
                    </h3>
                </div>
                <div class="p-6 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-3 py-2 text-left">Zeitpunkt</th>
                            <th class="px-3 py-2 text-left">Familie</th>
                            <th class="px-3 py-2 text-left">Von</th>
                            <th class="px-3 py-2 text-left">Nach</th>
                            <th class="px-3 py-2 text-left">Begründung</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y">
                        @foreach($ruleHistoryEntries as $history)
                            <tr>
                                <td class="px-3 py-2">{{ $history['created_at']->format('d.m.Y H:i') }}</td>
                                <td class="px-3 py-2">{{ $history['family_name'] }}</td>
                                <td class="px-3 py-2">{{ $history['from_mode'] ?? '-' }} {{ $history['from_custom_required_hours'] ? '('.$history['from_custom_required_hours'].'h)' : '' }}</td>
                                <td class="px-3 py-2">{{ $history['to_mode'] }} {{ $history['to_custom_required_hours'] ? '('.$history['to_custom_required_hours'].'h)' : '' }}</td>
                                <td class="px-3 py-2">
                                    <div>{{ $history['reason'] ?? '-' }}</div>
                                    <div class="text-xs text-gray-500">von {{ $history['changed_by_name'] }}</div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <style>
        /* Select2 Container - Base */
        .select2-container {
            z-index: 99999 !important;
            width: 100% !important;
        }

        /* Select2 Selection Box */
        .select2-container--default .select2-selection--single {
            height: 42px !important;
            border: 2px solid #d1d5db !important;
            border-radius: 0.5rem !important;
            padding: 0.5rem 1rem !important;
            background-color: #ffffff !important;
            line-height: 26px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 26px !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            color: #374151 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #9ca3af !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
            right: 10px !important;
            top: 1px !important;
        }

        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        }

        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        }

        /* Select2 Dropdown - Kritisch für Sichtbarkeit */
        .select2-dropdown {
            z-index: 99999 !important;
            border: 2px solid #3b82f6 !important;
            border-radius: 0.5rem !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
            background-color: #ffffff !important;
            margin-top: 4px !important;
        }

        /* Select2 Search Field */
        .select2-search--dropdown {
            padding: 8px !important;
            background-color: #ffffff !important;
        }

        .select2-search--dropdown .select2-search__field {
            border: 2px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            padding: 0.5rem !important;
            outline: none !important;
            background-color: #ffffff !important;
            color: #374151 !important;
        }

        .select2-search--dropdown .select2-search__field:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        }

        /* Select2 Results */
        .select2-results {
            background-color: #ffffff !important;
        }

        .select2-results__options {
            max-height: 300px !important;
            overflow-y: auto !important;
        }

        .select2-results__option {
            padding: 10px 12px !important;
            background-color: #ffffff !important;
            color: #374151 !important;
        }

        .select2-results__option--highlighted {
            background-color: #3b82f6 !important;
            color: #ffffff !important;
        }

        .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: #dbeafe !important;
            color: #1e40af !important;
        }

        .select2-results__option--highlighted[aria-selected=true] {
            background-color: #2563eb !important;
            color: #ffffff !important;
        }

        /* No Results Message */
        .select2-results__option--load-more,
        .select2-results__option--searching,
        .select2-results__option--no-results {
            padding: 10px 12px !important;
            background-color: #ffffff !important;
            color: #6b7280 !important;
        }

        /* Sicherstellen dass Dropdown immer sichtbar ist */
        .select2-container--open {
            z-index: 99999 !important;
        }

        .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
            border-color: transparent transparent #3b82f6 transparent !important;
        }

        /* Clear Button */
        .select2-container--default .select2-selection--single .select2-selection__clear {
            color: #ef4444 !important;
            font-size: 18px !important;
            line-height: 26px !important;
            margin-right: 10px !important;
        }
    </style>
@endpush

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            // Select2 initialisieren
            $('#user_id').select2({
                placeholder: '🔍 Nutzer suchen und auswählen...',
                allowClear: true,
                width: '100%',
                dropdownParent: $('body'),
                theme: 'default',
                language: {
                    noResults: function() {
                        return "❌ Keine Nutzer gefunden";
                    },
                    searching: function() {
                        return "🔍 Suche...";
                    },
                    inputTooShort: function() {
                        return "Bitte mehr Zeichen eingeben";
                    }
                },
                // Dropdown-Position korrigieren
                dropdownAutoWidth: false,
                // Sicherstellen dass Dropdown über allem erscheint
                containerCssClass: 'select2-container--custom',
                dropdownCssClass: 'select2-dropdown--custom'
            });

            // Beim Öffnen Z-Index setzen
            $('#user_id').on('select2:open', function (e) {
                $('.select2-dropdown--custom').css({
                    'z-index': '99999',
                    'background-color': '#ffffff'
                });
            });

            // Focus-State verbessern
            $('#user_id').on('select2:opening', function (e) {
                $(this).data('select2').$dropdown.css({
                    'z-index': '99999',
                    'background-color': '#ffffff'
                });
            });

            // Auto-Scroll zur nächsten Pflichtstunde nach Bestätigung
            const urlParams = new URLSearchParams(window.location.search);
            const scrollToId = urlParams.get('scroll_to');

            if (scrollToId) {
                setTimeout(function() {
                    const targetRow = document.querySelector(`tr[data-pflichtstunde-id="${scrollToId}"]`);
                    if (targetRow) {
                        // Scroll zur Zeile mit smooth scrolling
                        targetRow.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });

                        // Highlight-Effekt für die Zeile
                        targetRow.classList.add('bg-yellow-100');
                        setTimeout(function() {
                            targetRow.classList.remove('bg-yellow-100');
                            targetRow.classList.add('transition-colors', 'duration-1000');
                        }, 1500);

                        // Nur scroll_to Parameter entfernen, bereich_filter behalten
                        const url = new URL(window.location.href);
                        url.searchParams.delete('scroll_to');
                        window.history.replaceState({}, '', url.toString());
                    }
                }, 500);
            }
        });
    </script>
@endpush
