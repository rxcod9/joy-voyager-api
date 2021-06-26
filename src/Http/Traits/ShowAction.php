<?php

declare(strict_types=1);

namespace Joy\VoyagerApi\Http\Traits;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use TCG\Voyager\Facades\Voyager;

trait ShowAction
{
    //***************************************
    //                _____
    //               |  __ \
    //               | |__) |
    //               |  _  /
    //               | | \ \
    //               |_|  \_\
    //
    //  Read an item of our Data Type B(R)EAD
    //
    //****************************************

    public function show(Request $request, $id)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        $isSoftDeleted = false;

        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);
            $query = $model->query();

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model))) {
                $query = $query->withTrashed();
            }
            if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope' . ucfirst($dataType->scope))) {
                $query = $query->{$dataType->scope}();
            }
            $dataTypeContent = call_user_func([$query, 'findOrFail'], $id);
            if ($dataTypeContent->deleted_at) {
                $isSoftDeleted = true;
            }
        } else {
            // If Model doest exist, get data from table name
            $dataTypeContent = DB::table($dataType->name)->where('id', $id)->first();
        }

        // Replace relationships' keys for labels and create READ links if a slug is provided.
        $dataTypeContent = $this->resolveRelations($dataTypeContent, $dataType, true);

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'read');

        // Check permission
        $this->authorize('read', $dataTypeContent);

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($dataTypeContent);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'read', $isModelTranslatable);

        $response = $this->overrideSendShowResponse(
            $request,
            $dataTypeContent
        );
        if ($response) {
            return $response;
        }

        $resourceClass = 'joy-voyager-api.json';

        if (app()->bound("joy-voyager-api.$slug.json")) {
            $resourceClass = "joy-voyager-api.$slug.json";
        }

        $resource = app()->make($resourceClass);

        return $resource::make($dataTypeContent)
            ->additional(
                compact(
                    // 'dataType', // @TODO
                    'isModelTranslatable',
                    'isSoftDeleted'
                )
            );
    }

    /**
     * Override send Show response.
     *
     * @param Request $request         Request
     * @param mixed   $dataTypeContent DataTypeContent
     *
     * @return mixed
     */
    protected function overrideSendShowResponse(
        Request $request,
        $dataTypeContent
    ) {
        //
    }
}
