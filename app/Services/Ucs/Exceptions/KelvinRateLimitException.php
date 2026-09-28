<?php

namespace App\Services\Ucs\Exceptions;

use RuntimeException;

/**
 * Wird geworfen, wenn die Kelvin REST API HTTP 429 (Rate Limit) zurückgibt
 * und alle Retry-Versuche ausgeschöpft sind.
 */
class KelvinRateLimitException extends RuntimeException
{
}

