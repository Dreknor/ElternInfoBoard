@if(!$nachricht->is_archived and $nachricht->rueckmeldung->pflicht == 1)
    <div class="container-fluid mb-3">
        <div class="progress">
            <div class="progress-bar amount" role="progressbar" id="progress_{{$nachricht->id}}" style="width: @if($nachricht->userRueckmeldung->count() == 0) 99 @else {{round(100 -($nachricht->userRueckmeldung->groupBy('users_id')->count() / ($nachricht->users->unique('email')->count() - $nachricht->users()->doesnthave('sorgeberechtigter2')->count()) *100))}}@endif%"></div>
        </div>
        <i>
            {{round($nachricht->userRueckmeldung->groupBy('users_id')->count() / ($nachricht->users->unique('email')->count() - $nachricht->users()->doesnthave('sorgeberechtigter2')->count()) *100, 2)}}% der erforderlichen Rückmeldungen sind eingegangen.
        </i>
    </div>
@endif
