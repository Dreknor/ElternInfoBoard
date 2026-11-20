@extends('layouts.app')

@section('content')
    <div class="container-fluid px-4 py-3 space-y-4">
        @php
            // Berechne grundlegende Werte für alle Gamification-Cards
            $approved_minutes = $pflichtstunden->where('approved', true)->sum('duration');
            $pending_minutes = $pflichtstunden->where('approved', false)->where('rejected', false)->sum('duration');
            $rejected_minutes = $pflichtstunden->where('rejected', true)->sum('duration');
            $required_minutes = $currentFamilySummary['required_minutes'] ?? ($pflichtstunden_settings->pflichtstunden_anzahl * 60);
            $opening_balance_minutes = $currentFamilySummary['opening_balance_minutes'] ?? 0;
            $credited_minutes = max(0, $opening_balance_minutes + $approved_minutes);
            $progress_percentage = $required_minutes > 0 ? min(round(($credited_minutes / $required_minutes) * 100), 100) : 100;
            $hourly_rate = $currentFamilySummary['hourly_rate'] ?? $pflichtstunden_settings->pflichtstunden_betrag;
            $open_minutes = max(0, (int) ($currentFamilySummary['openMinutes'] ?? max(0, $required_minutes - $credited_minutes)));
            $closing_balance_minutes = $currentFamilySummary['closing_balance_minutes'] ?? 0;
            $carryover_preview_minutes = $currentFamilySummary['carryover_preview_minutes'] ?? 0;
            $mode_label = match($currentFamilySummary['rule_mode'] ?? 'standard') {
                'reduced' => 'Ermäßigt',
                'custom' => 'Individuell',
                default => 'Standard',
            };

            // Finde die Pflichtstunden-Hilfe-Site
            $helpSite = \App\Model\Site::where('name', 'Pflichtstunden Hilfe')->where('is_active', true)->first();
        @endphp

        <!-- Hilfe Link -->
        <div class="flex justify-end mb-4">
            @if($helpSite)
                <a href="{{ route('sites.show', $helpSite->id) }}"
                   class="ea-btn ea-btn-secondary"
                   style="border-color: var(--color-primary); color: var(--color-primary)">
                    <i class="fas fa-question-circle"></i>
                    <span class="hidden sm:inline">Zur Erklärung</span>
                </a>
            @endif
        </div>

        <!-- Gamification Stats Card -->

        @if($pflichtstunden_settings->gamification_show_progress || $pflichtstunden_settings->gamification_show_ranking || $pflichtstunden_settings->gamification_show_comparison)
        <div class="grid grid-cols-1 {{ $pflichtstunden_settings->gamification_show_progress && $pflichtstunden_settings->gamification_show_ranking && $pflichtstunden_settings->gamification_show_comparison ? 'md:grid-cols-3' : ($pflichtstunden_settings->gamification_show_progress && ($pflichtstunden_settings->gamification_show_ranking || $pflichtstunden_settings->gamification_show_comparison) ? 'md:grid-cols-2' : 'md:grid-cols-1') }} gap-3">

            <!-- Progress Card -->
            @if($pflichtstunden_settings->gamification_show_progress)
            <div class="rounded-lg shadow-md overflow-hidden border"
                 style="background: linear-gradient(to bottom right, var(--color-widget-primary-bg), var(--color-widget-body-bg)); border-color: var(--color-widget-primary-border)">
                <div class="px-3 py-2 border-b"
                     style="background: linear-gradient(to right, var(--color-widget-primary-from), var(--color-widget-primary-to)); border-color: var(--color-widget-primary-border)">
                    <h4 class="text-sm font-bold flex items-center gap-2 mb-0" style="color: var(--color-widget-header-text)">
                        <i class="fas fa-chart-line"></i>
                        Fortschritt
                    </h4>
                </div>
                <div class="p-3">

                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-semibold ea-text">Fortschritt</span>
                            <span class="text-sm font-bold" style="color: var(--color-widget-primary-from)">{{ $progress_percentage }}%</span>
                        </div>

                        <div class="ea-progress shadow-inner">
                            <div class="h-full transition-all duration-500"
                                 style="width: {{ $progress_percentage }}%; background: linear-gradient(to right, var(--color-widget-primary-from), var(--color-widget-primary-to))"></div>
                        </div>

                        <div class="text-center text-xs ea-muted">
                            <span class="font-semibold text-green-600">{{ floor($credited_minutes / 60) }}h {{ $credited_minutes % 60 }}m</span>
                            / <span class="font-semibold">{{ round($required_minutes / 60, 2) }}h</span>
                            <span class="block text-[11px] ea-muted mt-0.5">(bestätigt, zählt zum Fortschritt)</span>
                        </div>

                        @if($pending_minutes > 0)
                            <div class="text-center text-xs text-amber-700 dark:text-amber-300">
                                <i class="fas fa-hourglass-half"></i>
                                + {{ floor($pending_minutes / 60) }}h {{ $pending_minutes % 60 }}m unbestätigt
                                <span class="block text-[11px] ea-muted">wartet auf Bestätigung, zählt noch nicht</span>
                            </div>
                        @endif

                        <!-- Achievement Badge -->
                        @if($progress_percentage >= 100)
                            <div class="flex justify-center pt-1">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300 text-xs font-bold rounded-full animate-pulse">
                                    <i class="fas fa-trophy"></i>
                                    Ziel erreicht!
                                </span>
                            </div>
                        @elseif($progress_percentage >= 75)
                            <div class="flex justify-center pt-1">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300 text-xs font-bold rounded-full">
                                    <i class="fas fa-star"></i>
                                    Fast geschafft!
                                </span>
                            </div>
                        @elseif($progress_percentage >= 50)
                            <div class="flex justify-center pt-1">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-bold rounded-full" style="background-color: color-mix(in srgb, var(--color-primary) 12%, transparent); color: var(--color-primary)">
                                    <i class="fas fa-thumbs-up"></i>
                                    Auf halbem Weg!
                                </span>
                            </div>
                        @endif

                    </div>
                </div>
            </div>
            @endif

            <!-- Ranking Card -->
            @if($pflichtstunden_settings->gamification_show_ranking)
            <div class="rounded-lg shadow-md overflow-hidden border"
                 style="background: linear-gradient(to bottom right, var(--color-widget-accent-bg), var(--color-widget-body-bg)); border-color: var(--color-widget-accent-border)">
                <div class="px-3 py-2 border-b"
                     style="background: linear-gradient(to right, var(--color-widget-accent-from), var(--color-widget-accent-to)); border-color: var(--color-widget-accent-border)">
                    <h4 class="text-sm font-bold flex items-center gap-2 mb-0" style="color: var(--color-widget-header-text)">
                        <i class="fas fa-trophy"></i>
                        Ranking
                    </h4>
                </div>
                <div class="p-3">
                    @php
                        $total_parents = $parent_stats['total_parents'] ?? 1;
                        $your_rank = $parent_stats['your_rank'] ?? 1;
                        $rank_percentage = round(($your_rank / $total_parents) * 100);
                    @endphp

                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-semibold ea-text">Platzierung</span>
                            <span class="text-2xl font-bold" style="color: var(--color-widget-accent-from)">{{ $your_rank }}.</span>
                        </div>

                        <div class="text-center text-xs ea-muted">
                            von <span class="font-semibold">{{ $total_parents }}</span> Familien
                        </div>

                        <!-- Rank Badge -->
                        <div class="mt-2 p-1.5 rounded-lg border text-center" style="background-color: var(--color-card-bg); border-color: var(--color-widget-accent-border)">
                            @if($your_rank == 1)
                                <i class="fas fa-medal text-yellow-500 text-lg"></i>
                                <p class="text-md font-bold text-yellow-600 mt-1">🥇 Platz 1!</p>
                            @elseif($rank_percentage <= 25)
                                <i class="fas fa-medal text-lg" style="color: var(--color-text-secondary)"></i>
                                <p class="text-md font-bold mt-1" style="color: var(--color-widget-accent-from)">Top 25%!</p>
                            @else
                                <i class="fas fa-arrow-trend-up text-green-500 text-lg"></i>
                                <p class="text-md ea-muted mt-1">Noch Platz nach oben 💪</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Statistics/Comparison Card -->
            @if($pflichtstunden_settings->gamification_show_comparison)
            <div class="rounded-lg shadow-md overflow-hidden border"
                 style="background: linear-gradient(to bottom right, var(--color-widget-success-bg), var(--color-widget-body-bg)); border-color: var(--color-widget-success-border)">
                <div class="px-3 py-2 border-b"
                     style="background: linear-gradient(to right, var(--color-widget-success-from), var(--color-widget-success-to)); border-color: var(--color-widget-success-border)">
                    <h4 class="text-sm font-bold flex items-center gap-2 mb-0" style="color: var(--color-widget-header-text)">
                        <i class="fas fa-chart-bar"></i>
                        Vergleich
                    </h4>
                </div>
                <div class="p-3">
                    @php
                        $avg_progress = $parent_stats['avg_progress'] ?? $progress_percentage;
                    @endphp

                    <div class="space-y-2">
                        <div class="text-xs">
                            <div class="flex justify-between mb-0.5">
                                <span class="ea-text font-medium">Dein Wert:</span>
                                <span class="font-bold text-green-600">{{ $progress_percentage }}%</span>
                            </div>
                            <div class="flex justify-between mb-0.5">
                                <span class="ea-text font-medium">Ø alle:</span>
                                <span class="font-bold ea-muted">{{ round($avg_progress) }}%</span>
                            </div>
                        </div>

                        <div class="pt-1.5 border-t" style="border-color: var(--color-widget-success-border)">
                            @if($progress_percentage > $avg_progress)
                                <div class="flex items-center gap-2 text-xs">
                                    <i class="fas fa-check-circle text-green-500"></i>
                                    <span class="text-green-700 dark:text-green-400">Über Ø! <strong>+{{ round($progress_percentage - $avg_progress) }}%</strong></span>
                                </div>
                            @elseif($progress_percentage < $avg_progress)
                                <div class="flex items-center gap-2 text-xs">
                                    <i class="fas fa-arrow-up text-orange-500"></i>
                                    <span class="text-orange-700 dark:text-orange-400"><strong>{{ round($avg_progress - $progress_percentage) }}%</strong> bis Ø</span>
                                </div>
                            @else
                                <div class="flex items-center gap-2 text-xs">
                                    <i class="fas fa-equals" style="color: var(--color-primary)"></i>
                                    <span style="color: var(--color-primary)">Im Durchschnitt</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
        @endif

        <div class="ea-card overflow-hidden">
            <div class="ea-card-header px-4 py-3 justify-between">
                <h3 class="ea-card-title text-xl font-bold flex items-center gap-2">
                    <span class="ea-card-icon"><i class="fas fa-wallet"></i></span>
                    Familienkonto & Soll-Modell
                </h3>
            </div>
            <div class="p-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="rounded-lg border ea-border p-3">
                    <div class="text-xs ea-muted">Soll-Modell</div>
                    <div class="font-semibold ea-text">{{ $mode_label }}</div>
                    @isset($basisDescription)
                        <div class="text-[11px] ea-muted mt-0.5">{{ $basisDescription }}</div>
                    @endisset
                </div>
                <div class="rounded-lg border ea-border p-3">
                    <div class="text-xs ea-muted">Startsaldo</div>
                    <div class="font-semibold ea-text">{{ floor(abs($opening_balance_minutes) / 60) }}h {{ abs($opening_balance_minutes) % 60 }}m</div>
                </div>
                <div class="rounded-lg border ea-border p-3">
                    <div class="text-xs ea-muted">Aktueller Kontostand</div>
                    <div class="font-semibold {{ $closing_balance_minutes < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-700 dark:text-green-400' }}">
                        {{ $closing_balance_minutes < 0 ? '-' : '' }}{{ floor(abs($closing_balance_minutes) / 60) }}h {{ abs($closing_balance_minutes) % 60 }}m
                    </div>
                    <div class="text-[11px] ea-muted mt-0.5">nur bestätigte Stunden</div>
                </div>
                @if($pending_minutes > 0)
                    <div class="rounded-lg border p-3 border-amber-300 bg-amber-500/10 dark:border-amber-500/40">
                        <div class="text-xs text-amber-700 dark:text-amber-300">
                            <i class="fas fa-hourglass-half"></i>
                            davon unbestätigt
                        </div>
                        <div class="font-semibold text-amber-700 dark:text-amber-300">{{ floor($pending_minutes / 60) }}h {{ $pending_minutes % 60 }}m</div>
                        <div class="text-[11px] ea-muted mt-0.5">noch nicht im Kontostand enthalten</div>
                    </div>
                @endif
                @if($pflichtstunden_settings->konto_uebertrag_aktiv)
                    <div class="rounded-lg border ea-border p-3">
                        <div class="text-xs ea-muted">Voraussichtlicher Übertrag</div>
                        <div class="font-semibold ea-text">{{ floor($carryover_preview_minutes / 60) }}h {{ $carryover_preview_minutes % 60 }}m</div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Pflichtstunden Übersicht Card -->
        <div class="ea-card">
            <div class="ea-card-header px-4 py-3 justify-between flex-wrap gap-2">
                <div class="flex items-center gap-3">
                    <span class="ea-card-icon"><i class="fas fa-tasks"></i></span>
                    <div>
                    <h3 class="ea-card-title text-xl font-bold">
                        Pflichtstunden
                    </h3>
                    <p class="ea-card-sub mt-1">
                        Zeitraum: {{ $periodStart->format('d.m.Y') }} - {{ $periodEnd->format('d.m.Y') }}
                        @if ($selectedYear)
                            <span class="ml-1 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium" style="background-color: color-mix(in srgb, var(--color-primary) 12%, transparent); color: var(--color-primary)">
                                <i class="fas fa-history"></i> vergangener Zeitraum
                            </span>
                        @endif
                    </p>
                    </div>
                </div>

                <div x-data="{ showPeriodMenu: false }" class="relative">
                    <button @click="showPeriodMenu = !showPeriodMenu"
                            class="ea-btn ea-btn-secondary">
                        <i class="fas fa-calendar-alt"></i>
                        Zeitraum
                        <i class="fas fa-chevron-down text-sm"></i>
                    </button>

                    <!-- Dropdown Menu -->
                    <div x-show="showPeriodMenu"
                         @click.away="showPeriodMenu = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="ea-card absolute right-0 mt-2 w-64 rounded-lg shadow-xl z-50"
                         style="display: none;">
                        <div class="py-2">
                            <a href="{{ route('pflichtstunden.index') }}"
                               class="ea-row-link flex items-center gap-3 px-4 py-3 {{ ! $selectedYear ? 'ea-subtle' : '' }} ea-text">
                                <i class="fas fa-calendar-day" style="color: var(--color-primary)"></i>
                                <div>
                                    <div class="font-medium">Aktueller Zeitraum</div>
                                    <div class="text-xs ea-muted">{{ \Carbon\Carbon::createFromFormat('m-d', $pflichtstunden_settings->pflichtstunden_start)->format('d.m.') }} - {{ \Carbon\Carbon::createFromFormat('m-d', $pflichtstunden_settings->pflichtstunden_ende)->format('d.m.Y') }}</div>
                                </div>
                            </a>
                            @if (count($availableYears) > 0)
                                <div class="border-t ea-border my-1"></div>
                                @foreach ($availableYears as $availableYear)
                                    <a href="{{ route('pflichtstunden.index', ['year' => $availableYear]) }}"
                                       class="ea-row-link flex items-center gap-3 px-4 py-3 {{ $selectedYear === $availableYear ? 'ea-subtle' : '' }} ea-text">
                                        <i class="fas fa-history" style="color: var(--color-primary)"></i>
                                        <div>
                                            <div class="font-medium">{{ $loop->first ? 'Vorjahr' : $availableYear.'-'.($availableYear + 1) }}</div>
                                            <div class="text-xs ea-muted">Zeitraum {{ $availableYear }}-{{ $availableYear + 1 }}</div>
                                        </div>
                                    </a>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-4 prose help-prose max-w-none">
                @safeHtml($pflichtstunden_settings->pflichtstunden_text)
            </div>

            <div class="p-4">
                <div class="overflow-x-auto">
                    <table class="w-full border ea-border rounded-lg overflow-hidden">
                        <thead class="ea-subtle">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold ea-muted">Datum</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold ea-muted">Stundenanzahl</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold ea-muted">Grund</th>
                                @if(!empty($pflichtstunden_settings->pflichtstunden_bereiche) && count($pflichtstunden_settings->pflichtstunden_bereiche) > 0)
                                <th class="px-4 py-3 text-left text-sm font-semibold ea-muted">Bereich</th>
                                @endif
                                <th class="px-4 py-3 text-left text-sm font-semibold ea-muted">Status</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold ea-muted">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-theme">
                            @foreach ($pflichtstunden as $pflichtstunde)
                                <tr class="ea-row-link" x-data="{
                                    showEdit: false,
                                    editData: {
                                        start: '{{ $pflichtstunde->start->format('Y-m-d\TH:i') }}',
                                        end: '{{ $pflichtstunde->end->format('Y-m-d\TH:i') }}',
                                        description: {{ Js::from($pflichtstunde->description) }}
                                    }
                                }">
                                    <td class="px-4 py-3 text-sm ea-text">
                                        <span x-show="!showEdit">
                                            @if($pflichtstunde->start->isSameDay($pflichtstunde->end))
                                                {{ $pflichtstunde->start->format('d.m.Y') }} von {{ $pflichtstunde->start->format('H:i') }} bis {{ $pflichtstunde->end->format('H:i') }}
                                            @else
                                                {{ $pflichtstunde->start->format('d.m.Y H:i') }} bis {{ $pflichtstunde->end->format('d.m.Y H:i') }}
                                            @endif
                                        </span>
                                        <div x-show="showEdit" x-cloak class="space-y-2">
                                            <input type="datetime-local" x-model="editData.start" class="ea-input px-2 py-1 text-xs">
                                            <input type="datetime-local" x-model="editData.end" class="ea-input px-2 py-1 text-xs">
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm ea-text font-medium">
                                        @if($pflichtstunde->duration > 60)
                                            {{ floor($pflichtstunde->duration / 60) }} Std. {{ $pflichtstunde->duration % 60 }} Min.
                                        @else
                                            {{ $pflichtstunde->duration }} Min.
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm ea-text">
                                        <span x-show="!showEdit">{{ $pflichtstunde->description }}</span>
                                        <textarea x-show="showEdit" x-cloak x-model="editData.description" rows="3" class="ea-input px-2 py-1 text-xs"></textarea>
                                    </td>
                                    @if(!empty($pflichtstunden_settings->pflichtstunden_bereiche) && count($pflichtstunden_settings->pflichtstunden_bereiche) > 0)
                                    <td class="px-4 py-3 text-sm ea-text">
                                        @if($pflichtstunde->bereich)
                                            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded" style="background-color: var(--color-surface-subtle); color: var(--color-text-primary); border: 1px solid var(--color-card-border)">
                                                <i class="fas fa-folder"></i>
                                                {{ $pflichtstunde->bereich }}
                                            </span>
                                        @else
                                            <span class="ea-muted text-xs">-</span>
                                        @endif
                                    </td>
                                    @endif
                                    <td class="px-4 py-3">
                                        @if($pflichtstunde->approved)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300 text-xs font-medium rounded-full">
                                                <i class="fas fa-check-circle"></i>
                                                Bestätigt
                                            </span>
                                        @elseif($pflichtstunde->rejected)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300 text-xs font-medium rounded-full">
                                                <i class="fas fa-times-circle"></i>
                                                Abgelehnt
                                            </span>
                                            @if($pflichtstunde->rejection_reason)
                                                <p class="text-xs text-red-600 mt-1">{{$pflichtstunde->rejection_reason}}</p>
                                            @endif
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 text-xs font-medium rounded-full">
                                                <i class="fas fa-hourglass-half"></i>
                                                In Bearbeitung
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if(!$pflichtstunde->approved && !$pflichtstunde->rejected)
                                            <div class="flex items-center gap-2">
                                                <button @click="showEdit = !showEdit" type="button"
                                                        class="ea-btn ea-btn-secondary ea-btn-sm">
                                                    <i class="fas" :class="showEdit ? 'fa-times' : 'fa-edit'"></i>
                                                    <span x-text="showEdit ? 'Abbrechen' : 'Bearbeiten'"></span>
                                                </button>
                                                <form x-show="showEdit" x-cloak :action="`{{ route('pflichtstunden.update', $pflichtstunde) }}`" method="POST" class="inline">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="start" :value="editData.start">
                                                    <input type="hidden" name="end" :value="editData.end">
                                                    <input type="hidden" name="description" :value="editData.description">
                                                    <button type="submit"
                                                            class="ea-btn ea-btn-primary ea-btn-sm"
                                                            onclick="return confirm('Änderungen speichern?');">
                                                        <i class="fas fa-save"></i>
                                                        Speichern
                                                    </button>
                                                </form>
                                                <form action="{{ route('pflichtstunden.destroy', $pflichtstunde) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="ea-btn ea-btn-danger ea-btn-sm"
                                                            onclick="return confirm('Möchten Sie diese Pflichtstunde wirklich löschen?');">
                                                        <i class="fas fa-trash"></i>
                                                        Löschen
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-xs ea-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="ea-subtle border-t-2 ea-border">
                            <tr>
                                <th colspan="{{ !empty($pflichtstunden_settings->pflichtstunden_bereiche) && count($pflichtstunden_settings->pflichtstunden_bereiche) > 0 ? '5' : '4' }}" class="px-4 py-3 text-right text-sm font-semibold ea-text">
                                    <span class="inline-flex items-center gap-1 text-green-700 dark:text-green-400">
                                        <i class="fas fa-check-circle"></i>
                                        bestätigte Stunden (zählen zum Fortschritt):
                                    </span>
                                </th>
                                <th class="px-4 py-3 text-sm font-bold text-green-700 dark:text-green-400">
                                    @if($approved_minutes > 60)
                                        {{ floor($approved_minutes / 60) }} Std. {{ $approved_minutes % 60 }} Min.
                                    @else
                                        {{ $approved_minutes }} Min.
                                    @endif
                                </th>
                            </tr>
                            @if($pending_minutes > 0)
                            <tr>
                                <th colspan="{{ !empty($pflichtstunden_settings->pflichtstunden_bereiche) && count($pflichtstunden_settings->pflichtstunden_bereiche) > 0 ? '5' : '4' }}" class="px-4 py-3 text-right text-sm font-semibold ea-text">
                                    <span class="inline-flex items-center gap-1 text-amber-700 dark:text-amber-300">
                                        <i class="fas fa-hourglass-half"></i>
                                        noch unbestätigte Stunden (zählen noch nicht zum Fortschritt):
                                    </span>
                                </th>
                                <th class="px-4 py-3 text-sm font-bold text-amber-700 dark:text-amber-300">
                                    @if($pending_minutes > 60)
                                        {{ floor($pending_minutes / 60) }} Std. {{ $pending_minutes % 60 }} Min.
                                    @else
                                        {{ $pending_minutes }} Min.
                                    @endif
                                </th>
                            </tr>
                            @endif
                            @if($rejected_minutes > 0)
                            <tr>
                                <th colspan="{{ !empty($pflichtstunden_settings->pflichtstunden_bereiche) && count($pflichtstunden_settings->pflichtstunden_bereiche) > 0 ? '5' : '4' }}" class="px-4 py-3 text-right text-sm font-semibold ea-text">
                                    <span class="inline-flex items-center gap-1 text-red-700 dark:text-red-400">
                                        <i class="fas fa-times-circle"></i>
                                        abgelehnte Stunden (zählen nicht zum Fortschritt):
                                    </span>
                                </th>
                                <th class="px-4 py-3 text-sm font-bold text-red-700 dark:text-red-400">
                                    @if($rejected_minutes > 60)
                                        {{ floor($rejected_minutes / 60) }} Std. {{ $rejected_minutes % 60 }} Min.
                                    @else
                                        {{ $rejected_minutes }} Min.
                                    @endif
                                </th>
                            </tr>
                            @endif
                            <tr>
                                <th colspan="{{ !empty($pflichtstunden_settings->pflichtstunden_bereiche) && count($pflichtstunden_settings->pflichtstunden_bereiche) > 0 ? '5' : '4' }}" class="px-4 py-3 text-right text-sm font-semibold ea-text">
                                    offene Stunden (Soll abzüglich bestätigter Stunden):
                                </th>
                                <th class="px-4 py-3 text-sm font-bold text-orange-600">
                                    @php
                                        $remaining = $open_minutes;
                                    @endphp
                                    @if($remaining > 60)
                                        {{ floor($remaining / 60) }} Std. {{ $remaining % 60 }} Min.
                                    @elseif($remaining > 0)
                                        {{ $remaining }} Min.
                                    @else
                                        <span class="text-green-600">0 Min.</span>
                                    @endif
                                </th>
                            </tr>
                            <tr>
                                <th colspan="{{ !empty($pflichtstunden_settings->pflichtstunden_bereiche) && count($pflichtstunden_settings->pflichtstunden_bereiche) > 0 ? '5' : '4' }}" class="px-4 py-3 text-right text-sm font-semibold ea-text">
                                   Offener Betrag ({{ number_format($hourly_rate, 2, ',', '.') }} € je Pflichtstunde):
                                </th>
                                <th class="px-4 py-3 text-sm font-bold text-red-600">
                                   @php
                                       $remaining_hours = $open_minutes / 60;
                                       $betrag_gesamt = ($required_minutes / 60) * $hourly_rate;
                                       $offener_betrag = $remaining_hours * $hourly_rate;
                                    @endphp
                                    @if($offener_betrag<0)
                                        0.00
                                    @else
                                        {{ number_format($offener_betrag, 2, ',', '.') }}
                                    @endif
                                    <span class="ea-muted font-normal">von {{ number_format($betrag_gesamt, 2, ',', '.') }} €</span>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pflichtstunden eintragen Card -->
        <div class="ea-card overflow-hidden">
            <div class="ea-card-header px-4 py-3">
                <h3 class="ea-card-title text-xl font-bold flex items-center gap-2">
                    <span class="ea-card-icon"><i class="fas fa-plus-circle"></i></span>
                    Pflichtstunden eintragen
                </h3>
            </div>

            <div class="p-4">
                <form method="POST" action="{{ route('pflichtstunden.store') }}" class="space-y-4">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="start" class="ea-label mb-2">
                                <i class="fas fa-calendar-day mr-1" style="color: var(--color-primary)"></i>
                                Startdatum und -uhrzeit
                            </label>
                            <input type="datetime-local"
                                   class="ea-input px-4 py-2 @error('start') ea-input-invalid @enderror"
                                   id="start"
                                   name="start"
                                   value="{{ old('start') }}"
                                   required>
                            @error('start')
                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="end" class="ea-label mb-2">
                                <i class="fas fa-calendar-check mr-1" style="color: var(--color-primary)"></i>
                                Enddatum und -uhrzeit
                            </label>
                            <input type="datetime-local"
                                   class="ea-input px-4 py-2 @error('end') ea-input-invalid @enderror"
                                   id="end"
                                   name="end"
                                   value="{{ old('end') }}"
                                   required>
                            @error('end')
                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="description" class="ea-label mb-2">
                            <i class="fas fa-align-left mr-1" style="color: var(--color-primary)"></i>
                            Grund <span class="text-red-500">*</span>
                        </label>
                        <textarea class="ea-input px-4 py-2 resize-none @error('description') ea-input-invalid @enderror"
                                  id="description"
                                  name="description"
                                  rows="4"
                                  placeholder="Bitte geben Sie den Grund für die geleisteten Pflichtstunden an (z.B. 'Helfen beim Schulfest', 'Gartenarbeit', etc.)..."
                                  required>{{ old('description') }}</textarea>
                        @error('description')
                            <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                        @enderror
                        <p class="ea-hint mt-1">Pflichtfeld - Bitte beschreiben Sie die durchgeführte Tätigkeit.</p>
                    </div>

                    @if(!empty($pflichtstunden_settings->pflichtstunden_bereiche) && count($pflichtstunden_settings->pflichtstunden_bereiche) > 0)
                    <div>
                        <label for="bereich" class="ea-label mb-2">
                            <i class="fas fa-folder mr-1" style="color: var(--color-primary)"></i>
                            Bereich
                        </label>
                        <select class="ea-input px-4 py-2 @error('bereich') ea-input-invalid @enderror"
                                id="bereich"
                                name="bereich">
                            <option value="">-- Bitte wählen --</option>
                            @foreach($pflichtstunden_settings->pflichtstunden_bereiche as $bereich)
                                <option value="{{ $bereich }}" {{ old('bereich') == $bereich ? 'selected' : '' }}>{{ $bereich }}</option>
                            @endforeach
                        </select>
                        @error('bereich')
                            <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                        @enderror
                        <p class="ea-hint mt-1">Optional - Wählen Sie einen Bereich für eine bessere Kategorisierung aus.</p>
                    </div>
                    @endif

                    <button type="submit"
                            class="ea-btn ea-btn-primary ea-btn-lg w-full md:w-auto font-medium">
                        <i class="fas fa-save"></i>
                        Pflichtstunden eintragen
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
