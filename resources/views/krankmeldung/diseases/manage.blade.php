@extends('layouts.app')

@section('title')
    - Krankheiten verwalten
@endsection

@section('content')
    <div class="container-fluid px-4 py-6">
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Meldepflichtige Krankheiten verwalten</h1>
            <p class="text-gray-600">Verwalten Sie Krankheitsstammdaten und lösen Sie aktive Erkrankungen aus</p>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <!-- Active Diseases Section -->
            <div class="space-y-4">
                <!-- Trigger Active Disease Card -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-red-600 to-red-700 px-4 py-3 border-b border-red-800">
                        <h5 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                            <i class="fas fa-virus"></i>
                            Aktive Erkrankung auslösen
                        </h5>
                    </div>
                    <div class="p-4">
                        <form action="{{route('active-diseases.store')}}" method="post">
                            @csrf
                            <div class="space-y-4">
                                <div>
                                    <label for="disease_id" class="block text-sm font-semibold text-gray-700 mb-2">
                                        Krankheit auswählen: <span class="text-red-500">*</span>
                                    </label>
                                    <select name="disease_id"
                                            id="disease_id"
                                            class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg
                                                   focus:border-red-500 focus:ring-2 focus:ring-red-200
                                                   transition-all duration-200 outline-none"
                                            required>
                                        <option value="">Bitte wählen...</option>
                                        @foreach($diseases as $disease)
                                            <option value="{{$disease->id}}">
                                                {{$disease->name}} ({{$disease->aushang_dauer}} Tage)
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('disease_id')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <button type="submit"
                                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2
                                               bg-red-600 hover:bg-red-700 text-white font-medium
                                               rounded-lg transition-colors duration-200">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Erkrankung auslösen
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Active Diseases List -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-orange-500 to-amber-600 px-4 py-3 border-b border-orange-800">
                        <h5 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                            <i class="fas fa-clipboard-list"></i>
                            Aktive Erkrankungen
                        </h5>
                    </div>
                    <div class="p-4">
                        @if($activeDiseases->isEmpty())
                            <div class="text-center py-8">
                                <i class="fas fa-check-circle text-5xl text-green-500 mb-3"></i>
                                <p class="text-gray-600">Keine aktiven Erkrankungen vorhanden</p>
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach($activeDiseases as $activeDisease)
                                    <div class="border-2 @if($activeDisease->active) border-red-300 bg-red-50 @else border-gray-200 bg-gray-50 @endif rounded-lg p-3
                                                transition-all duration-200">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <h6 class="font-bold text-gray-800 mb-0">
                                                        {{$activeDisease->disease->name}}
                                                    </h6>
                                                    @if($activeDisease->active)
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5
                                                                     bg-red-600 text-white text-xs font-semibold rounded-full">
                                                            <i class="fas fa-circle text-[6px] animate-pulse"></i>
                                                            Aktiv
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5
                                                                     bg-gray-400 text-white text-xs font-semibold rounded-full">
                                                            <i class="fas fa-pause text-[6px]"></i>
                                                            Inaktiv
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="text-sm text-gray-600 space-y-1">
                                                    <p class="mb-0">
                                                        <i class="fas fa-calendar text-xs mr-1"></i>
                                                        {{$activeDisease->start->format('d.m.Y')}} - {{$activeDisease->end->format('d.m.Y')}}
                                                        ({{$activeDisease->start->diffInDays($activeDisease->end)}} Tage)
                                                    </p>
                                                    <p class="mb-0">
                                                        <i class="fas fa-user text-xs mr-1"></i>
                                                        Erstellt von {{$activeDisease->user->name}}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="flex flex-col gap-2">
                                                @if($activeDisease->active)
                                                    <form action="{{route('active-diseases.toggle', $activeDisease->id)}}"
                                                          method="post">
                                                        @csrf
                                                        @method('put')
                                                        <button type="submit"
                                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-sm
                                                                       bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium
                                                                       rounded-lg transition-colors duration-200"
                                                                title="Deaktivieren">
                                                            <i class="fas fa-pause"></i>
                                                            <span class="hidden sm:inline">Deaktivieren</span>
                                                        </button>
                                                    </form>
                                                    <a href="{{route('active-diseases.extend', $activeDisease->id)}}"
                                                       class="inline-flex items-center gap-1 px-3 py-1.5 text-sm
                                                              bg-blue-600 hover:bg-blue-700 text-white font-medium
                                                              rounded-lg transition-colors duration-200"
                                                       title="Verlängern">
                                                        <i class="fas fa-plus"></i>
                                                        <span class="hidden sm:inline">Verlängern</span>
                                                    </a>
                                                @else
                                                    <form action="{{route('active-diseases.toggle', $activeDisease->id)}}"
                                                          method="post">
                                                        @csrf
                                                        @method('put')
                                                        <button type="submit"
                                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-sm
                                                                       bg-green-600 hover:bg-green-700 text-white font-medium
                                                                       rounded-lg transition-colors duration-200"
                                                                title="Freigeben">
                                                            <i class="fas fa-check"></i>
                                                            <span class="hidden sm:inline">Freigeben</span>
                                                        </button>
                                                    </form>
                                                    <form action="{{route('active-diseases.delete', $activeDisease->id)}}"
                                                          method="post"
                                                          onsubmit="return confirm('Sind Sie sicher?');">
                                                        @csrf
                                                        @method('delete')
                                                        <button type="submit"
                                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-sm
                                                                       bg-red-600 hover:bg-red-700 text-white font-medium
                                                                       rounded-lg transition-colors duration-200"
                                                                title="Löschen">
                                                            <i class="fas fa-trash"></i>
                                                            <span class="hidden sm:inline">Löschen</span>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Disease Master Data Section -->
            <div class="space-y-4">
                <!-- Add New Disease Card -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden" x-data="{ showForm: false }">
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3 border-b border-blue-800">
                        <div class="flex items-center justify-between">
                            <h5 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                                <i class="fas fa-database"></i>
                                Krankheitsstammdaten
                            </h5>
                            <button @click="showForm = !showForm"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 text-sm
                                           bg-white hover:bg-gray-100 text-blue-600 font-medium
                                           rounded-lg transition-colors duration-200">
                                <i class="fas" :class="showForm ? 'fa-times' : 'fa-plus'"></i>
                                <span x-text="showForm ? 'Abbrechen' : 'Neue Krankheit'"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Add Form -->
                    <div x-show="showForm"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         style="display: none;"
                         class="border-b border-gray-200 bg-gray-50 p-4">
                        <form action="{{route('diseases.store')}}" method="post">
                            @csrf
                            <div class="space-y-3">
                                <div>
                                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">
                                        Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text"
                                           name="name"
                                           id="name"
                                           class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg
                                                  focus:border-blue-500 focus:ring-2 focus:ring-blue-200
                                                  transition-all duration-200 outline-none"
                                           required>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label for="reporting" class="block text-sm font-semibold text-gray-700 mb-1">
                                            Meldepflichtig <span class="text-red-500">*</span>
                                        </label>
                                        <select name="reporting"
                                                id="reporting"
                                                class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg
                                                       focus:border-blue-500 focus:ring-2 focus:ring-blue-200
                                                       transition-all duration-200 outline-none"
                                                required>
                                            <option value="1">Ja</option>
                                            <option value="0">Nein</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="aushang_dauer" class="block text-sm font-semibold text-gray-700 mb-1">
                                            Aushangdauer (Tage) <span class="text-red-500">*</span>
                                        </label>
                                        <input type="number"
                                               name="aushang_dauer"
                                               id="aushang_dauer"
                                               value="14"
                                               min="1"
                                               class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg
                                                      focus:border-blue-500 focus:ring-2 focus:ring-blue-200
                                                      transition-all duration-200 outline-none"
                                               required>
                                    </div>
                                </div>

                                <div>
                                    <label for="wiederzulassung_durch" class="block text-sm font-semibold text-gray-700 mb-1">
                                        Wiederzulassung durch <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text"
                                           name="wiederzulassung_durch"
                                           id="wiederzulassung_durch"
                                           placeholder="z.B. ärztliches Attest"
                                           class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg
                                                  focus:border-blue-500 focus:ring-2 focus:ring-blue-200
                                                  transition-all duration-200 outline-none"
                                           required>
                                </div>

                                <div>
                                    <label for="wiederzulassung_wann" class="block text-sm font-semibold text-gray-700 mb-1">
                                        Wiederzulassung wann <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text"
                                           name="wiederzulassung_wann"
                                           id="wiederzulassung_wann"
                                           placeholder="z.B. nach 24h Symptomfreiheit"
                                           class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg
                                                  focus:border-blue-500 focus:ring-2 focus:ring-blue-200
                                                  transition-all duration-200 outline-none"
                                           required>
                                </div>

                                <button type="submit"
                                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2
                                               bg-blue-600 hover:bg-blue-700 text-white font-medium
                                               rounded-lg transition-colors duration-200">
                                    <i class="fas fa-save"></i>
                                    Krankheit speichern
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Diseases List -->
                    <div class="p-4">
                        @if($diseases->isEmpty())
                            <div class="text-center py-8">
                                <i class="fas fa-database text-5xl text-gray-300 mb-3"></i>
                                <p class="text-gray-600">Keine Krankheiten angelegt</p>
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach($diseases as $disease)
                                    <div class="border-2 border-gray-200 rounded-lg p-3 hover:border-blue-500
                                                hover:shadow-md transition-all duration-200"
                                         x-data="{ editing: false }">
                                        <!-- View Mode -->
                                        <div x-show="!editing">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2 mb-2">
                                                        <h6 class="font-bold text-gray-800 mb-0">{{$disease->name}}</h6>
                                                        @if($disease->reporting)
                                                            <span class="inline-flex items-center px-2 py-0.5
                                                                         bg-red-100 text-red-700 text-xs font-semibold rounded-full">
                                                                Meldepflichtig
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="text-sm text-gray-600 space-y-1">
                                                        <p class="mb-0">
                                                            <i class="fas fa-clock text-xs mr-1"></i>
                                                            Aushang: {{$disease->aushang_dauer}} Tage
                                                        </p>
                                                        <p class="mb-0">
                                                            <i class="fas fa-file-medical text-xs mr-1"></i>
                                                            Wiederzulassung durch: {{$disease->wiederzulassung_durch}}
                                                        </p>
                                                        <p class="mb-0">
                                                            <i class="fas fa-info-circle text-xs mr-1"></i>
                                                            Wiederzulassung wann: {{$disease->wiederzulassung_wann}}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="flex flex-col gap-2">
                                                    <button @click="editing = true"
                                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-sm
                                                                   bg-blue-600 hover:bg-blue-700 text-white font-medium
                                                                   rounded-lg transition-colors duration-200">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form action="{{route('diseases.destroy', $disease->id)}}"
                                                          method="post"
                                                          onsubmit="return confirm('Wirklich löschen?');">
                                                        @csrf
                                                        @method('delete')
                                                        <button type="submit"
                                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-sm
                                                                       bg-red-600 hover:bg-red-700 text-white font-medium
                                                                       rounded-lg transition-colors duration-200">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Edit Mode -->
                                        <div x-show="editing" style="display: none;">
                                            <form action="{{route('diseases.update', $disease->id)}}" method="post">
                                                @csrf
                                                @method('put')
                                                <div class="space-y-3">
                                                    <div>
                                                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                                                            Name <span class="text-red-500">*</span>
                                                        </label>
                                                        <input type="text"
                                                               name="name"
                                                               value="{{$disease->name}}"
                                                               class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg
                                                                      focus:border-blue-500 focus:ring-2 focus:ring-blue-200
                                                                      transition-all duration-200 outline-none"
                                                               required>
                                                    </div>

                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                        <div>
                                                            <label class="block text-sm font-semibold text-gray-700 mb-1">
                                                                Meldepflichtig <span class="text-red-500">*</span>
                                                            </label>
                                                            <select name="reporting"
                                                                    class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg
                                                                           focus:border-blue-500 focus:ring-2 focus:ring-blue-200
                                                                           transition-all duration-200 outline-none"
                                                                    required>
                                                                <option value="1" {{$disease->reporting ? 'selected' : ''}}>Ja</option>
                                                                <option value="0" {{!$disease->reporting ? 'selected' : ''}}>Nein</option>
                                                            </select>
                                                        </div>

                                                        <div>
                                                            <label class="block text-sm font-semibold text-gray-700 mb-1">
                                                                Aushangdauer (Tage) <span class="text-red-500">*</span>
                                                            </label>
                                                            <input type="number"
                                                                   name="aushang_dauer"
                                                                   value="{{$disease->aushang_dauer}}"
                                                                   min="1"
                                                                   class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg
                                                                          focus:border-blue-500 focus:ring-2 focus:ring-blue-200
                                                                          transition-all duration-200 outline-none"
                                                                   required>
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                                                            Wiederzulassung durch <span class="text-red-500">*</span>
                                                        </label>
                                                        <input type="text"
                                                               name="wiederzulassung_durch"
                                                               value="{{$disease->wiederzulassung_durch}}"
                                                               class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg
                                                                      focus:border-blue-500 focus:ring-2 focus:ring-blue-200
                                                                      transition-all duration-200 outline-none"
                                                               required>
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                                                            Wiederzulassung wann <span class="text-red-500">*</span>
                                                        </label>
                                                        <input type="text"
                                                               name="wiederzulassung_wann"
                                                               value="{{$disease->wiederzulassung_wann}}"
                                                               class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg
                                                                      focus:border-blue-500 focus:ring-2 focus:ring-blue-200
                                                                      transition-all duration-200 outline-none"
                                                               required>
                                                    </div>

                                                    <div class="flex gap-2">
                                                        <button type="submit"
                                                                class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2
                                                                       bg-blue-600 hover:bg-blue-700 text-white font-medium
                                                                       rounded-lg transition-colors duration-200">
                                                            <i class="fas fa-save"></i>
                                                            Speichern
                                                        </button>
                                                        <button type="button"
                                                                @click="editing = false"
                                                                class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2
                                                                       bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium
                                                                       rounded-lg transition-colors duration-200">
                                                            <i class="fas fa-times"></i>
                                                            Abbrechen
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

