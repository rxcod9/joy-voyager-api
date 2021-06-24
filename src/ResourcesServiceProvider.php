<?php

declare(strict_types=1);

namespace Joy\VoyagerApi;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Joy\VoyagerApi\Http\Resources\Json;
use Joy\VoyagerApi\Http\Resources\JsonCollection;

/**
 * Class VoyagerApiServiceProvider
 *
 * @category  Package
 * @package   JoyVoyagerApi
 * @author    Ramakant Gangwar <gangwar.ramakant@gmail.com>
 * @copyright 2021 Copyright (c) Ramakant Gangwar (https://github.com/rxcod9)
 * @license   http://github.com/rxcod9/joy-voyager-api/blob/main/LICENSE New BSD License
 * @link      https://github.com/rxcod9/joy-voyager-api
 */
class ResourcesServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('joy-voyager-api.json', function ($app) {
            return new Json(null);
        });

        $this->app->bind('joy-voyager-api.json-collection', function ($app) {
            return new JsonCollection([]);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['joy-voyager-api.json', 'joy-voyager-api.json-collection'];
    }
}
