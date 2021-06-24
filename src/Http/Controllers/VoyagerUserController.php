<?php

declare(strict_types=1);

namespace Joy\VoyagerApi\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use TCG\Voyager\Facades\Voyager;

class VoyagerUserController extends VoyagerBaseController
{
    /**
     * @OA\Get(
     * path="/api/profile",
     *   tags={"User"},
     *   security={
     *      {"token": {}},
     *   },
     *   summary="Profile",
     *   operationId="user",
     *   @OA\Response(
     *      response=200,
     *      description="Success",
     *      @OA\JsonContent(
     *         @OA\Property(property="user", type="object", ref="#/components/schemas/User"),
     *      )
     *   ),
     *   @OA\Response(
     *      response=401,
     *       description="Unauthenticated"
     *   ),
     *   @OA\Response(
     *       response=403,
     *       description="Forbidden"
     *   )
     * )
     *
     * @OA\Schema(
     *    schema="User",
     *    @OA\Property(
     *       property="id",
     *       type="integer",
     *       readOnly="true",
     *       example="1"
     *    ),
     *    @OA\Property(
     *       property="name",
     *       type="string",
     *       description="User Name"
     *    ),
     *    @OA\Property(
     *       property="email",
     *       type="string",
     *       format="email",
     *       description="User unique email address",
     *       example="user@gmail.com"
     *    )
     * )
     */
    public function profile(Request $request)
    {
        $route    = '';
        $dataType = Voyager::model('DataType')->where('model_name', Auth::guard(config('joy-voyager-api.guard', 'api'))->getProvider()->getModel())->first();
        if (!$dataType && config('joy-voyager-api.guard', 'api') == 'api') {
            $route = route('voyager.users.edit', Auth::user()->getKey());
        } elseif ($dataType) {
            $route = route('voyager.' . $dataType->slug . '.edit', Auth::user()->getKey());
        }

        return Voyager::view('voyager::profile', compact('route'));
    }

    // POST BR(E)AD
    public function update(Request $request, $id)
    {
        if (Auth::user()->getKey() == $id) {
            $request->merge([
                'role_id'                              => Auth::user()->role_id,
                'user_belongstomany_role_relationship' => Auth::user()->roles->pluck('id')->toArray(),
            ]);
        }

        return parent::update($request, $id);
    }
}
