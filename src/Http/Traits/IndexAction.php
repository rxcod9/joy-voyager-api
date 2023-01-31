<?php

declare(strict_types=1);

namespace Joy\VoyagerApi\Http\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Joy\VoyagerApi\Services\Filter;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Models\DataType;

trait IndexAction
{
    //***************************************
    //               ____
    //              |  _ \
    //              | |_) |
    //              |  _ <
    //              | |_) |
    //              |____/
    //
    //      Browse our Data Type (B)READ
    //
    //****************************************

    public function index(Request $request)
    {
        // GET THE SLUG, ex. 'posts', 'pages', etc.
        $slug = $this->getSlug($request);

        // GET THE DataType based on the slug
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('browse', app($dataType->model_name));

        $getter = 'paginate';

        $orderBy         = $request->get('order_by', $dataType->order_column);
        $sortOrder       = $request->get('sort_order', $dataType->order_direction);
        $usesSoftDeletes = false;
        $showSoftDeleted = false;

        // Next Get or Paginate the actual content from the MODEL that corresponds to the slug DataType
        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);

            $query = $model::select($dataType->name . '.*');

            if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope' . ucfirst($dataType->scope))) {
                $query->{$dataType->scope}();
            }

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model)) && Auth::user()->can('delete', app($dataType->model_name))) {
                $usesSoftDeletes = true;

                if ($request->get('showSoftDeleted')) {
                    $showSoftDeleted = true;
                    $query           = $query->withTrashed();
                }
            }

            // If a column has a relationship associated with it, we do not want to show that field
            $this->removeRelationshipField($dataType, 'browse');

            $row = $dataType->rows->where('field', $orderBy)->firstWhere('type', 'relationship');
            if ($orderBy && (in_array($orderBy, $dataType->fields()) || !empty($row))) {
                $querySortOrder = (!empty($sortOrder)) ? $sortOrder : 'desc';
                if (!empty($row)) {
                    $query->select([
                        $dataType->name . '.*',
                        'joined.' . $row->details->label . ' as ' . $orderBy,
                    ])->leftJoin(
                        $row->details->table . ' as joined',
                        $dataType->name . '.' . $row->details->column,
                        'joined.' . $row->details->key
                    );
                }

                $query->orderBy($orderBy, $querySortOrder);
            } elseif ($model->timestamps) {
                $query->latest($model::CREATED_AT);
            } else {
                $query->orderBy($model->getKeyName(), 'DESC');
            }

            $this->processGlobalSearch($query, $dataType, $request);
            $this->processDataTableFilters($query, $dataType, $request);
            $this->processApiFilters($query, $dataType, $request);

            $dataTypeContent = call_user_func([
                $query,
                $getter,
            ]);

            // Replace relationships' keys for labels and create READ links if a slug is provided.
            $dataTypeContent = $this->resolveRelations($dataTypeContent, $dataType);
        } else {
            // If Model doesn't exist, get data from table name
            $dataTypeContent = call_user_func([DB::table($dataType->name), $getter]);
            $model           = false;
        }

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($model);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'browse', $isModelTranslatable);

        // Check if a default search key is set
        $defaultSearchKey = $dataType->default_search_key ?? null;

        // Actions
        $actions = [];
        if (!empty($dataTypeContent->first())) {
            foreach (Voyager::actions() as $action) {
                $action = new $action($dataType, $dataTypeContent->first());

                if ($action->shouldActionDisplayOnDataType()) {
                    $actions[] = $action;
                }
            }
        }

        // Define showCheckboxColumn
        $showCheckboxColumn = false;
        if (Auth::user()->can('delete', app($dataType->model_name))) {
            $showCheckboxColumn = true;
        } else {
            foreach ($actions as $action) {
                if (method_exists($action, 'massAction')) {
                    $showCheckboxColumn = true;
                }
            }
        }

        // Define orderColumn
        $orderColumn = [];
        if ($orderBy) {
            $index       = $dataType->browseRows->where('field', $orderBy)->keys()->first() + ($showCheckboxColumn ? 1 : 0);
            $orderColumn = [[$index, $sortOrder ?? 'desc']];
        }

        // Define list of columns that can be sorted server side
        $sortableColumns = $this->getSortableColumns($dataType->browseRows);

        $response = $this->overrideSendIndexResponse(
            $request,
            $dataTypeContent
        );
        if ($response) {
            return $response;
        }

        $resourceClass = 'joy-voyager-api.json-collection';

        if (app()->bound("joy-voyager-api.$slug.json-collection")) {
            $resourceClass = "joy-voyager-api.$slug.json-collection";
        }

        $resourceCollection = app()->make($resourceClass);

        $meta = [
            'showCheckboxColumn' => $showCheckboxColumn,
            // 'recordsTotal'       => $unfilteredCount,
            'recordsFiltered' => (
                $dataTypeContent instanceof LengthAwarePaginator
                ? $dataTypeContent->total()
                : (
                    $dataTypeContent instanceof Collection
                    ? $dataTypeContent->count()
                    : count($dataTypeContent)
                )
            ),
        ];

        return $resourceCollection::make($dataTypeContent)
            ->additional(
                compact(
                    'actions',  // @FIXME
                    // 'dataType', // @TODO
                    'isModelTranslatable',
                    // 'search',
                    'orderBy',
                    'orderColumn',
                    'sortableColumns',
                    'sortOrder',
                    // 'searchNames',
                    // 'isServerSide',
                    'defaultSearchKey',
                    'usesSoftDeletes',
                    'showSoftDeleted',
                    'showCheckboxColumn'
                )
            );
    }

    /**
     * Override send Index response.
     *
     * @param Request $request         Request
     * @param mixed   $dataTypeContent DataTypeContent
     *
     * @return mixed
     */
    protected function overrideSendIndexResponse(
        Request $request,
        $dataTypeContent
    ) {
        //
    }

    /**
     * Process Global Search.
     *
     * @param Request $query Request
     * @param mixed   DataType  $dataType
     * @param mixed   Request   $request
     *
     * @return void
     */
    protected function processGlobalSearch(
        $query,
        DataType $dataType,
        Request $request
    ) {
        $searchValue = $request->input('search.value', $request->input('q'));

        if (!$searchValue) {
            return;
        }

        if (modelHasScope($dataType->model_name, 'globalSearch')) {
            $query->scopes([
                Str::camel('globalSearch') => [$searchValue],
            ]);
            return;
        }

        $model = app($dataType->model_name);

        switch ($model->getKeyType()) {
            case 'int':
                $query->whereKey((int) $searchValue);
                break;
            case 'string':
                $query->whereKey($searchValue);
                break;

            default:
                // code...
                break;
        }
    }

    /**
     * Process datatable filters.
     *
     * @param Request $query Request
     * @param mixed   DataType  $dataType
     * @param mixed   Request   $request
     *
     * @return void
     */
    protected function processDataTableFilters(
        $query,
        DataType $dataType,
        Request $request
    ) {
        $dataRows = Voyager::model('DataRow')->whereDataTypeId($dataType->id)->get();
        $columns  = $request->input('columns', []); //.name, .search.value .search.regex

        foreach ($columns as $key => $column) {
            $searchKey   = $request->input('columns.' . $key . '.name');
            $searchValue = $request->input('columns.' . $key . '.search.value');

            if (!($searchValue !== null && $searchValue !== '' && $searchValue !== ',' && $searchValue !== ',,' && $searchValue !== 'null')) {
                continue;
            }

            $row = $dataRows->where('field', $searchKey)->first();

            app(Filter::class)->handle($query, $searchValue, $row, $dataType, $request);
        }
    }

    /**
     * Process filters.
     *
     * @param Request $query Request
     * @param mixed   DataType  $dataType
     * @param mixed   Request   $request
     *
     * @return void
     */
    protected function processApiFilters(
        $query,
        DataType $dataType,
        Request $request
    ) {
        foreach ($dataType->browseRows as $row) {
            $searchValue = $request->input('filters.' . $row->field);

            if (!($searchValue !== null && $searchValue !== '' && $searchValue !== ',' && $searchValue !== ',,' && $searchValue !== 'null')) {
                continue;
            }

            app(Filter::class)->handle($query, $searchValue, $row, $dataType, $request);
        }
    }
}
