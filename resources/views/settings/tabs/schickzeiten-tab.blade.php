<div class="tab-pane" id="schickzeiten" role="tabpanel" aria-labelledby="notify-tab">
    <form action="{{url('settings/schickzeiten')}}" method="post" class="form-horizontal">
        @csrf
        @method('PUT')

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100 ">
                    Schickzeiten ab
                </label>
                <input type="time" class="form-control" name="schicken_ab" value="{{$schickzeitenSettings->schicken_ab}}">
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Ab welcher Uhrzeit dürfen die Schüler das Haus verlassen?
                </div>
            </div>
        </div>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100 ">
                    Schickzeiten bis
                </label>
                <input type="time" class="form-control" name="schicken_bis" value="{{$schickzeitenSettings->schicken_bis}}">
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Bis zu welcher Uhrzeit dürfen die Schüler das Haus verlassen?
                </div>
            </div>
        </div>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100 ">
                    Erklärung Schickzeiten
                </label>
                <textarea class="form-control" name="schicken_text">{{$schickzeitenSettings->schicken_text}}</textarea>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Hier kann der Text angepasst werden, der den Eltern angezeigt wird, wenn sie die Schickzeiten erfassen. So sind sie über die Regeln und Rahmenbedingungen informiert.
                </div>
            </div>
        </div>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100 ">
                    Interval Schickzeiten
                </label>
                <input type="number" class="form-control" name="schicken_intervall" value="{{$schickzeitenSettings->schicken_intervall}}">
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Hier kann das Intervall angepasst werden, in dem die Kinder losgeschickt werden können. Abweichende können angegeben werden, es wird den Eltern dann angezeigt, dass die Kinder selbstständig losgehen müssen.
                </div>
            </div>
        </div>

        <div class="form-row">
            <button type="submit" class="btn btn-success btn-block">
                Einstellungen speichern
            </button>
        </div>
    </form>
</div>
