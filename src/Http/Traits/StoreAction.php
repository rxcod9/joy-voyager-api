<?php

declare(strict_types=1);

namespace Joy\VoyagerApi\Http\Traits;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use TCG\Voyager\Events\BreadDataAdded;
use TCG\Voyager\Facades\Voyager;

trait StoreAction
{
    /**
     * POST BRE(A)D - Store data.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('add', app($dataType->model_name));

        // Validate fields with ajax
        $val = $this->validateBread($request->all(), $dataType->addRows)->validate();

        // Use dry-run only to validate
        if ($request->has('dry-run')) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $data = $this->insertUpdateData($request, $slug, $dataType->addRows, new $dataType->model_name());

        event(new BreadDataAdded($dataType, $data));

        $response = $this->overrideSendStoreResponse(
            $request,
            $data
        );
        if ($response) {
            return $response;
        }

        if (!$request->has('_tagging')) {
            $resourceClass = 'joy-voyager-api.json';

            if (app()->bound("joy-voyager-api.$slug.json")) {
                $resourceClass = "joy-voyager-api.$slug.json";
            }

            $resource = app()->make($resourceClass);

            return $resource::make($data)
                ->additional(
                    [
                        'message' => __('voyager::generic.successfully_added_new')
                            . " {$dataType->getTranslatedAttribute('display_name_singular')}",
                        'alert-type' => 'success',
                        'canBrowse'  => auth()->user()->can(
                            'browse',
                            app($dataType->model_name)
                        ),
                    ]
                );
        } else {
            return response()->json(['success' => true, 'data' => $data]);
        }
    }

    /**
     * Override send Store response.
     *
     * @param Request $request Request
     * @param mixed   $data    Data
     *
     * @return mixed
     */
    protected function overrideSendStoreResponse(
        Request $request,
        $data
    ) {
        //
    }
}
