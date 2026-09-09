<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;

class StoreImage extends Model
{
    protected $fillable = ['store_id', 'image'];
    
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
