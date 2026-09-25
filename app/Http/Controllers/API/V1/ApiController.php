<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Basis der App-API v1. Fehler aus Services (HttpException) und Policies werden
 * als JSON `{ message }` mit passendem Status ausgeliefert.
 */
abstract class ApiController extends Controller
{
    use AuthorizesRequests;
}
