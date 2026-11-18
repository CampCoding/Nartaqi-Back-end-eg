<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;

class QrBookModel extends Model
{
    protected $table = 'qr_books';

    protected $fillable = ['title'];

    public function sections()
    {
        return $this->hasMany(QrSectionModel::class, 'qr_book_id');
    }
}
