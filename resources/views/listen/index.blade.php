@extends('layouts.app')
@section('title') - Listen @endsection

@section('content')
    <div class="w-full max-w-7xl mx-auto px-2 sm:px-4 py-4 sm:py-6 space-y-6">
        <!-- Aktuelle Listen Section -->
        <div class="rounded-lg shadow-lg overflow-hidden" style="background: var(--color-card-bg);">
            <!-- Header -->
            <div class="px-6 py-4 border-b"
                 style="background: var(--color-main-header-bg); border-color: var(--color-main-header-bg);">
                <h2 class="text-2xl font-bold flex items-center gap-3 mb-0" style="color: #ffffff;">
                    <i class="fas fa-list"></i>
                    Aktuelle Listen
                </h2>
            </div>

            <!-- Body -->
            <div class="px-6 py-6">
                @if(count($listen) < 1)
                    <div class="rounded-lg p-6 text-center" style="background: var(--color-widget-body-bg);">
                        <p class="text-lg" style="color: var(--color-text-secondary);">Es wurden keine aktuellen Listen gefunden</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @can('create terminliste')
                            <div class="border-2 border-dashed rounded-lg p-6 flex flex-col items-center justify-center transition-all duration-200"
                                 style="border-color: var(--color-widget-success-accent);"
                                 onmouseover="this.style.borderColor=getComputedStyle(document.documentElement).getPropertyValue('--color-widget-success-from');this.style.background=getComputedStyle(document.documentElement).getPropertyValue('--color-widget-body-bg')"
                                 onmouseout="this.style.borderColor=getComputedStyle(document.documentElement).getPropertyValue('--color-widget-success-accent');this.style.background=''">
                                <i class="fas fa-plus text-4xl mb-3" style="color: var(--color-widget-success-from);"></i>
                                <h3 class="text-lg font-semibold mb-4" style="color: var(--color-text-primary);">Neue Liste</h3>
                                <a href="{{ url('listen/create') }}"
                                   class="inline-flex items-center gap-2 px-4 py-2 text-white font-medium rounded-lg transition-colors duration-200"
                                   style="background: var(--color-widget-success-from);"
                                   onmouseover="this.style.background=getComputedStyle(document.documentElement).getPropertyValue('--color-widget-success-to')"
                                   onmouseout="this.style.background=getComputedStyle(document.documentElement).getPropertyValue('--color-widget-success-from')">
                                    <i class="fas fa-plus"></i>
                                    Erstellen
                                </a>
                            </div>
                        @endcan

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
            <div class="rounded-lg shadow-lg overflow-hidden" style="background: var(--color-card-bg);">
                <!-- Header -->
                <div class="px-6 py-4 border-b"
                     style="background: linear-gradient(to right, var(--color-widget-warning-from), var(--color-widget-warning-to)); border-color: var(--color-widget-warning-border);">
                    <h2 class="text-2xl font-bold flex items-center gap-3 mb-0" style="color: var(--color-widget-header-text);">
                        <i class="fas fa-history"></i>
                        Abgelaufene Listen
                    </h2>
                </div>

                <!-- Body -->
                <div class="px-6 py-6">
                    <form method="POST" action="{{ url('listen/search') }}" class="mb-6">
                        @csrf
                        <div class="flex gap-3">
                            <input type="text"
                                   name="query"
                                   class="flex-1 px-4 py-2 rounded-lg transition-all duration-200 outline-none"
                                   style="border: 2px solid var(--color-input-border); background: var(--color-input-bg); color: var(--color-text-primary);"
                                   placeholder="Suche nach Listenname..."
                                   onfocus="this.style.borderColor=getComputedStyle(document.documentElement).getPropertyValue('--color-widget-warning-from')"
                                   onblur="this.style.borderColor=getComputedStyle(document.documentElement).getPropertyValue('--color-input-border')">
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-6 py-2 text-white font-medium rounded-lg transition-colors duration-200"
                                    style="background: var(--color-text-secondary);"
                                    onmouseover="this.style.background=getComputedStyle(document.documentElement).getPropertyValue('--color-text-primary')"
                                    onmouseout="this.style.background=getComputedStyle(document.documentElement).getPropertyValue('--color-text-secondary')">
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
