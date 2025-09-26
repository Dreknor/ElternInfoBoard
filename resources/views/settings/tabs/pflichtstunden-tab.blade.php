<div class="tab-pane" id="pflichtstunden" role="tabpanel" aria-labelledby="pflichtstunden-tab">
    <form action="{{url('settings/pflichtstunden')}}" method="post" class="form-horizontal">
        @csrf
        @method('PUT')
        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    <input type="text" class="form-control" name="pflichtstunden_start"
                           value="{{$pflichtstundenSettings->pflichtstunden_start ?? '08-01'}}">
                    Startdatum der Pflichtstunden (Format: Monat-Tag)
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Hier wird das Startdatum der Pflichtstunden im Format Monat-Tag (z.B. 08-01 für den 1. August) festgelegt. Dieses Datum markiert den Beginn des Zeitraums, in dem die Pflichtstunden gezählt werden.
                </div>
            </div>
        </div>
        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    <input type="text" class="form-control" name="pflichtstunden_ende"
                           value="{{$pflichtstundenSettings->pflichtstunden_ende ?? '07-31'}}">
                    Enddatum der Pflichtstunden (Format: Monat-Tag)
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Hier wird das Enddatum der Pflichtstunden im Format Monat-Tag (z.B. 07-31 für den 31. Juli) festgelegt. Dieses Datum markiert das Ende des Zeitraums, in dem die Pflichtstunden gezählt werden.
                </div>
            </div>
        </div>
        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    <input type="number" class="form-control" name="pflichtstunden_anzahl"
                           value="{{$pflichtstundenSettings->pflichtstunden_anzahl ?? 0}}">
                    Anzahl der Pflichtstunden pro Jahr
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Hier wird die Anzahl der Pflichtstunden festgelegt, die pro Jahr erfüllt werden müssen. Diese Zahl gibt an, wie viele Stunden insgesamt im definierten Zeitraum (zwischen Start- und Enddatum) abgeleistet werden müssen.
                </div>
            </div>
        </div>
        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    <input type="number" class="form-control" name="pflichtstunden_betrag" step="0.01"
                           value="{{$pflichtstundenSettings->pflichtstunden_betrag ?? 0}}">
                    Betrag je Pflichtstunden
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Der Betrag, der je nicht geleisteter Pflichtstunde berechnet wird. Dieser Wert wird verwendet, um die finanziellen Konsequenzen für nicht erfüllte Pflichtstunden zu bestimmen.
                </div>
            </div>
        </div>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    <input type="checkbox" name="listen_autocreate" value="1"
                           class="form-control" @if($pflichtstundenSettings->listen_autocreate) checked @endif>
                    Pflichtstunden aus Listen automatisch erstellen
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Wenn diese Option aktiviert ist, werden Pflichtstunden automatisch aus Listeneinträgen erstellt. Dies bedeutet, dass jedes Mal, wenn ein Listeneintrag gebucht oder aktualisiert wird, die entsprechenden Pflichtstunden automatisch generiert und dem Benutzerkonto zugewiesen werden.
                </div>
            </div>
        </div>
        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    <textarea class="form-control" name="pflichtstunden_text" rows="3">{{$pflichtstundenSettings->pflichtstunden_text}}</textarea>
                    Text für Pflichtstunden
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Hier kann der Text definiert werden, der im Bereich Pflichtstunden angezeigt wird. Dieser Text kann Informationen über die Pflichtstunden, deren Bedeutung oder Anweisungen für die Benutzer enthalten.
                </div>
            </div>
        </div>
        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <button type="submit" class="btn btn-primary">Speichern</button>
            </div>
        </div>

    </form>
