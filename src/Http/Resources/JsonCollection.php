<?php

declare(strict_types=1);

namespace Joy\VoyagerApi\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *    schema="Links",
 *    @OA\Property(
 *       property="first",
 *       type="string",
 *       description="First page api link",
 *       example="http://localhost:8000/api/users?page=1"
 *    ),
 *    @OA\Property(
 *       property="last",
 *       type="string",
 *       description="Last page api link",
 *       example="http://localhost:8000/api/users?page=10"
 *    ),
 *    @OA\Property(
 *       property="prev",
 *       type="string",
 *       description="revious page api link",
 *       example=null
 *    ),
 *    @OA\Property(
 *       property="next",
 *       type="string",
 *       description="Next page api link",
 *       example="http://localhost:8000/api/users?page=2"
 *    ),
 * )
 *
 * @OA\Schema(
 *    schema="Meta",
 *    @OA\Property(
 *       property="current_page",
 *       type="integer",
 *       description="Current page",
 *       example=1
 *    ),
 *    @OA\Property(
 *       property="from",
 *       type="integer",
 *       description="From Offset",
 *       example=1
 *    ),
 *    @OA\Property(
 *       property="last_page",
 *       type="integer",
 *       description="Last page",
 *       example=10
 *    ),
 *    @OA\Property(
 *       property="path",
 *       type="string",
 *       description="Current api path",
 *       example="http://localhost:8000/api/users?page=2"
 *    ),
 *    @OA\Property(
 *       property="per_page",
 *       type="integer",
 *       description="Per page length",
 *       example=10
 *    ),
 *    @OA\Property(
 *       property="to",
 *       type="integer",
 *       description="To Offset",
 *       example=10
 *    ),
 *    @OA\Property(
 *       property="total",
 *       type="integer",
 *       description="Total items",
 *       example=100
 *    ),
 *    @OA\Property(
 *       property="showCheckboxColumn",
 *       type="boolean",
 *       description="True if user has bulk delete/action persmission",
 *       example=false
 *    ),
 *    @OA\Property(
 *       property="recordsTotal",
 *       type="integer",
 *       description="Total items",
 *       example=100
 *    ),
 *    @OA\Property(
 *       property="recordsFiltered",
 *       type="integer",
 *       description="Total filtered items",
 *       example=100
 *    ),
 * )
 */

class JsonCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array
     */
    public function toArray($request): array
    {
        return parent::toArray($request);
    }
}
