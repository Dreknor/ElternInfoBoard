@if(isset($Reinigung) and !is_null($Reinigung) and ($Reinigung->users_id == auth()->user()->id or $Reinigung->users_id == optional(auth()->user()->sorgeberechtigter2)->id))
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
            Achtung: inder Woche vom <b>{{$Reinigung->datum->startOfWeek()->format('d.m')}} bis {{$Reinigung->datum->endOfWeek()->format('d.m')}}</b> sind sie für <b>{{$Reinigung->aufgabe}} </b> eingeteilt.<br>
            Bitte denken Sie auch an das mitnehmen des Beutels mit Wäsche am Ende der Woche.
            <br>
            Danke
        </p>
    </div>

</div>
@endif