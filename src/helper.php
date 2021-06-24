<?php

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
