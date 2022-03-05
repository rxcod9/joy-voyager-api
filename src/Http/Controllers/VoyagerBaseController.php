<?php

declare(strict_types=1);

namespace Joy\VoyagerApi\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Joy\VoyagerApi\Http\Traits\CrudActions;
use TCG\Voyager\Http\Controllers\VoyagerBaseController as TCGVoyagerBaseController;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title=APP_NAME,
 *      description="Joy VoyagerApi module adds REST Api end points to Voyager with Passport and Swagger support https://github.com/rxcod9/joy-voyager-api."
 * )
 *
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST,
 *      description="Primary API Server"
 * )
 *
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST2,
 *      description="Another API Server"
 * )
 */
class VoyagerBaseController extends TCGVoyagerBaseController
{
    use CrudActions;

    /**
     * Create a new instance
     */
    public function __construct()
    {
        Auth::shouldUse(config('joy-voyager-api.guard', 'api'));
    }
}
