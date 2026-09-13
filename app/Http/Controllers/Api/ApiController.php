<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;

/**
 * Base controller for every mobile API controller.
 *
 * Extending this gives a controller the unified success()/error() helpers, so
 * all endpoints speak the same {status, message, data} envelope. Only the data
 * changes from one endpoint to the next.
 */
abstract class ApiController extends Controller
{
    use ApiResponse;
}
