<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Datenschutzerklärung – ElternInfo-App</title>
    <style>
        body { font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; background: #f3f4f6; color: #111827;
               margin: 0; padding: 16px; line-height: 1.6; }
        main { background: #fff; border-radius: 12px; padding: 32px 24px; max-width: 760px; margin: 0 auto;
               box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        h1 { font-size: 24px; margin: 0 0 4px; }
        h2 { font-size: 18px; margin: 28px 0 8px; }
        p, li { color: #374151; }
        .muted { color: #6b7280; font-size: 14px; }
        address { font-style: normal; white-space: pre-line; background: #f9fafb; border-radius: 8px; padding: 12px 16px; }
        .todo { color: #b45309; }
    </style>
</head>
<body>
<main>
    <h1>Datenschutzerklärung der ElternInfo-App</h1>
    <p class="muted">Stand: {{ $stand }}</p>

    <h2>1. Worum es geht</h2>
    <p>Die ElternInfo-App ist eine App für Eltern zum ElternInfoBoard ihrer Schule. Sie zeigt Inhalte an, die die Schule im
        ElternInfoBoard bereitstellt, und übermittelt Ihre Eingaben an diesen Server. Die App funktioniert nur mit einem Zugang, den
        Sie von Ihrer Schule erhalten.</p>

    <h2>2. Herausgeber der App</h2>
    @if($publisher)
        <address>{{ $publisher }}</address>
    @else
        <p class="todo">Angaben zum Herausgeber folgen.</p>
    @endif
    <p>Für die Verarbeitung Ihrer Daten im ElternInfoBoard ist die Schule verantwortlich, deren Server Sie in der App auswählen.
        Der Herausgeber der App erhält über die App keine personenbezogenen Daten.</p>

    <h2>3. Keine Werbung, keine Analyse</h2>
    <p>Die App enthält keine Werbung und keine Tracking-, Analyse- oder Absturzberichts-Dienste. Es werden keine Daten an den
        Herausgeber oder an Dritte zu Werbe- oder Analysezwecken übermittelt.</p>

    <h2>4. Welche Daten die App verarbeitet</h2>
    <p>Die App sendet Daten ausschließlich an den Server Ihrer Schule (verschlüsselt über HTTPS):</p>
    <ul>
        <li><strong>Anmeldung:</strong> E-Mail-Adresse und Passwort bzw. Anmeldung über das Schulkonto oder einen QR-Code. Das Passwort
            wird nicht auf dem Gerät gespeichert, sondern nur ein Zugangsschlüssel (Token).</li>
        <li><strong>Inhalte des ElternInfoBoards:</strong> z. B. Nachrichten, Termine, Listen, Angaben zu Ihren Kindern (Klasse, Stundenplan,
            Hort), Vertretungsplan.</li>
        <li><strong>Ihre Eingaben:</strong> z. B. Lesebestätigungen, Rückmeldungen, Krankmeldungen (einschließlich der von Ihnen angegebenen
            Erkrankung), Schickzeiten, Nachrichten im Messenger, Pflichtstunden, hochgeladene Fotos und Dateien, Kontaktangaben in Ihrem Profil.</li>
        <li><strong>Gerät:</strong> ein Gerätename (z. B. „Android – ElternInfo-App“) zur Anzeige Ihrer angemeldeten Geräte sowie – wenn Sie
            Mitteilungen erlauben – ein Push-Token.</li>
    </ul>

    <h2>5. Speicherung auf dem Gerät</h2>
    <p>Damit die App schnell und auch ohne Netz funktioniert, speichert sie geladene Inhalte und noch nicht gesendete Eingaben auf dem
        Gerät. Diese Daten sind verschlüsselt; der Schlüssel liegt im geschützten Speicher des Betriebssystems. Beim Abmelden werden alle
        gespeicherten Daten gelöscht, ebenso beim Deinstallieren der App.</p>

    <h2>6. Mitteilungen (Push)</h2>
    <p>Wenn Sie Mitteilungen erlauben, übermittelt die App ein Push-Token an den Server Ihrer Schule. Die Schule stellt Mitteilungen über
        Firebase Cloud Messaging (Google Ireland Ltd.) bzw. den Apple Push Notification Service (Apple Inc.) zu. Diese Dienste verarbeiten
        dabei das Token und den Mitteilungstext. Mitteilungen zu Kindern und Gesundheit sind deshalb neutral formuliert. Welche
        Mitteilungen Sie erhalten, legen Sie in der App unter „Mitteilungen“ fest; Sie können sie jederzeit in den Einstellungen des
        Geräts abschalten.</p>

    <h2>7. Berechtigungen</h2>
    <ul>
        <li><strong>Kamera:</strong> nur zum Scannen des QR-Codes bei der Einrichtung bzw. Anmeldung. Es werden keine Bilder gespeichert.</li>
        <li><strong>Fotos und Dateien:</strong> nur, wenn Sie selbst ein Foto oder eine Datei auswählen, um sie hochzuladen.</li>
        <li><strong>Mitteilungen:</strong> für Push-Mitteilungen (siehe oben).</li>
        <li><strong>Internet:</strong> für die Verbindung zum Server Ihrer Schule.</li>
    </ul>

    <h2>8. Ihre Rechte</h2>
    <p>Auskunft, Berichtigung und Löschung Ihrer Daten im ElternInfoBoard richten sich an Ihre Schule. In der App sehen Sie unter
        „Mehr → Einstellungen → Meine Daten“, welche Daten gespeichert sind, und können dort unter „Angemeldete Geräte“ Geräte abmelden. Sie haben außerdem das
        Recht, sich bei einer Datenschutz-Aufsichtsbehörde zu beschweren.</p>

    <h2 id="loeschen">9. Konto und Daten löschen</h2>
    <p>Ihr Konto im ElternInfoBoard legt Ihre Schule an und löscht es auch. So beantragen Sie die Löschung:</p>
    <ol>
        <li>Wenden Sie sich an das Sekretariat bzw. die Verwaltung des ElternInfoBoards Ihrer Schule (z. B. über „Kontakt zur Schule“ in der App)
            und bitten Sie um Löschung Ihres Kontos.</li>
        <li>Die Schule löscht Ihr Konto mit Profil, Geräten (Push-Token) und Ihren Eingaben, soweit sie nicht aufbewahrt werden müssen
            (z. B. Krankmeldungen innerhalb der schulrechtlichen Fristen). Gelöschte Konten werden nach der Frist der Schule endgültig entfernt.</li>
        <li>Auf Ihrem Gerät löschen Sie alle Daten der App sofort, indem Sie sich abmelden oder die App deinstallieren.</li>
    </ol>
    <p>Einzelne Geräte melden Sie in der App unter „Mehr → Einstellungen → Angemeldete Geräte“ ab.</p>

    <h2>10. Kontakt</h2>
    @if($contact)
        <address>{{ $contact }}</address>
    @else
        <p>Bei Fragen zum Datenschutz wenden Sie sich bitte an Ihre Schule bzw. deren Datenschutzbeauftragte(n).</p>
    @endif
</main>
</body>
</html>
