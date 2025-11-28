@extends('layouts.app')
@section('title') - Termine @endsection

@section('content')
    <div class="w-full max-w-7xl mx-auto px-4 py-6 space-y-6">
        <!-- Back Button -->
        <div class="flex items-center gap-2">
            <a href="{{ url('listen') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg transition-colors duration-200">
                <i class="fas fa-arrow-left"></i>
                Zurück zur Übersicht
            </a>
        </div>

        <!-- Main Card -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-teal-500 to-teal-600 px-6 py-4 border-b border-teal-700 @if($liste->active == 0) from-cyan-500 to-cyan-600 border-cyan-700 @endif">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-2xl font-bold text-white flex items-center gap-3 mb-0">
                        <i class="fas fa-calendar-check"></i>
                        {{ $liste->listenname }}
                        @if($liste->active == 0)
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-yellow-400 text-yellow-900 rounded-full text-sm font-semibold">
                                inaktiv
                            </span>
                        @endif
                    </h2>

                    @if(auth()->user()->id == $liste->besitzer or auth()->user()->can('edit terminliste'))
                        <div class="flex items-center gap-2">
                            <button class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-gray-100 text-teal-600 font-medium rounded-lg transition-colors duration-200"
                                    data-toggle="modal" data-target="#createEintragungModal">
                                <i class="fas fa-plus"></i>
                                <span class="hidden md:inline">Termin</span>
                            </button>
                            <a href="{{ url('listen/' . $liste->id . '/export') }}"
                               class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-gray-100 text-teal-600 font-medium rounded-lg transition-colors duration-200">
                                <i class="fas fa-print"></i>
                                <span class="hidden md:inline">Druck</span>
                            </a>
                            <button type="button" id="showAll"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-gray-100 text-teal-600 font-medium rounded-lg transition-colors duration-200">
                                <i class="fas fa-eye"></i>
                                <span class="hidden md:inline">Alle</span>
                            </button>
                        </div>
                    @endif
                </div>

                @if($liste->comment)
                    <p class="text-teal-100">{!! $liste->comment !!}</p>
                @endif
            </div>

            <!-- Termine List -->
            <div class="px-6 py-6">
                @if($liste->termine->count() > 0)
                    <div class="space-y-3">
                        @foreach($liste->termine->sortBy('termin') as $eintrag)
                            @include('listen.terminListen.termin')
                        @endforeach
                    </div>
                @else
                    <div class="bg-gray-50 rounded-lg p-6 text-center border-2 border-dashed border-gray-300">
                        <i class="fas fa-calendar-times text-4xl text-gray-400 mb-3"></i>
                        <p class="text-gray-600 text-lg">Es wurden bisher keine Termine angelegt.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal zum Anlegen von Terminen -->
    <div class="modal fade" id="createEintragungModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content rounded-lg">
                <div class="modal-header bg-gradient-to-r from-teal-500 to-teal-600 border-0">
                    <h5 class="modal-title text-white font-semibold">
                        <i class="fas fa-calendar-plus mr-2"></i>
                        Neuen Termin erstellen
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-6">
                    <form method="post" action="{{ url("listen/termine/$liste->id/store") }}" id="terminForm" class="space-y-4">
                        @csrf

                        <div>
                            <label for="termin" class="block text-sm font-semibold text-gray-700 mb-2">
                                Datum <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="termin" id="termin"
                                   class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-teal-500 focus:ring-2 focus:ring-teal-200 transition-all duration-200 outline-none"
                                   required>
                        </div>

                        <div>
                            <label for="zeit" class="block text-sm font-semibold text-gray-700 mb-2">
                                Uhrzeit <span class="text-red-500">*</span>
                            </label>
                            <input type="time" name="zeit" id="zeit"
                                   class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-teal-500 focus:ring-2 focus:ring-teal-200 transition-all duration-200 outline-none"
                                   required>
                        </div>

                        <div>
                            <label for="duration" class="block text-sm font-semibold text-gray-700 mb-2">
                                Dauer (Minuten)
                            </label>
                            <input type="number" name="duration" id="duration"
                                   class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-teal-500 focus:ring-2 focus:ring-teal-200 transition-all duration-200 outline-none"
                                   value="{{ $liste->duration }}">
                        </div>

                        <div>
                            <label for="comment" class="block text-sm font-semibold text-gray-700 mb-2">
                                Anmerkung
                            </label>
                            <input type="text" name="comment" id="comment"
                                   class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-teal-500 focus:ring-2 focus:ring-teal-200 transition-all duration-200 outline-none"
                                   placeholder="z.B. Turnhalle A">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="weekly" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Wöchentlich?
                                </label>
                                <select name="weekly" id="weekly"
                                        class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-teal-500 focus:ring-2 focus:ring-teal-200 transition-all duration-200 outline-none">
                                    <option value="0">Nein</option>
                                    <option value="1">Ja</option>
                                </select>
                            </div>

                            <div>
                                <label for="repeat" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Anzahl
                                </label>
                                <input type="number" name="repeat" id="repeat"
                                       class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-teal-500 focus:ring-2 focus:ring-teal-200 transition-all duration-200 outline-none"
                                       min="1" step="1" value="1">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-gray-50 border-t px-6 py-4">
                    <button type="button" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg transition-colors duration-200"
                            data-dismiss="modal">
                        <i class="fas fa-times"></i>
                        Schließen
                    </button>
                    <button type="submit" id="submitBtn" form="terminForm"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white font-medium rounded-lg transition-colors duration-200">
                        <i class="fas fa-check"></i>
                        Speichern
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal zur Absage -->
    <div class="modal fade" id="deleteEintragungModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content rounded-lg">
                <div class="modal-header bg-gradient-to-r from-red-600 to-red-700 border-0">
                    <h5 class="modal-title text-white font-semibold">
                        <i class="fas fa-calendar-times mr-2"></i>
                        Termin absagen
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-6">
                    <form method="post" action="" id="absagenForm" class="space-y-4">
                        @csrf
                        @method('DELETE')

                        <div>
                            <label for="text" class="block text-sm font-semibold text-gray-700 mb-2">
                                Nachricht an Teilnehmer
                            </label>
                            <textarea name="text" id="text" rows="4"
                                      class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-200 transition-all duration-200 outline-none resize-none"
                                      placeholder="Grund der Absage (optional)"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-gray-50 border-t px-6 py-4">
                    <button type="button" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg transition-colors duration-200"
                            data-dismiss="modal">
                        <i class="fas fa-times"></i>
                        Abbrechen
                    </button>
                    <button type="submit" form="absagenForm"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors duration-200">
                        <i class="fas fa-trash-alt"></i>
                        Absagen
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('js')
    <script>
        $('#submitBtn').on('click', function () {
            $("#terminForm").submit();
        });

        $('#showAll').on('click', function () {
            $(this).addClass('d-none');
            $('.hide').removeClass('d-none');
        });

        $('.btnAbsage').on('click', function () {
            const id = $(this).data('terminid');
            const url = "{{ url('listen/termine/absagen/') }}" + "/" + id;
            $('#absagenForm').attr('action', url);
        });
    </script>
@endpush

