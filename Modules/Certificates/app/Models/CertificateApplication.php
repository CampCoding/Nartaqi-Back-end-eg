<?php

namespace Modules\Certificates\Models;

use Modules\Courses\Models\Rounds;
use Illuminate\Database\Eloquent\Model;
use Modules\Authentication\Models\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Certificates\Database\Factories\CertificateFactory;

class CertificateApplication extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'student_id',
        'round_id',
        'name',
        'national_id',
        'nationality',
        'phone',
        'email',
        'status',
    ];

    // protected static function newFactory(): CertificateFactory
    // {
    //     // return CertificateFactory::new();
    // }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function round()
    {
        return $this->belongsTo(Rounds::class, 'round_id');
    }
}
