<h6>
    Downloads
</h6>
<ul class="list-group list-group-flush">
    @foreach($nachricht->getMedia('files') as $media)
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