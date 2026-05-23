<?php

namespace App\Services\Ucs\Exceptions;

use RuntimeException;

/**
 * Wird geworfen, wenn die Authentifizierung gegen die Kelvin REST API
 * fehlschlägt (HTTP 401 / 403 oder fehlender access_token).
 */
class KelvinAuthException extends RuntimeException
{
}

