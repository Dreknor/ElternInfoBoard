<div id="carousel_post_{{$nachricht->id}}" class="carousel slide mx-auto" data-ride="carousel" style="width:480px; height:240px;">

    <div class="carousel-inner">
        @foreach($nachricht->getMedia('images') as $media)
        <div class="carousel-item @if($loop->first) active @endif">
            <img class="d-block mx-auto" src="{{url('/image/'.$media->id)}}" style="max-height: 240px">
        </div>
        @endforeach

    </div>
    <a class="carousel-control-prev" href="#carousel_post_{{$nachricht->id}}" role="button" data-slide="prev">
        <span class="carousel-control-prev-icon bg-primary " aria-hidden="true"></span>
        <span class="sr-only">Previous</span>
    </a>
    <a class="carousel-control-next" href="#carousel_post_{{$nachricht->id}}" role="button" data-slide="next">
        <span class="carousel-control-next-icon bg-primary" aria-hidden="true"></span>
        <span class="sr-only">Next</span>
    </a>
</div>

@can('edit posts')
    <ul class="list-group list-group-flush">
        @foreach($nachricht->getMedia('images') as $media)
            <li class="list-group-item  list-group-item-action ">
                <a href="{{url('/image/'.$media->id)}}" target="_blank" class="mx-auto ">
                    <i class="fas fa-file-download"></i>
                    {{$media->name}}
                </a>
                @can('edit posts')
                    <button class="pull-right btn btn-sm btn-danger fileDelete" data-id="{{$media->id}}">
                        <i class="fas fa-times"></i>
                    </button>

                @endcan
            </li>



        @endforeach
    </ul>
@endcan