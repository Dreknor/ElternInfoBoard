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
            <div class="col-md-12">
                <label class="label-control w-100">
                    Informationstext für Eltern
                    <textarea class="form-control" name="pflichtstunden_text" rows="6">{{$pflichtstundenSettings->pflichtstunden_text ?? 'Bitte tragen Sie hier Ihre geleisteten Pflichtstunden ein. Pro Familie sind 20 Stunden jährlich zu leisten. Vielen Dank für Ihre Unterstützung!'}}</textarea>
                </label>
            </div>
            <div class="col-md-12 mt-2">
                <div class="small">
                    Dieser Text wird den Eltern oben auf der Pflichtstunden-Seite angezeigt. Hier können Sie Informationen, Hinweise und Anweisungen zu den Pflichtstunden kommunizieren.
                </div>
            </div>
        </div>
        <div class="form-row mt-1 p-2 border">
            <div class="col-md-12">
                <label class="label-control w-100">
                    <strong>Bereiche für Pflichtstunden (einer pro Zeile)</strong>
                    <textarea class="form-control no-tinymce" name="pflichtstunden_bereiche" rows="8" placeholder="z.B.&#10;Gartenarbeit&#10;Renovierung&#10;Reinigung&#10;Feste/Events&#10;Administrative Aufgaben">{{ is_array($pflichtstundenSettings->pflichtstunden_bereiche ?? []) ? implode("\n", $pflichtstundenSettings->pflichtstunden_bereiche) : '' }}</textarea>
                </label>
            </div>
            <div class="col-md-12 mt-2">
                <div class="small">
                    Definieren Sie hier die verschiedenen Bereiche, in denen Pflichtstunden geleistet werden können. Geben Sie jeden Bereich in einer neuen Zeile ein. Diese Bereiche können dann bei der Erfassung von Pflichtstunden ausgewählt werden und ermöglichen eine gezielte Filterung in der Verwaltungsansicht.
                </div>
            </div>
        </div>
        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    <input type="checkbox" name="listen_autocreate" value="1"
                           @if($pflichtstundenSettings->listen_autocreate ?? false) checked @endif>
                    Listen automatisch erstellen
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Wenn aktiviert, werden die Pflichtstunden-Listen automatisch für alle Familien erstellt. Andernfalls müssen die Listen manuell angelegt werden.
                </div>
            </div>
        </div>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <h5 class="label-control w-100">
                    <strong>🎮 Gamification-Einstellungen</strong>
                </h5>
            </div>
        </div>



        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    <input type="checkbox" name="gamification_show_progress" value="1"
                           @if($pflichtstundenSettings->gamification_show_progress) checked @endif>
                    Fortschritts-Card anzeigen
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Zeigt einen animierten Fortschrittsbalken mit deinem aktuellen Fortschritt und Achievement-Badges an.
                </div>
            </div>
        </div>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    <input type="checkbox" name="gamification_show_ranking" value="1"
                           @if($pflichtstundenSettings->gamification_show_ranking) checked @endif>
                    Ranking-Card anzeigen
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Zeigt deine aktuelle Platzierung unter allen Eltern an mit zusätzlichen Rang-Abzeichen (z.B. 🥇 Platz 1, 🏆 Top 3).
                </div>
            </div>
        </div>

        <div class="form-row mt-1 p-2 border">
            <div class="col-md-6 col-sm-12">
                <label class="label-control w-100">
                    <input type="checkbox" name="gamification_show_comparison" value="1"
                           @if($pflichtstundenSettings->gamification_show_comparison) checked @endif>
                    Vergleichs-Card anzeigen
                </label>
            </div>
            <div class="col-md-6 col-sm-12 m-auto">
                <div class="small">
                    Zeigt einen Vergleich deines Fortschritts mit dem Durchschnitt aller Eltern an und motiviert dich, das Ziel zu erreichen.
                </div>
            </div>
        </div>

        <hr>

        <div class="form-row mt-3">
            <button type="submit" class="btn btn-success btn-block">
                <i class="fas fa-save"></i> Einstellungen speichern
            </button>
        </div>

    </form>
</div>
