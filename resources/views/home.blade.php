@extends('layouts.app')
@section('title') - Aktuelles @endsection

@section('content')


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

                @foreach(auth()->user()->groups as $group)
                    $('#{{\Illuminate\Support\Str::camel($group->name)}}').on('click', function (event) {
                        let target = event.target
                        if(target.dataset.show == 'true'){
                            $('.nachricht').not('.{{\Illuminate\Support\Str::camel($group->name)}}').hide()

                            target.dataset.show = 'false'
                            target.classList.add("btn-success")
                            target.classList.remove("btn-outline-primary")
                        } else {
                            $('.nachricht').not('.{{\Illuminate\Support\Str::camel($group->name)}}').show()
                            target.dataset.show = 'true'

                            target.classList.remove("btn-success")
                            target.classList.add("btn-outline-primary")

                        }

                    });
                @endforeach
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

            $('.btnShowRueckmeldungen').on('click', function () {
                var btn = this;

                if ($(btn).hasClass('aktiv')){
                    $(btn).html( '<i class="fa fa-eye"></i> Rückmeldungen anzeigen') ;
                    $(btn).removeClass('aktiv');
                } else {
                    $(btn).text("ausblenden");
                    $(btn).addClass('aktiv');
                }


            });
        </script>

    <script>
        $('.commentLinks').on('click', function () {
            var btn = this;
                $(btn).addClass('d-none');
                $('.comment').removeClass('d-none');



        });
    </script>


@endpush

