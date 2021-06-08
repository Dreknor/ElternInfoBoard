
<div class="nachricht card @if($nachricht->released == 0) border border-info @endif" id="{{$nachricht->id}}">
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
            </div>
        </div>

    </div>
    <div class="card-body ">

        @if(count($nachricht->getMedia('images'))>0 or count($nachricht->getMedia('files'))>0)
            <div class="row">
                <div class="col-md-6 col-sm-12">
                    <p>
                        {!! $nachricht->news !!}
                    </p>
                </div>
                <div class="col-md-6 col-sm-12">
                    @if(count($nachricht->getMedia('images'))>0)
                        @include('kiosk.footer.bilder')
                    @endif

                    @if(count($nachricht->getMedia('files'))>0)
                        @include('kios.footer.dateiliste')
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
