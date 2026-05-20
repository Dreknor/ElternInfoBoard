@extends('layouts.app')

@section('content')
    <div class="container-fluid px-4 py-3">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="px-4 py-3 border-b"
                 style="background: linear-gradient(to right, var(--color-widget-accent-from), var(--color-widget-accent-to)); border-color: var(--color-widget-accent-border)">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <h4 class="text-xl font-bold mb-0 flex items-center gap-2" style="color: var(--color-widget-header-text)">
                        <i class="fas fa-users"></i>
                        Teilnehmerverwaltung: {{ $arbeitsgemeinschaft->name }}
                    </h4>
                    <a href="{{ route('verwaltung.arbeitsgemeinschaften.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-gray-100 font-semibold rounded-lg transition-colors duration-200 shadow-md"
                       style="color: var(--color-widget-accent-from)">
                        <i class="fas fa-arrow-left"></i>
                        Zurück
                    </a>
                </div>
            </div>

            <div class="p-4 space-y-4">
                <!-- Info-Box -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 rounded-lg border-l-4"
                     style="background-color: var(--color-widget-accent-bg); border-color: var(--color-widget-accent-from)">
                    <div class="text-sm">
                        <span class="font-semibold text-gray-700">Maximale Teilnehmer:</span>
                        <span class="ml-2 font-bold" style="color: var(--color-widget-accent-from)">{{ $arbeitsgemeinschaft->max_participants }}</span>
                    </div>
                    <div class="text-sm">
                        <span class="font-semibold text-gray-700">Aktuelle Teilnehmer:</span>
                        <span class="ml-2 font-bold" style="color: var(--color-widget-accent-from)">{{ $participants->count() }}</span>
                    </div>
                    <div class="text-sm">
                        <span class="font-semibold text-gray-700">Freie Plätze:</span>
                        <span class="ml-2 font-bold text-green-600">{{ $arbeitsgemeinschaft->max_participants - $participants->count() }}</span>
                    </div>
                </div>

                <!-- Teilnehmer hinzufügen -->
                @if($arbeitsgemeinschaft->participants()->count() < $arbeitsgemeinschaft->max_participants)
                    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <div class="px-4 py-3 border-b"
                             style="background: linear-gradient(to right, var(--color-widget-success-from), var(--color-widget-success-to)); border-color: var(--color-widget-success-border)">
                            <h5 class="text-base font-bold mb-0 flex items-center gap-2" style="color: var(--color-widget-header-text)">
                                <i class="fas fa-user-plus"></i>
                                Teilnehmer hinzufügen
                            </h5>
                        </div>
                        <div class="p-4">
                            <form action="{{ route('verwaltung.arbeitsgemeinschaften.teilnehmer.add', $arbeitsgemeinschaft) }}"
                                  method="POST"
                                  class="flex flex-col sm:flex-row gap-3 items-end">
                                @csrf
                                <div class="flex-1">
                                    <label for="child_id" class="block text-sm font-medium text-gray-700 mb-2">Kind auswählen</label>
                                    <select name="child_id" id="child_id"
                                            class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:outline-none transition-all duration-200"
                                            onfocus="this.style.borderColor='var(--color-widget-success-from)'"
                                            onblur="this.style.borderColor='#d1d5db'"
                                            required>
                                        <option value="">Bitte wählen...</option>
                                        @foreach($availableChildren->sortBy('last_name') as $child)
                                            <option value="{{ $child->id }}">
                                                {{ $child->last_name }}, {{ $child->first_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit"
                                        class="inline-flex items-center gap-2 px-4 py-2 text-white font-semibold rounded-lg transition-colors duration-200 shadow-md"
                                        style="background-color: var(--color-widget-success-from)"
                                        onmouseover="this.style.backgroundColor='var(--color-widget-success-to)'"
                                        onmouseout="this.style.backgroundColor='var(--color-widget-success-from)'">
                                    <i class="fas fa-plus-circle"></i>
                                    Hinzufügen
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

                <!-- Teilnehmerliste -->
                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <div class="px-4 py-3 border-b bg-gray-50">
                        <h5 class="text-base font-bold mb-0 text-gray-800 flex items-center gap-2">
                            <i class="fas fa-list" style="color: var(--color-widget-accent-from)"></i>
                            Aktuelle Teilnehmer
                        </h5>
                    </div>
                    <div class="p-4">
                        @if($participants->isEmpty())
                            <div class="flex items-start gap-3 p-4 rounded border-l-4"
                                 style="background-color: var(--color-widget-accent-bg); border-color: var(--color-widget-accent-from)">
                                <i class="fas fa-info-circle mt-1" style="color: var(--color-widget-accent-from)"></i>
                                <p class="text-sm mb-0 text-gray-700">Noch keine Teilnehmer vorhanden.</p>
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full border border-gray-200 rounded-lg overflow-hidden">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Name</th>
                                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Gruppen</th>
                                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Hinzugefügt von</th>
                                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Aktionen</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach($participants as $participant)
                                            <tr class="hover:bg-gray-50 transition-colors">
                                                <td class="px-4 py-3 text-sm text-gray-800 font-medium">
                                                    {{ $participant->last_name }}, {{ $participant->first_name }}
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-700">
                                                    {{ $participant->group->name }}
                                                    @if($participant->class->id != $participant->group->id)
                                                        , {{ $participant->class->name }}
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-700">
                                                    {{ $participant->pivot->user->name ?? 'Unbekannt' }}
                                                </td>
                                                <td class="px-4 py-3">
                                                    <form action="{{ route('verwaltung.arbeitsgemeinschaften.teilnehmer.remove', [$arbeitsgemeinschaft, $participant]) }}"
                                                          method="POST"
                                                          class="inline"
                                                          onsubmit="return confirm('Soll dieser Teilnehmer wirklich entfernt werden?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg transition-colors duration-200">
                                                            <i class="fas fa-trash"></i>
                                                            <span class="hidden sm:inline">Entfernen</span>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
