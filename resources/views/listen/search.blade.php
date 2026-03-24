@extends('layouts.app')
@section('title') - Listen Suche @endsection

@section('content')
    <div class="w-full max-w-7xl mx-auto px-4 py-6 space-y-6">
        <!-- Back Button -->
        <div class="flex items-center gap-2">
            <a href="{{ url('listen') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg transition-colors duration-200">
                <i class="fas fa-arrow-left"></i>
                Zurück zu Listen
            </a>
        </div>

        @if(auth()->user()->can('edit terminliste'))
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-amber-600 to-orange-600 px-6 py-4 border-b border-orange-800">
                    <h2 class="text-2xl font-bold text-white flex items-center gap-3 mb-0">
                        <i class="fas fa-search"></i>
                        Gefundene Listen
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
                                   value="{{ $query ?? '' }}"
                                   class="flex-1 px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 outline-none"
                                   placeholder="Suche nach Listenname...">
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-6 py-2 bg-orange-600 hover:bg-orange-700 text-white font-medium rounded-lg transition-colors duration-200">
                                <i class="fas fa-search"></i>
                                Suchen
                            </button>
                        </div>
                    </form>

                    <!-- Results Table -->
                    @if($archiv->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-100 border-b-2 border-gray-300">
                                    <tr>
                                        <th class="text-left px-4 py-3 font-semibold text-gray-700">Liste</th>
                                        <th class="text-left px-4 py-3 font-semibold text-gray-700">Abgelaufen am</th>
                                        <th class="text-center px-4 py-3 font-semibold text-gray-700">Aktionen</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($archiv as $liste)
                                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                                            <td class="px-4 py-3">
                                                <span class="font-medium text-gray-800">{{ $liste->listenname }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-gray-600">
                                                {{ $liste->ende->format('d.m.Y') }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center justify-center gap-2">
                                                    <a href="{{ url("listen/$liste->id") }}"
                                                       class="inline-flex items-center justify-center p-2 rounded-lg text-blue-600 hover:bg-blue-50 transition-all duration-200"
                                                       title="Anzeigen">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ url("listen/$liste->id/refresh") }}"
                                                       class="inline-flex items-center justify-center p-2 rounded-lg text-green-600 hover:bg-green-50 transition-all duration-200"
                                                       title="Reaktivieren">
                                                        <i class="fas fa-redo"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6 border-t border-gray-200 pt-4">
                            {{ $archiv->links() }}
                        </div>
                    @else
                        <div class="bg-gray-50 rounded-lg p-6 text-center">
                            <p class="text-gray-600 text-lg">Es wurden keine Listen gefunden</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection
