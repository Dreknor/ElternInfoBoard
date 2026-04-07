@extends('layouts.app')
@section('title') - Listen @endsection

@section('content')
    <div class="w-full max-w-7xl mx-auto px-2 sm:px-4 py-4 sm:py-6 space-y-6">
        <!-- Aktuelle Listen Section -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 border-b border-blue-800">
                <h2 class="text-2xl font-bold text-white flex items-center gap-3 mb-0">
                    <i class="fas fa-list"></i>
                    Aktuelle Listen
                </h2>
            </div>

            <!-- Body -->
            <div class="px-6 py-6">
                @if(count($listen) < 1)
                    <div class="bg-gray-50 rounded-lg p-6 text-center">
                        <p class="text-gray-600 text-lg">Es wurden keine aktuellen Listen gefunden</p>
                    </div>
                @else
                    <!-- Grid Layout für Listen Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @can('create terminliste')
                            <!-- Neue Liste Card -->
                            <div class="border-2 border-dashed border-green-400 rounded-lg p-6 flex flex-col items-center justify-center hover:border-green-500 hover:bg-green-50 transition-all duration-200">
                                <i class="fas fa-plus text-4xl text-green-500 mb-3"></i>
                                <h3 class="text-lg font-semibold text-gray-800 mb-4">Neue Liste</h3>
                                <a href="{{ url('listen/create') }}"
                                   class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors duration-200">
                                    <i class="fas fa-plus"></i>
                                    Erstellen
                                </a>
                            </div>
                        @endcan

                        <!-- Listen Cards -->
                        @foreach($listen as $liste)
                            @if($liste->type == 'termin')
                                @include('listen.cards.terminListe')
                            @else
                                @include('listen.cards.eintragListe')
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Abgelaufene Listen Section (für Admin) -->
        @if(auth()->user()->can('edit terminliste'))
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-amber-600 to-orange-600 px-6 py-4 border-b border-orange-800">
                    <h2 class="text-2xl font-bold text-white flex items-center gap-3 mb-0">
                        <i class="fas fa-history"></i>
                        Abgelaufene Listen
                    </h2>
                </div>

                <!-- Body -->
                <div class="px-6 py-6">
                    <!-- Search Form -->
                    <form method="POST" action="{{ url('listen/search') }}" class="mb-6">
                        @csrf
                        <div class="flex gap-3">
                            <input type="text"
                                   name="query"
                                   class="flex-1 px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                   placeholder="Suche nach Listenname...">
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors duration-200">
                                <i class="fas fa-search"></i>
                                Suchen
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection
