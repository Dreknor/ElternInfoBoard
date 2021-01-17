@if($reinigung and $reinigung!="")
<div class="card border">
    <div class="  bg-warning card-header border-bottom" >
        <div class="row">
            <div class="col-md-10">
                <h5 class="card-title">
                    Anstehende Reinigung
                </h5>
            </div>
        </div>
    </div>
    <div class="card-body" >
        <p>
            Achtung: In der Woche vom <b>{{$reinigung->datum->startOfWeek()->format('d.m')}} bis {{$reinigung->datum->endOfWeek()->format('d.m')}}</b> sind sie für <b>{{$reinigung->aufgabe}} </b> eingeteilt.<br>
            Bitte denken Sie auch an das Mitnehmen des Beutels mit Wäsche am Ende der Woche.
            <br>
            Danke
        </p>
    </div>

</div>
@endif
