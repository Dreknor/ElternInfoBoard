@extends('layouts.app')
@section('title') - Archiv @endsection

@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Compact Header Card -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
        <!-- Compact Header with Inline Controls -->
        <div class="px-4 sm:px-6 py-3"
             style="background: linear-gradient(to right, var(--color-widget-primary-from), var(--color-widget-primary-to))">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-2">
                    <i class="fas fa-archive text-lg" style="color: var(--color-widget-header-text)"></i>
                    <h5 class="text-lg sm:text-xl font-bold mb-0" style="color: var(--color-widget-header-text)">Archiv</h5>
                </div>

                <!-- Compact Month/Year Selector with Custom Dropdown -->
                <div class="flex items-center gap-2" x-data="{ open: false }" x-init="$watch('open', value => { if(value) { $nextTick(() => { $refs.dropdown.scrollTop = 0; }); } })">
                    <label class="text-sm font-medium whitespace-nowrap hidden sm:block" style="color: var(--color-widget-header-text)">
                        <i class="fas fa-calendar-alt mr-1"></i>Zeitraum:
                    </label>
                    <div class="relative w-full sm:w-64" @click.away="open = false">
                        <!-- Trigger Button -->
                        <button @click="open = !open" type="button"
                                class="w-full pl-3 pr-10 py-2 text-sm font-medium bg-white border-2 border-white/20 rounded-lg shadow-sm focus:outline-none cursor-pointer hover:bg-gray-50 transition-colors text-left">
                            <span class="text-gray-700">Monat wählen...</span>
                        </button>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-700">
                            <i class="fas fa-chevron-down text-xs transition-transform" :class="open && 'rotate-180'"></i>
                        </div>

                        <!-- Dropdown Menu (Fixed Position) -->
                        <div x-show="open"
                             x-ref="dropdown"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="fixed z-[100] bg-white rounded-lg shadow-2xl w-80 overflow-hidden"
                             style="display: none; border: 2px solid var(--color-widget-primary-from); max-height: calc(100vh - 100px); top: 80px; right: 20px;"
                             @click.away="open = false">

                            <!-- Header -->
                            <div class="px-4 py-3 flex items-center justify-between"
                                 style="background: linear-gradient(to right, var(--color-widget-primary-from), var(--color-widget-primary-to))">
                                <span class="font-semibold text-sm flex items-center gap-2" style="color: var(--color-widget-header-text)">
                                    <i class="fas fa-calendar-alt"></i>
                                    Archiv-Monat wählen
                                </span>
                                <button @click="open = false" class="hover:opacity-70 transition-opacity" style="color: var(--color-widget-header-text)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            <!-- Scrollable Content -->
                            <div class="overflow-y-auto" style="max-height: calc(100vh - 160px);">
                                @php
                                    $firstPostDate = (!is_null($first_post) && !is_null($first_post->archiv_ab)) ? $first_post->archiv_ab : \Carbon\Carbon::now();
                                    $monthsByYear = [];

                                    for($x = \Carbon\Carbon::now(); $x->greaterThanOrEqualTo($firstPostDate); $x->subMonth()) {
                                        $year = $x->format('Y');
                                        if (!isset($monthsByYear[$year])) {
                                            $monthsByYear[$year] = [];
                                        }
                                        $monthsByYear[$year][] = [
                                            'value' => url('archiv/'.$x->format('Y-m')),
                                            'label' => $x->locale('de')->monthName,
                                            'formatted' => $x->format('Y-m')
                                        ];
                                    }
                                @endphp

                                @foreach($monthsByYear as $year => $months)
                                    <!-- Year Header -->
                                    <div class="sticky top-0 px-4 py-2.5 border-b z-10"
                                         style="background-color: var(--color-widget-body-bg); border-color: var(--color-widget-primary-from)">
                                        <span class="text-sm font-bold" style="color: var(--color-widget-primary-border)">
                                            <i class="fas fa-calendar-check mr-2" style="color: var(--color-widget-primary-from)"></i>{{$year}}
                                        </span>
                                    </div>

                                    <!-- Months -->
                                    @foreach($months as $month)
                                        <a href="{{$month['value']}}"
                                           class="block px-5 py-3 text-sm text-gray-700 transition-colors border-b border-gray-100 last:border-b-0"
                                           onmouseover="this.style.backgroundColor='var(--color-widget-body-bg)'; this.style.color='var(--color-widget-primary-border)'"
                                           onmouseout="this.style.backgroundColor=''; this.style.color=''">
                                            <i class="fas fa-calendar text-xs mr-2" style="color: var(--color-widget-primary-from)"></i>
                                            {{$month['label']}}
                                        </a>
                                    @endforeach
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- No Messages Info -->
        @if($nachrichten == null or count($nachrichten)<1)
            <div class="border-t px-4 sm:px-6 py-4"
                 style="background-color: var(--color-widget-body-bg); border-color: var(--color-widget-primary-from)">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 mt-0.5">
                        <i class="fas fa-info-circle text-lg" style="color: var(--color-widget-primary-from)"></i>
                    </div>
                    <div>
                        <p class="font-medium text-sm mb-1" style="color: var(--color-widget-primary-border)">
                            Keine Nachrichten verfügbar
                        </p>
                        <p class="text-xs mb-0 text-gray-500">
                            Es sind keine archivierten Nachrichten für den ausgewählten Zeitraum vorhanden.
                        </p>
                    </div>
                </div>
            </div>
        @endif

    </div>

    <!-- Messages Container -->
    @if($nachrichten != null and count($nachrichten) > 0)
        <div class="space-y-6">
            @foreach($nachrichten AS $nachricht)
                @if($nachricht->released == 1 or auth()->user()->can('edit posts'))
                    <div class="@foreach($nachricht->groups as $group) {{$group->name}} @endforeach">
                        @include('archiv.nachricht')
                    </div>
                @endif
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-8 flex justify-center">
            <div class="bg-white rounded-lg shadow-md px-6 py-4">
                {{$nachrichten->links()}}
            </div>
        </div>
    @endif
</div>

@endsection
