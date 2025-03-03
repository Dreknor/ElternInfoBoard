<div class="row mb-2">
    <div class="col-auto">
        {{ $nachricht->autor?->name }}
    </div>
    <div class="col-auto ml-auto">
        <div class="d-md-none">
            <a class="" data-toggle="collapse" href="#info_{{$nachricht->id}}" role="button" aria-expanded="false"
               aria-controls="collapseExample">
                <i class="fa fa-info-circle"></i>
            </a>
        </div>
    </div>
</div>


<div class="d-md-block collapse" id="info_{{$nachricht->id}}">
    <div class="row mt-1">
        @foreach($nachricht->groups as $group)
            <div class="col-auto m-1">
                <span class="badge badge-green p-2">
                {{ $group->name }}
                </span>
            </div>
        @endforeach
    </div>
    <div class="row mt-1">
        <div class="col-auto">
        <span class="p-2">
            aktualisiert: {{ $nachricht->updated_at->format('d.m.Y H:i') }} (Archiv {{$nachricht->archiv_ab->format('d.m.Y')}})
        </span>
        </div>

    </div>
</div>

