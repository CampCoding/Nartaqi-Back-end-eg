<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * Upload size limits shared by every upload endpoint.
 *
 * PHP must allow at least this much too: upload_max_filesize / post_max_size in public/.user.ini.
 */
class Uploads
{
    /** Largest PDF accepted anywhere, in KB (the unit of Laravel's "max" rule for files): 500 MB. */
    public const PDF_MAX_KB = 512000;

    public const PDF_MAX_MESSAGE = 'حجم ملف الـ PDF يجب ألا يزيد عن 500 ميجابايت';
    public const FILE_MAX_MESSAGE = 'حجم الملف يجب ألا يزيد عن 500 ميجابايت';
    public const UPLOAD_FAILED_MESSAGE = 'فشل رفع الملف، غالباً حجمه أكبر من الحد المسموح على السيرفر';

    /** True for an uploaded file whose content is a PDF (checked from the bytes, not the file name). */
    public static function isPdf($file): bool
    {
        return $file instanceof UploadedFile && $file->isValid() && $file->guessExtension() === 'pdf';
    }
}
