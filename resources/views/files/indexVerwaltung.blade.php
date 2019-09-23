@extends('layouts.app')

@section('content')

    <div class="container-fluid">
        <div class="card">
            <div class="card-header border-bottom">
                Datei-Downloads
            </div>

            <div class="card-body">
                <ul class="list-group">

                    @foreach($gruppen as $gruppe)
                        <li class="list-group-item border-top">
                            {{$gruppe->name}}
                        </li>
                        @foreach($gruppe->getMedia() as $medium)
                            <li class="list-group-item">
                                <a href="{{url('/image/'.$medium->id)}}" target="_blank" class="mx-auto ">
                                    <i class="fas fa-file-download"></i>
                                    {{$medium->name}}
                                </a>
                                @can('upload files')
                                    <button class="pull-right btn btn-sm btn-danger fileDelete" data-id="{{$medium->id}}">
                                        <i class="fas fa-times"></i>
                                    </button>

                                @endcan
                            </li>
                        @endforeach
                    @endforeach
                </ul>
            </div>
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
                            }
                        });
                    }
                });
            });

        </script>
    @endcan
@endpush