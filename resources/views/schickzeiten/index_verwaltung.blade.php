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

                    <!-- Aktuelle Abfragen -->
                    <div class="mt-6 bg-white rounded-lg shadow border border-gray-200">
                        <div class="bg-gradient-to-r from-teal-600 to-teal-700 px-4 py-3">
                            <h3 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                                <i class="fas fa-list"></i>
                                Aktuelle Anwesenheitsabfragen
                            </h3>
                        </div>
                        <div class="p-4">
                            <p class="text-sm text-gray-600 mb-4">
                                Aktuelle Anwesenheitsabfragen werden hier angezeigt. Kommentare erscheinen bei der Elternansicht und können kurze Hinweise für den Tag sein.
                            </p>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Datum</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Anzahl</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Kommentar</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider">Aktionen</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @forelse($abfragen as $date => $abfrage)
                                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    <div class="flex items-center gap-2">
                                                        <i class="fas fa-calendar text-blue-600"></i>
                                                        <span class="text-sm font-medium text-gray-900">{{$date}}</span>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        {{$abfrage['count']}}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="text-sm text-gray-600">{{$abfrage['comment'] ?? '-'}}</span>
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                                                    @if(\Carbon\Carbon::parse($date)->isFuture())
                                                        <div class="flex items-center justify-end gap-2">
                                                            <button type="button"
                                                                    class="edit-comment-button inline-flex items-center gap-1 px-3 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors duration-200"
                                                                    data-date="{{ $date }}"
                                                                    data-comment="{{$abfrage['comment'] ?? ''}}">
                                                                <i class="fa fa-edit"></i>
                                                                Bearbeiten
                                                            </button>
                                                            <form action="{{route('care.abfrage.destroy', ['date' => $date])}}" method="post" class="delete-form inline">
                                                                @csrf
                                                                @method('delete')
                                                                <button type="button"
                                                                        class="delete-button inline-flex items-center gap-1 px-3 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors duration-200">
                                                                    <i class="fa fa-trash"></i>
                                                                    Löschen
                                                                </button>
                                                            </form>
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="px-4 py-8 text-center">
                                                    <div class="flex flex-col items-center gap-2 text-gray-500">
                                                        <i class="fas fa-inbox text-4xl"></i>
                                                        <p class="text-sm">Keine zukünftigen Anwesenheitsabfragen vorhanden</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Neue Abfrage erstellen -->
                    <div class="mt-6 bg-white rounded-lg shadow border border-gray-200">
                        <div class="bg-gradient-to-r from-green-600 to-green-700 px-4 py-3">
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

                    <!-- Kinder-Schickzeiten -->
                    <div class="bg-white rounded-lg shadow border border-gray-200">
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

