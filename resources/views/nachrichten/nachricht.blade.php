<div class="card">
    <div class="card-header @if($nachricht->released == 0) bg-warning @endif border-bottom">
       <h5 class="card-title">
           {{$nachricht->header}}
       </h5>
        <p class="">
            {{$nachricht->updated_at->format('d.m.Y H:i')}}
        </p>
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

</div>