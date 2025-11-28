@extends('layouts.app')
@section('title') - Liste Details @endsection

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
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 border-b border-blue-800 @if($liste->active == 0) from-cyan-500 to-cyan-600 border-cyan-700 @endif">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-2xl font-bold text-white flex items-center gap-3 mb-0">
                        <i class="fas fa-list-check"></i>
                        {{ $liste->listenname }}
                        @if($liste->active == 0)
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-yellow-400 text-yellow-900 rounded-full text-sm font-semibold">
                                <i class="fas fa-exclamation-circle"></i>
                                inaktiv
                            </span>
                        @endif
                    </h2>

                    @if(auth()->user()->id == $liste->besitzer or auth()->user()->can('edit terminliste'))
                        <div class="flex items-center gap-2">
                            <a href="{{ route('listen.export-excel.termine', ['id' => $liste->id]) }}"
                               class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-gray-100 text-blue-600 font-medium rounded-lg transition-colors duration-200">
                                <i class="fas fa-file-excel"></i>
                                <span class="hidden md:inline">Excel</span>
                            </a>
                            <button onclick="generatePDF()"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-gray-100 text-red-600 font-medium rounded-lg transition-colors duration-200">
                                <i class="fas fa-file-pdf"></i>
                                <span class="hidden md:inline">PDF</span>
                            </button>
                        </div>
                    @endif
                </div>

                @if($liste->comment)
                    <p class="text-blue-100">{!! $liste->comment !!}</p>
                @endif
            </div>

            <!-- Entry Form -->
            @if($liste->make_new_entry or $liste->besitzer == auth()->user()->id or auth()->user()->can('edit terminliste'))
                @if($liste->eintragungen->filter(function ($eintragung) {
                    return $eintragung->user_id == auth()->id();
                })->count() == null or $liste->multiple)
                    <div class="border-b border-gray-200 px-6 py-4">
                        <form action="{{ url('/listen/' . $liste->id . '/eintragungen/') }}" method="post" class="flex gap-3">
                            @csrf
                            <input type="text"
                                   name="eintragung"
                                   maxlength="100"
                                   class="flex-1 px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                   placeholder="Eintrag hinzufügen...">
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors duration-200">
                                <i class="fas fa-save"></i>
                                <span class="hidden md:inline">Speichern</span>
                            </button>
                        </form>
                    </div>
                @endif
            @endif

            <!-- Entries List -->
            <div class="px-6 py-6" id="export">
                @if($liste->eintragungen->count() > 0)
                    <div class="space-y-2">
                        @foreach($liste->eintragungen as $eintrag)
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200 hover:border-blue-300 hover:shadow-md transition-all duration-200">
                                <div class="flex-1">
                                    <p class="font-medium text-gray-800">{{ $eintrag->eintragung }}</p>
                                </div>

                                <div class="flex items-center gap-3 ml-4">
                                    @if($eintrag->user_id != null)
                                        <div class="text-right">
                                            @if($liste->visible_for_all or auth()->user()->can('edit terminliste') or $eintrag->user_id == auth()->id())
                                                <span class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                                                    <i class="fas fa-user mr-1"></i>
                                                    {{ $eintrag->user->name }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-3 py-1 bg-gray-200 text-gray-700 rounded-full text-sm font-medium">
                                                    vergeben
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <form method="post" action="{{ url("listen/eintragungen/" . $eintrag->id) }}" style="display: inline;">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white font-medium rounded-lg transition-colors duration-200">
                                                <i class="fas fa-check"></i>
                                                <span class="hidden md:inline">Reservieren</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                @if($eintrag->user_id == auth()->id() or ($eintrag->created_by == auth()->id()) or auth()->user()->can('edit terminliste'))
                                    <form method="post" action="{{ url("listen/eintragungen/" . $eintrag->id) }}" style="display: inline;" class="ml-2">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center px-3 py-2 text-red-600 hover:text-red-700 font-medium transition-colors duration-200"
                                                title="{{ $eintrag->user_id == auth()->id() ? 'Absagen' : ($eintrag->user_id != null && ($eintrag->created_by == auth()->id() || auth()->user()->can('edit terminliste')) ? 'Freigeben' : 'Löschen') }}">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-gray-50 rounded-lg p-6 text-center border-2 border-dashed border-gray-300">
                        <i class="fas fa-inbox text-4xl text-gray-400 mb-3"></i>
                        <p class="text-gray-600 text-lg">Es wurden bisher keine Eintragungen angelegt.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection

@push('js')
    <script src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
    <script>
        function generatePDF() {
            const element = document.getElementById('export');
            html2pdf().from(element).save();
        }
    </script>
@endpush

