<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;

class StudentBook extends Model
{
    protected $fillable = [
        'student_id',
        'store_id',
        'book_name',
        'book_url',
        'payment_id'
    ];

    public function storeItem()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
