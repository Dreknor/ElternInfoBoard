
<div class="nachricht  {{$nachricht->type}} card @if($nachricht->released == 0) border border-info @endif" id="{{$nachricht->id}}">

    <div class=" @if($nachricht->released == 0) bg-info @endif card-header border-bottom" >
       <div class="row">
           <div class="col-md-10">
               <h5 class="card-title">
                   {{$nachricht->header}}  @if($nachricht->released == 0) (unveröffentlicht) @endif
               </h5>
               <div class="row">
                   <div class="col">
                       aktualisiert: {{$nachricht->updated_at->isoFormat('DD. MMMM YYYY HH:mm')}}
                   </div>
                   <div class="col">
                       Archiv ab: {{optional($nachricht->archiv_ab)->isoFormat('DD. MMMM YYYY')}}
                   </div>
                   <div class="col">
                       <div class="pull-right">
                           Autor: {{optional($nachricht->autor)->name}}
                       </div>
                   </div>
               </div>
               @if(auth()->user()->can('edit posts') or auth()->user()->id == $nachricht->author )
                   <button class="btn btn-primary hidden  d-md-none" type="button" data-toggle="collapse" data-target="#collapse{{$nachricht->id}}" aria-expanded="false" aria-controls="collapseExample">
                       Gruppen zeigen
                   </button>
                   <div class="row collapse d-md-block" id="collapse{{$nachricht->id}}">
                       <small class="col">
                           @foreach($nachricht->groups as $group)
                               <div class="btn @if($nachricht->released == 0) btn-outline-warning @else  btn-outline-info @endif btn-sm">
                                   {{$group->name}}
                               </div>
                           @endforeach
                       </small>
                   </div>
               @endif

               <div class="row">
                   <div class="col">
                       @if($nachricht->type == "info")
                           <div class="btn btn-outline-info btn-sm">
                               Information
                           </div>
                       @endif
                       @if($nachricht->type == "wahl")
                           <div class="btn btn-outline-warning btn-sm">
                               Wahlaufgabe
                           </div>
                       @endif
                       @if($nachricht->type == "pflicht")
                           <div class="btn btn-outline-danger btn-sm">
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
                   @if(!$archiv and $nachricht->released == 1)
                           <a href="{{url('/posts/archiv/'.$nachricht->id)}}" class="btn btn-sm btn-warning"  data-toggle="tooltip" data-placement="top" title="Nachricht ins Archiv">
                               <i class="fas fa-archive"></i>
                           </a>
                    @endif
               </div>
           @endif
       </div>
        @if($archiv)
            <button class="btn btn-outline-info btn-block btnShow" data-toggle="collapse" data-target="#Collapse{{$nachricht->id}}">
                <i class="fa fa-eye"></i>
                Text anzeigen
            </button>
        @endif
    </div>
    @if(!is_null($nachricht->rueckmeldung))

    @endif
    <div class="card-body @if($archiv) collapse @endif" id="Collapse{{$nachricht->id}}">
        @if(count($nachricht->getMedia('images'))>0 or count($nachricht->getMedia('files'))>0)
            <div class="row">
                <div class="col-md-6 col-sm-12">
                    <p>
                        {!! $nachricht->news !!}
                    </p>
                </div>
                <div class="col-md-6 col-sm-12">
                    @if(count($nachricht->getMedia('images'))>0)
                        @include('nachrichten.footer.bilder')
                    @endif

                    @if(count($nachricht->getMedia('files'))>0)
                        @include('nachrichten.footer.dateiliste')
                    @endif
                </div>
            </div>
        @else
            <p>
                {!! $nachricht->news !!}
            </p>
        @endif
    </div>
    @if(!is_null($nachricht->rueckmeldung))
        @if(!$archiv and $nachricht->rueckmeldung->pflicht == 1)
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
    @endif

</div>
