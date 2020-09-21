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
                                <ul class="comment-section">

                                    @foreach(optional($beitrag->comments)->sortByDesc('created_at') as $comment)
                                        <li class="comment @if ($loop->index % 2 == 0) user-comment @else author-comment @endif">
                                            <div class="info">
                                                <a href="">{{$comment->creator->name}}</a>
                                                <span>{{$comment->created_at->diffForHumans()}}</span>
                                            </div>
                                            <p>{{$comment->body}}</p>
                                        </li>
                                    @endforeach
                                <!-- More comments -->
                                    <li class="write-new">
                                        <form action="{{url("beitrag/$beitrag->id/comment/create")}}" method="post">
                                            @csrf
                                            <textarea placeholder="Kommentar hier schreiben" name="body"></textarea>
                                            <button type="submit" class="btn btn-success">kommentieren</button>
                                        </form>
                                    </li>
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
                            @foreach($directories as $directory)
                                <div class="card-body">
                                    <h6 id="{{$directory}}" data-toggle="collapse" href="#{{$directory}}_list" role="tab" >
                                        {{$directory}}
                                        <i class="more-less fas fa-xs fa-plus"></i>
                                    </h6>

                                    <ul class="list-group collapse" id="{{$directory}}_list">
                                        @if($group->getMedia($directory)->count() > 0)
                                            @foreach($group->getMedia($directory) as $medium)
                                        <li class="list-group-item" id="file_{{$medium->id}}">
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
                                                @if(auth()->user()->can('delete elternrat file'))
                                                    <div class="col-sm-3 col-md-3">
                                                        <button class="pull-right btn btn-sm btn-danger fileDelete" data-id="{{$medium->id}}">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>

                                        </li>
                                    @endforeach
                                        @else
                                            <li class="list-group-item">
                                                Keine Dateien gefunden
                                            </li>
                                        @endif
                                </ul>
                                </div>
                            @endforeach
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
                                $('#file_'+fileId).fadeOut();
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

@section('css')

    <link href="{{asset('css/comments.css')}}" media="all" rel="stylesheet" type="text/css" />

    <style>
        .more-less {
            float: right;
            color: darkblue;
        }
    </style>
@endsection
