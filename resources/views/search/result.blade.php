@extends('layouts.app')

@section('content')
    <div class="container-fluid px-4 py-3">
        <!-- Suchergebnis Header -->
        <div class="mb-4">
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ url()->previous() }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors duration-200">
                    <i class="fas fa-arrow-left"></i>
                    Zurück
                </a>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                <i class="fas fa-search text-blue-600"></i>
                Suchergebnisse für <span class="text-blue-600">"{{$Suche}}"</span>
            </h2>
        </div>

        <!-- Seiten-Ergebnisse -->
        @if($sites && count($sites) > 0)
            <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3 border-b border-blue-800">
                    <h5 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                        <i class="fas fa-file-alt"></i>
                        Gefundene Seiten
                        <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold text-blue-600 bg-white rounded-full ml-2">
                            {{count($sites)}}
                        </span>
                    </h5>
                </div>

                <!-- Body -->
                <div class="p-4">
                    <div class="space-y-2">
                        @foreach($sites as $site)
                            <a href="{{ route('sites.show', $site->id) }}"
                               class="block border border-gray-200 rounded-lg p-4 hover:border-blue-500 hover:shadow-md hover:bg-blue-50 transition-all duration-200 group">
                                <div class="flex items-center gap-3">
                                    <div class="flex-shrink-0 w-10 h-10 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition-colors duration-200">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h6 class="font-semibold text-gray-800 group-hover:text-blue-600 transition-colors duration-200 mb-0">
                                            {{$site->name}}
                                        </h6>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-chevron-right text-gray-400 group-hover:text-blue-600 transition-colors duration-200"></i>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Mitteilungs-Ergebnisse -->
        @if($nachrichten != null and count($nachrichten) > 0)
            <!-- Header Card -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-4">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3">
                    <h5 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                        <i class="fas fa-envelope"></i>
                        Gefundene Mitteilungen
                        <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold text-blue-600 bg-white rounded-full ml-2">
                            {{count($nachrichten)}}
                        </span>
                    </h5>
                </div>

                <!-- Themen-Navigation -->
                <div class="bg-gray-50 border-t border-gray-200 px-4 py-3">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fas fa-bookmark text-blue-600"></i>
                        <h6 class="font-semibold text-gray-700 mb-0">Themen:</h6>
                    </div>

                    <!-- Mobile: Collapsible -->
                    <button class="md:hidden w-full inline-flex items-center justify-between px-4 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 font-medium rounded-lg transition-colors duration-200 mb-3"
                            type="button"
                            data-toggle="collapse"
                            data-target="#Themen"
                            aria-expanded="false"
                            aria-controls="Themen">
                        <span>Themen anzeigen</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>

                    <!-- Themen Links -->
                    <div class="d-md-block" id="Themen">
                        <div class="flex flex-wrap gap-2">
                            @foreach($nachrichten AS $nachricht)
                                @if($nachricht->released == 1 or auth()->user()->can('edit posts'))
                                    <a href="#{{$nachricht->id}}"
                                       class="inline-flex items-center gap-2 px-3 py-2
                                              @if($nachricht->released == 1)
                                                  bg-blue-100 hover:bg-blue-600 text-blue-700 hover:text-white border border-blue-300
                                              @else
                                                  bg-yellow-100 hover:bg-yellow-600 text-yellow-700 hover:text-white border border-yellow-300
                                              @endif
                                              rounded-lg font-medium transition-all duration-200 text-sm">
                                        @if(!is_null($nachricht->rueckmeldung) and (is_null($user->userRueckmeldung->where('posts_id', $nachricht->id)->first()) or (!is_null($user->sorgeberechtigter2) and is_null($user->sorgeberechtigter2->userRueckmeldung->where('posts_id', $nachricht->id)->first()))))
                                            <i class="fas fa-reply text-red-500" data-toggle="tooltip" data-placement="top" title="Rückmeldung benötigt"></i>
                                        @endif
                                        <span>{{$nachricht->header}}</span>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mitteilungen Liste (direkt ohne Container) -->
            <div class="space-y-0">
                @foreach($nachrichten AS $nachricht)
                    @if($nachricht->released == 1 or auth()->user()->can('edit posts'))
                        <div class="@foreach($nachricht->groups as $group) {{$group->name}} @endforeach">
                            @include('nachrichten.nachricht')
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            <!-- Keine Ergebnisse -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3">
                    <h5 class="text-lg font-bold text-white flex items-center gap-2 mb-0">
                        <i class="fas fa-envelope"></i>
                        Gefundene Mitteilungen
                    </h5>
                </div>

                <!-- Keine Ergebnisse Content -->
                <div class="p-6">
                    <div class="flex items-start gap-3 p-4 bg-blue-50 border-l-4 border-blue-500 rounded">
                        <i class="fas fa-info-circle text-blue-600 text-xl mt-1"></i>
                        <div>
                            <p class="text-blue-800 font-semibold mb-1">Keine Mitteilungen gefunden</p>
                            <p class="text-blue-700 text-sm mb-0">Es wurden keine Mitteilungen mit dem Suchbegriff "{{$Suche}}" gefunden.</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

@endsection


@section('css')

@endsection
@push('js')

    <script src="{{asset('js/plugins/tinymce/jquery.tinymce.min.js')}}"></script>
    <script src="{{asset('js/plugins/tinymce/tinymce.min.js')}}"></script>
    <script src="{{asset('js/plugins/tinymce/langs/de.js')}}"></script>
    <script>tinymce.init({
            selector: 'textarea',
            lang:'de',
            plugins: "autoresize",
            menubar: false,

        });</script>

    @can('edit posts')
        <script src="{{asset('js/plugins/sweetalert2.all.min.js')}}"></script>

        <script>
            $('.fileDelete').on('click', function () {
                var fileId = $(this).data('id');
                var button = $(this);

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
                                $(button).parent('li').fadeOut();
                            }
                        });
                    }
                });
            });

        </script>

        <script>
            $(document).ready(function () {
                if ($( window ).width() < 992) {
                    $("table").addClass('table table-responsive');
                }

                $( window ).resize(function() {
                    if ($( window ).width() < 992) {
                        $("table").addClass('table table-responsive');
                    } else {
                        $("table").removeClass('table table-responsive');
                    }
                });
            });
        </script>
    @endcan
@endpush
