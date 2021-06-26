<?php

declare(strict_types=1);

namespace Joy\VoyagerApi\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Joy\VoyagerApi\Http\Traits\CrudActions;
use TCG\Voyager\Http\Controllers\VoyagerBaseController as TCGVoyagerBaseController;

class VoyagerBaseController extends TCGVoyagerBaseController
{
    use CrudActions;

    /**
     * @OA\Info(
     *      version="1.0.0",
     *      title=APP_NAME,
     *      description="Api documentation"
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

    /**
     * Create a new instance
     */
    public function __construct()
    {
        Auth::shouldUse(config('joy-voyager-api.guard', 'api'));
    }
}
