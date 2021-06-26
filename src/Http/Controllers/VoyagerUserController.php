<?php

declare(strict_types=1);

namespace Joy\VoyagerApi\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Joy\VoyagerApi\Http\Traits\CrudActions;

class VoyagerUserController extends VoyagerBaseController
{
    use CrudActions;

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
