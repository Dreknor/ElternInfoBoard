@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12 col-md-8">
                <div class="card ">
                    <div class="card-header">
                        <h5 class="card-title">
                            Beiträge
                        </h5>
                    </div>
                    <div class="card-body">
                        <a href="{{url('elternrat/discussion/create')}}" class="btn btn-block btn-primary">
                            neuen Beitrag erstellen
                        </a>
                    </div>
                </div>
                @foreach($themen as $beitrag)
                    <div class="card @if($beitrag->sticky) border border-primary @endif">
                        <div class="card-header">
                            <div class="card-title">
                                <div class="row">
                                    <div class="col-12">
                                        <b>
                                            {{$beitrag->header}}
                                        </b>
                                        @if($beitrag->owner == auth()->user()->id)
                                            <a href="{{url('/elternrat/discussion/edit/'.$beitrag->id)}}" class="btn btn-sm btn-warning pull-right" id="editTextBtn"   data-toggle="tooltip" data-placement="top" title="Nachricht bearbeiten">
                                                <i class="far fa-edit"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                                <div class="row small">
                                    <div class="col">
                                        geändert: {{optional($beitrag->updated_at)->format('d.m.Y H:i')}}
                                    </div>
                                    <div class="col">
                                        Autor: {{$beitrag->author->name}}
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                {!! $beitrag->text !!}
                            </div>
                            <div class="card-body collapse border-top" id="collapse{{$beitrag->id}}">
                                <ul class="list-group">
                                    @foreach($beitrag->comments->sortByDesc('created_at') as $comment)
                                        <li class="list-group-item" id="comment{{$comment->id}}">
                                            <div class="row">
                                                <div class="col-sm-9 col-md-10 col-lg-11">
                                                    <div class="small">
                                                        {{$comment->creator->name}}, {{$comment->created_at->format('d.m.Y H:i')}}
                                                    </div>
                                                    {{$comment->body}}
                                                </div>
                                                <div class="col-sm-3 col-md-2 col-lg-1">
                                                    @if($comment->creator->id == auth()->user()->id)
                                                        <button class="btn btn-sm btn-outline-danger deleteComment" data-commentid = {{$comment->id}}>
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>

                            </div>
                            <div class="card-footer">
                                <div class="container-fluid">
                                    <div class="row">
                                        <div class="col-sm-12 col-md-12 col-lg-4">
                                            <button class="btn btn-outline-default" type="button" data-toggle="collapse" data-target="#collapse{{$beitrag->id}}" aria-expanded="false" aria-controls="collapse{{$beitrag->id}}">
                                                <i class="fas fa-comment"></i>
                                                {{$beitrag->commentCount()}} Kommentare
                                            </button>
                                        </div>
                                        <div class="col-sm-12 col-md-12 col-lg-8 mt-1">
                                            <form action="{{url("beitrag/$beitrag->id/comment/create")}}" method="post" class="form-inline">
                                                @csrf
                                                <div class="container-fluid">
                                                    <div class="row mt-1">
                                                        <div class="col-sm-12 col-md-8 mt-1">
                                                            <input type="text" class="form-control w-100" placeholder="neuer Kommentar" name="body">
                                                        </div>
                                                        <div class="col-sm-12 col-md-4 mt-1">
                                                            <button type="submit" class="btn btn-success w-100">kommentieren</button>
                                                        </div>
                                                    </div>
                                                </div>

                                            </form>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="card">
                    <div class="card-footer">
                        {{$themen->links()}}
                    </div>
                </div>
                </div>
            <div class="col-sm-12 col-md-4">
                <div class="row">
                    <div class="col-12 m-1">
                        <div class="card ">
                            <div class="card-header">
                                <h5 class="card-title">
                                    Dateien
                                </h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group">
                                    @foreach($files as $medium)
                                        <li class="list-group-item">
                                            <div class="row">
                                                <div class="col-sm-9 col-md-9">
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <a href="{{url('/image/'.$medium->id)}}" target="_blank" class="mx-auto ">
                                                                <i class="fas fa-file-download"></i>
                                                                {{$medium->name}}
                                                            </a>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <small>{{$medium->updated_at->format('d.m.Y H:i')}}</small>
                                                        </div>

                                                    </div>
                                                </div>
                                                <div class="col-sm-3 col-md-3">
                                                    <button class="pull-right btn btn-sm btn-danger fileDelete" data-id="{{$medium->id}}">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>

                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="card-footer">
                                <a href="{{url('elternrat/add/file')}}" class="btn btn-xs btn-block btn-success ">
                                    <i class="fa fa-plus-circle"></i>
                                    Datei hinzufügen
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 m-1">
                        <div class="card ">
                            <div class="card-header">
                                <h5 class="card-title">
                                    Zugriff auf Bereich
                                </h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group">
                                    @foreach($users as $user)
                                        <li class="list-group-item">
                                            {{$user->name}}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('js')
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
                            url: '{{url("elternrat/file/")}}'+'/'+fileId,
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

            $('.deleteComment').on('click', function () {
                var commentId = $(this).data('commentid');
                var button = $(this);
                let id = "#comment" + commentId;
                swal.fire({
                    title: "Kommentar wirklich löschen?",
                    type: "warning",
                    showCancelButton: true,
                    cancelButtonText: "Kommentar behalten",
                    confirmButtonText: "Kommentar entfernen!",
                    confirmButtonColor: "danger"
                }).then((confirmed) => {
                    if (confirmed.value) {
                        $.ajax({
                            url: '{{url("elternrat/comment/")}}'+'/'+commentId,
                            type: 'DELETE',
                            data: {
                                "_token": "{{csrf_token()}}",
                            },
                            success: function(result) {
                                $(id).fadeOut();
                            }
                        });
                    }
                });
            });

        </script>
@endpush
