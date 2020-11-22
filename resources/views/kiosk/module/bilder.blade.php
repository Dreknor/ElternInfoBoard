@push('head')
    <link href="{{asset('css/kiosk/bilder.css')}}" rel="stylesheet" />

@endpush

@push('slider')
    <div class="carousel-item " id="picture_div">
        <img src="{{asset('img/bilder/bg_').\Carbon\Carbon::now()->format('d').".jpg"}}" id="picture" alt="" width="100%">
    </div>

@endpush

@push('js')

@endpush
