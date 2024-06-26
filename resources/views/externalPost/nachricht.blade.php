@if((count($nachricht->getMedia('images'))>0 or count($nachricht->getMedia('files'))>0) and $nachricht->type == 'image')
    <div
        class="container-fluid info  @foreach($nachricht->groups as $group) {{\Illuminate\Support\Str::camel($group->name)}} @endforeach">
        <div class="row ">
            <div class="col mx-auto">
                @if(request()->segment(1)!="kiosk" and (auth()->user()->can('edit posts') or auth()->user()->id == $nachricht->author ))
                    <div class="pull-right">


                        @if($nachricht->updated_at->greaterThan(\Carbon\Carbon::now()->subWeeks(3)))
                            <a href="{{url('/posts/edit/'.$nachricht->id)}}" class="btn btn-sm btn-warning"
                               id="editTextBtn" data-toggle="tooltip" data-placement="top" title="Nachricht bearbeiten">
                                <i class="far fa-edit"></i>
                            </a>
                            <a href="{{url('/posts/touch/'.$nachricht->id)}}" class="btn btn-sm btn-secondary"
                               data-toggle="tooltip" data-placement="top" title="Nachricht nach oben schieben">
                                <i class="fas fa-redo"></i>
                            </a>
                        @else
                            <a href="{{url('/posts/touch/'.$nachricht->id)}}" class="btn btn-sm btn-secondary"
                               data-toggle="tooltip" data-placement="top" title="Nachricht kopieren">
                                <i class="far fa-clone"></i>
                            </a>
                        @endif
                        @if($nachricht->released == 0)
                            <a href="{{url('/posts/release/'.$nachricht->id)}}" class="btn btn-sm btn-secondary"
                               data-toggle="tooltip" data-placement="top" title="Nachricht veröffentlichen">
                                <i class="far fa-eye"></i>
                            </a>
                        @endif
                        @if($nachricht->released == 1 and !$nachricht->is_archived)
                            <a href="{{url('/posts/archiv/'.$nachricht->id)}}" class="btn btn-sm btn-warning"
                               data-toggle="tooltip" data-placement="top" title="Nachricht ins Archiv">
                                <i class="fas fa-archive"></i>
                            </a>
                        @endif

                    </div>
                @endif
            </div>
        </div>
        <div class="row mb-2">
            <div class="col-12">
                <div id="carousel_post_{{$nachricht->id}}" class="carousel slide mx-auto" data-ride="carousel">
                    <div class="carousel-inner">
                        @foreach($nachricht->getMedia('images')->sortBy('name') as $media)
                            <div class="carousel-item text-center @if($loop->first) active @endif">
                                <a href="{{url('/image/'.$media->id)}}" target="_blank">
                                    <img class="d-block mx-auto" src="{{url('/image/'.$media->id)}}">
                                </a>
                            </div>
                        @endforeach

                    </div>

                    @if(count($nachricht->getMedia('images'))>1)
                        <a class="carousel-control-prev" href="#carousel_post_{{$nachricht->id}}" role="button"
                           data-slide="prev">
                            <span class="carousel-control-prev-icon bg-primary " aria-hidden="true"></span>
                            <span class="sr-only">Previous</span>
                        </a>
                        <a class="carousel-control-next" href="#carousel_post_{{$nachricht->id}}" role="button"
                           data-slide="next">
                            <span class="carousel-control-next-icon bg-primary" aria-hidden="true"></span>
                            <span class="sr-only">Next</span>
                        </a>
                    @endif
                </div>

            </div>
        </div>
    </div>
@else
    <div class="nachricht {{$nachricht->type}} card @if($nachricht->released == 0) border border-info @endif blur"
         id="{{$nachricht->id}}">
        @if(count($nachricht->getMedia('header'))>0)
            <img class="card-img-top" src="{{url('/image/'.$nachricht->getMedia('header')->first()->id)}}"
                 style="max-height: 250px;object-fit: cover; object-position: 0 70%;" alt="Header-Bild">
        @endif
        <div class=" @if($nachricht->released == 0) bg-info @endif card-header border-bottom">
            <div class="container-fluid ">
                <div class="row">
                    <div class="col-md-10">
                        <h5 class="card-title">
                            @if($nachricht->sticky)
                                <i class="fas fa-thumbtack fa-xs "></i>
                            @endif
                            {{$nachricht->header}} @if($nachricht->released == 0)
                                (unveröffentlicht)
                            @endif
                        </h5>
                        <div class="row">

                            <div class="col">
                                aktualisiert: {{$nachricht->updated_at->isoFormat('DD. MMMM YYYY HH:mm')}}
                            </div>
                            <div class="col">
                                Archiv ab: {{$nachricht->archiv_ab?->isoFormat('DD. MMMM YYYY')}}
                            </div>
                            <div class="col">
                                <div class="pull-right">
                                    Autor: {{$nachricht->autor?->name}}
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(auth()->user()->can('edit posts') or auth()->user()->id == $nachricht->author )
                        <div class="col-md-2 col-sm-4">
                            <a href="{{url('/posts/edit/'.$nachricht->id)}}" class="btn btn-sm btn-warning"
                               id="editTextBtn" data-toggle="tooltip" data-placement="top" title="Nachricht bearbeiten">
                                <i class="far fa-edit"></i>
                            </a>
                            @if($nachricht->released == 0)
                                <a href="{{url('/posts/release/'.$nachricht->id)}}" class="btn btn-sm btn-secondary"
                                   data-toggle="tooltip" data-placement="top" title="Nachricht veröffentlichen">
                                    <i class="far fa-eye"></i>
                                </a>
                            @endif
                            @if($nachricht->released == 1 and !$nachricht->is_archived)
                                <a href="{{url('/posts/archiv/'.$nachricht->id)}}" class="btn btn-sm btn-warning"
                                   data-toggle="tooltip" data-placement="top" title="Nachricht ins Archiv">
                                    <i class="fas fa-archive"></i>
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
                @if($nachricht->is_archived)
                    <button class="btn btn-outline-info btn-block btnShow" data-toggle="collapse"
                            data-target="#Collapse{{$nachricht->id}}">
                        <i class="fa fa-eye"></i>
                        Text anzeigen
                    </button>
                @endif
            </div>

        </div>
        <div class="card-body @if($nachricht->is_archived) collapse @endif" id="Collapse{{$nachricht->id}}">
            <div class="container-fluid">
                @if(count($nachricht->getMedia('images'))>0 or count($nachricht->getMedia('files'))>0)
                    <div class="row">
                        <div class="col-md-8 col-sm-12">
                            <p>
                                {!! $nachricht->news !!}
                            </p>
                        </div>
                        <div class="col-md-4 col-sm-12">
                            @if(count($nachricht->getMedia('images'))>0)
                                @include('nachrichten.footer.bilder')
                            @endif

                            @if(count($nachricht->getMedia('files'))>0)
                                @include('nachrichten.footer.dateiliste')
                            @endif
                        </div>
                    </div>
                @else
                    <p class="pl-2">
                        {!! $nachricht->news !!}
                    </p>
                @endif
            </div>
        </div>
        @if(!is_null($nachricht->rueckmeldung) and $nachricht->rueckmeldung->type == 'email')
            @if(!$nachricht->is_archived and $nachricht->rueckmeldung->pflicht == 1)
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            @for($x=1; $x <= $nachricht->userRueckmeldung->count(); $x++)
                                <i class="fas fa-user-alt text-success" title="{{$x}}"></i>
                            @endfor
                            @for($x=1; $x <= ((round($nachricht->users->where('sorg2', '!=', null)->unique('email')->count()/2)) + $nachricht->users->where('sorg2', 0)->unique('email')->count())-$nachricht->userRueckmeldung->count(); $x++)
                                <i class="fas fa-user-alt text-danger" title="{{$x}}"></i>
                            @endfor
                        </div>
                    </div>
                </div>
            @endif
            @include('nachrichten.footer.rueckmeldung')
            @can('view rueckmeldungen')
                <button class="btn btn-outline-info btn-block btnShowRueckmeldungen" data-toggle="collapse"
                        data-target="#{{$nachricht->id}}_rueckmeldungen">
                    <i class="fa fa-eye"></i>
                    {{$nachricht->userRueckmeldung->count()}} Rückmeldungen anzeigen
                </button>
                <div id="{{$nachricht->id."_rueckmeldungen"}}" class="collapse">
                    @include('nachrichten.footer.eingegangeneRueckmeldung')
                </div>

            @endif
        @endif
        @if(!is_null($nachricht->rueckmeldung) and $nachricht->rueckmeldung->type == 'bild' and $nachricht->rueckmeldung->ende->greaterThan(\Carbon\Carbon::now()))
            @include('nachrichten.footer.imageRueckmeldung')
        @endif
    </div>
@endif
