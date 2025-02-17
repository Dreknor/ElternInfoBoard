<!--
<div class="row">
    @if(count($nachrichten->filter(function ($item, $key){ if ($item->type == "info") { return $item;}})) > 0)
        <div class="col">
            <div class="btn btn-outline-primary btn-sm btn-block" type="button" id="infoButton">
                <i class="fas fa-eye"></i> Infos ausblenden
            </div>
        </div>
    @endif
    @if(count($nachrichten->filter(function ($item, $key){ if ($item->type == "pflicht") { return $item;}})) > 0)
        <div class="col">
            <div class="btn btn-outline-danger btn-sm btn-block" type="button" id="pflichtButton">
                <i class="fas fa-eye"></i> Pflichtaufgaben ausblenden
            </div>
        </div>
    @endif
    @if(count($nachrichten->filter(function ($item, $key){ if ($item->type == "wahl") { return $item;}})) > 0)
        <div class="col">
            <div class="btn btn-outline-warning btn-sm btn-block" type="button" id="wahlButton">
                <i class="fas fa-eye"></i> Wahlaufgaben ausblenden
            </div>
        </div>
    @endif

</div>
-->

<div class="row mt-1">

    @foreach(auth()->user()->groups as $group)
        <div class="col">
            <div class="btn btn-outline-primary btn-sm btn-block" type="button" id="{{\Illuminate\Support\Str::camel($group->name)}}" data-show="true">
                {{$group->name}}
            </div>
        </div>

    @endforeach
</div>
