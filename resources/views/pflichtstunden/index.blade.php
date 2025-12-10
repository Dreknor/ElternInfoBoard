@extends('layouts.app')

@section('content')
    <div class="container-fluid px-4 py-3 space-y-4">
        @php
            // Berechne grundlegende Werte für alle Gamification-Cards
            $approved_minutes = $pflichtstunden->where('approved', true)->sum('duration');
            $required_minutes = $pflichtstunden_settings->pflichtstunden_anzahl * 60;
            $progress_percentage = min(round(($approved_minutes / $required_minutes) * 100), 100);

            // Finde die Pflichtstunden-Hilfe-Site
            $helpSite = \App\Model\Site::where('name', 'Pflichtstunden Hilfe')->where('is_active', true)->first();
        @endphp

        <!-- Hilfe Link -->
        <div class="flex justify-end mb-4">
            @if($helpSite)
                <a href="{{ route('sites.show', $helpSite->id) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 border-2 border-indigo-600 text-indigo-600 hover:bg-indigo-50 hover:border-indigo-700 hover:text-indigo-700 font-medium rounded-lg transition-all duration-200">
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
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg shadow-md overflow-hidden border border-blue-200">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-3 py-2 border-b border-blue-800">
                    <h4 class="text-sm font-bold text-white flex items-center gap-2 mb-0">
                        <i class="fas fa-chart-line"></i>
                        Fortschritt
                    </h4>
                </div>
                <div class="p-3">

                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-semibold text-gray-700">Fortschritt</span>
                            <span class="text-sm font-bold text-blue-600">{{ $progress_percentage }}%</span>
                        </div>

                        <div class="w-full bg-gray-300 rounded-full h-2 overflow-hidden shadow-inner">
                            <div class="h-full bg-gradient-to-r from-blue-500 to-indigo-600 transition-all duration-500"
                                 style="width: {{ $progress_percentage }}%"></div>
                        </div>

                        <div class="text-center text-xs text-gray-600">
                            <span class="font-semibold text-green-600">{{ floor($approved_minutes / 60) }}h {{ $approved_minutes % 60 }}m</span>
                            / <span class="font-semibold">{{ $pflichtstunden_settings->pflichtstunden_anzahl }}h</span>
                        </div>

                        <!-- Achievement Badge -->
                        @if($progress_percentage >= 100)
                            <div class="flex justify-center pt-1">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-yellow-100 text-yellow-700 text-xs font-bold rounded-full animate-pulse">
                                    <i class="fas fa-trophy"></i>
                                    Ziel erreicht!
                                </span>
                            </div>
                        @elseif($progress_percentage >= 75)
                            <div class="flex justify-center pt-1">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-green-100 text-green-700 text-xs font-bold rounded-full">
                                    <i class="fas fa-star"></i>
                                    Fast geschafft!
                                </span>
                            </div>
                        @elseif($progress_percentage >= 50)
                            <div class="flex justify-center pt-1">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 text-blue-700 text-xs font-bold rounded-full">
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
            <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-lg shadow-md overflow-hidden border border-purple-200">
                <div class="bg-gradient-to-r from-purple-600 to-pink-600 px-3 py-2 border-b border-purple-800">
                    <h4 class="text-sm font-bold text-white flex items-center gap-2 mb-0">
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
                            <span class="text-xs font-semibold text-gray-700">Platzierung</span>
                            <span class="text-2xl font-bold text-purple-600">{{ $your_rank }}.</span>
                        </div>

                        <div class="text-center text-xs text-gray-600">
                            von <span class="font-semibold">{{ $total_parents }}</span> Familien
                        </div>

                        <!-- Rank Badge -->
                        <div class="mt-2 p-1.5 rounded-lg bg-white border border-purple-200 text-center">
                            @if($your_rank == 1)
                                <i class="fas fa-medal text-yellow-500 text-lg"></i>
                                <p class="text-md font-bold text-yellow-600 mt-1">🥇 Platz 1!</p>
                            @elseif($rank_percentage <= 25)
                                <i class="fas fa-medal text-gray-400 text-lg"></i>
                                <p class="text-md font-bold text-purple-600 mt-1">Top 25%!</p>
                            @else
                                <i class="fas fa-arrow-trend-up text-green-500 text-lg"></i>
                                <p class="text-md text-gray-600 mt-1">Noch Platz nach oben 💪</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Statistics/Comparison Card -->
            @if($pflichtstunden_settings->gamification_show_comparison)
            <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-lg shadow-md overflow-hidden border border-green-200">
                <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-3 py-2 border-b border-green-800">
                    <h4 class="text-sm font-bold text-white flex items-center gap-2 mb-0">
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
                                <span class="text-gray-700 font-medium">Dein Wert:</span>
                                <span class="font-bold text-green-600">{{ $progress_percentage }}%</span>
                            </div>
                            <div class="flex justify-between mb-0.5">
                                <span class="text-gray-700 font-medium">Ø alle:</span>
                                <span class="font-bold text-gray-600">{{ round($avg_progress) }}%</span>
                            </div>
                        </div>

                        <div class="pt-1.5 border-t border-green-200">
                            @if($progress_percentage > $avg_progress)
                                <div class="flex items-center gap-2 text-xs">
                                    <i class="fas fa-check-circle text-green-500"></i>
                                    <span class="text-green-700">Über Ø! <strong>+{{ round($progress_percentage - $avg_progress) }}%</strong></span>
                                </div>
                            @elseif($progress_percentage < $avg_progress)
                                <div class="flex items-center gap-2 text-xs">
                                    <i class="fas fa-arrow-up text-orange-500"></i>
                                    <span class="text-orange-700"><strong>{{ round($avg_progress - $progress_percentage) }}%</strong> bis Ø</span>
                                </div>
                            @else
                                <div class="flex items-center gap-2 text-xs">
                                    <i class="fas fa-equals text-blue-500"></i>
                                    <span class="text-blue-700">Im Durchschnitt</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
        @endif

        <!-- Pflichtstunden Übersicht Card -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3 border-b border-blue-800">
                <h3 class="text-xl font-bold text-white flex items-center gap-2 mb-0">
                    <i class="fas fa-tasks"></i>
                    Pflichtstunden
                </h3>
            </div>

            <div class="p-4 prose max-w-none">
                {!! $pflichtstunden_settings->pflichtstunden_text !!}
            </div>

            <div class="p-4">
                <div class="overflow-x-auto">
                    <table class="w-full border border-gray-200 rounded-lg overflow-hidden">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Datum</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Stundenanzahl</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Grund</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($pflichtstunden as $pflichtstunde)
                                <tr class="hover:bg-gray-50 transition-colors" x-data="{
                                    showEdit: false,
                                    editData: {
                                        start: '{{ $pflichtstunde->start->format('Y-m-d\TH:i') }}',
                                        end: '{{ $pflichtstunde->end->format('Y-m-d\TH:i') }}',
                                        description: {{ Js::from($pflichtstunde->description) }}
                                    }
                                }">
                                    <td class="px-4 py-3 text-sm text-gray-800">
                                        <span x-show="!showEdit">
                                            @if($pflichtstunde->start->isSameDay($pflichtstunde->end))
                                                {{ $pflichtstunde->start->format('d.m.Y') }} von {{ $pflichtstunde->start->format('H:i') }} bis {{ $pflichtstunde->end->format('H:i') }}
                                            @else
                                                {{ $pflichtstunde->start->format('d.m.Y H:i') }} bis {{ $pflichtstunde->end->format('d.m.Y H:i') }}
                                            @endif
                                        </span>
                                        <div x-show="showEdit" x-cloak class="space-y-2">
                                            <input type="datetime-local" x-model="editData.start" class="w-full px-2 py-1 text-xs border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-200">
                                            <input type="datetime-local" x-model="editData.end" class="w-full px-2 py-1 text-xs border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-200">
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-800 font-medium">
                                        @if($pflichtstunde->duration > 60)
                                            {{ floor($pflichtstunde->duration / 60) }} Std. {{ $pflichtstunde->duration % 60 }} Min.
                                        @else
                                            {{ $pflichtstunde->duration }} Min.
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        <span x-show="!showEdit">{{ $pflichtstunde->description }}</span>
                                        <textarea x-show="showEdit" x-cloak x-model="editData.description" rows="3" class="w-full px-2 py-1 text-xs border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-200"></textarea>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($pflichtstunde->approved)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-green-100 text-green-700 text-xs font-medium rounded-full">
                                                <i class="fas fa-check-circle"></i>
                                                Bestätigt
                                            </span>
                                        @elseif($pflichtstunde->rejected)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-red-100 text-red-700 text-xs font-medium rounded-full">
                                                <i class="fas fa-times-circle"></i>
                                                Abgelehnt
                                            </span>
                                            @if($pflichtstunde->rejection_reason)
                                                <p class="text-xs text-red-600 mt-1">{{$pflichtstunde->rejection_reason}}</p>
                                            @endif
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-amber-100 text-amber-700 text-xs font-medium rounded-full">
                                                <i class="fas fa-hourglass-half"></i>
                                                In Bearbeitung
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if(!$pflichtstunde->approved && !$pflichtstunde->rejected)
                                            <div class="flex items-center gap-2">
                                                <button @click="showEdit = !showEdit" type="button"
                                                        class="inline-flex items-center gap-1 px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded transition-colors duration-200">
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
                                                            class="inline-flex items-center gap-1 px-2 py-1 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded transition-colors duration-200"
                                                            onclick="return confirm('Änderungen speichern?');">
                                                        <i class="fas fa-save"></i>
                                                        Speichern
                                                    </button>
                                                </form>
                                                <form action="{{ route('pflichtstunden.destroy', $pflichtstunde) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="inline-flex items-center gap-1 px-2 py-1 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded transition-colors duration-200"
                                                            onclick="return confirm('Möchten Sie diese Pflichtstunde wirklich löschen?');">
                                                        <i class="fas fa-trash"></i>
                                                        Löschen
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-500">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                            <tr>
                                <th colspan="4" class="px-4 py-3 text-right text-sm font-semibold text-gray-700">
                                    Gesamtstunden:
                                </th>
                                <th class="px-4 py-3 text-sm font-bold text-blue-600">
                                    @if($pflichtstunden->sum('duration') > 60)
                                        {{ floor($pflichtstunden->sum('duration') / 60) }} Std. {{ $pflichtstunden->sum('duration') % 60 }} Min.
                                    @else
                                        {{ $pflichtstunden->sum('duration') }} Min.
                                    @endif
                                </th>
                            </tr>
                            <tr>
                                <th colspan="4" class="px-4 py-3 text-right text-sm font-semibold text-gray-700">
                                    Verbleibende Stunden:
                                </th>
                                <th class="px-4 py-3 text-sm font-bold text-orange-600">
                                    @php
                                        $remaining = $pflichtstunden_settings->pflichtstunden_anzahl *60 - $pflichtstunden->where('approved', true)->sum('duration');
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
                                <th colspan="4" class="px-4 py-3 text-right text-sm font-semibold text-gray-700">
                                    Offener Betrag ({{$pflichtstunden_settings->pflichtstunden_betrag}} € je Pflichtstunde):
                                </th>
                                <th class="px-4 py-3 text-sm font-bold text-red-600">
                                   @php
                                       $remaining_hours = ($pflichtstunden_settings->pflichtstunden_anzahl * 60 - $pflichtstunden->where('approved', true)->sum('duration')) / 60;
                                       $betrag_gesamt = $pflichtstunden_settings->pflichtstunden_anzahl * $pflichtstunden_settings->pflichtstunden_betrag;
                                       $offener_betrag = $remaining_hours * $pflichtstunden_settings->pflichtstunden_betrag;
                                    @endphp
                                    {{number_format($offener_betrag, 2)}} €
                                    <span class="text-gray-500 font-normal">von {{$betrag_gesamt}} €</span>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pflichtstunden eintragen Card -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-green-600 to-green-700 px-4 py-3 border-b border-green-800">
                <h3 class="text-xl font-bold text-white flex items-center gap-2 mb-0">
                    <i class="fas fa-plus-circle"></i>
                    Pflichtstunden eintragen
                </h3>
            </div>

            <div class="p-4">
                <form method="POST" action="{{ route('pflichtstunden.store') }}" class="space-y-4">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="start" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-calendar-day text-green-600 mr-1"></i>
                                Startdatum und -uhrzeit
                            </label>
                            <input type="datetime-local"
                                   class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-200 outline-none @error('start') border-red-500 @enderror"
                                   id="start"
                                   name="start"
                                   value="{{ old('start') }}"
                                   required>
                            @error('start')
                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="end" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-calendar-check text-green-600 mr-1"></i>
                                Enddatum und -uhrzeit
                            </label>
                            <input type="datetime-local"
                                   class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-200 outline-none @error('end') border-red-500 @enderror"
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
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-align-left text-green-600 mr-1"></i>
                            Grund <span class="text-red-500">*</span>
                        </label>
                        <textarea class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-200 outline-none resize-none @error('description') border-red-500 @enderror"
                                  id="description"
                                  name="description"
                                  rows="4"
                                  placeholder="Bitte geben Sie den Grund für die geleisteten Pflichtstunden an (z.B. 'Helfen beim Schulfest', 'Gartenarbeit', etc.)..."
                                  required>{{ old('description') }}</textarea>
                        @error('description')
                            <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                        @enderror
                        <p class="text-xs text-gray-500 mt-1">Pflichtfeld - Bitte beschreiben Sie die durchgeführte Tätigkeit.</p>
                    </div>

                    <button type="submit"
                            class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors duration-200">
                        <i class="fas fa-save"></i>
                        Pflichtstunden eintragen
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection

