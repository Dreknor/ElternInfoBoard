@extends('layouts.app')

@section('content')

    <div class="container-fluid">
        <div class="space-y-4">
            @foreach($gruppen as $gruppe)
                @if(!$gruppe->protected or auth()->user()->can('view protected') or auth()->user()->groups->where('name', $gruppe->name)->first() != null)
                    <div class="rounded-lg border-2 overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-200" style="background-color: var(--color-card-bg); border-color: var(--color-card-border)">
                        <div class="px-3 py-2"
                             style="background: linear-gradient(to right, var(--color-widget-primary-from), var(--color-widget-primary-to))">
                            <div class="flex items-center gap-2">
                                <h6 class="text-sm font-semibold mb-0" style="color: var(--color-widget-header-text)">{{ $gruppe->name }} ({{ count($gruppe->getMedia()) }})</h6>
                            </div>
                        </div>

                        <div class="divide-y divide-theme">
                            @forelse($gruppe->getMedia()->sortBy('name') as $medium)
                                <div class="group transition-colors duration-200"
                                     onmouseover="this.style.backgroundColor='var(--color-primary-light)'"
                                     onmouseout="this.style.backgroundColor=''">
                                    <div class="flex items-center justify-between px-3 py-2.5">
                                        <a href="{{ url('/image/'.$medium->id) }}"
                                           target="_blank"
                                           class="flex-1 flex items-center gap-2 transition-colors duration-200 min-w-0"
                                           style="color: var(--color-text-main, #374151)"
                                           onmouseover="this.style.color='var(--color-primary)'"
                                           onmouseout="this.style.color='var(--color-text-main, #374151)'">
                                            <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center transition-colors duration-200"
                                                 style="background-color: var(--color-widget-body-bg)">
                                                @php
                                                    $extension = strtolower(pathinfo($medium->name, PATHINFO_EXTENSION));
                                                    // Gemeinsamer Dokumentenpfad (Heroicons document-text)
                                                    $docPath = 'M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z';
                                                    $iconColor = match($extension) {
                                                        'pdf'              => '#ef4444',
                                                        'doc', 'docx'      => '#3b82f6',
                                                        'xls', 'xlsx'      => '#22c55e',
                                                        'ppt', 'pptx'      => '#f97316',
                                                        'zip', 'rar'       => '#ca8a04',
                                                        'jpg', 'jpeg', 'png', 'gif' => '#a855f7',
                                                        default            => '#6b7280',
                                                    };
                                                @endphp
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="{{ $iconColor }}" class="w-4 h-4">
                                                    <path fill-rule="evenodd" d="{{ $docPath }}" clip-rule="evenodd"/>
                                                </svg>
                                            </div>

                                            <span class="text-sm font-medium truncate">{{ $medium->name }}</span>
                                        </a>

                                        <div class="flex items-center gap-1 flex-shrink-0">
                                            <a href="{{ url('/image/'.$medium->id) }}"
                                               target="_blank"
                                               class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-white transition-all duration-200 opacity-0 group-hover:opacity-100"
                                               style="background-color: var(--color-primary-light); color: var(--color-primary)"
                                               onmouseover="this.style.backgroundColor='var(--color-primary)'; this.style.color='#ffffff'"
                                               onmouseout="this.style.backgroundColor='var(--color-primary-light)'; this.style.color='var(--color-primary)'"
                                               title="Download">
                                                {{-- Download-Icon --}}
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5">
                                                    <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z"/>
                                                    <path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z"/>
                                                </svg>
                                            </a>

                                            @can('upload files')
                                                <button class="inline-flex items-center justify-center gap-1 px-2 h-8 rounded-lg bg-red-100 text-red-600 hover:bg-red-600 hover:text-white transition-all duration-200 text-xs font-medium fileDelete"
                                                        data-id="{{ $medium->id }}"
                                                        title="Löschen">
                                                    {{-- X-Icon --}}
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5">
                                                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                                                    </svg>
                                                    Löschen
                                                </button>
                                            @endcan
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="px-3 py-3 text-sm" style="color: var(--color-text-muted)">Keine Dateien in dieser Gruppe.</div>
                            @endforelse
                        </div>

                        <div class="px-3 py-2 border-t" style="background-color: var(--color-surface-subtle); border-color: var(--color-card-border)">
                            <p class="text-xs text-center mb-0" style="color: var(--color-text-muted)">
                                {{-- Info-Icon --}}
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5 inline mr-1" style="color: var(--color-primary)">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a.75.75 0 0 0 0 1.5h.253a.25.25 0 0 1 .244.304l-.459 2.066A1.75 1.75 0 0 0 10.747 15H11a.75.75 0 0 0 0-1.5h-.253a.25.25 0 0 1-.244-.304l.459-2.066A1.75 1.75 0 0 0 9.253 9H9Z" clip-rule="evenodd"/>
                                </svg>
                                Klicken zum Herunterladen
                            </p>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>

@endsection

@push('js')
    @can('upload files')
        <script src="{{asset('js/plugins/sweetalert2.all.min.js')}}"></script>

        <script>
            $('.fileDelete').on('click', function () {
                var fileId = $(this).data('id');
                var button = $(this);

                console.log(fileId);

                swal.fire({
                    title: "Datei wirklich entfernen?",
                    type: "warning",
                    showCancelButton: true,
                    cancelButtonText: "Datei behalten",
                    confirmButtonText: "Datei entfernen!",
                    confirmButtonColor: "danger"
                }).then((confirmed) => {
                    if (confirmed.value) {
                        $.ajax({
                            url: '{{url("/file/")}}'+'/'+fileId,
                            type: 'DELETE',
                            data: {
                                "_token": "{{csrf_token()}}",
                            },
                            success: function(result) {
                                console.log(result);
                                $(button).parent('li').fadeOut();
                                // Fallback: remove closest group row
                                $(button).closest('.group').fadeOut();
                            }
                        });
                    }
                });
            });

        </script>
    @endcan
@endpush
