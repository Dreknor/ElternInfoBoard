# Schülerkonten (optional)

Stand: 03.11.2026 · Entwurf · standardmäßig aus

Schulen können Schülerinnen und Schülern ein eigenes, stark eingeschränktes Konto geben.
Wo ein Schul-Login (SSO) besteht, melden sich Schüler darüber an. Ohne SSO gibt es einen
Benutzernamen statt einer E-Mail-Adresse. Die Übernahme aus UCS@school ist optional.
Ist die Funktion aus, ändert sich nichts.

---

## 1. Ziele und Grundsätze

* **Optional:** eigenes Modul „Schülerkonten“, standardmäßig inaktiv.
* **Grundsätzlich verboten:** Ein Schülerkonto darf nur, was ausdrücklich freigegeben ist.
  Neue Funktionen sind für Schüler automatisch gesperrt.
* **Schüler sind keine Eltern:** Schülerkonten stehen nie in `group_user`, nie in einer
  Familie und haben nie Elternrechte. Dadurch tauchen sie in keinem Verteiler, keiner
  Statistik und keinem Pool auf (Reinigung, Pflichtstunden, Rückmeldungen …).
* **Keine E-Mail nötig:** Anmeldung über SSO oder über einen Benutzernamen.
* **Datensparsam:** Es werden nur Name, Klasse und Login gespeichert, keine Kontaktdaten.
  Gesundheitsdaten und Krankmeldungen bleiben für Schüler unsichtbar.

## 2. Ausgangslage im Code

Diese Stellen bestimmen das Konzept:

| Befund | Folge |
|---|---|
| Sichtbarkeit und Empfänger laufen über `group_user` (`Post::users()`, `Termin::users()`, `Liste::users()`, `PostPolicy`, `PostQuery::visibleTo`) | Wäre ein Schüler Mitglied der Klassengruppe, sähe er alle Elternbeiträge und stünde in allen Verteilern |
| Reinigungsplan-Pool `User::whereHas('groups')` ohne Rollenfilter (`ReinigungController:136/407`) | Schüler würden zum Putzen eingeteilt |
| Rückmelde- und Lesestatistik „wer hat noch nicht“ = `$post->users` (`RueckmeldungStatusService`, `ReadReceiptsController`, `ProcessRemindersJob`) | Falsche Zahlen und Erinnerungen an Schüler |
| Mail, Newsletter und Push gehen an Gruppenmitglieder (`NachrichtenController:989/1057`, `TerminController:260`, `ListenController:158`, `VertretungObserver:34`) | Unerwünschte Mails und Pushes an Schüler |
| Messenger: Gruppenchats und Kontaktsuche über gemeinsame Gruppe (`GroupsController:298ff`, `MessengerController:143ff`) | Schüler könnten Eltern anschreiben |
| `children` hat keine Verbindung zu einem Konto | Neue Verknüpfung Kind ↔ Schülerkonto nötig |
| `users.email` ist Pflichtfeld (eindeutig je `email, ucs_source`) | Für Schüler ohne E-Mail muss das Feld leer sein dürfen |
| Allgemeiner Keycloak-Login (`LoginController::handleKeycloakCallback`) sucht das Konto über die E-Mail und **legt unbekannte Konten automatisch als normale Konten an** | **Schon heute ein Risiko:** Ein Schüler mit Schul-SSO bekommt ein vollwertiges Konto. Wird unabhängig von diesem Konzept zuerst behoben (S-02) |

Daraus folgt: Schülerkonten bekommen **keine** Gruppenmitgliedschaft. Ihre Klassen werden
über das verknüpfte Kind bestimmt (`Child::class`, `Child::group`, `child_group`).

## 3. Datenmodell (nur additiv)

* `users.account_type`: `standard` (Standard) | `student`. Das ist ein festes Merkmal,
  keine Rolle, damit niemand es versehentlich über die Rollenverwaltung vergibt.
* `users.username` (nullable, eindeutig): Login ohne E-Mail. Bei UCS ist es der
  UCS-Benutzername.
* `users.email` nullable. Für `account_type = student` ist die E-Mail immer leer, auch wenn
  das SSO eine liefert. So gehen keine Mails an Schüler, und die Adresse kann nicht mit einem
  Elternkonto kollidieren.
* `children.user_id` (nullable, eindeutig, FK `users`): ein Konto je Kind.
* `posts.zielgruppe` (und optional `termine.zielgruppe`): `eltern` (Standard) |
  `eltern_schueler` | `schueler`. Bestehende Beiträge bleiben `eltern`.
* `student_account_events`: Protokoll über Anlage, Sperre, Code-Ausgabe und Löschung
  (wer, wann, was; keine Inhalte).
* Neue Einstellung `SchuelerSetting` (siehe §9), neues Modul „Schülerkonten“.

`User::isStudent()` ist die einzige Abfrage, die der restliche Code benutzt.

## 4. Was ein Schülerkonto darf

| Bereich | Schüler | Hinweis |
|---|---|---|
| Nachrichten | nur `zielgruppe` ≠ `eltern` **und** an eine Klasse/Gruppe des Kindes | keine Lesebestätigung, Rückmeldung, Umfrage, Kommentare, Reaktionen |
| Termine | nur Termine mit `zielgruppe` ≠ `eltern` der eigenen Gruppen | kein iCal-Abo mit Elterndaten |
| Vertretungsplan | eigene Klasse | ohne Abwesenheiten von Lehrkräften, falls die Schule das wünscht |
| Stundenplan | eigene Klasse | |
| AGs | ansehen | Anmeldung bleibt bei den Eltern |
| Eigenes Profil | Name ansehen, Passwort ändern (nur Login ohne SSO) | keine Kontaktdaten |
| Push in der App | nur bei Schülerbeiträgen | später, siehe §8 |

**Gesperrt** sind alle anderen Bereiche, zum Beispiel: Krankmeldung, Kinder, Rückmeldungen,
Listen und Buchungen, Pflichtstunden, Reinigung, Elternrat, Messenger, Kontaktformular,
Datei-Uploads, Personensuche, Datenschutz-Auskunft (geht über die Eltern) und jede
Verwaltungsfunktion.

Messenger und Kommentare können später als eigene Option kommen. Sie bleiben dann auf
Lehrkräfte beschränkt, nie Eltern oder andere Schüler.

## 5. Durchsetzung (mehrere Ebenen)

1. **Freigabeliste je Route:** Die Middleware `RestrictStudentAccounts` liegt in den
   Gruppen `web` und `api`. Für Schülerkonten lässt sie nur Routen mit dem Namenspräfix
   `student.` sowie Login und Logout durch. Alles andere ergibt 404, wie bei inaktiven
   Modulen. Neue Routen sind so automatisch gesperrt.
2. **Rechte:** Ein `Gate::before` gibt Schülern bei jeder Berechtigung `false`, außer bei den
   wenigen `student …`-Rechten. Rollen werden an Schülerkonten nicht vergeben
   (`UserService::syncRoles` lehnt das ab).
3. **Daten:**
   * Schülerkonten werden nie in `group_user` geschrieben. `GroupsController`,
     `GroupMembershipService`, Importe und der Schuljahreswechsel (`SchoolYearService`)
     prüfen `isStudent()`. Zusätzlich wird es in der Datenbank verhindert (Trigger bzw.
     Prüfung im Observer von `group_user`).
   * `FamilyBuilder` und `FamilyResolver` überspringen Schülerkonten.
   * `messenger_discoverable = false`, `benachrichtigung` aus, `sendCopy` aus.
   * Eigene Abfrage `StudentPostQuery` statt `PostPolicy` und `PostQuery`. Gruppen kommen
     aus `Child::derivedGroupIds()`.
4. **Schutz bei der Verwaltung:** Schülerkonten erscheinen in der Benutzerliste nur auf
   einem eigenen Reiter „Schülerkonten“. In „alle Nutzer“-Auswahlen
   (`User::query()` in Verwaltungsmasken) fehlen sie über den Scope `withoutStudents()`.
5. **Test als Netz:** Ein Feature-Test geht alle registrierten Routen durch und erwartet
   für ein Schülerkonto 404/403, außer bei der Freigabeliste. Jede neue Route ist damit
   automatisch abgedeckt.

## 6. Anmeldung

### 6.1 Mit SSO (UCS-OIDC oder Keycloak)

* Schüler melden sich nur über das SSO an. Es gibt kein lokales Passwort, und
  „Passwort vergessen“ entfällt.
* Zuordnung wie bei Eltern (`UcsLoginController`, §6.2 UCS-Konzept): zuerst `ucs_oidc_sub`,
  dann `username`. Die Zuordnung zum Kind läuft über `children.ucs_username`.
  **Nie über die E-Mail-Adresse.**
* Ob jemand Schüler ist, ergibt sich aus der Kelvin-Rolle `student` bzw. aus einem
  einstellbaren Claim (z. B. Gruppe `schueler` oder Rolle im Token) beim allgemeinen
  Keycloak.
* Ist kein Konto vorhanden:
  * Bei aktivierter **UCS-Provisionierung** (§7) wurde es bereits angelegt.
  * Sonst und bei `jit_anlage = an`: Das Konto wird beim ersten Login angelegt, aber nur,
    wenn ein passendes Kind existiert und die Klasse freigegeben ist (§9).
  * Sonst erscheint der Hinweis „Für Sie ist kein Zugang freigeschaltet.“
* **S-02 (Pflicht, auch ohne Schülerkonten):** Der allgemeine Keycloak-Login legt
  Schülerinnen und Schüler (Claim) nie als normale Konten an. Optional lässt sich die
  automatische Anlage ganz abschalten.

### 6.2 Ohne SSO: Benutzername

* Das Anmeldefeld heißt „E-Mail oder Benutzername“. Schülerkonten melden sich nur mit
  Benutzername an, z. B. `vorname.n3` (eindeutig, ohne Klassenangabe).
* **Erstzugang über einen Code:** Die Verwaltung (oder, falls erlaubt, ein Elternteil mit
  `can_manage`) erzeugt einen Einmal-Code bzw. QR-Code (gültig 7 Tage). Damit setzt der
  Schüler sein Passwort. Das nutzt den vorhandenen Login-Code-Mechanismus aus
  `API/V1/AuthController` (`app_login_code`).
* **Passwort vergessen:** Ohne E-Mail gibt es kein Zurücksetzen per Mail. Verwaltung oder
  Eltern erzeugen einen neuen Code.
* Strengere Sperre bei Fehlversuchen (`throttle:login-student`). Sitzungen und
  App-Tokens laufen kürzer (Standard 30 Tage).

## 7. UCS@school-Provisionierung (optional)

Einstellung `ucs_provisionierung` (Standard: aus), nur wirksam bei `UcsSetting::enabled`.

* **An:** `sync:ucs-parents` legt für Kelvin-Schüler der freigegebenen Klassen Schülerkonten
  an (`account_type = student`, `username = ucs_username`, `ucs_source = kelvin`, keine
  E-Mail, kein Passwort) und verknüpft sie mit dem Kind (`children.user_id`).
* **Aus:** Es werden keine Konten vorab angelegt. Bei SSO entstehen sie erst beim ersten
  Login (JIT, §6.1) oder durch manuelle Anlage bzw. CSV.
* Aufräumen nach den bestehenden Regeln: Verschwindet ein Schüler aus UCS oder hat das Kind
  `status = left`, wird das Konto sofort gesperrt (`is_active = false`) und nach
  `loeschen_nach_tagen` (Standard 30) gelöscht. Geändert oder gelöscht werden nur Konten mit
  `ucs_source = kelvin`.
* Es kommen keine Daten außer Vorname, Nachname, Benutzername und Klassen in die
  Datenbank.

## 8. App (elterninfo_app)

Die App ist laut Projektregel **nur für Eltern**. Vorschlag:

* **Phase 1:** Schüler nutzen nur die Weboberfläche. `POST v1/token` und der SSO-Austausch
  lehnen Schülerkonten mit 403 ab („Dieser Zugang ist nur im Browser nutzbar.“).
* **Phase 2 (eigene Entscheidung):** Freigegebene Endpunkte unter `v1/student/*`, ein
  Sanctum-Token mit der Fähigkeit `student` und eine eigene, reduzierte Ansicht in der App.
  Dafür müsste die Projektregel der App angepasst werden.

## 9. Einstellungen (`SchuelerSetting`, Reiter „Schülerkonten“)

| Einstellung | Standard | Bedeutung |
|---|---|---|
| `enabled` | aus | Modul an/aus |
| `login_sso` | an | Login über SSO erlauben |
| `login_benutzername` | aus | Login mit Benutzername + Passwort (ohne SSO) |
| `jit_anlage` | aus | Konto beim ersten SSO-Login anlegen |
| `ucs_provisionierung` | aus | Konten per UCS-Sync anlegen |
| `freigegebene_gruppen` | leer | Klassen/Bereiche, für die Konten möglich sind (z. B. ab Klasse 5) |
| `eltern_zustimmung` | an | Konto erst aktiv, wenn ein Elternteil zugestimmt hat |
| `eltern_codes` | aus | Eltern dürfen Einmal-Codes erzeugen |
| `loeschen_nach_tagen` | 30 | Löschfrist nach Austritt |
| `vertretung_abwesenheiten` | aus | Abwesenheiten von Lehrkräften auch Schülern zeigen |

**Eltern-Sicht:** Im Bereich „Kinder“ steht je Kind der Status des Schülerkontos (aktiv,
gesperrt, nicht vorhanden). Bei `eltern_zustimmung` können Eltern zustimmen oder das Konto
sperren. Bei `eltern_codes` können sie einen neuen Code erzeugen.

**Beiträge:** Im Editor steht unter den Gruppen das Feld „Sichtbar für“ mit den Werten
Eltern / Eltern und Schüler / nur Schüler. Es erscheint nur, wenn das Modul aktiv ist.

## 10. Datenschutz

* Rechtsgrundlage und Verarbeitungsverzeichnis klärt die Schule. Das System unterstützt
  sie mit Zustimmung der Eltern, Protokoll und Löschfrist.
* Keine E-Mail-Adressen, keine Telefonnummern, keine Fotos, keine Gesundheitsdaten.
* Schüler sehen keine Namen anderer Eltern oder Schüler, also keine Lesebestätigungen,
  keine Listen und keine Kommentare.
* Logs enthalten nur die Konto-ID, keine Namen.
* Die Datenschutz-Auskunft eines Elternteils enthält das Schülerkonto des Kindes.

## 11. Arbeitspakete

| # | Paket | Abhängig |
|---|---|---|
| S-01 | Migrationen (§3), `User::isStudent()`, Scope `withoutStudents()`, Modul + Einstellungen | – |
| S-02 | Keycloak-Login: Schüler per Claim erkennen und nie als normales Konto anlegen (unabhängig dringlich) | – |
| S-03 | Middleware `RestrictStudentAccounts`, `Gate::before`, Routen-Test (§5) | S-01 |
| S-04 | Schutz in Gruppen- und Familienlogik (`group_user`-Sperre, FamilyBuilder, Importe, Schuljahreswechsel) | S-01 |
| S-05 | `posts.zielgruppe`, Editor-Feld, `StudentPostQuery`, Schüler-Startseite mit Nachrichten und Terminen | S-03 |
| S-06 | Vertretungs- und Stundenplan für Schüler | S-03 |
| S-07 | Login mit Benutzername, Einmal-Code/QR, Verwaltung der Schülerkonten (Reiter, Sperren, Codes) | S-03 |
| S-08 | SSO: Zuordnung über `children.ucs_username`, JIT-Anlage | S-02, S-07 |
| S-09 | UCS-Provisionierung inkl. Aufräumen (§7) | S-08 |
| S-10 | Eltern-Sicht: Status, Zustimmung, Codes | S-07 |
| S-11 | Protokoll, Löschfrist, Datenschutz-Auskunft | S-01 |
| S-12 | (Phase 2) API `v1/student/*` und App-Ansicht | Entscheidung §8 |

## 12. Offene Fragen

1. Welche Klassen bzw. welches Mindestalter? Soll das je Schule frei wählbar sein
   (`freigegebene_gruppen`)?
2. Ist die Zustimmung der Eltern Pflicht oder nur eine Option der Schule?
3. Sollen Schüler Lehrkräften schreiben können (Messenger, nur Lehrkräfte)?
4. Wie wird ein Schüler im allgemeinen Keycloak erkannt: Rolle, Gruppe oder E-Mail-Domain?
5. App für Schüler (Phase 2): ja oder nein?
