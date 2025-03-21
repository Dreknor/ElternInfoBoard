
    @if((count($nachricht->getMedia('images'))>0 or count($nachricht->getMedia('files'))>0) and $nachricht->type == 'image')
        <div
            class="container-fluid info  @foreach($nachricht->groups as $group) {{\Illuminate\Support\Str::camel($group->name)}} @endforeach">
            <div class="row ">
                <div class="col mx-auto">
                    @if(request()->segment(1)!="kiosk" and (auth()->user()->can('edit posts') or auth()->user()->id == $nachricht->author ))
                        <div class="pull-right">

                            @if(!is_null($nachricht->rueckmeldung) and $nachricht->rueckmeldung->type == 'abfrage')
                                <a href="{{url('rueckmeldungen/'.$nachricht->rueckmeldung->id."/download")}}"
                                   title="Download" class="btn btn-sm btn-info">
                                    <i class="fa fa-download"></i>
                                </a>
                            @endif
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
                            @if(auth()->user()->can('make sticky'))
                                <a href="{{url('/posts/stick/'.$nachricht->id)}}"
                                   class="btn btn-sm @if($nachricht->sticky) btn-outline-success @else btn-primary @endif"
                                   data-toggle="tooltip" data-placement="top" title="Nachricht anheften">
                                    <i class="fas fa-thumbtack"
                                       @if($nachricht->sticky)  style="transform: rotate(45deg)" @endif></i>
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
                                        @if($nachricht->rueckmeldung?->type == 'bild')
                                            <h6 class="small">{{$media->name}}</h6>
                                        @endif
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
        <div
            class="card {{$nachricht->type}}  @if($nachricht->released == 0) border border-info @endif @foreach($nachricht->groups as $group) {{\Illuminate\Support\Str::camel($group->name)}} @endforeach"
             id="{{$nachricht->id}}">
            @if(count($nachricht->getMedia('header'))>0)
                <img class="card-img-top" src="{{url('/image/'.$nachricht->getMedia('header')->first()->id)}}"
                     style="max-height: 250px;object-fit: cover; object-position: 0 40%;">
            @endif
            <div class=" @if($nachricht->released == 0) bg-info @endif card-header border-bottom">
                <div class="container-fluid blur"
                     @if(count($nachricht->getMedia('header'))>0) style="margin-top: -90px;" @endif>
                    <div class="row">
                        <div class="col-10 ">
                            <h5 class="card-title text-wrap ">
                                @if($nachricht->sticky)
                                    <i class="fas fa-thumbtack fa-xs "></i>
                                @endif
                                {{$nachricht->header}}  @if($nachricht->released == 0)
                                    (unveröffentlicht)
                                @endif
                            </h5>
                        </div>
                        @if(request()->segment(1)!="kiosk" and (auth()->user()->can('edit posts') or auth()->user()->id == $nachricht->author ))
                            <div class="col-1 ml-auto">
                                <div class="pull-right">
                                    @include('nachrichten.header.admin-post')
                                </div>
                            </div>
                        @endif
                    </div>
                    @if($nachricht->type != 'image')
                        @include('nachrichten.header.info')
                    @endif



                    @if($nachricht->is_archived)
                        <button class="btn btn-outline-info btn-block btnShow" data-toggle="collapse"
                                data-target="#Collapse{{$nachricht->id}}">
                            <i class="fa fa-eye"></i>
                            Text anzeigen
                        </button>
                    @endif
                </div>

            </div>
            <div class="card-body  @if($nachricht->is_archived) collapse @endif" id="Collapse{{$nachricht->id}}">
                <div class="container-fluid">
                    @if((count($nachricht->getMedia('images'))>0 or count($nachricht->getMedia('files'))>0) and $nachricht->type != 'image')

                        <div class="row">
                            <div class="col-md-8 col-sm-12 blur">
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
                        <div class="container-fluid">
                            <p class="pl-2  blur">
                                {!! $nachricht->news !!}
                            </p>
                        </div>

                    @endif
                </div>
            </div>
                @if($nachricht->read_receipt == 1)
                    @include('nachrichten.footer.read_receipt', ['post' => $nachricht])
                @endif
            @include('nachrichten.footer.reactions')
            @include('nachrichten.footer.poll_anonym')
            @if(!is_null($nachricht->rueckmeldung))
                    <div class="container-fluid ">
                        <div class="row">
                            <div class="col-12">
                                @if($nachricht->rueckmeldung->multiple)
                                    <p class="text-info font-weight-bold">
                                        <i class="fas fa-check-double"></i> Es können mehrere Rückmeldungen abgegeben
                                        werden.
                                    </p>
                                @endif
                                @if($nachricht->rueckmeldung->pflicht)
                                    <p class="text-danger font-weight-bold">
                                        <i class="fas fa-exclamation-triangle"></i> Rückmeldung
                                        bis {{$nachricht->rueckmeldung->ende->format('d.m.Y')}} ist Pflicht.
                                    </p>
                                @endif
                            </div>
                        </div>

                    </div>

                @if($nachricht->rueckmeldung != null)
                    @include('nachrichten.elements.progressbar')
                @endif
                @if($nachricht->rueckmeldung->type == 'email')
                    @include('nachrichten.footer.rueckmeldung')
                    @can('manage rueckmeldungen')
                        <div class="card-footer">
                            <a href="{{url('rueckmeldungen')}}" class="btn btn-block btn-outline-info">
                                Rückmeldungen ansehen
                            </a>
                        </div>

                    @elsecan('view rueckmeldungen')
                        <button class="btn btn-outline-info btn-block btnShowRueckmeldungen" data-toggle="collapse"
                                data-target="#{{$nachricht->id}}_rueckmeldungen">
                            <i class="fa fa-eye"></i>
                            {{$nachricht->userRueckmeldung->count()}} Rückmeldungen anzeigen
                        </button>
                        <div id='{{$nachricht->id."_rueckmeldungen"}}' class="collapse">
                            @include('nachrichten.footer.eingegangeneRueckmeldung')
                        </div>
                    @endcan
                @endif
            @endif

            @if(!is_null($nachricht->rueckmeldung) and $nachricht->rueckmeldung->type == 'abfrage')
                @include('nachrichten.footer.abfrage')
            @elseif(!is_null($nachricht->rueckmeldung) and $nachricht->rueckmeldung->type == 'bild' and $nachricht->rueckmeldung->ende->greaterThan(\Carbon\Carbon::now()))
                @include('nachrichten.footer.imageRueckmeldung')
            @elseif(!is_null($nachricht->rueckmeldung) and $nachricht->rueckmeldung->type == 'commentable' and $nachricht->rueckmeldung->ende->greaterThan(\Carbon\Carbon::now()))
                <div class="container-fluid">
                    @include('nachrichten.footer.comments')
                </div>
            @endif
        </div>
    @endif
