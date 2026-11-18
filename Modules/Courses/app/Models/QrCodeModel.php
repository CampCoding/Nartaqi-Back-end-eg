<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class QrCodeModel extends Model
{
    protected $table = 'qr_codes';

    protected $fillable = ['section_id', 'serial', 'name', 'target_url', 'notes', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // qr_url is what the QR image must encode; never the target_url, which is free to change.
    protected $appends = ['qr_url'];

    public function section()
    {
        return $this->belongsTo(QrSectionModel::class, 'section_id');
    }

    public function scans()
    {
        return $this->hasMany(QrScanModel::class, 'qr_code_id');
    }

    public function getQrUrlAttribute(): string
    {
        return static::urlFor($this->serial);
    }

    public static function urlFor(string $serial): string
    {
        return config('qr.base_url') . '/' . $serial;
    }

    public static function cacheKey(string $serial): string
    {
        return 'qr:' . strtoupper($serial);
    }

    public static function forgetCached(string $serial): void
    {
        Cache::store('file')->forget(static::cacheKey($serial));
    }
}
