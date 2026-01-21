@extends('layouts.app')
@section('title') - Hort-Modul @endsection

@section('content')
    <div class="container-fluid px-4 py-6" x-data="{
        activeTab: (function() {
            const hash = window.location.hash.substring(1);
            const validTabs = ['anwesenheit', 'schickzeiten', 'anwesenheitsabfrage', 'vollmacht'];
            return validTabs.includes(hash) ? hash : 'anwesenheit';
        })(),
        showTypeForm: 'genau'
    }" x-init="window.location.hash && setTimeout(() => window.scrollTo({ top: 0, behavior: 'smooth' }), 100)">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Hort-Modul</h1>
            <p class="text-gray-600">Anwesenheit, Schickzeiten und Abholvollmachten verwalten</p>
        </div>

        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Tab Navigation -->
            <div class="border-b border-gray-200">
                <nav class="flex flex-wrap -mb-px" role="tablist">
                    <button @click="activeTab = 'anwesenheit'"
                            :class="activeTab === 'anwesenheit' ? 'border-teal-600 text-teal-600 bg-teal-50' : 'border-transparent text-gray-600 hover:text-gray-800 hover:border-gray-300'"
                            class="flex-1 px-6 py-3 border-b-2 font-medium text-sm transition-all duration-200 flex items-center justify-center gap-2">
                        <i class="fas fa-child"></i>
                        <span class="hidden sm:inline">Anwesenheit</span>
                    </button>
                    <button @click="activeTab = 'schickzeiten'"
                            :class="activeTab === 'schickzeiten' ? 'border-green-600 text-green-600 bg-green-50' : 'border-transparent text-gray-600 hover:text-gray-800 hover:border-gray-300'"
                            class="flex-1 px-6 py-3 border-b-2 font-medium text-sm transition-all duration-200 flex items-center justify-center gap-2">
                        <i class="fas fa-clock"></i>
                        <span class="hidden sm:inline">Schickzeiten</span>
                    </button>
                    <button @click="activeTab = 'anwesenheitsabfrage'"
                            :class="activeTab === 'anwesenheitsabfrage' ? 'border-indigo-600 text-indigo-600 bg-indigo-50' : 'border-transparent text-gray-600 hover:text-gray-800 hover:border-gray-300'"
                            class="flex-1 px-6 py-3 border-b-2 font-medium text-sm transition-all duration-200 flex items-center justify-center gap-2">
                        <i class="fas fa-calendar-check"></i>
                        <span class="hidden sm:inline">Anwesenheitsabfrage</span>
                    </button>
                    <button @click="activeTab = 'vollmacht'"
                            :class="activeTab === 'vollmacht' ? 'border-amber-600 text-amber-600 bg-amber-50' : 'border-transparent text-gray-600 hover:text-gray-800 hover:border-gray-300'"
                            class="flex-1 px-6 py-3 border-b-2 font-medium text-sm transition-all duration-200 flex items-center justify-center gap-2">
                        <i class="fas fa-user-shield"></i>
                        <span class="hidden sm:inline">Abholvollmacht</span>
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-6">
                <!-- Anwesenheit Tab -->
                <div x-show="activeTab === 'anwesenheit'"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        @foreach($children as $child)
                            <div>
                                @include('child.include.child_card')
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Schickzeiten Tab -->
                <div x-show="activeTab === 'schickzeiten'"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     style="display: none;">

                    <!-- Info Box -->
                    <div class="mb-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-info-circle text-blue-600 mt-1"></i>
                            <div class="text-sm text-blue-800">
                                @include('schickzeiten.infos')
                            </div>
                        </div>
                    </div>

                    <!-- Kinder-Schickzeiten -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        @foreach($children as $child)
                            <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                                <!-- Child Header -->
                                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3">
                                    <h3 class="text-lg font-bold text-white mb-0">
                                        {{$child->first_name}} {{$child->last_name}}
                                    </h3>
                                </div>
                                <div class="p-4 border-b border-gray-200">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                        <i class="fas fa-calendar-day text-purple-600"></i>
                                        Tagesaktuelle Schickzeiten
                                    </h4>
                                    @forelse($child->schickzeiten->where('specific_date', '!=', NULL) as $schickzeit)
                                        <div class="flex items-start justify-between p-3 bg-purple-50 rounded-lg mb-2">
                                            <div>
                                                <div class="font-medium text-gray-900">{{$schickzeit->specific_date->format('d.m.Y')}}</div>
                                                <div class="text-sm text-gray-600">
                                                    @if($schickzeit->type =="genau")
                                                        <i class="fas fa-clock text-green-600"></i> Genau {{$schickzeit->time?->format('H:i')}} Uhr
                                                    @else
                                                        <i class="fas fa-hourglass-half text-amber-600"></i> Ab {{$schickzeit->time_ab?->format('H:i')}}
                                                        @if(!is_null($schickzeit->time_ab) && $schickzeit->time_spaet)
                                                            - {{$schickzeit->time_spaet?->format('H:i')}}
                                                        @endif
                                                        Uhr
                                                    @endif
                                                </div>
                                            </div>
                                            <form action="{{route('schickzeiten.destroy', ['schickzeit' => $schickzeit->id])}}" method="post">
                                                @csrf
                                                @method('delete')
                                                <button type="submit"
                                                        class="p-2 text-red-600 hover:bg-red-100 rounded transition-colors duration-150">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @empty
                                        <p class="text-sm text-gray-400 italic">Keine tagesaktuellen Zeiten hinterlegt</p>
                                    @endforelse
                                </div>

                                <!-- Regelmäßige Schickzeiten -->
                                <div class="p-4 border-b border-gray-200">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                        <i class="fas fa-calendar-week text-blue-600"></i>
                                        Regelmäßige Schickzeiten
                                    </h4>
                                    <ul class="space-y-2">
                                        @for($x=1;$x<6;$x++)
                                            <li class="flex items-start justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-150" x-data="{ showMenu: false }">
                                                <div class="flex-1">
                                                    <div class="font-medium text-gray-900 mb-1">{{$weekdays[$x]}}</div>
                                                    <div class="text-sm text-gray-600">
                                                        @if($child->schickzeiten->where('weekday', $x)->first())
                                                            @if($child->schickzeiten->where('weekday', $x)->first()->type == 'genau')
                                                                <i class="fas fa-clock text-green-600"></i>
                                                                {{$child->schickzeiten->where('weekday', $x)->first()->time?->format('H:i')}} Uhr
                                                            @else
                                                                <i class="fas fa-hourglass-half text-amber-600"></i>
                                                                {{$child->schickzeiten->where('weekday', $x)->first()->time_ab?->format('H:i')}}
                                                                @if(!is_null($child->schickzeiten->where('weekday', $x)->first()->time_ab) && $child->schickzeiten->where('weekday', $x)->first()->time_spaet)
                                                                    - {{$child->schickzeiten->where('weekday', $x)->first()->time_spaet?->format('H:i')}}
                                                                @endif
                                                                Uhr
                                                            @endif
                                                        @else
                                                            <span class="text-gray-400 italic">Keine Zeit hinterlegt</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <!-- Actions Dropdown -->
                                                <div class="relative ml-2">
                                                    <button @click="showMenu = !showMenu" @click.away="showMenu = false" type="button"
                                                            class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-200 rounded transition-colors duration-150">
                                                        <i class="fa fa-ellipsis-v"></i>
                                                    </button>
                                                    <div x-show="showMenu"
                                                         x-transition:enter="transition ease-out duration-100"
                                                         x-transition:enter-start="opacity-0 scale-95"
                                                         x-transition:enter-end="opacity-100 scale-100"
                                                         x-transition:leave="transition ease-in duration-75"
                                                         x-transition:leave-start="opacity-100 scale-100"
                                                         x-transition:leave-end="opacity-0 scale-95"
                                                         class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-10"
                                                         style="display: none;">
                                                        <a href="{{url("schickzeiten/edit/$x/".$child->id)}}"
                                                           class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-t-lg transition-colors duration-150">
                                                            <i class="fa fa-edit"></i> Bearbeiten
                                                        </a>
                                                        @if($child->schickzeiten->where('weekday', $x)->first())
                                                            <button type="button"
                                                                    @click="showMenu = false"
                                                                    onclick="confirmDeleteSchickzeit({{$child->schickzeiten->where('weekday', $x)->first()->id}}, {{$child->id}}, {{$x}})"
                                                                    class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-700 hover:bg-red-50 rounded-b-lg transition-colors duration-150 text-left">
                                                                <i class="fa fa-trash"></i> Löschen
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            </li>
                                        @endfor
                                    </ul>
                                </div>

                                <!-- Tagesaktuelle Schickzeiten -->

                                <!-- Footer -->
                                <div class="p-4 bg-gray-50">
                                    <form action="{{url("schickzeiten/$child->id")}}" method="post">
                                        @csrf
                                        @method('delete')
                                        <button type="submit"
                                                class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors duration-200 flex items-center justify-center gap-2">
                                            <i class="fa fa-trash"></i>
                                            Alle Schickzeiten löschen
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Neue tagesaktuelle Schickzeit -->
                    <div class="mt-6 bg-white rounded-lg shadow border border-gray-200 overflow-hidden max-w-2xl mx-auto">
                        <div class="bg-gradient-to-r from-green-600 to-green-700 px-4 py-3">
                            <h3 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                                <i class="fas fa-plus-circle"></i>
                                Neue tagesaktuelle Schickzeit anlegen
                            </h3>
                        </div>
                        <div class="p-4">
                            <form action="{{route('schickzeiten.store')}}" method="post">
                                @csrf
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calendar-alt text-blue-600"></i> Datum
                                    </label>
                                    <input type="date" name="specific_date" id="specific_date"
                                           value="{{old('specific_date', \Carbon\Carbon::now()->format('Y-m-d'))}}"
                                           class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none">
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-child text-blue-600"></i> Kind
                                    </label>
                                    <select name="child_id" id="child_id"
                                            class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none">
                                        <option value="" disabled selected>Bitte Kind auswählen</option>
                                        @foreach($children as $child)
                                            <option value="{{$child->id}}" @if(old('child_id') == $child->id) selected @endif>
                                                {{$child->first_name}} {{$child->last_name}}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-list text-blue-600"></i> Typ
                                    </label>
                                    <select name="type" id="type" x-model="showTypeForm"
                                            class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none">
                                        <option value="genau">Genau um ... Uhr</option>
                                        <option value="ab">Ab ... bis ... Uhr</option>
                                    </select>
                                </div>
                                <div class="mb-4" x-show="showTypeForm === 'genau'">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-clock text-blue-600"></i> Zeit
                                    </label>
                                    <input name="time" type="time"
                                           class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                           min="{{$vorgaben->schicken_ab}}"
                                           max="{{$vorgaben->schicken_bis}}"
                                           value="{{old('time')}}">
                                </div>
                                <div class="mb-4" x-show="showTypeForm === 'ab'" style="display: none;">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                <i class="fas fa-clock text-blue-600"></i> Ab ... Uhr
                                            </label>
                                            <input name="time_ab" type="time"
                                                   class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                                   min="{{$vorgaben->schicken_ab}}"
                                                   max="{{$vorgaben->schicken_bis}}"
                                                   value="{{old('time_ab')}}">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                <i class="fas fa-clock text-amber-600"></i> Spätestens (optional)
                                            </label>
                                            <input name="time_spaet" type="time"
                                                   class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                                   min="{{$vorgaben->schicken_ab}}"
                                                   max="{{$vorgaben->schicken_bis}}"
                                                   value="{{old('time_spaet')}}">
                                        </div>
                                    </div>
                                </div>
                                <button type="submit"
                                        class="w-full px-4 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-colors duration-200 flex items-center justify-center gap-2">
                                    <i class="fas fa-plus"></i>
                                    Neue individuelle Schickzeit anlegen
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Anwesenheitsabfrage Tab -->
                <div x-show="activeTab === 'anwesenheitsabfrage'"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     style="display: none;">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        @foreach($children as $child)
                            <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-4 py-3">
                                    <h3 class="text-lg font-bold text-white mb-0">
                                        {{$child->first_name}} {{$child->last_name}}
                                    </h3>
                                </div>
                                <div class="p-4">
                                    <!-- Desktop Ansicht -->
                                    <div class="hidden md:block overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Datum</th>
                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Angemeldet?</th>
                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Hinweis</th>
                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Aktion</th>
                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Frist</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                            @forelse($child->checkIns->sortBy('date') as $checkIn)
                                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                                    <td class="px-3 py-2 text-sm text-gray-900">{{$checkIn->date->dayName}}, {{$checkIn->date->format('d.m.Y')}}</td>
                                                    <td class="px-3 py-2">
                                                        @if($checkIn->should_be)
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                                <i class="fas fa-check mr-1"></i> Ja
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                                <i class="fas fa-times mr-1"></i> Nein
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2 text-sm text-gray-600">{{$checkIn->comment}}</td>
                                                    <td class="px-3 py-2">
                                                        @if(!$checkIn->should_be)
                                                            @if(($checkIn->lock_at && $checkIn->lock_at->endOfDay()?->gte(now()) or (!$checkIn->lock_at && $checkIn->date->gt(now()))))
                                                                <form action="{{route('checkIn.anmelden', ['childCheckIn' => $checkIn->id])}}" method="post">
                                                                    @csrf
                                                                    @method('put')
                                                                    <button type="submit" class="inline-flex items-center gap-1 px-3 py-1 text-xs bg-green-600 hover:bg-green-700 text-white rounded transition-colors duration-150">
                                                                        <i class="fa fa-check"></i> Anmelden
                                                                    </button>
                                                                </form>
                                                            @else
                                                                <span class="text-xs text-red-600">Zeitraum abgelaufen</span>
                                                            @endif
                                                        @else
                                                            <form action="{{route('checkIn.abmelden', ['childCheckIn' => $checkIn->id])}}" method="post">
                                                                @csrf
                                                                @method('PUT')
                                                                <button type="submit" class="inline-flex items-center gap-1 px-3 py-1 text-xs bg-red-600 hover:bg-red-700 text-white rounded transition-colors duration-150">
                                                                    <i class="fa fa-times"></i> Abmelden
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2 text-sm text-gray-600">
                                                        @if($checkIn->lock_at != null)
                                                            {{$checkIn->lock_at?->format('d.m.Y')}}
                                                        @else
                                                            <span class="text-gray-400">-</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="px-3 py-4 text-center text-sm text-gray-500">Keine Anwesenheitsabfragen vorhanden</td>
                                                </tr>
                                            @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    <!-- Mobile Ansicht -->
                                    <div class="md:hidden space-y-3">
                                        @forelse($child->checkIns->sortBy('date') as $checkIn)
                                            <div class="border border-gray-200 rounded-lg p-3 bg-gray-50">
                                                <div class="mb-2"><strong class="text-gray-700">Datum:</strong> {{$checkIn->date->dayName}}, {{$checkIn->date->format('d.m.Y')}}</div>
                                                <div class="mb-2">
                                                    <strong class="text-gray-700">Angemeldet?</strong>
                                                    @if($checkIn->should_be)
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 ml-2">
                                                            <i class="fas fa-check mr-1"></i> Ja
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 ml-2">
                                                            <i class="fas fa-times mr-1"></i> Nein
                                                        </span>
                                                    @endif
                                                </div>
                                                @if($checkIn->comment)
                                                    <div class="mb-2"><strong class="text-gray-700">Hinweis:</strong> {{$checkIn->comment}}</div>
                                                @endif
                                                @if($checkIn->lock_at != null)
                                                    <div class="mb-2"><strong class="text-gray-700">Frist:</strong> {{$checkIn->lock_at?->format('d.m.Y')}}</div>
                                                @endif
                                                <div class="mt-3">
                                                    @if(!$checkIn->should_be)
                                                        @if(($checkIn->lock_at && $checkIn->lock_at->endOfDay()?->gte(now()) or (!$checkIn->lock_at && $checkIn->date->gt(now()))))
                                                            <form action="{{route('checkIn.anmelden', ['childCheckIn' => $checkIn->id])}}" method="post">
                                                                @csrf
                                                                @method('put')
                                                                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded transition-colors duration-150">
                                                                    <i class="fa fa-check"></i> Anmelden
                                                                </button>
                                                            </form>
                                                        @else
                                                            <span class="text-sm text-red-600">Zeitraum abgelaufen</span>
                                                        @endif
                                                    @else
                                                        <form action="{{route('checkIn.abmelden', ['childCheckIn' => $checkIn->id])}}" method="post">
                                                            @csrf
                                                            @method('PUT')
                                                            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded transition-colors duration-150">
                                                                <i class="fa fa-times"></i> Abmelden
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        @empty
                                            <div class="text-center py-4 text-gray-500">Keine Anwesenheitsabfragen vorhanden</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Vollmacht Tab -->
                <div x-show="activeTab === 'vollmacht'"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     style="display: none;">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        @foreach($children as $child)
                            <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                                <div class="bg-gradient-to-r from-amber-600 to-orange-600 px-4 py-3">
                                    <h3 class="text-lg font-bold text-white mb-0">
                                        {{$child->first_name}} {{$child->last_name}}
                                    </h3>
                                </div>
                                <div class="p-4">
                                    @if($child->mandates->isEmpty())
                                        <div class="text-center py-4 text-gray-500">
                                            <i class="fas fa-info-circle text-blue-500 mb-2"></i>
                                            <p class="text-sm">Keine Abholvollmachten hinterlegt</p>
                                        </div>
                                    @else
                                        <ul class="space-y-2">
                                            @foreach($child->mandates as $mandate)
                                                <li class="flex items-start justify-between p-3 bg-gray-50 rounded-lg">
                                                    <div class="flex-1">
                                                        <div class="font-medium text-gray-900">{{$mandate->mandate_name}}</div>
                                                        <div class="text-sm text-gray-600">{{$mandate?->mandate_description}}</div>
                                                    </div>
                                                    <form action="{{route('child.mandate.destroy', ['mandate' => $mandate->id, 'child' => $child->id])}}" method="post">
                                                        @csrf
                                                        @method('delete')
                                                        <button type="submit"
                                                                class="p-2 text-red-600 hover:bg-red-100 rounded transition-colors duration-150">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                                <div class="p-4 bg-gray-50 border-t border-gray-200">
                                    <form action="{{route('child.mandate.store', ['child' => $child->id])}}" method="post">
                                        @csrf
                                        <div class="mb-3">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Name der bevollmächtigten Person</label>
                                            <input type="text" name="mandate_name"
                                                   class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-amber-500 focus:ring-2 focus:ring-amber-200 transition-all duration-200 outline-none"
                                                   value="{{old('mandate_name')}}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Beschreibung (z.B. Verwandtschaftsverhältnis, Telefonnummer)</label>
                                            <textarea name="mandate_description"
                                                      class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-amber-500 focus:ring-2 focus:ring-amber-200 transition-all duration-200 outline-none"
                                                      rows="2" required>{{old('mandate_description')}}</textarea>
                                        </div>
                                        <button type="submit"
                                                class="w-full px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg transition-colors duration-200 flex items-center justify-center gap-2">
                                            <i class="fas fa-plus"></i>
                                            Neue Abholvollmacht anlegen
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bestätigungsmodals für Schickzeiten --}}
    @include('components.schickzeiten-confirmation-modals')

@endsection

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Bestätigungsdialog für das Löschen von Schickzeiten
        async function confirmDeleteSchickzeit(schickzeitId, childId, weekday) {
            try {
                // Prüfe, ob tagesaktuelle Schickzeiten für diesen Wochentag existieren
                const response = await fetch('{{ route('schickzeiten.checkDailyTimes') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        child_id: childId,
                        weekday: weekday
                    })
                });

                const data = await response.json();

                let confirmOptions = {
                    title: 'Schickzeit löschen?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ja, löschen',
                    cancelButtonText: 'Abbrechen'
                };

                if (data.has_daily_times) {
                    confirmOptions.html = `
                        <p class="mb-4">Möchten Sie diese regelmäßige Schickzeit wirklich löschen?</p>
                        <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mb-3">
                            <p class="text-sm text-yellow-800 mb-2">
                                <i class="fas fa-exclamation-triangle"></i>
                                Es existieren ${data.count} tagesaktuelle Schickzeit(en) für diesen Wochentag:
                            </p>
                            <p class="text-xs text-yellow-700">${data.dates.join(', ')}</p>
                        </div>
                        <div class="text-left mt-3">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" id="deleteDailyTimes" class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                                <span class="text-sm text-gray-700">Auch tagesaktuelle Schickzeiten löschen</span>
                            </label>
                        </div>
                    `;
                } else {
                    confirmOptions.text = 'Möchten Sie diese Schickzeit wirklich löschen?';
                }

                const result = await Swal.fire(confirmOptions);

                if (result.isConfirmed) {
                    const deleteDailyTimes = document.getElementById('deleteDailyTimes')?.checked || false;

                    // Formular erstellen und absenden
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ url('schickzeiten') }}/' + schickzeitId + '/delete';

                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = '{{ csrf_token() }}';
                    form.appendChild(csrfInput);

                    const methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = 'DELETE';
                    form.appendChild(methodInput);

                    if (deleteDailyTimes) {
                        const dailyInput = document.createElement('input');
                        dailyInput.type = 'hidden';
                        dailyInput.name = 'delete_daily_times';
                        dailyInput.value = '1';
                        form.appendChild(dailyInput);
                    }

                    document.body.appendChild(form);
                    form.submit();
                }
            } catch (error) {
                console.error('Fehler:', error);
                Swal.fire({
                    title: 'Fehler',
                    text: 'Es ist ein Fehler aufgetreten.',
                    icon: 'error'
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Form submit handler for notices
            document.querySelectorAll('.form_submit').forEach(button => {
                button.addEventListener('click', function () {
                    const form = this.closest('form');
                    const notice = form.querySelector('textarea[name="notice"]').value;
                    const child_id = form.querySelector('input[name="child_id"]').value;
                    const url = "{{route('child.notice.store',['child' => 'child_id'])}}".replace('child_id', child_id);

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            date: form.querySelector('input[name="date"]').value,
                            notice: notice
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.fire({
                            title: 'Notiz gespeichert',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => location.reload());
                    })
                    .catch(error => {
                        Swal.fire({
                            title: 'Fehler',
                            text: 'Es ist ein Fehler aufgetreten.',
                            icon: 'error',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    });
                });
            });

            // Delete notice handler
            document.querySelectorAll('.delete-notice-btn').forEach(button => {
                button.addEventListener('click', function (e) {
                    e.preventDefault();
                    const form = this.closest('form');
                    const url = form.getAttribute('action');

                    Swal.fire({
                        title: 'Bist du sicher?',
                        text: 'Diese Notiz wird dauerhaft gelöscht!',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Ja, löschen!',
                        cancelButtonText: 'Abbrechen'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch(url, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                }
                            })
                            .then(() => {
                                Swal.fire('Gelöscht!', 'Die Notiz wurde erfolgreich gelöscht.', 'success');
                                form.closest('.card').remove();
                            })
                            .catch(() => {
                                Swal.fire('Fehler!', 'Es gab ein Problem beim Löschen der Notiz.', 'error');
                            });
                        }
                    });
                });
            });
        });
    </script>
@endpush



