<div class="row">
    <div class="col-12">
                @foreach($nachricht->getMedia('images') as $media)
                        <img class="d-block mx-auto" src="{{url('/image/'.$media->id)}}" style="max-height: 240px" >
                @endforeach
    </div>
</div>


