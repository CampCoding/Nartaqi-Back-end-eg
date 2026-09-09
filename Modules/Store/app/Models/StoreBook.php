<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;

class StoreBook extends Model
{
    protected $fillable = ['store_id', 'book_url'];
    
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
