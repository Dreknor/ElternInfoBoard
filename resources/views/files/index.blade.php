@extends('layouts.app')
@section('title') - Downloads @endsection

@section('content')

    <div class="container-fluid">
        <div class="rounded-lg border-2 overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-200" style="background-color: var(--color-card-bg); border-color: var(--color-card-border)">
            <div class="px-3 py-2"
                 style="background: linear-gradient(to right, var(--color-widget-primary-from), var(--color-widget-primary-to))">
                <div class="flex items-center gap-2">
                    <h6 class="text-sm font-semibold mb-0" style="color: var(--color-widget-header-text)">Datei-Downloads ({{ count($medien ?? []) }})</h6>
                </div>
            </div>

            <div class="divide-y divide-theme">
                @foreach(collect($medien ?? [])->sortBy('name') as $medium)
                    <div class="group transition-colors duration-200" onmouseover="this.style.backgroundColor='var(--color-surface-subtle)'" onmouseout="this.style.backgroundColor=''">
                        <div class="flex items-center justify-between px-3 py-2.5">
                            <a href="{{ url('/image/'.$medium->id) }}"
                               target="_blank"
                               class="flex-1 flex items-center gap-2 transition-colors duration-200 min-w-0" style="color: var(--color-text-primary)"
                               onmouseover="this.style.color='var(--color-widget-primary-from)'"
                               onmouseout="this.style.color=''">
                                <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center transition-colors duration-200"
                                     style="background-color: var(--color-widget-body-bg)">
                                    @php
                                        $extension = strtolower(pathinfo($medium->name, PATHINFO_EXTENSION));
                                        $iconClass = match($extension) {
                                            'pdf' => 'fa-file-pdf text-red-500',
                                            'doc', 'docx' => 'fa-file-word text-blue-500',
                                            'xls', 'xlsx' => 'fa-file-excel text-green-500',
                                            'ppt', 'pptx' => 'fa-file-powerpoint text-orange-500',
                                            'zip', 'rar' => 'fa-file-archive text-yellow-600',
                                            'jpg', 'jpeg', 'png', 'gif' => 'fa-file-image text-purple-500',
                                            default => 'fa-file text-gray-500',
                                        };
                                    @endphp
                                    <i class="fas {{ $iconClass }} text-sm"></i>
                                </div>

                                <span class="text-sm font-medium truncate">{{ $medium->name }}</span>
                            </a>

                            <a href="{{ url('/image/'.$medium->id) }}"
                               target="_blank"
                               class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-white transition-all duration-200 opacity-0 group-hover:opacity-100"
                               style="background-color: var(--color-widget-primary-from)"
                               title="Download">
                                <i class="fas fa-download text-xs"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="px-3 py-2 border-t" style="background-color: var(--color-surface-subtle); border-color: var(--color-card-border)">
                <p class="text-xs text-center mb-0" style="color: var(--color-text-muted)">
                    <i class="fas fa-info-circle mr-1" style="color: var(--color-widget-primary-from)"></i>
                    Klicken zum Herunterladen
                </p>
            </div>
        </div>
    </div>

@endsection
