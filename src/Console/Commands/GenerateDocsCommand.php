<?php

namespace Joy\VoyagerApi\Console\Commands;

use TCG\Voyager\Models\DataRow;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Schema;
use cebe\openapi\spec\Server;
use cebe\openapi\Writer;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use TCG\Voyager\Facades\Voyager;
use Illuminate\Database\Eloquent\SoftDeletes;

class GenerateDocsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'joy-voyager-api:l5-swagger:generate {documentation?} {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate docs';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->call('l5-swagger:generate');

        $this->generateVoyagerDocsJson();

        $this->mergePaths();
    }

    /**
     * Generate voyager docs json.
     *
     * @return void
     */
    protected function generateVoyagerDocsJson()
    {
        $this->comment('Generating voyager docs json');

        $path = config('l5-swagger.defaults.paths.docs') .
            DIRECTORY_SEPARATOR . config('l5-swagger.documentations.default.paths.joy_voyager_api_docs_json');

        $requestBodies = $this->requestBodies();
        $schemas       = $this->schemas();
        $paths         = $this->paths();

        // store base API Description
        $openapi = new OpenApi([
            'openapi' => '3.0.2',
            'info'    => [
                'title'       => config('app.name', 'Joy Voyager Api'),
                'description' => 'Joy VoyagerApi module adds REST Api end points to Voyager with Passport and Swagger support https://github.com/rxcod9/joy-voyager-api.',
                'version'     => '1.0.0',
            ],
            'servers' => [
                new Server([
                    'url'         => config('l5-swagger.defaults.constants.L5_SWAGGER_CONST_HOST'),
                    'description' => 'Primary API Server',
                ]),
            ],
            'paths'      => $paths,
            'components' => [
                'requestBodies' => $requestBodies,
                'schemas'       => $schemas,
            ],
        ]);

        Writer::writeToJsonFile($openapi, $path);
    }

    /**
     * Get Paths.
     */
    protected function paths(): array
    {
        $paths = [];

        foreach (Voyager::model('DataType')::all() as $dataType) {
            $this->resourcePaths($paths, $dataType);
        }

        return $paths;
    }

    /**
     * Get Resource Paths.
     */
    protected function resourcePaths(
        &$paths,
        $dataType
    ): void {
        $updateMethod = 'put';

        if (hasFile($dataType->editRows)) {
            $updateMethod = 'post';
        }

        $paths['/api/' . $dataType->slug] = new PathItem([
            'get'  => $this->indexOperation($dataType),
            'post' => $this->storeOperation($dataType),
        ]);
        $paths['/api/' . $dataType->slug . '/create'] = new PathItem([
            'get' => $this->createOperation($dataType),
        ]);
        $paths['/api/' . $dataType->slug . '/{id}'] = new PathItem([
            'get'         => $this->showOperation($dataType),
            $updateMethod => $this->updateOperation($dataType),
            'delete'      => $this->deleteOperation($dataType),
        ]);
        $paths['/api/' . $dataType->slug . '/{id}/single/{field}'] = new PathItem([
            $updateMethod => $this->singleUpdateOperation($dataType),
        ]);
        $paths['/api/' . $dataType->slug . '/{id}/edit'] = new PathItem([
            'get' => $this->editOperation($dataType),
        ]);
        $paths['/api/' . $dataType->slug . '/relation'] = new PathItem([
            'get' => $this->relationOperation($dataType),
        ]);
        $paths['/api/' . $dataType->slug . '/{id}/restore'] = new PathItem([
            'get' => $this->restoreOperation($dataType),
        ]);
    }

    /**
     * Index operation.
     */
    protected function indexOperation($dataType): Operation
    {
        $name       = $dataType->name;
        $browseName = 'Voyager' . Str::studly($dataType->name) . 'BrowseResource';

        return new Operation([
            'tags' => array_filter([
                $dataType->slug
            ]),
            'summary'     => $name . ' index',
            'operationId' => Str::snake($name) . '_index',
            'parameters'  => $this->indexParameters($dataType),
            'responses'   => [
                200 => [
                    'description' => 'Success',
                    'content'     => [
                        'application/json' => [
                            'schema' => [
                                'properties' => [
                                    'data' => [
                                        'type'  => 'array',
                                        'items' => [
                                            '$ref' => '#/components/schemas/' . $browseName,
                                        ],
                                    ],
                                    'links' => [
                                        '$ref' => '#/components/schemas/Links',
                                    ],
                                    'meta' => [
                                        '$ref' => '#/components/schemas/Meta',
                                    ],
                                ],
                                'type' => 'object',
                            ],
                        ],
                    ],
                ],
                401 => [
                    'description' => 'Unauthenticated',
                ],
                403 => [
                    'description' => 'Forbidden',
                ],
            ],
            'security' => [
                [
                    'token' => [],
                ],
            ],
        ]);
    }

    /**
     * Create operation.
     */
    protected function createOperation($dataType): Operation
    {
        $name       = $dataType->name;
        $browseName = 'Voyager' . Str::studly($dataType->name) . 'CreateResource';

        return new Operation([
            'tags' => array_filter([
                $dataType->slug
            ]),
            'summary'     => $name . ' create',
            'operationId' => Str::snake($name) . '_create',
            // 'parameters'  => $this->createParameters($dataType),
            'responses' => [
                200 => [
                    'description' => 'Success',
                    'content'     => [
                        'application/json' => [
                            'schema' => [
                                'properties' => [
                                    'data' => [],
                                ],
                                'type' => 'object',
                            ],
                        ],
                    ],
                ],
                401 => [
                    'description' => 'Unauthenticated',
                ],
                403 => [
                    'description' => 'Forbidden',
                ],
            ],
            'security' => [
                [
                    'token' => [],
                ],
            ],
        ]);
    }

    /**
     * Index paramaters.
     */
    protected function indexParameters($dataType): array
    {
        $parameters = [];

        $parameters['q'] = [
            'name'        => 'q',
            'in'          => 'query',
            'required'    => false,
            'description' => 'Global search',
            'schema'      => [
                'type' => 'string',
            ],
        ];

        $parameters['page'] = [
            'name'     => 'page',
            'in'       => 'query',
            'required' => false,
            'schema'   => [
                'type' => 'integer',
            ],
        ];

        $parameters['length'] = [
            'name'     => 'length',
            'in'       => 'query',
            'required' => false,
            'schema'   => [
                'type' => 'integer',
            ],
        ];

        $parameters['order_by'] = [
            'name'        => 'order_by',
            'in'          => 'query',
            'required'    => false,
            'description' => 'Sort by column',
            'schema'      => [
                'type' => 'string',
                'enum' => getDataTypeSortableColumns($dataType),
            ],
        ];

        $parameters['sort_order'] = [
            'name'        => 'sort_order',
            'in'          => 'query',
            'required'    => false,
            'description' => 'Sort by direction',
            'schema'      => [
                'type' => 'string',
                'enum' => ['asc', 'desc'],
            ],
        ];

        if (
            strlen($dataType->model_name) != 0 &&
            $dataType->model_name &&
            in_array(SoftDeletes::class, class_uses_recursive($dataType->model_name))
        ) {
            $parameters['showSoftDeleted'] = [
                'name'     => 'showSoftDeleted',
                'in'       => 'query',
                'required' => false,
                'schema'   => [
                    'type' => 'boolean',
                ],
            ];
        }

        $this->filterParemeters($parameters, $dataType);

        return $parameters;
    }

    /**
     * Show operation.
     */
    protected function showOperation($dataType): Operation
    {
        $parameters = [];
        $name       = $dataType->slug;
        $readName   = 'Voyager' . Str::studly($dataType->name) . 'ReadResource';

        $parameters[] = [
            'name'     => 'id',
            'in'       => 'path',
            'required' => true,
            'schema'   => [
                'type' => 'integer',
            ],
        ];

        return new Operation([
            'tags' => array_filter([
                $dataType->slug
            ]),
            'summary'     => $name . ' show',
            'operationId' => Str::snake($name) . '_show',
            'parameters'  => $parameters,
            'responses'   => [
                200 => [
                    'description' => 'Success',
                    'content'     => [
                        'application/json' => [
                            'schema' => [
                                'properties' => [
                                    'data' => [
                                        '$ref' => '#/components/schemas/' . $readName,
                                    ],
                                ],
                                'type' => 'object',
                            ],
                        ],
                    ],
                ],
                401 => [
                    'description' => 'Unauthenticated',
                ],
                403 => [
                    'description' => 'Forbidden',
                ],
            ],
            'security' => [
                [
                    'token' => [],
                ],
            ],
        ]);
    }

    /**
     * Edit operation.
     */
    protected function editOperation($dataType): Operation
    {
        $parameters = [];
        $name       = $dataType->slug;
        $editName   = 'Voyager' . Str::studly($dataType->name) . 'ReadResource';

        $parameters[] = [
            'name'     => 'id',
            'in'       => 'path',
            'required' => true,
            'schema'   => [
                'type' => 'integer',
            ],
        ];

        return new Operation([
            'tags' => array_filter([
                $dataType->slug
            ]),
            'summary'     => $name . ' edit',
            'operationId' => Str::snake($name) . '_edit',
            'parameters'  => $parameters,
            'responses'   => [
                200 => [
                    'description' => 'Success',
                    'content'     => [
                        'application/json' => [
                            'schema' => [
                                'properties' => [
                                    'data' => [
                                        '$ref' => '#/components/schemas/' . $editName,
                                    ],
                                ],
                                'type' => 'object',
                            ],
                        ],
                    ],
                ],
                401 => [
                    'description' => 'Unauthenticated',
                ],
                403 => [
                    'description' => 'Forbidden',
                ],
            ],
            'security' => [
                [
                    'token' => [],
                ],
            ],
        ]);
    }

    /**
     * Create operation.
     */
    protected function storeOperation($dataType): Operation
    {
        $parameters = [];
        $name       = $dataType->slug;
        $readName   = 'Voyager' . Str::studly($dataType->name) . 'ReadResource';
        $storeName  = 'Voyager' . Str::studly($dataType->name) . 'StoreRequest';

        return new Operation([
            'tags' => array_filter([
                $dataType->slug
            ]),
            'summary'     => $name . ' store',
            'operationId' => Str::snake($name) . '_store',
            'requestBody' => [
                '$ref' => '#/components/requestBodies/' . $storeName,
            ],
            'responses' => [
                200 => [
                    'description' => 'Success',
                    'content'     => [
                        'application/json' => [
                            'schema' => [
                                'properties' => [
                                    'data' => [
                                        '$ref' => '#/components/schemas/' . $readName,
                                    ],
                                ],
                                'type' => 'object',
                            ],
                        ],
                    ],
                ],
                401 => [
                    'description' => 'Unauthenticated',
                ],
                403 => [
                    'description' => 'Forbidden',
                ],
            ],
            'security' => [
                [
                    'token' => [],
                ],
            ],
        ]);
    }

    /**
     * Update operation.
     */
    protected function updateOperation($dataType): Operation
    {
        $parameters = [];
        $name       = $dataType->slug;
        $readName   = 'Voyager' . Str::studly($dataType->name) . 'ReadResource';
        $updateName = 'Voyager' . Str::studly($dataType->name) . 'UpdateRequest';

        $parameters[] = [
            'name'     => 'id',
            'in'       => 'path',
            'required' => true,
            'schema'   => [
                'type' => 'integer',
            ],
        ];

        return new Operation([
            'tags' => array_filter([
                $dataType->slug
            ]),
            'summary'     => $name . ' update',
            'operationId' => Str::snake($name) . '_update',
            'parameters'  => $parameters,
            'requestBody' => [
                '$ref' => '#/components/requestBodies/' . $updateName,
            ],
            'responses' => [
                200 => [
                    'description' => 'Success',
                    'content'     => [
                        'application/json' => [
                            'schema' => [
                                'properties' => [
                                    'data' => [
                                        '$ref' => '#/components/schemas/' . $readName,
                                    ],
                                ],
                                'type' => 'object',
                            ],
                        ],
                    ],
                ],
                401 => [
                    'description' => 'Unauthenticated',
                ],
                403 => [
                    'description' => 'Forbidden',
                ],
            ],
            'security' => [
                [
                    'token' => [],
                ],
            ],
        ]);
    }

    /**
     * Single Update operation.
     */
    protected function singleUpdateOperation($dataType): Operation
    {
        $parameters       = [];
        $name             = $dataType->slug;
        $readName         = 'Voyager' . Str::studly($dataType->name) . 'ReadResource';
        $singleUpdateName = 'Voyager' . Str::studly($dataType->name) . 'SingleUpdateRequest';

        $parameters[] = [
            'name'     => 'id',
            'in'       => 'path',
            'required' => true,
            'schema'   => [
                'type' => 'integer',
            ],
        ];

        $parameters[] = [
            'name'     => 'field',
            'in'       => 'path',
            'required' => false,
            'schema'   => [
                'type'     => 'string',
                'nullable' => true,
                'enum'     => $dataType->editRows->pluck('field'),
            ],
        ];

        $parameters[] = [
            'name'     => 'fields[]',
            'in'       => 'query',
            'required' => false,
            'schema'   => [
                'type'  => 'array',
                'items' => [
                    'type' => 'string',
                    'enum' => $dataType->editRows->pluck('field'),
                ],
            ],
        ];

        return new Operation([
            'tags' => array_filter([
                $dataType->slug
            ]),
            'summary'     => $name . ' single update',
            'operationId' => Str::snake($name) . '_single_update',
            'parameters'  => $parameters,
            'requestBody' => [
                '$ref' => '#/components/requestBodies/' . $singleUpdateName,
            ],
            'responses' => [
                200 => [
                    'description' => 'Success',
                    'content'     => [
                        'application/json' => [
                            'schema' => [
                                'properties' => [
                                    'data' => [
                                        '$ref' => '#/components/schemas/' . $readName,
                                    ],
                                ],
                                'type' => 'object',
                            ],
                        ],
                    ],
                ],
                401 => [
                    'description' => 'Unauthenticated',
                ],
                403 => [
                    'description' => 'Forbidden',
                ],
            ],
            'security' => [
                [
                    'token' => [],
                ],
            ],
        ]);
    }

    /**
     * Delete operation.
     */
    protected function deleteOperation($dataType): Operation
    {
        $parameters = [];
        $name       = $dataType->slug;
        $readName   = 'Voyager' . Str::studly($dataType->name) . 'ReadResource';

        $parameters[] = [
            'name'     => 'id',
            'in'       => 'path',
            'required' => true,
            'schema'   => [
                'type' => 'integer',
            ],
        ];

        return new Operation([
            'tags' => array_filter([
                $dataType->slug
            ]),
            'summary'     => $name . ' delete',
            'operationId' => Str::snake($name) . '_delete',
            'parameters'  => $parameters,
            'responses'   => [
                200 => [
                    'description' => 'Success',
                    'content'     => [
                        'application/json' => [
                            'schema' => [
                                'properties' => [
                                    'message' => [
                                        'type' => 'string',
                                    ],
                                ],
                                'type' => 'object',
                            ],
                        ],
                    ],
                ],
                401 => [
                    'description' => 'Unauthenticated',
                ],
                403 => [
                    'description' => 'Forbidden',
                ],
            ],
            'security' => [
                [
                    'token' => [],
                ],
            ],
        ]);
    }

    /**
     * Restore operation.
     */
    protected function restoreOperation($dataType): Operation
    {
        $parameters = [];
        $name       = $dataType->slug;
        $readName   = 'Voyager' . Str::studly($dataType->name) . 'ReadResource';

        $parameters[] = [
            'name'     => 'id',
            'in'       => 'path',
            'required' => true,
            'schema'   => [
                'type' => 'integer',
            ],
        ];

        return new Operation([
            'tags' => array_filter([
                $dataType->slug
            ]),
            'summary'     => $name . ' restore',
            'operationId' => Str::snake($name) . '_restore',
            'parameters'  => $parameters,
            'responses'   => [
                200 => [
                    'description' => 'Success',
                    'content'     => [
                        'application/json' => [
                            'schema' => [
                                'properties' => [
                                    'message' => [
                                        'type' => 'string',
                                    ],
                                ],
                                'type' => 'object',
                            ],
                        ],
                    ],
                ],
                401 => [
                    'description' => 'Unauthenticated',
                ],
                403 => [
                    'description' => 'Forbidden',
                ],
            ],
            'security' => [
                [
                    'token' => [],
                ],
            ],
        ]);
    }

    /**
     * Relation operation.
     */
    protected function relationOperation($dataType): Operation
    {
        $parameters = [];
        $slug       = $dataType->slug;
        $name       = $dataType->slug;

        $parameters[] = [
            'name'     => 'type',
            'in'       => 'query',
            'required' => true,
            'schema'   => [
                'type' => 'string',
            ],
        ];

        $parameters[] = [
            'name'     => 'method',
            'in'       => 'query',
            'required' => false,
            'schema'   => [
                'type' => 'string',
            ],
        ];

        $parameters[] = [
            'name'     => 'required',
            'in'       => 'query',
            'required' => false,
            'schema'   => [
                'type' => 'boolean',
            ],
        ];

        $parameters[] = [
            'name'     => 'page',
            'in'       => 'query',
            'required' => false,
            'schema'   => [
                'type' => 'integer',
            ],
        ];

        return new Operation([
            // 'get' => [
            'tags' => array_filter([
                $dataType->slug
            ]),
            'summary'     => $name . ' relation',
            'operationId' => Str::snake($name) . '_relation',
            'parameters'  => $parameters,
            'responses'   => [
                200 => [
                    'description' => 'Success',
                    'content'     => [
                        'application/json' => [],
                    ],
                ],
                401 => [
                    'description' => 'Unauthenticated',
                ],
                403 => [
                    'description' => 'Forbidden',
                ],
            ],
            'security' => [
                [
                    'token' => [],
                ],
            ],
            // ],
        ]);
    }

    /**
     * Get requestBodies.
     */
    protected function requestBodies(): array
    {
        $requestBodies = [];

        foreach (Voyager::model('DataType')::all() as $dataType) {
            $this->resourceRegisterBodies($requestBodies, $dataType);
        }

        return $requestBodies;
    }

    /**
     * Get RegisterBodies.
     */
    protected function resourceRegisterBodies(
        &$requestBodies,
        $dataType
    ): void {
        $storeName        = 'Voyager' . Str::studly($dataType->name) . 'StoreRequest';
        $updateName       = 'Voyager' . Str::studly($dataType->name) . 'UpdateRequest';
        $singleUpdateName = 'Voyager' . Str::studly($dataType->name) . 'SingleUpdateRequest';

        $requestBodies[$storeName]        = $this->requestBody('add', $dataType);
        $requestBodies[$updateName]       = $this->requestBody('edit', $dataType);
        $requestBodies[$singleUpdateName] = $this->singleRequestBody($dataType);
    }

    /**
     * Get Request Body.
     */
    protected function requestBody(
        string $updateOrCreate,
        $dataType
    ): RequestBody {
        $properties = [
            'dry-run' => [
                'description' => 'Use dry-run only to validate',
                'type'        => 'boolean',
            ]
        ];

        $enctype = 'application/x-www-form-urlencoded';

        if (hasFile($dataType->{$updateOrCreate . 'Rows'})) {
            $enctype = 'multipart/form-data';
        }

        foreach ($dataType->{$updateOrCreate . 'Rows'} as $row) {
            $property = $this->getProperty($row);

            $properties[$row->field] = $property;
        }

        return new RequestBody([
            'required' => true,
            'content'  => [
                $enctype => [
                    'schema' => [
                        'properties' => $properties,
                        'type'       => 'object',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Get Single Request Body.
     */
    protected function singleRequestBody(
        $dataType
    ): RequestBody {
        $properties = [
            'dry-run' => [
                'description' => 'Use dry-run only to validate',
                'type'        => 'boolean',
            ],
        ];

        $enctype = 'application/x-www-form-urlencoded';

        if (hasFile($dataType->editRows)) {
            $enctype = 'multipart/form-data';
        }

        foreach ($dataType->editRows as $row) {
            $property = $this->getProperty($row);

            $properties[$row->field] = $property;
        }

        return new RequestBody([
            'required' => true,
            'content'  => [
                $enctype => [
                    'schema' => [
                        'properties' => $properties,
                        'type'       => 'object',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Get Property.
     */
    protected function getProperty(
        DataRow $row
    ): array {
        $property = [
            'description' => $row->display_name,
            'type'        => 'string',
        ];

        switch ($row->type) {
            case 'text':
                // code...
                break;

            case 'image':
                $property['format']      = 'binary';
                $property['description'] = 'jpg,jpeg,png';
                break;

            case 'password':
                $property['type'] = 'password';
                break;

            case 'select_dropdown':
                $property['description'] = json_encode($row->details->options ?? [], JSON_PRETTY_PRINT);
                $property['enum']        = array_keys((array) $row->details->options ?? []);
                break;

            case 'hidden':
                // code...
                break;

            case 'number':
                $property['type'] = 'integer';
                break;

            case 'timestamp':
                $property['type']    = 'date-time';
                $property['pattern'] = '/([0-9]{4})-(?:[0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2})/';
                $property['example'] = '2021-05-17 00:00';
                break;

            case 'relationship':
                $property['type'] = 'integer';
                break;

            case 'select_multiple':
                // code...
                break;

            case 'rich_text_box':
                // code...
                break;

            case 'date':
                $property['type']    = 'date';
                $property['pattern'] = '/([0-9]{4})-(?:[0-9]{2})-([0-9]{2})/';
                $property['example'] = '2021-05-17';
                break;

            case 'checkbox':
                // code...
                break;

            case 'text_area':
                // code...
                break;

            case 'code_editor':
                // code...
                break;

            case 'file':
                $property['format']      = 'binary';
                $property['description'] = 'doc,docx,pdf';
                break;

            default:
                // code...
                break;
        }

        return $property;
    }

    /**
     * Get schemas.
     */
    protected function schemas(): array
    {
        $schemas = [];

        foreach (Voyager::model('DataType')::all() as $dataType) {
            $this->resourceSchema($schemas, $dataType);
        }

        return $schemas;
    }

    /**
     * Get Schema.
     */
    protected function resourceSchema(
        &$schemas,
        $dataType
    ): void {
        $browseName           = 'Voyager' . Str::studly($dataType->name) . 'BrowseResource';
        $readName             = 'Voyager' . Str::studly($dataType->name) . 'ReadResource';
        $filterName           = 'Voyager' . Str::studly($dataType->name) . 'FilterRequest';
        $schemas[$browseName] = $this->schema('browse', $dataType);
        $schemas[$readName]   = $this->schema('read', $dataType);
    }

    /**
     * Get Schema.
     */
    protected function schema(
        string $browseOrShow,
        $dataType
    ): Schema {
        $properties = [];

        foreach ($dataType->{$browseOrShow . 'Rows'} as $row) {
            $property = [
                'description' => $row->display_name,
                'type'        => 'string',
            ];

            switch ($row->type) {
                case 'text':
                    // code...
                    break;

                case 'image':
                    $property['format']      = 'binary';
                    $property['description'] = 'jpg,jpeg,png';
                    break;

                case 'password':
                    $property['type'] = 'password';
                    break;

                case 'select_dropdown':
                    $property['description'] = json_encode($row->details->options ?? [], JSON_PRETTY_PRINT);
                    $property['enum']        = array_keys((array) $row->details->options ?? []);
                    break;

                case 'hidden':
                    // code...
                    break;

                case 'number':
                    $property['type'] = 'integer';
                    break;

                case 'timestamp':
                    $property['type']    = 'date-time';
                    $property['pattern'] = '/([0-9]{4})-(?:[0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2})/';
                    $property['example'] = '2021-05-17 00:00';
                    break;

                case 'relationship':
                    $property['type'] = 'integer';
                    break;

                case 'select_multiple':
                    // code...
                    break;

                case 'rich_text_box':
                    // code...
                    break;

                case 'date':
                    $property['type']    = 'date';
                    $property['pattern'] = '/([0-9]{4})-(?:[0-9]{2})-([0-9]{2})/';
                    $property['example'] = '2021-05-17';
                    break;

                case 'checkbox':
                    // code...
                    break;

                case 'text_area':
                    // code...
                    break;

                case 'code_editor':
                    // code...
                    break;

                case 'file':
                    $property['format']      = 'binary';
                    $property['description'] = 'doc,docx,pdf';
                    break;

                default:
                    // code...
                    break;
            }

            $properties[$row->field] = $property;
        }

        return new Schema([
            'properties' => $properties,
            'type'       => 'object',
        ]);
    }

    /**
     * Get Paremeters.
     */
    protected function filterParemeters(
        array &$parameters,
        $dataType
    ): void {
        foreach ($dataType->{'browseRows'} as $row) {
            if (in_array($row->type, config('voyager.bread.column_filters.type_hidden', []))) {
                continue;
            }

            if (
                $row->type === 'relationship' &&
                $row->details->type !== 'belongsToMany' &&
                $row->details->type !== 'belongsToJson'
            ) {
                $parameters[@$row->details->column] = $this->filterRowParemeter($row);
                continue;
            }

            $parameters[$row->field] = $this->filterRowParemeter($row);
        }
    }

    /**
     * Get Row Paremeter.
     */
    protected function filterRowParemeter(
        DataRow $row
    ): array {
        $parameter = [
            'name'     => 'filters[' . $row->field . ']',
            'in'       => 'query',
            'required' => false,
            'schema'   => [
                'type' => 'string',
            ],
        ];

        switch ($row->type) {
            case 'text':
                // code...
                break;

            case 'select_dropdown':
                $parameter['name']                    = 'filters[' . $row->field . '][]';
                $parameter['schema']['type']          = 'array';
                $parameter['schema']['items']         = [];
                $parameter['schema']['items']['type'] = 'string';
                $parameter['schema']['items']['enum'] = array_keys((array) $row->details->options ?? []);
                // $parameter['explode']    = false;
                $parameter['description'] = json_encode($row->details->options ?? [], JSON_PRETTY_PRINT);
                break;

            case 'hidden':
                // code...
                break;

            case 'number':
                $parameter['schema']['type']    = 'string';
                $parameter['schema']['example'] = '10,,20';
                break;

            case 'timestamp':
                $parameter['schema']['type']    = 'string';
                $parameter['schema']['example'] = '2020-01-01 00:00,,2021-06-01 00:00';
                break;

            case 'relationship':
                $parameter['name'] = 'filters[' . @$row->field . '][]';
                if (
                    $row->type === 'relationship' &&
                    $row->details->type !== 'belongsToMany' &&
                    $row->details->type !== 'belongsToJson'
                ) {
                    $parameter['name'] = 'filters[' . @$row->details->column . '][]';
                }
                $parameter['schema']['type']          = 'array';
                $parameter['schema']['items']         = [];
                $parameter['schema']['items']['type'] = 'integer';
                break;

            case 'select_multiple':
                // code...
                break;

            case 'rich_text_box':
                // code...
                break;

            case 'date':
                $parameter['schema']['type']    = 'string';
                $parameter['schema']['example'] = '2020-01-01,,2021-06-01';
                break;

            case 'checkbox':
                // code...
                break;

            case 'text_area':
                // code...
                break;

            case 'code_editor':
                // code...
                break;

            case 'file':
                $parameter['schema']['format']      = 'binary';
                $parameter['schema']['description'] = 'doc,docx,pdf';
                break;

            default:
                // code...
                break;
        }

        return $parameter;
    }

    /**
     * Merge paths json.
     *
     * @return void
     */
    protected function mergePaths()
    {
        $this->comment('Merging voyager docs and api docs json');

        $apiDocsPath = config('l5-swagger.defaults.paths.docs') .
            DIRECTORY_SEPARATOR . config('l5-swagger.documentations.default.paths.docs_json');
        $voyagerApiDocsPath = config('l5-swagger.defaults.paths.docs') .
            DIRECTORY_SEPARATOR . config('l5-swagger.documentations.default.paths.joy_voyager_api_docs_json');

        $apiDocs        = file_get_contents($apiDocsPath);
        $voyagerApiDocs = file_get_contents($voyagerApiDocsPath);

        $apiDocsData        = json_decode($apiDocs, true);
        $voyagerApiDocsData = json_decode($voyagerApiDocs, true);

        $apiDocsData['paths'] = array_merge(
            $apiDocsData['paths'],
            $voyagerApiDocsData['paths']
        );

        $apiDocsData['components']['schemas'] = array_merge(
            $apiDocsData['components']['schemas'] ?? [],
            $voyagerApiDocsData['components']['schemas'] ?? []
        );

        $apiDocsData['components']['requestBodies'] = array_merge(
            $apiDocsData['components']['requestBodies'] ?? [],
            $voyagerApiDocsData['components']['requestBodies'] ?? []
        );

        file_put_contents($apiDocsPath, json_encode($apiDocsData, JSON_PRETTY_PRINT));
    }
}
