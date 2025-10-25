@extends('layouts.app')
@section('title') - Listen Export @endsection

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

        <!-- Export Card -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden" id="export">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 border-b border-blue-800 @if($liste->active == 0) from-cyan-500 to-cyan-600 border-cyan-700 @endif">
                <h2 class="text-2xl font-bold text-white flex items-center gap-3 mb-0">
                    <i class="fas fa-list-check"></i>
                    {{ $liste->listenname }}
                    @if($liste->active == 0)
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-yellow-400 text-yellow-900 rounded-full text-sm font-semibold">
                            inaktiv
                        </span>
                    @endif
                </h2>
                @if($liste->comment)
                    <p class="text-blue-100 text-sm mt-2">{!! $liste->comment !!}</p>
                @endif
            </div>

            <!-- Body -->
            <div class="px-6 py-6">
                @if($liste->eintragungen->count() > 0)
                    <div class="space-y-2">
                        @foreach($liste->eintragungen as $eintrag)
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <div class="flex-1">
                                    <p class="font-medium text-gray-800">{{ $eintrag->eintragung }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">
                                        <strong>{{ $eintrag->user?->name ?? 'unbekannt' }}</strong>
                                    </p>
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
