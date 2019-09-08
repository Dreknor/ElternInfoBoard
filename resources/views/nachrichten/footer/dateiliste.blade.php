<h6>
    Downloads
</h6>
<ul class="list-group list-group-flush">
    @foreach($nachricht->getMedia('files') as $media)
        <a href="{{$media->getPath()}}" class="list-group-item list-group-item-action @if($loop->iteration%2 == 0) list-group-item-dark @endif">
            <i class="fas fa-file-download"></i>
            {{$media->name}}
        </a>
    @endforeach
</ul>