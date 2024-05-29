<div class="btn-group">
    <a href="#" class="card-link text-black-50" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
    </a>
    <div class="dropdown-menu">
        <a class="dropdown-item" href="{{url('/posts/edit/'.$nachricht->id)}}">
            <i class="far fa-edit"></i> bearbeiten
        </a>
        @if($nachricht->updated_at->greaterThan(\Carbon\Carbon::now()->subWeeks(3)) or $nachricht->archiv_ab->greaterThan(\Carbon\Carbon::now()))
            <a href="{{url('/posts/touch/'.$nachricht->id)}}" class="dropdown-item">
                <i class="fas fa-redo"></i> aktualisieren
            </a>
        @else
            <a href="{{url('/posts/touch/'.$nachricht->id)}}" class="dropdown-item">
                <i class="far fa-clone"></i> kopieren
            </a>
        @endif
        @if($nachricht->released == 0 and auth()->user()->can('release posts'))
            <a class="dropdown-item" href="{{url('/posts/release/'.$nachricht->id)}}">
                <i class="far fa-eye"></i> veröffentlichen
            </a>
        @endif
        @if($nachricht->released == 1 and !$nachricht->is_archived)
            <a href="{{url('/posts/archiv/'.$nachricht->id)}}" class="dropdown-item">
                <i class="fas fa-archive"></i> archivieren
            </a>
        @endif
        @if(auth()->user()->can('make sticky'))
            <a href="{{url('/posts/stick/'.$nachricht->id)}}"
               class="dropdown-item">
                <i class="fas fa-thumbtack" @if($nachricht->sticky)  style="transform: rotate(45deg)" @endif></i>
                @if(! $nachricht->sticky)
                    anheften
                @else
                    lösen
                @endif
            </a>
        @endif
        <div class="dropdown-divider"></div>
        @if($nachricht->released != 1 and (auth()->user()->can('delete posts') or auth()->id() == $nachricht->author))
            <a href="{{url('/posts/delete/'.$nachricht->id)}}"
               class="dropdown-item text-danger">
                <i class="fas fa-trash"></i>
                löschen
            </a>
        @endif
    </div>
</div>






