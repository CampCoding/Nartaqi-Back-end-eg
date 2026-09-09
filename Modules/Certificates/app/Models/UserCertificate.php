<?php

namespace Modules\Certificates\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Certificates\Database\Factories\UserCertificateFactory;

class UserCertificate extends Model
{
    use HasFactory;
    protected $table = 'student_certificates';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'student_id',
        'application_id',
        'round_id',
        'certification_name',
        'pdf_path',
    ];

    // protected static function newFactory(): UserCertificateFactory
    // {
    //     // return UserCertificateFactory::new();
    // }
}
