<?php

namespace Modules\Courses\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class StudentsResource extends ResourceCollection
{
    /**
     * Disable default data wrapper.
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $paginator = $this->resource; // LengthAwarePaginator

        return [
            'status' => 'success',
            'message' => $this->collection,
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'pages_count' => $paginator->lastPage(),
            ],
        ];
    }
}


