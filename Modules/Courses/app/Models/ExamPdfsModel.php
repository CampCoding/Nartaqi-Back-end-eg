<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Courses\Database\Factories\ExamPdfsModelFactory;

class ExamPdfsModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */

    protected $fillable = [
        'lesson_id',
        'title',
        'description',
        'pdf_url',
        'type',
        'for_type'
    ];
    protected $table = 'exam_pdfs';

    public function lesson()
    {
        return $this->belongsTo(LessonsModel::class, 'lesson_id');
    }

    // protected static function newFactory(): ExamPdfsModelFactory
    // {
    //     // return ExamPdfsModelFactory::new();
    // }
    /**
     * Accessor to return the full URL for the stored PDF path.
     */
    public function getPdfUrlAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // $value is the raw path stored in DB, e.g. "exam_pdfs/file.pdf"
        return asset('storage/' . $value);
    }
}
