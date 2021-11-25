@if(!is_null($nachricht->reactable))
    <div class="card-footer">

        <div class="row">
            <div class="col-auto">
                <a href="{{url('posts/'.$nachricht->id.'/react/like')}}"
                   class="card-link reaction @if($nachricht->reacted()) reacted @endif">
                    <i class="far fa-thumbs-up "></i>
                    @if($nachricht->reactions->count() > 0)
                        {{($nachricht->reactions->where('name', 'like')->count()/$nachricht->reactions->count())*100}} %
                    @endif
                </a>

            </div>
            <div class="col-auto">
                <a href="{{url('posts/'.$nachricht->id.'/react/happy')}}"
                   class="card-link reaction @if($nachricht->reacted()) reacted @endif">
                    <i class="far fa-sad-tear @if($nachricht->reacted()) reacted @endif"></i>
                    @if($nachricht->reactions->count() > 0)
                        {{($nachricht->reactions->where('name', 'happy')->count()/$nachricht->reactions->count())*100}}
                        %
                    @endif
                </a>
            </div>
            <div class="col-auto">
                <a href="{{url('posts/'.$nachricht->id.'/react/love')}}"
                   class="card-link reaction @if($nachricht->reacted()) reacted @endif">
                    <i class="far fa-heart @if($nachricht->reacted()) reacted @endif"></i>
                    @if($nachricht->reactions->count() > 0)
                        {{($nachricht->reactions->where('name', 'love')->count()/$nachricht->reactions->count())*100}} %
                    @endif
                </a>
            </div>
            <div class="col-auto">
                <a href="{{url('posts/'.$nachricht->id.'/react/wow')}}"
                   class="card-link reaction @if($nachricht->reacted()) reacted @endif">
                    <i class="far fa-surprise @if($nachricht->reacted()) reacted @endif"></i>
                    @if($nachricht->reactions->count() > 0)
                        {{($nachricht->reactions->where('name', 'wow')->count()/$nachricht->reactions->count())*100}} %
                    @endif
                </a>
            </div>
            <div class="col-auto">
                <a href="{{url('posts/'.$nachricht->id.'/react/haha')}}"
                   class="card-link reaction @if($nachricht->reacted()) reacted @endif">
                    <i class="far fa-laugh-squint @if($nachricht->reacted()) reacted @endif"></i>
                    @if($nachricht->reactions->count() > 0)
                        {{($nachricht->reactions->where('name', 'haha')->count()/$nachricht->reactions->count())*100}} %
                    @endif
                </a>
            </div>
            <div class="col-auto">
                <a href="{{url('posts/'.$nachricht->id.'/react/sad')}}"
                   class="card-link reaction @if($nachricht->reacted()) reacted @endif">
                    <i class="far fa-sad-tear @if($nachricht->reacted()) reacted @endif"></i>
                    @if($nachricht->reactions->count() > 0)
                        {{($nachricht->reactions->where('name', 'sad')->count()/$nachricht->reactions->count())*100}} %
                    @endif
                </a>
            </div>
        </div>
    </div>
@endif
