<?php

namespace App\Services\Ucs\Exceptions;

use RuntimeException;

/**
 * Wird geworfen, wenn die Kelvin REST API nicht erreichbar ist oder
 * mit einem unerwarteten HTTP-Fehler antwortet (5xx, Timeout, Proxy-Block).
 *
 * Hinweis: Ein Proxy-Block erzeugt denselben Netzwerkfehler wie ein echter
 * Verbindungsfehler und ist nicht sicher davon unterscheidbar.
 * Bei wiederholtem Auftreten bitte Proxy-Whitelist prüfen:
 *
 * @see docs/kelvin-api-endpunkte.md#proxy-whitelist-checkliste
 */
class KelvinUnavailableException extends RuntimeException
{
}

