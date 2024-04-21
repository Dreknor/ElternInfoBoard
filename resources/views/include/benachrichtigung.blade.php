@if(count($notifications) == 0)
    <div class="">
        <i class="fas fa-bell" title="Keine neuen Benachrichtigungen vorhanden"></i>
    </div>
@else
    <div class="nav-item ">
        <a href="#" class=""
           data-toggle="dropdown" aria-expanded="false">
            <i class="far fa-bell nav-item" style="font-size: 1.5rem"></i>
            @if($notifications->where('read', 0)->count() > 0)
                <span class="badge badge-primary ">
                        {{count($notifications->where('read', 0))}}
                </span>
            @endif
        </a>
        <ul class="dropdown-menu dropdown-menu-right">
            <li class="dropdown-header">
                <b>
                    Benachrichtigungen
                </b>

            </li>
            <li class="dropdown-divider"></li>
            <li class="dropdown-item" onclick="event.stopPropagation()">
                <label class="switch switch-sm ">
                    <input type="checkbox" class="filter_switch" id="show_readed_switch">
                    <span class="slider slider-sm round"></span>
                </label>
                alle anzeigen
            </li>
            @if($notifications->where('read', 0)->count() > 0)
                <li class="dropdown-header" style="font-size: 0.7rem">
                    <a href="{{route('notification.readAll')}}">
                        Alle gelesen
                    </a>
                </li>
                <li class="dropdown-divider"></li>
            @endif
            @foreach($notifications->sortBy('read') as $item)
                <li class="@if($item->read) read_1 d-none @endif" id="notification-{{$item->id}}">
                <a class="dropdown-item @if(!$item->read) bg-light @endif" @if($item->read) style="opacity: 0.5"
                   @endif href="{{$item['url']}}" onclick="readNotification({{$item->id}})">
                    <div class="container-fluid">
                        <div class="row h-100">
                            @if($item['icon'])
                                <div class="col-md-2 p-0 my-auto d-none d-md-block">
                                    <img src="{{$item['icon']}}" alt="Circle Image" class="rounded-circle img-fluid">
                                </div>
                            @endif
                            <div class="col-md-9">
                                <div class="row">
                                    <div class="col-12 title">
                                        {{$item['title']}}
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 message">
                                        {{ $item['message']}}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </a>
            </li>
            @endforeach


        </ul>
    </div>
@endif

@push('js')
    <script>
        $(document).ready(function () {
            $('#show_readed_switch').change(function () {
                if ($(this).is(':checked')) {
                    $('.read_1').removeClass('d-none');
                } else {
                    $('.read_1').addClass('d-none');
                }
            });
        });
    </script>
    <script>
        function readNotification(id) {
            $.ajax({
                url: "{{route('notification.read')}}",
                type: "POST",
                data: {
                    id: id,
                    _token: "{{csrf_token()}}"
                },
                success: function (data) {
                    if (data.success) {
                        $('#notification-' + id).remove();
                    }
                }
            });
        }
    </script>
@endpush
