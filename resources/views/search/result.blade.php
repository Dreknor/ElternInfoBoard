@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
        </div>
        <div class="row mt-1">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-sm-12 col-md-9">
                                <h5>
                                    Seiten mit dem Suchbegriff "{{$Suche}}"
                                </h5>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            @foreach($sites as $site)
                                <a href="{{ route('sites.show', $site->id) }}" class="list-group">
                                    <li class="list-group-item">

                                           {{$site->name}}

                                    </li>
                                </a>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-10">
                                <h4>
                                        Alle gefundenen Mitteilungen zur Suche "{{$Suche}}"
                                </h4>
                            </div>
                        </div>
                    </div>
                    @if($nachrichten != null and count($nachrichten)>0)
                        <div class="card-body">
                                    <h5 class="card-title">
                                        Themen
                                    </h5>

                                <button class="btn btn-primary hidden  d-md-none" type="button" data-toggle="collapse" data-target="#cThemen" aria-expanded="false" aria-controls="collapseThemen">
                                    Themen zeigen
                                </button>
                                <div class="row collapse d-md-block" id="Themen">
                                    <div class="col">
                                        @foreach($nachrichten AS $nachricht)
                                            @if($nachricht->released == 1 or auth()->user()->can('edit posts'))
                                                <a href="#{{$nachricht->id}}" class="btn btn-sm @if($nachricht->released == 1) btn-outline-primary @else btn-outline-warning @endif">
                                                    @if(!is_null($nachricht->rueckmeldung) and (is_null($user->userRueckmeldung->where('posts_id', $nachricht->id)->first() or (!is_null($user->sorgeberechtigter2) and is_null($user->sorgeberechtigter2->userRueckmeldung->where('posts_id', $nachricht->id)->first())))))
                                                        <i class="fas fa-reply text-danger" data-toggle="tooltip" data-placement="top" title="Rückmeldung benötigt"></i>
                                                    @endif
                                                    {{$nachricht->header}}

                                                </a>
                                            @endif
                                        @endforeach
                                    </div>

                                </div>

                            </div>
                        <div id="">
                            @foreach($nachrichten AS $nachricht)
                                @if($nachricht->released == 1 or auth()->user()->can('edit posts'))
                                    <div class="@foreach($nachricht->groups as $group) {{$group->name}} @endforeach">
                                        @include('nachrichten.nachricht')
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @else

                        <div class="card-body bg-info">
                            <p>
                                Es wurden keine Nachrichten gefunden
                            </p>
                        </div>

                    @endif
                </div>
            </div>
        </div>
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

        <script>
            $('.btnShow').on('click', function () {
                var btn = this;

                if ($(btn).hasClass('aktiv')){
                    $(btn).html( '<i class="fa fa-eye"></i> Text anzeigen') ;
                    $(btn).removeClass('aktiv');
                } else {
                    $(btn).text("ausblenden");
                    $(btn).addClass('aktiv');
                }


            });
        </script>
    @endcan
@endpush
