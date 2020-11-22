@push('head')
    <link href="{{asset('css/kiosk/losung.css?time=').\Carbon\Carbon::now()->format('h_i_s')}}" rel="stylesheet" />

@endpush

@push('slider')
    <div class="carousel-item " id="losung">
        <div id="losung_kreuz">
            <img src="{{asset('img/kreuz.png')}}" id="picture" alt="" width="100%" style="">
        </div>
        <div id="losung_text">
            <div id="losung_losung">
                <p>
                    {{$losung->Losungstext}}
                </p>
                <p>
                    <i>
                        {{$losung->Losungsvers}}
                    </i>
                </p>
            </div>
            <div id="losung_lehrtext">
                <p>
                    {{$losung->Lehrtext}}
                </p>
                <p>
                    <i>
                        {{$losung->Lehrtextvers}}
                    </i>
                </p>
            </div>
        </div>
    </div>

@endpush

@push('js')

@endpush
