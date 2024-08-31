<div class="container-fluid">
    <p>
        Die angegebenen Zeiten gelten als regelmäßige Schickzeiten, zu denen das Kind jede Woche den Hort verlassen darf.<br>
    </p>
    <p>
        Sie können für Ihr Kind auch die Zeit angeben, ab der es frühestens gehen darf. Wir werden das Kind dann nicht losschicken, es aber gehen lassen, wenn es sich verabschiedet.<br>
        Dabei können Sie auch angeben, wann Ihr Kind spätestens den Hort verlassen soll. Die Zeit für "spätestens" wirkt somit nur in Verbindung mit der Angabe "ab".
    </p>
    <p>
        Die Schickzeiten müssen zwischen <b>{{config('schicken.ab')}} Uhr und {{config('schicken.max')}} Uhr</b>  liegen. <br>
    </p>
    <p class="text-danger">
        Beachten Sie, dass wir die Kinder der 1. und 2. Klassenstufe zur vollen oder halben Stunde losschicken. Geben Sie andere Zeiten an, muss Ihr Kind selbstständig daran denken.
    </p>
    <p class="container-fluid">
        Die Liste wird jeweils montags aktualisiert. Gewünschte Änderungen müssen daher bis Montag 8.00 Uhr eingetragen sein. <br>
        Änderungen werden berücksichtigt ab: <b>{{\Carbon\Carbon::now()->next('monday')->format('d.m.Y')}}</b>
    </p>
</div>
