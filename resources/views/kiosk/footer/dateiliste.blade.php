<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h6>
                Downloads
            </h6>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <ul class="list-group list-group-flush">
                @foreach($nachricht->getMedia('files') as $media)
                    <li class="list-group-item">
                        <a href="{{url('/image/'.$media->id)}}" target="_blank" class="mx-auto ">
                            <i class="fas fa-file-download"></i>
                            {{$media->name}}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
