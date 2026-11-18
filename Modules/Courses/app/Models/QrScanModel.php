<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;

class QrScanModel extends Model
{
    protected $table = 'qr_scans';

    public $timestamps = false;

    protected $fillable = ['qr_code_id', 'scanned_at', 'user_agent'];

    protected $casts = [
        'scanned_at' => 'datetime',
    ];
}
