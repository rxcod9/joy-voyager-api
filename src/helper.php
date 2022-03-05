<?php

use Illuminate\Support\Str;

// if (! function_exists('joyVoyagerApi')) {
//     /**
//      * Helper
//      */
//     function joyVoyagerApi($argument1 = null)
//     {
//         //
//     }
// }
if (!function_exists('hasFile')) {
    function hasFile($rows): bool
    {
        return $rows->contains(function ($row, $key) {
            return in_array($row->type, ['image', 'file']);
        });
    }
}

if (!function_exists('modelHasScope')) {
    /**
     * May have html
     *
     * @param Model|string $model
     */
    function modelHasScope($model, string $scope): bool
    {
        return method_exists($model, 'scope' . Str::studly($scope));
    }
}
