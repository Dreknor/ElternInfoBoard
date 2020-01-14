@extends('layouts.app')

@section('content')
<div class="container-fluid">

    <div class="row justify-content-center">
        <div class="col-md-10 col-sm-6">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-10">
                            <h4>@if(!isset($archiv))
                                    Alle aktuellen Mitteilungen
                                @else
                                    Ältere Nachrichten
                                @endif
                            </h4>
                        </div>

                    </div>

                </div>
                <div class="card-body">

                </div>
            </div>
        </div>
    </div>
    @if($nachrichten != null and count($nachrichten)>0)
        <div class="card">
            <div class="card-header ">
                <div class="col-12 ">
                    <div class="card-title">
                        <h5>
                            Themen
                            @if(!isset($archiv))
                                <a href="{{url('pdf')}}" class="btn btn-sm btn-outline-primary pull-right">
                                    <i class="far fa-file-pdf"></i>
                                </a>
                            @endif
                        </h5>

                    </div>
                </div>

            </div>
            <div class="card-body">
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
        </div>
    <div id="">
        @include('termine.nachricht')
        @include('reinigung.nachricht')
        @if($archiv)
            <div class="card">
                <div class="card-body bg-warning">
                    <b>{{$nachrichten->first()->updated_at->locale('de')->getTranslatedMonthName('Do MMMM')}} {{$nachrichten->first()->updated_at->format('Y')}}</b>
                </div>
            </div>
        @endif
        @foreach($nachrichten AS $nachricht)
            @if($nachricht->released == 1 or auth()->user()->can('edit posts'))
                @if($nachricht->updated_at->month < $datum->month )
                    @php($datum = $nachricht->updated_at->copy())
                    <div class="card">
                        <div class="card-body bg-warning">
                            <b>{{$datum->locale('de')->getTranslatedMonthName('Do MMMM')}} {{$datum->format('Y')}}</b>
                        </div>
                    </div>
                @endif
                <div class="@foreach($nachricht->groups as $group) {{$group->name}} @endforeach">
                    @include('nachrichten.nachricht')
                </div>
            @endif
        @endforeach
    </div>

    <div class="archiv">
        {{$nachrichten->links()}}
    </div>
    @else
        @include('termine.nachricht')

        @include('reinigung.nachricht')
        <div class="card">
            <div class="card-body bg-info">
                <p>
                    Es sind keine Nachrichten vorhanden
                </p>
            </div>
        </div>
            @endif
</div>
@endsection


@section('css')

@endsection
@push('js')
    @if(is_null($archiv))
        <script src="{{asset('js/plugins/tinymce/jquery.tinymce.min.js')}}"></script>
        <script src="{{asset('js/plugins/tinymce/tinymce.min.js')}}"></script>
        <script src="{{asset('js/plugins/tinymce/langs/de.js')}}"></script>
        <script>tinymce.init({
                selector: 'textarea',
                lang:'de',
                plugins: "autoresize",
                menubar: false,
                toolbar: [
                    "bold italic underline strikethrough |  bullist |  restoredraft |  fontsizeselect | forecolor hilitecolor"
                ],
                setup:function(ed) {
                    ed.on('change', function(e) {
                        var id = "#btnSave_"+ ed.id;
                        $(id).show();
                    });
                }
            });
        </script>
    @endif

    <script>
        $(document).ready(function () {
            if ($( window ).width() < 992) {
                $("table").addClass('table table-responsive');
            }

            $( window ).resize(function() {
                if ($( window ).width() < 992) {
                    $("table").addClass('table table-responsive');
                } else {
                    $("table").removeClass('table-responsive');
                }
            });
        });
    </script>

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
    @endcan


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

@endpush

