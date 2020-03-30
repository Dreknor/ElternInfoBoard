<ul class="comment-section">
    @foreach(optional($nachricht->comments)->sortByDesc('created_at') as $comment)
        <li class="comment @if ($loop->index % 2 == 0) user-comment @else author-comment @endif @if ($loop->index >= 10) d-none @endif">
            <div class="info">
                <a href="">{{$comment->creator->name}}</a>
                <span>{{$comment->created_at->diffForHumans()}}</span>
            </div>
            <p>{{$comment->body}}</p>
        </li>

    @endforeach

    @if(optional($nachricht->comments)->count() >= 10)
        <a href="#{{$nachricht->id}}" class="commentLinks">
            alle Kommentare anzeigen
        </a>
    @endif
    <!-- More comments -->

    <li class="write-new">
        <form action="{{url("nachricht/$nachricht->id/comment/create")}}" method="post">
            @csrf
            <textarea placeholder="Kommentar hier schreiben" name="comment"></textarea>
            <button type="submit" class="btn btn-success">kommentieren</button>
        </form>

    </li>

</ul>
