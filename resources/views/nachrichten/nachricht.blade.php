<div class="card @if($nachricht->released == 0) border border-info @endif">
    <div class=" @if($nachricht->released == 0) bg-info @endif card-header border-bottom">
       <div class="row">
           <div class="col-11">
               <h5 class="card-title">
                   {{$nachricht->header}}
               </h5>
               <div class="row">
                   <div class="col">
                       {{$nachricht->updated_at->format('d.m.Y H:i')}}
                   </div>
                   <div class="col">
                       <div class="pull-right">
                           Autor: {{optional($nachricht->autor)->name}}
                       </div>
                   </div>
               </div>
               @can('edit posts')
                   <div class="row">
                       <small class="col">
                           @foreach($nachricht->groups as $group)
                               <div class="btn btn-outline-info btn-sm">
                                   {{$group->name}}
                               </div>
                           @endforeach
                       </small>
                   </div>
               @endcan
           </div>
           <div class="col-1">
               @can('edit posts')
                    <a href="{{url('/posts/edit/'.$nachricht->id)}}" class="btn btn-sm btn-warning" id="editTextBtn">
                        <i class="far fa-edit"></i>
                    </a>
                    <a href="{{url('/posts/touch/'.$nachricht->id)}}" class="btn btn-sm btn-secondary"  data-toggle="tooltip" data-placement="top" title="Nachricht nach oben schieben">
                        <i class="fas fa-redo"></i>
                    </a>
               @endcan
           </div>
       </div>

    </div>
    <div class="card-body">
        @if(count($nachricht->getMedia('images'))>0 or count($nachricht->getMedia('files'))>0)
            <div class="row">
                <div class="col-md-6">
                    <p>
                        {!! $nachricht->news !!}
                    </p>
                </div>
                <div class="col-md-6">
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
        <div class="card-footer border-top">
            @include('nachrichten.footer.rueckmeldung')
        </div>
    @endif

</div>