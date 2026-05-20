@extends('layouts.app')
@section('content')
    <div class="container-fluid px-4 py-3">
        <!-- Header Card -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-4">
            <div class="px-4 py-3 border-b"
                 style="background: linear-gradient(to right, var(--color-widget-primary-from), var(--color-widget-primary-to)); border-color: var(--color-widget-primary-border)">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <h3 class="text-xl font-bold mb-0" style="color: var(--color-widget-header-text)">
                        <i class="fas fa-file-alt mr-2"></i>
                        Seitenübersicht
                    </h3>
                    @can('create sites')
                        <a href="#createSite"
                           class="inline-flex items-center gap-2 px-4 py-2 bg-white font-medium rounded-lg transition-colors duration-200"
                           style="color: var(--color-primary)"
                           onmouseover="this.style.backgroundColor='var(--color-primary-light)'"
                           onmouseout="this.style.backgroundColor='#ffffff'">
                            <i class="fas fa-plus"></i>
                            Neue Seite erstellen
                        </a>
                    @endcan
                </div>
            </div>

            @if(count($sites) < 1)
                <div class="p-4">
                    <div class="flex items-start gap-3 p-3 border-l-4 rounded"
                         style="background-color: var(--color-primary-light); border-color: var(--color-primary)">
                        <i class="fas fa-info-circle mt-1" style="color: var(--color-primary)"></i>
                        <p class="text-sm mb-0" style="color: var(--color-text-main, #1e293b)">Keine Seiten vorhanden</p>
                    </div>
                </div>
            @else
                <div class="p-4">
                    <!-- Mobile View -->
                    <div class="md:hidden space-y-2">
                        @foreach($sites as $site)
                            <a href="{{ route('sites.show', $site->id) }}"
                               class="block border border-gray-200 rounded-lg p-3 transition-all duration-200 @if(!$site->is_active) bg-amber-50 border-amber-300 @endif"
                               onmouseover="this.style.borderColor='var(--color-primary)'; this.style.boxShadow='0 4px 6px -1px rgba(0,0,0,0.1)'"
                               onmouseout="this.style.borderColor=''; this.style.boxShadow=''">
                                <div class="flex items-start justify-between gap-2">
                                    <h6 class="font-semibold text-gray-800 mb-0">
                                        {{ $site->name }}
                                    </h6>
                                    @if(!$site->is_active)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-amber-100 text-amber-700 text-xs font-medium rounded-full">
                                            <i class="fas fa-eye-slash"></i>
                                            Unveröffentlicht
                                        </span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>

                    <!-- Desktop View -->
                    <div class="hidden md:grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($sites as $site)
                            <a href="{{ route('sites.show', $site->id) }}"
                               class="block border border-gray-200 rounded-lg p-4 transition-all duration-200 @if(!$site->is_active) bg-amber-50 border-amber-300 @endif"
                               onmouseover="this.style.borderColor='var(--color-primary)'; this.style.boxShadow='0 4px 6px -1px rgba(0,0,0,0.1)'"
                               onmouseout="this.style.borderColor=''; this.style.boxShadow=''">
                                <div class="flex flex-col gap-2">
                                    <h6 class="font-semibold text-gray-800 mb-0">
                                        {{ $site->name }}
                                    </h6>
                                    @if(!$site->is_active)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-amber-100 text-amber-700 text-xs font-medium rounded-full w-fit">
                                            <i class="fas fa-eye-slash"></i>
                                            Unveröffentlicht
                                        </span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Neue Seite erstellen Form -->
        @can('create sites',600)
            @cache('createSite')
                <div class="bg-white rounded-lg shadow-lg overflow-hidden" id="createSite">
                    <div class="px-4 py-3 border-b"
                         style="background: linear-gradient(to right, var(--color-widget-success-from), var(--color-widget-success-to)); border-color: var(--color-widget-success-border)">
                        <h3 class="text-xl font-bold mb-0" style="color: var(--color-widget-header-text)">
                            <i class="fas fa-plus-circle mr-2"></i>
                            Neue Seite erstellen
                        </h3>
                    </div>
                    <div class="p-4">
                        <form action="{{ route('sites.store') }}" method="post" class="space-y-4">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Titel</label>
                                    <input type="text"
                                           name="name"
                                           id="title"
                                           class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg transition-all duration-200 outline-none @error('title') border-red-500 @enderror"
                                           style="--tw-ring-color: var(--color-primary)"
                                           value="{{ old('name') }}"
                                           placeholder="Name der Seite">
                                    @error('title')
                                        <span class="text-red-600 text-sm mt-1 block">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                                <div>
                                    @include('include.formGroups')
                                </div>
                            </div>

                            <button type="submit"
                                    class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-6 py-2 text-white font-medium rounded-lg transition-colors duration-200"
                                    style="background-color: var(--color-widget-success-from)"
                                    onmouseover="this.style.backgroundColor='var(--color-widget-success-to)'"
                                    onmouseout="this.style.backgroundColor='var(--color-widget-success-from)'">
                                <i class="fas fa-save"></i>
                                Speichern
                            </button>
                        </form>
                    </div>
                </div>
            @endcache
        @endcan
    </div>
@endsection
