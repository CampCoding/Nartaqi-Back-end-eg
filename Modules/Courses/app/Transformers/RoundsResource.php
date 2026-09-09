<?php

namespace Modules\Courses\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class RoundsResource extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     */
    public static $wrap = null;
    public function toArray(Request $request): array
    {
        $paginator = $this->resource; // LengthAwarePaginator

        return [
            'status'=>'success',
            'message' => $this->collection,
             'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'pages_count' => $paginator->lastPage()

            ],
        ];
    }
}
