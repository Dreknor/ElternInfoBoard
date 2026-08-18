@if($reinigung and $reinigung!="")
<div class="rounded-lg shadow-lg overflow-hidden" style="background: var(--color-card-bg);">
    <div class="px-4 py-3 border-b"
         style="background: linear-gradient(to right, var(--color-widget-warning-from), var(--color-widget-warning-to)); border-color: var(--color-widget-warning-border);">
        <h5 class="text-lg font-bold flex items-center gap-2 mb-0" style="color: var(--color-widget-header-text);">
            <i class="fas fa-broom"></i>
            Anstehende Reinigung
        </h5>
    </div>
    <div class="p-4">
        <p class="mb-0" style="color: var(--color-text-primary);">
            Achtung: In der Woche vom
            <b>{{$reinigung->datum->startOfWeek()->format('d.m')}} bis {{$reinigung->datum->endOfWeek()->format('d.m')}}</b>
            sind sie für <b>{{$reinigung->aufgabe}}</b> eingeteilt.
            <br>
            Bitte denken Sie auch an das Mitnehmen des Beutels mit Wäsche am Ende der Woche.
            <br>
            Danke
        </p>
    </div>
</div>
@endif

