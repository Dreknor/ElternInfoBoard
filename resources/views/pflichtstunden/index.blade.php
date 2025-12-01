@extends('layouts.app')

@section('content')
    <div class="container-fluid px-4 py-3 space-y-4">
        <!-- Pflichtstunden Übersicht Card -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3 border-b border-blue-800">
                <h3 class="text-xl font-bold text-white flex items-center gap-2 mb-0">
                    <i class="fas fa-clock"></i>
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
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($pflichtstunden as $pflichtstunde)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3 text-sm text-gray-800">
                                        @if($pflichtstunde->start->isSameDay($pflichtstunde->end))
                                            {{ $pflichtstunde->start->format('d.m.Y') }} von {{ $pflichtstunde->start->format('H:i') }} bis {{ $pflichtstunde->end->format('H:i') }}
                                        @else
                                            {{ $pflichtstunde->start->format('d.m.Y H:i') }} bis {{ $pflichtstunde->end->format('d.m.Y H:i') }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-800 font-medium">
                                        @if($pflichtstunde->duration > 60)
                                            {{ floor($pflichtstunde->duration / 60) }} Std. {{ $pflichtstunde->duration % 60 }} Min.
                                        @else
                                            {{ $pflichtstunde->duration }} Min.
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $pflichtstunde->description }}
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
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                            <tr>
                                <th colspan="3" class="px-4 py-3 text-right text-sm font-semibold text-gray-700">
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
                                <th colspan="3" class="px-4 py-3 text-right text-sm font-semibold text-gray-700">
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
                                <th colspan="3" class="px-4 py-3 text-right text-sm font-semibold text-gray-700">
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

