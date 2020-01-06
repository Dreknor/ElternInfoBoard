@if(!isset($news))
    <li>
        <i class="fas fa-bell-slash" title="Benachrichtigungsfunktion ist nicht aktiviert. Dazu bitte in den Einstellungen die Speicherung des Logins erlauben."></i>
    </li>

@elseif(count($news) == 0)
    <li>
        <i class="fas fa-bell" title="Keine neuen Benachrichtigungen vorhanden"></i>
    </li>
@else
    <li class="nav-item dropdown">
        <a href="#" class="dropdown-toggle text-success" data-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-bell" title="Keine neuen Benachrichtigungen vorhanden"></i>
        </a>
        <ul class="dropdown-menu">
            @foreach($news as $item)
            <li>
                <a class="dropdown-item" href="{{$item['link']}}">
                    {{$item['title']}}
                </a>
            </li>
            @endforeach
        </ul>
    </li>
@endif