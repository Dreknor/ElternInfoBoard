# Authentifizierung der Anfragen

Um die Anfrage zu authentifizieren, fügen Sie einen **`Authorization`**-Header mit dem Wert **`"Bearer {YOUR_AUTH_KEY}"`** ein.

Alle authentifizierten Endpunkte sind in der Dokumentation unten mit einem `Authentifizierung erforderlich`-Badge gekennzeichnet.

Das Authentifizierungs-Token muss im Header als Bearer Token übergeben werden. Es authentifiziert den Benutzer und gibt Zugriff auf die API. Das Token ist nur für den Benutzer bestimmt und sollte nicht weitergegeben werden.<br>Wird in den persönlichen Einstellungen generiert.
