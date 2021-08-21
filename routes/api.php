<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use TCG\Voyager\Events\Routing;
use TCG\Voyager\Events\RoutingAdmin;
use TCG\Voyager\Events\RoutingAdminAfter;
use TCG\Voyager\Events\RoutingAfter;
use TCG\Voyager\Facades\Voyager;

/*
|--------------------------------------------------------------------------
| Voyager API Routes
|--------------------------------------------------------------------------
|
| This file is where you may override any of the routes that are included
| with VoyagerApi.
|
*/

Route::group(['as' => 'joy-voyager-api.'], function () {
    // event(new Routing()); @deprecated

    $namespacePrefix = '\\' . config('joy-voyager-api.controllers.namespace') . '\\';

    Route::group(['middleware' => 'auth:api'], function () use ($namespacePrefix) {
        // event(new RoutingAdmin()); @deprecated

        try {
            foreach (Voyager::model('DataType')::all() as $dataType) {
                // api_controller
                $breadController = $dataType->api_controller
                                 ? Str::start($dataType->api_controller, '\\')
                                 : $namespacePrefix . 'VoyagerBaseController';

                Route::post($dataType->slug . '/action', $breadController . '@action')->name($dataType->slug . '.action');
                Route::get($dataType->slug . '/{id}/restore', $breadController . '@restore')->name($dataType->slug . '.restore');
                Route::get($dataType->slug . '/relation', $breadController . '@relation')->name($dataType->slug . '.relation');
                Route::post($dataType->slug . '/remove', $breadController . '@remove_media')->name($dataType->slug . '.media.remove');
                Route::post($dataType->slug . '/{id}', $breadController . '@update')->name($dataType->slug . '.postUpdate');
                Route::addRoute(['post', 'put'], $dataType->slug . '/{id}/single/{field?}', $breadController . '@singleUpdate')->name($dataType->slug . '.single-update');
                Route::resource($dataType->slug, $breadController, ['parameters' => [$dataType->slug => 'id']]);
            }
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException("Custom routes hasn't been configured because: " . $e->getMessage(), 1);
        } catch (\Exception $e) {
            // do nothing, might just be because table not yet migrated.
        }

        // event(new RoutingAdminAfter()); @deprecated
    });

    // event(new RoutingAfter()); @deprecated
});
