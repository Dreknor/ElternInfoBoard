@if(!isset($notifications) || $notifications == false)
    <li>
        <i class="fas fa-bell-slash" title="Benachrichtigungsfunktion ist nicht aktiviert. Dazu bitte in den Einstellungen die Speicherung des Logins erlauben."></i>
    </li>

@elseif(count($notifications) == 0)
    <li>
        <i class="fas fa-bell" title="Keine neuen Benachrichtigungen vorhanden"></i>
    </li>
@else
    <li class="nav-item dropdown">
        <a href="#" class="dropdown-toggle text-success" data-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-bell" title="Keine neuen Benachrichtigungen vorhanden"></i>
        </a>
        <ul class="dropdown-menu dropdown-menu-right">
            @foreach($notifications as $item)
            <li class="" id="notification-{{$item->id}}">
                <a class="dropdown-item" href="{{$item['url']}}" onclick="readNotification({{$item->id}})">
                    <div class="container-fluid">
                        <div class="row h-100">
                            @if($item['icon'])
                                <div class="col-md-2 p-0 my-auto">
                                    <img src="{{$item['icon']}}" alt="Circle Image" class="rounded-circle img-fluid">
                                </div>
                            @endif
                            <div class="col-md-9">
                                <div class="row">
                                    <div class="col-12">
                                        <p>
                                            <b>
                                                {{$item['title']}}
                                            </b>
                                        </p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <p>
                                            {{$item['message']}}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </a>
            </li>
            @endforeach
        </ul>
    </li>
@endif

@push('js')
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
