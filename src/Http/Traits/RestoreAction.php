<?php

declare(strict_types=1);

namespace Joy\VoyagerApi\Http\Traits;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use TCG\Voyager\Events\BreadDataRestored;
use TCG\Voyager\Facades\Voyager;

trait RestoreAction
{
    public function restore(Request $request, $id)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $model = app($dataType->model_name);
        $this->authorize('delete', $model);

        // Get record
        $query = $model->withTrashed();
        if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope' . ucfirst($dataType->scope))) {
            $query = $query->{$dataType->scope}();
        }
        $data = $query->findOrFail($id);

        $displayName = $dataType->getTranslatedAttribute('display_name_singular');

        $res  = $data->restore($id);
        $data = $res
            ? [
                'message'    => __('voyager::generic.successfully_restored') . " {$displayName}",
                'alert-type' => 'success',
            ]
            : [
                'message'    => __('voyager::generic.error_restoring') . " {$displayName}",
                'alert-type' => 'error',
            ];

        if ($res) {
            event(new BreadDataRestored($dataType, $data));
        }

        $response = $this->overrideSendRestoreResponse(
            $request,
            $data
        );
        if ($response) {
            return $response;
        }

        return JsonResource::make($data);
    }

    /**
     * Override send Restore response.
     *
     * @param Request $request Request
     * @param mixed   $data    Data
     *
     * @return mixed
     */
    protected function overrideSendRestoreResponse(
        Request $request,
        $data
    ) {
        //
    }
}
