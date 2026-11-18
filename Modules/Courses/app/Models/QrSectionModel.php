<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;

class QrSectionModel extends Model
{
    protected $table = 'qr_sections';

    protected $fillable = ['qr_book_id', 'title'];

    public function book()
    {
        return $this->belongsTo(QrBookModel::class, 'qr_book_id');
    }

    public function codes()
    {
        return $this->hasMany(QrCodeModel::class, 'section_id');
    }
}
