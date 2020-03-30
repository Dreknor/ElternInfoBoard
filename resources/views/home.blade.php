@extends('layouts.app')

@section('content')
<div class="container-fluid">

    <div class="card">
        <div class="card-header border-bottom">
            <ul class="nav nav-tabs border-info w-100">
                <li class="nav-item">
                    <a class="nav-link @if(request()->segment(1)=="" or (request()->segment(1)=="home" and request()->segment(2)!="archiv")) active @endif" href="{{url('/')}}">
                        <h4>
                            <i class="far fa-newspaper"></i>
                            Aktuelle Themen
                        </h4>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if(request()->segment(2)=="archiv") active  @endif" href="{{url('/home/archiv')}}">
                        <h4>
                            <i class="fas fa-archive"></i>
                            Archiv
                        </h4>
                    </a>
                </li>
            </ul>
        </div>


    @if($nachrichten != null and count($nachrichten)>0)
            <div class="card-body">
                <button class="btn btn-primary hidden  d-md-none" type="button" data-toggle="collapse" data-target="#Themen" aria-expanded="false" aria-controls="collapseThemen">
                    Themen zeigen
                </button>
                <div class="row collapse d-md-block" id="Themen">
                    <div class="col">
                        @foreach($nachrichten AS $nachricht)
                            @if($nachricht->released == 1 or auth()->user()->can('edit posts'))
                                <a href="#{{$nachricht->id}}" class="btn btn-sm {{$nachricht->type}} @if($nachricht->released == 1) btn-outline-primary @else btn-outline-warning @endif">
                                    @if(!is_null($nachricht->rueckmeldung) and $nachricht->rueckmeldung->ende->greaterThan(\Carbon\Carbon::now()))
                                        <i class="fas fa-reply @if(is_null($user->getRueckmeldung()->where('posts_id', $nachricht->id)->first()) and $nachricht->rueckmeldung->pflicht ==1) text-danger  @elseif(is_null($user->getRueckmeldung()->where('posts_id', $nachricht->id)->first()) and $nachricht->rueckmeldung->pflicht ==0) text-warning @else text-success @endif" data-toggle="tooltip" data-placement="top" title="Rückmeldung benötigt"></i>
                                    @endif
                                    <div class="
                                        @switch($nachricht->type)
                                            @case('pflicht')
                                           text-danger
                                            @break

                                            @case('wahl')
                                            text-warning
                                            @break
                                        @endswitch
                                    ">
                                        {{$nachricht->header}}
                                    </div>
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
            @if($archiv == null)
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-12 col-md-4">
                            <div class="btn btn-outline-primary btn-sm btn-block" type="button" id="infoButton">
                                <i class="fas fa-eye"></i> Infos ausblenden
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-4">
                            <div class="btn btn-outline-danger btn-sm btn-block" type="button" id="pflichtButton">
                                <i class="fas fa-eye"></i> Pflichtaufgaben ausblenden
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-4">
                            <div class="btn btn-outline-warning btn-sm btn-block" type="button" id="wahlButton">
                                <i class="fas fa-eye"></i> Wahlaufgaben ausblenden
                            </div>
                        </div>
                    </div>



                </div>
            @endif

    @else

            <div class="card-body bg-info">
                <p>
                    Es sind keine Nachrichten vorhanden
                </p>
            </div>

    @endif
</div>

    <div id="">
        @include('termine.nachricht')
        @include('reinigung.nachricht')
        @if($archiv != null)
            <div class="card">
                <div class="card-body bg-warning">
                    <b>{{$nachrichten->first()->updated_at->locale('de')->getTranslatedMonthName('Do MMMM')}} {{$nachrichten->first()->updated_at->format('Y')}}</b>
                </div>
            </div>
        @endif
        @foreach($nachrichten AS $nachricht)
            @if($nachricht->released == 1 or auth()->user()->can('edit posts'))
                @if($nachricht->updated_at->month < $datum->month and $archiv != null )
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

    @if($nachrichten != null and count($nachrichten)>0)
        <div class="archiv">
            {{$nachrichten->links()}}
        </div>
    @endif
</div>
@endsection


@section('css')

@endsection
@push('js')
    @if(is_null($archiv))
        <script>
            $.fn.extend({
                toggleText: function(a, b){
                    return this.text(this.text() == b ? a : b);
                }
            });

            $(document).ready(function() {
                $('#infoButton').on('click', function (event) {
                    $('.info').toggle('show');
                    $("#infoButton").toggleText('Infos ausblenden', 'Infos einblenden');
                });

                $('#wahlButton').on('click', function (event) {
                    $('.wahl').toggle('show');
                    $(this).toggleText('Wahlaufgaben ausblenden', 'Wahlaufgaben einblenden');
                });

                $('#pflichtButton').on('click', function (event) {
                    $('.pflicht').toggle('show');
                    $(this).toggleText('Pflichtaufgaben ausblenden', 'Pflichtaufgaben einblenden');
                });
            });
        </script>
        <script src="{{asset('js/plugins/tinymce/jquery.tinymce.min.js')}}"></script>
        <script src="{{asset('js/plugins/tinymce/tinymce.min.js')}}"></script>
        <script src="{{asset('js/plugins/tinymce/langs/de.js')}}"></script>
        <script>tinymce.init({
                selector: '.rueckmeldung',
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

