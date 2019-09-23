@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h4>@if(!isset($archiv))
                            Alle aktuellen Mitteilungen
                        @else
                            Ältere Nachrichten
                        @endif</h4>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs  nav-pills nav-fill" id="myTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link @if(!isset($archiv)) active @endif" href="{{url('/home')}}">Aktuelles</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link @if(isset($archiv)) active @endif"  href="{{url('/home/archiv')}}">Archiv</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
    @if($nachrichten != null and count($nachrichten)>0)
        @foreach($nachrichten AS $nachricht)
            @if($nachricht->released == 1 or auth()->user()->can('edit posts'))
                @include('nachrichten.nachricht')
            @endif
        @endforeach

    <div class="archiv">
        {{$nachrichten->links()}}
    </div>
    @else
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

@push('js')
    <script src="{{asset('js/plugins/tinymce/jquery.tinymce.min.js')}}"></script>
    <script src="{{asset('js/plugins/tinymce/tinymce.min.js')}}"></script>
    <script src="{{asset('js/plugins/tinymce/langs/de.js')}}"></script>
    <script>tinymce.init({
            selector: 'textarea',
            lang:'de',

            menubar: false,

        });</script>

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
                            }
                        });
                    }
                });
            });

        </script>
    @endcan
@endpush
