<div class="nachricht blur {{$nachricht->type}} card @if($nachricht->released == 0) border border-info @endif" id="{{$nachricht->id}}" >
    @if(count($nachricht->getMedia('header'))>0)
        <img class="card-img-top" src="{{url('/image/'.$nachricht->getMedia('header')->first()->id)}}" style="max-height: 250px;object-fit: cover; object-position: 0 40%;">
    @endif
    <div class=" @if($nachricht->released == 0) bg-info @endif card-header border-bottom blur" >
        <div class="container-fluid " @if(count($nachricht->getMedia('header'))>0) style="margin-top: -90px;" @endif>
            <div class="row  blur">
                <div class="col-md-10">
                    <h5 class="card-title">

                        @if($nachricht->sticky)
                            <i class="fas fa-thumbtack fa-xs "></i>
                        @endif
                        {{$nachricht->header}}  @if($nachricht->released == 0) (unveröffentlicht) @endif
                    </h5>
                    <div class="row">

                        <div class="col-auto">
                            aktualisiert: {{$nachricht->updated_at->isoFormat('DD. MMMM YYYY HH:mm')}}
                        </div>
                        <div class="col-auto">
                            Archiv ab: {{optional($nachricht->archiv_ab)->isoFormat('DD. MMMM YYYY')}}
                        </div>
                        <div class="col-auto">
                            <div class="pull-right">
                                Autor: {{optional($nachricht->autor)->name}}
                            </div>
                        </div>
                    </div>
                    <div class="row mt-1">
                        <div class="col-12">
                            Gruppen:
                            @foreach($nachricht->groups as $group)
                                <span class="badge">
                                            {{$group->name}}@if(!$loop->last), @endif
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                    <div class="row mt-1 mb-1">
                        <div class="col-12">

                                @if($nachricht->type == "info")
                                    <div class="badge badge-info p-2">
                                        Information
                                    </div>
                                @endif
                                @if($nachricht->type == "wahl")
                                    <div class="badge badge-warning p-2">
                                        Wahlaufgabe
                                    </div>
                                @endif
                                @if($nachricht->type == "pflicht")
                                    <div class="badge badge-danger p-2">
                                        Pflichtaufgabe
                                    </div>
                                @endif

                        </div>

                    </div>
                </div>

                @if(request()->segment(1)!="kiosk" and (auth()->user()->can('edit posts') or auth()->user()->id == $nachricht->author ))
                    <div class="col-md-2 col-sm-4">
                        @if($nachricht->updated_at->greaterThan(\Carbon\Carbon::now()->subWeeks(3)))
                            <a href="{{url('/posts/edit/'.$nachricht->id)}}" class="btn btn-sm btn-warning" id="editTextBtn"   data-toggle="tooltip" data-placement="top" title="Nachricht bearbeiten">
                                <i class="far fa-edit"></i>
                            </a>
                            <a href="{{url('/posts/touch/'.$nachricht->id)}}" class="btn btn-sm btn-secondary"  data-toggle="tooltip" data-placement="top" title="Nachricht nach oben schieben">
                                <i class="fas fa-redo"></i>
                            </a>
                        @else
                            <a href="{{url('/posts/touch/'.$nachricht->id)}}" class="btn btn-sm btn-secondary"  data-toggle="tooltip" data-placement="top" title="Nachricht kopieren">
                                <i class="far fa-clone"></i>
                            </a>
                        @endif
                        @if($nachricht->released == 0)
                            <a href="{{url('/posts/release/'.$nachricht->id)}}" class="btn btn-sm btn-secondary"  data-toggle="tooltip" data-placement="top" title="Nachricht veröffentlichen">
                                <i class="far fa-eye"></i>
                            </a>
                        @endif
                        @if($nachricht->released == 1 and !$nachricht->is_archived)
                            <a href="{{url('/posts/archiv/'.$nachricht->id)}}" class="btn btn-sm btn-warning"  data-toggle="tooltip" data-placement="top" title="Nachricht ins Archiv">
                                <i class="fas fa-archive"></i>
                            </a>
                        @endif
                        @if(auth()->user()->can('make sticky'))
                            <a href="{{url('/posts/stick/'.$nachricht->id)}}" class="btn btn-sm @if($nachricht->sticky) btn-outline-success @else btn-primary @endif"  data-toggle="tooltip" data-placement="top" title="Nachricht anheften">
                                <i class="fas fa-thumbtack" @if($nachricht->sticky)  style="transform: rotate(45deg)" @endif></i>
                            </a>
                        @endif
                    </div>
                @endif
            </div>
            @if($nachricht->is_archived)
                <button class="btn btn-outline-info btn-block btnShow" data-toggle="collapse" data-target="#Collapse{{$nachricht->id}}">
                    <i class="fa fa-eye"></i>
                    Text anzeigen
                </button>
            @endif
        </div>

    </div>
    <div class="card-body  @if($nachricht->is_archived) collapse @endif" id="Collapse{{$nachricht->id}}" >
        <div class="container-fluid">
            @if(count($nachricht->getMedia('images'))>0 or count($nachricht->getMedia('files'))>0)
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
        @include('nachrichten.footer.reactions')
        @include('nachrichten.footer.poll')
        @if(!is_null($nachricht->rueckmeldung) and $nachricht->rueckmeldung->type == 'email')
            @if(!$nachricht->is_archived and $nachricht->rueckmeldung->pflicht == 1)
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            @for($x=1; $x <= $nachricht->userRueckmeldung->count(); $x++)
                                <i class="fas fa-user-alt text-success" title="{{$x}}"></i>
                            @endfor
                            @for($x=1; $x <= (($nachricht->users->unique('email')->count() - $nachricht->users()->doesnthave('sorgeberechtigter2')->count()))-$nachricht->userRueckmeldung->count(); $x++)
                                <i class="fas fa-user-alt text-danger" title="{{$x}}"></i>
                            @endfor
                            @can('view rueckmeldungen')
                                {{$nachricht->users->unique('email')->count()}}
                                - {{$nachricht->users()->doesnthave('sorgeberechtigter2')->count()}}
                            @endcan
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
            @endcan
        @endif

        @if(!is_null($nachricht->rueckmeldung) and $nachricht->rueckmeldung->type == 'bild' and $nachricht->rueckmeldung->ende->greaterThan(\Carbon\Carbon::now()))
            @include('nachrichten.footer.imageRueckmeldung')
        @elseif(!is_null($nachricht->rueckmeldung) and $nachricht->rueckmeldung->type == 'commentable' and $nachricht->rueckmeldung->ende->greaterThan(\Carbon\Carbon::now()))
            <div class="container-fluid">
                @include('nachrichten.footer.comments')
            </div>

        @endif
</div>
