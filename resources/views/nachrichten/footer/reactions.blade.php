@if($nachricht->reactable != 0)
    <div class="card-footer">
        <div class="col-12">
            <div class="pull-right">
                <div class="btn-group ">
                    <a type="button"
                       class="btn btn-info btn-link rounded dropdown-toggle @if($nachricht->reacted()) text-success font-weight-bold @endif"
                       data-toggle="dropdown" aria-expanded="false">
                        <i class="far fa-thumbs-up" style="font-size: 1.6em;"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a href="{{url('posts/'.$nachricht->id.'/react/like')}}"
                           class="dropdown-item reaction @if($nachricht->reacted()) reacted @endif">
                            <i class="far fa-thumbs-up "></i>
                            @if($nachricht->reactions->count() > 0)
                                {{round(($nachricht->reactions->where('name', 'like')->count()/$nachricht->reactions->count())*100,2)}}
                                %
                            @endif
                        </a>
                        <a href="{{url('posts/'.$nachricht->id.'/react/sad')}}"
                           class="dropdown-item reaction @if($nachricht->reacted()) reacted @endif">
                            <i class="far fa-sad-tear @if($nachricht->reacted()) reacted @endif"></i>
                            @if($nachricht->reactions->count() > 0)
                                {{round(($nachricht->reactions->where('name', 'sad')->count()/$nachricht->reactions->count())*100,2)}}
                                %
                            @endif
                        </a>
                        <a href="{{url('posts/'.$nachricht->id.'/react/haha')}}"
                           class="dropdown-item reaction @if($nachricht->reacted()) reacted @endif">
                            <i class="far fa-laugh-squint @if($nachricht->reacted()) reacted @endif"></i>
                            @if($nachricht->reactions->count() > 0)
                                {{round(($nachricht->reactions->where('name', 'haha')->count()/$nachricht->reactions->count())*100,2)}}
                                %
                            @endif
                        </a>
                        <a href="{{url('posts/'.$nachricht->id.'/react/wow')}}"
                           class="dropdown-item reaction @if($nachricht->reacted()) reacted @endif">
                            <i class="far fa-surprise @if($nachricht->reacted()) reacted @endif"></i>
                            @if($nachricht->reactions->count() > 0)
                                {{round(($nachricht->reactions->where('name', 'wow')->count()/$nachricht->reactions->count())*100,2)}}
                                %
                            @endif
                        </a>
                        <a href="{{url('posts/'.$nachricht->id.'/react/love')}}"
                           class="dropdown-item reaction @if($nachricht->reacted()) reacted @endif">
                            <i class="far fa-heart @if($nachricht->reacted()) reacted @endif"></i>
                            @if($nachricht->reactions->count() > 0)
                                {{round(($nachricht->reactions->where('name', 'love')->count()/$nachricht->reactions->count())*100,2)}}
                                %
                            @endif
                        </a>
                        <a href="{{url('posts/'.$nachricht->id.'/react/happy')}}"
                           class="dropdown-item  reaction @if($nachricht->reacted()) reacted @endif">
                            <i class="far fa-smile @if($nachricht->reacted()) reacted @endif"></i>
                            @if($nachricht->reactions->count() > 0)
                                {{round(($nachricht->reactions->where('name', 'happy')->count()/$nachricht->reactions->count())*100,2)}}
                                %
                            @endif
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>

@endif
