<div class="row">
    <div class="col">
        <div id="carousel_post_{{$nachricht->id}}" class="carousel slide mx-auto" data-ride="carousel" style="max-width:480px; max-height:240px;">

            <div class="carousel-inner">
                @foreach($nachricht->getMedia('images')->sortBy('name') as $media)
                    <div class="carousel-item @if($loop->first) active @endif">
                        <img class="d-block mx-auto" src="{{url('/image/'.$media->id)}}" style="max-height: 240px" >
                    </div>
                @endforeach

            </div>

            @if(count($nachricht->getMedia('images'))>1)
                <a class="carousel-control-prev" href="#carousel_post_{{$nachricht->id}}" role="button" data-slide="prev">
                    <span class="carousel-control-prev-icon bg-primary " aria-hidden="true"></span>
                    <span class="sr-only">Previous</span>
                </a>
                <a class="carousel-control-next" href="#carousel_post_{{$nachricht->id}}" role="button" data-slide="next">
                    <span class="carousel-control-next-icon bg-primary" aria-hidden="true"></span>
                    <span class="sr-only">Next</span>
                </a>
            @endif
        </div>
    </div>
</div>


