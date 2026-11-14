@extends('layouts.app')
@section('title') - Liste Details @endsection

@section('content')
    @php
        $preview ??= false;
        $canManage = ! $preview && (auth()->id() == $liste->besitzer or auth()->user()->can('edit terminliste'));
    @endphp
    <div class="w-full max-w-7xl mx-auto px-4 py-6 space-y-6">
        <!-- Back Button -->
        <div class="flex items-center gap-2">
            <a href="{{ url('listen') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg transition-colors duration-200">
                <i class="fas fa-arrow-left"></i>
                Zurück zur Übersicht
            </a>
        </div>

        @if($preview)
            @include('listen.partials.preview_banner')
        @endif

        <!-- Main Card -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden" @if($preview) inert @endif>
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 border-b border-blue-800 @if($liste->active == 0) from-cyan-500 to-cyan-600 border-cyan-700 @endif">
                <div class="flex flex-wrap items-start justify-between gap-2 mb-3">
                    <h2 class="text-xl sm:text-2xl font-bold text-white flex flex-wrap items-center gap-2 mb-0">
                        <i class="fas fa-list-check"></i>
                        {{ $liste->listenname }}
                        @if($liste->active == 0)
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-yellow-400 text-yellow-900 rounded-full text-sm font-semibold">
                                <i class="fas fa-exclamation-circle"></i>
                                inaktiv
                            </span>
                        @endif
                    </h2>

                    @if($canManage)
                        <div class="flex flex-wrap items-center gap-2 flex-shrink-0">
                            <a href="{{ route('listen.export-excel.termine', ['id' => $liste->id]) }}"
                               class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-gray-100 text-blue-600 font-medium rounded-lg transition-colors duration-200">
                                <i class="fas fa-file-excel"></i>
                                <span>Excel</span>
                            </a>
                            <button onclick="generatePDF()"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-gray-100 text-red-600 font-medium rounded-lg transition-colors duration-200">
                                <i class="fas fa-file-pdf"></i>
                                <span>PDF</span>
                            </button>
                            <a href="{{ route('listen.preview', $liste->id) }}"
                               class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-gray-100 text-amber-700 font-medium rounded-lg transition-colors duration-200">
                                <i class="fas fa-eye"></i>
                                <span>Vorschau</span>
                            </a>
                        </div>
                    @endif
                </div>

                @if($liste->comment)
                    <p class="text-blue-100">@safeHtml($liste->comment)</p>
                @endif
            </div>

            <!-- Entry Form -->
            @if($liste->make_new_entry or $canManage)
                @php $bookableChildren = app(\App\Services\App\ListenService::class)->bookableChildren(auth()->user(), $liste); @endphp
                @if($preview or $liste->bookingPerChild() or $liste->eintragungen->filter(function ($eintragung) {
                    return $eintragung->user_id == auth()->id();
                })->count() == null or $liste->multiple)
                    <div class="border-b border-gray-200 px-6 py-4">
                        <form action="{{ url('/listen/' . $liste->id . '/eintragungen/') }}" method="post" class="flex flex-wrap gap-3">
                            @csrf
                            <input type="text"
                                   name="eintragung"
                                   maxlength="100"
                                   class="flex-1 min-w-0 px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                   placeholder="Eintrag hinzufügen...">
                            @include('listen.partials.child_select')
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
            <div class="px-3 py-4 sm:px-6 sm:py-6" id="export">
                @if($liste->eintragungen->count() > 0)
                    <div class="space-y-2">
                        @foreach($liste->eintragungen as $eintrag)
                            {{-- Mobil: Eintragstext in voller Breite, Aktionen darunter; ab sm nebeneinander --}}
                            <div class="flex flex-col sm:flex-row sm:items-center gap-3 p-3 sm:p-4 bg-gray-50 rounded-lg border border-gray-200 hover:border-blue-300 hover:shadow-md transition-all duration-200">
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-800 break-words mb-0">{{ $eintrag->eintragung }}</p>
                                    @if($eintrag->bemerkung)
                                        <p class="text-sm text-gray-600 mb-0"><i class="fas fa-comment-dots mr-1"></i>{{ $eintrag->bemerkung }}</p>
                                    @endif
                                    @if($eintrag->child && ($liste->visible_for_all or (! $preview and (auth()->user()->can('edit terminliste') or app(\App\Services\App\ListenService::class)->mayCancel(auth()->user(), $eintrag->user_id ? (int) $eintrag->user_id : null, $eintrag->child_id)))))
                                        <p class="text-xs text-gray-500 mb-0"><i class="fas fa-child mr-1"></i>für {{ $eintrag->child->first_name }}</p>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2 sm:ml-4 sm:flex-shrink-0">
                                    @if($eintrag->user_id != null)
                                        <div class="flex-1 sm:flex-none sm:text-right min-w-0">
                                            @if($liste->visible_for_all or (! $preview and (auth()->user()->can('edit terminliste') or $eintrag->user_id == auth()->id())))
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
                                        <form method="post" action="{{ url("listen/eintragungen/" . $eintrag->id) }}" class="flex flex-1 sm:flex-none flex-wrap items-center sm:justify-end gap-2 min-w-0">
                                            @csrf
                                            @method('PUT')
                                            <input type="text"
                                                   name="bemerkung"
                                                   maxlength="255"
                                                   class="flex-1 basis-32 min-w-0 sm:flex-none sm:w-56 px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none text-sm"
                                                   placeholder="Bemerkung (optional)"
                                                   aria-label="Bemerkung zu {{ $eintrag->eintragung }}">
                                            @include('listen.partials.child_select')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white font-medium rounded-lg transition-colors duration-200"
                                                    title="Reservieren">
                                                <i class="fas fa-check"></i>
                                                <span class="hidden md:inline">Reservieren</span>
                                            </button>
                                        </form>
                                    @endif

                                    @if($eintrag->user_id == null and $canManage)
                                        <form method="post" action="{{ route('listen.eintragungen.copy', $eintrag->id) }}" class="flex-shrink-0 mb-0">
                                            @csrf
                                            <button type="submit"
                                                    class="inline-flex items-center px-3 py-2 text-blue-600 hover:text-blue-700 font-medium transition-colors duration-200"
                                                    title="Kopieren">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </form>
                                    @endif

                                    @if(! $preview and ($eintrag->user_id == auth()->id() or ($eintrag->created_by == auth()->id()) or auth()->user()->can('edit terminliste') or ($eintrag->user_id && app(\App\Services\App\ListenService::class)->mayCancel(auth()->user(), (int) $eintrag->user_id, $eintrag->child_id))))
                                        <form method="post" action="{{ url("listen/eintragungen/" . $eintrag->id) }}" class="flex-shrink-0 mb-0">
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

