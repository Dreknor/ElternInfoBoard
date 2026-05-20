@extends('layouts.app')

@section('content')

    <div class="container-fluid">
        <div class="space-y-4">
            @foreach($gruppen as $gruppe)
                @if(!$gruppe->protected or auth()->user()->can('view protected') or auth()->user()->groups->where('name', $gruppe->name)->first() != null)
                    <div class="bg-white rounded-lg border-2 border-gray-200 overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-200">
                        <div class="px-3 py-2"
                             style="background: linear-gradient(to right, var(--color-widget-primary-from), var(--color-widget-primary-to))">
                            <div class="flex items-center gap-2">
                                <h6 class="text-sm font-semibold mb-0" style="color: var(--color-widget-header-text)">{{ $gruppe->name }} ({{ count($gruppe->getMedia()) }})</h6>
                            </div>
                        </div>

                        <div class="divide-y divide-gray-200">
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

                                        <div class="flex items-center gap-1 flex-shrink-0">
                                            <a href="{{ url('/image/'.$medium->id) }}"
                                               target="_blank"
                                               class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-white transition-all duration-200 opacity-0 group-hover:opacity-100"
                                               style="background-color: var(--color-primary-light); color: var(--color-primary)"
                                               onmouseover="this.style.backgroundColor='var(--color-primary)'; this.style.color='#ffffff'"
                                               onmouseout="this.style.backgroundColor='var(--color-primary-light)'; this.style.color='var(--color-primary)'"
                                               title="Download">
                                                <i class="fas fa-download text-xs"></i>
                                            </a>

                                            @can('upload files')
                                                <button class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-100 text-red-600 hover:bg-red-600 hover:text-white transition-all duration-200 opacity-0 group-hover:opacity-100 fileDelete"
                                                        data-id="{{ $medium->id }}"
                                                        title="Löschen">
                                                    <i class="fas fa-times text-xs"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="px-3 py-3 text-sm text-gray-500">Keine Dateien in dieser Gruppe.</div>
                            @endforelse
                        </div>

                        <div class="bg-gray-50 px-3 py-2 border-t border-gray-200">
                            <p class="text-xs text-gray-500 text-center mb-0">
                                <i class="fas fa-info-circle mr-1" style="color: var(--color-primary)"></i>
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
    @can('edit posts')
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
