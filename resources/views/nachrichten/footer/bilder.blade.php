<div id="carousel_post_{{$nachricht->id}}" class="carousel slide mx-auto" data-ride="carousel" style="width:480px; height:240px;">

    <div class="carousel-inner">
        @foreach($nachricht->getMedia('images') as $media)
        <div class="carousel-item @if($loop->first) active @endif">
            <img class="d-block w-100" src="{{url('/image/'.$media->id)}}" style="max-height: 240px">
        </div>
        @endforeach

    </div>
    <a class="carousel-control-prev" href="#carousel_post_{{$nachricht->id}}" role="button" data-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="sr-only">Previous</span>
    </a>
    <a class="carousel-control-next" href="#carousel_post_{{$nachricht->id}}" role="button" data-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="sr-only">Next</span>
    </a>
</div>