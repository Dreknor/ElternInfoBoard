@push('head')
    <link href="{{asset('css/kiosk/elterninfo.css?time=').\Carbon\Carbon::now()->format('h_i_s')}}" rel="stylesheet" />
@endpush

@push('slider')
    @for($x=1; $x <= ceil($elterninfo->count()/5); $x++)
        <div class="carousel-item" id="elterninfo_{{$x}}">
            <div class="site-title">
                <h2>
                    ElternInfos (Seite {{$x}} von {{ceil($elterninfo->count()/5)}})
                </h2>
            </div>
            <div class="" id="news_{{$x}}">
                <div class="card-columns">
                    @foreach($elterninfo->slice($x-1,5) as $info)
                        <div class="card">
                            <div class="card-body info-body">
                                <h4 class="card-title">
                                    {{$info->header}}   @if($info->released == 0) (unveröffentlicht) @endif
                                </h4>
                                <p class="card-text">{!! $info->news !!}</p>
                            </div>
                            <div class="card-footer">
                                {{ strlen($info->news)}}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endfor
@endpush

@push('js')

@endpush
