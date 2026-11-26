<?php

namespace App\Support;

use App\Exceptions\UploadFailedException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToCreateDirectory;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Throwable;

/**
 * Upload size limits shared by every upload endpoint, and the reason an upload failed.
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

    // ---------------------------------------------------------------- why an upload failed

    /**
     * Why PHP did not receive a sent file (too big, cut off, server temp folder...). Null when it arrived fine.
     */
    public static function receiveError(UploadedFile $file, ?string $field = null, ?Request $request = null): ?UploadFailedException
    {
        $code = $file->getError();
        if ($code === UPLOAD_ERR_OK) {
            return null;
        }

        $name = '«' . $file->getClientOriginalName() . '»';
        $requestSize = $request?->server('CONTENT_LENGTH');

        [$reason, $status, $message] = match ($code) {
            UPLOAD_ERR_INI_SIZE => ['file_too_large_for_server', 422,
                "حجم الملف {$name} أكبر من الحد المسموح لرفع ملف واحد على السيرفر (" . self::mb(self::iniBytes('upload_max_filesize')) . ')'
                . ($requestSize ? '، وحجم الطلب المرسل حوالي ' . self::mb((int) $requestSize) : '') . '.'],
            UPLOAD_ERR_FORM_SIZE => ['file_too_large_for_form', 422,
                "حجم الملف {$name} أكبر من الحد المحدد في الفورم (MAX_FILE_SIZE)."],
            UPLOAD_ERR_PARTIAL => ['partial_upload', 422,
                "تم استلام جزء من الملف {$name} فقط: غالباً انقطع الاتصال أثناء الرفع أو استغرق الرفع وقتاً أطول من المسموح. حاول مرة أخرى."],
            UPLOAD_ERR_NO_TMP_DIR => ['server_no_tmp_dir', 500,
                "تعذر استلام الملف {$name}: لا يوجد مجلد مؤقت لاستقبال الملفات على السيرفر (upload_tmp_dir)، وهذه مشكلة في إعدادات السيرفر."],
            UPLOAD_ERR_CANT_WRITE => ['server_tmp_write_failed', 500,
                "تعذر استلام الملف {$name}: فشل حفظه المؤقت على قرص السيرفر"
                . self::freeSpaceNote(ini_get('upload_tmp_dir') ?: sys_get_temp_dir()) . '.'],
            UPLOAD_ERR_EXTENSION => ['server_extension_blocked', 500,
                "تعذر استلام الملف {$name}: أوقفته إحدى إضافات PHP على السيرفر."],
            default => ['upload_failed', 500,
                "تعذر استلام الملف {$name} (كود الخطأ {$code})."],
        };

        return new UploadFailedException($message, $reason, $status, $field, $file->getClientOriginalName());
    }

    /** The whole request is bigger than post_max_size, so PHP dropped it. */
    public static function requestTooLarge(?Request $request = null): UploadFailedException
    {
        $size = $request?->server('CONTENT_LENGTH');

        return new UploadFailedException(
            'حجم البيانات المرسلة' . ($size ? ' (' . self::mb((int) $size) . ')' : '')
            . ' أكبر من الحد المسموح على السيرفر (' . self::mb(self::iniBytes('post_max_size')) . ').'
            . ' الحد الأقصى لملف الـ PDF هو 500 ميجابايت.',
            'request_too_large',
            413
        );
    }

    /**
     * Saving the file failed after it was received: disk full, no write permission, or another cause.
     * $dir is the folder the file was going to (absolute, or relative to the public disk).
     */
    public static function storageFailure(?string $dir = null, ?int $bytes = null, ?string $detail = null, ?UploadedFile $file = null): UploadFailedException
    {
        $dir = $dir === null ? storage_path('app/public')
            : (self::isAbsolute($dir) ? $dir : storage_path('app/public/' . ltrim($dir, '/\\')));

        // Nearest folder that exists, to measure free space and permissions.
        $probe = $dir;
        while (!is_dir($probe) && dirname($probe) !== $probe) {
            $probe = dirname($probe);
        }
        $free = @disk_free_space($probe);
        $bytes ??= $file?->getSize();
        $detail = $detail ? self::hidePaths($detail) : null;

        if (($detail && stripos($detail, 'No space left') !== false)
            || ($free !== false && ($free < 1048576 || ($bytes && $free < $bytes)))) {
            [$reason, $message] = ['server_disk_full',
                'تعذر حفظ الملف: مساحة السيرفر ممتلئة (المساحة المتاحة ' . self::mb($free ?: 0)
                . ($bytes ? '، وحجم الملف ' . self::mb($bytes) : '') . ').'];
        } elseif (($detail && stripos($detail, 'Permission denied') !== false) || !is_writable($probe)) {
            [$reason, $message] = ['server_no_permission',
                'تعذر حفظ الملف: لا توجد صلاحية كتابة على المجلد ' . self::hidePaths($dir) . ' على السيرفر.'];
        } else {
            [$reason, $message] = ['storage_failed',
                'تعذر حفظ الملف على السيرفر' . ($detail ? ': ' . $detail : '.')];
        }

        return new UploadFailedException($message, $reason, 500, null, $file?->getClientOriginalName());
    }

    /** Throw the reason a store()/storeAs() call returned false. */
    public static function storageFailed(?UploadedFile $file = null, ?string $dir = null): never
    {
        throw self::storageFailure($dir, null, null, $file);
    }

    /** The reason behind an exception thrown while saving an uploaded file. */
    public static function failureFrom(Throwable $e, ?UploadedFile $file = null): UploadFailedException
    {
        if ($e instanceof UploadFailedException) {
            return $e;
        }

        // Storage (Flysystem) errors say where and why: "Unable to create a directory at ..." / "Unable to write file at ...".
        if ($e instanceof FilesystemException && method_exists($e, 'location')) {
            $location = (string) $e->location();
            $dir = $e instanceof UnableToCreateDirectory ? $location : dirname($location);
            $detail = method_exists($e, 'reason') && $e->reason() !== '' ? $e->reason() : $e->getMessage();
            return self::storageFailure($dir === '.' ? null : $dir, null, $detail, $file);
        }

        $message = $e->getMessage();
        $dir = null;
        if (preg_match('/ to "([^"]+)"/', $message, $m)) {
            $dir = dirname($m[1]);                       // Could not move the file "x" to "dir/name"
        } elseif (preg_match('/"([^"]+)" directory/', $message, $m)) {
            $dir = $m[1];                                // Unable to create / write in the "dir" directory
        }
        // The PHP warning in the last parentheses is the useful part ("No space left on device", ...).
        $detail = preg_match('/\(([^()]*)\)\s*\.?$/', $message, $m) ? $m[1] : $message;

        return self::storageFailure($dir, null, $detail, $file);
    }

    /** True when an exception is about saving an upload (so it should be answered with its reason). */
    public static function isUploadProblem(Throwable $e): bool
    {
        return $e instanceof UploadFailedException || $e instanceof FileException || $e instanceof FilesystemException;
    }

    /**
     * PHP stopped the request at max_execution_time while handling an upload
     * ("Maximum execution time of 300 seconds exceeded"). Null for any other fatal error.
     */
    public static function timeoutFrom(Throwable $e): ?UploadFailedException
    {
        if (!preg_match('/Maximum execution time of (\d+) seconds? exceeded/i', $e->getMessage(), $m)) {
            return null;
        }

        return new UploadFailedException(
            "انتهت المهلة المسموحة لمعالجة الطلب على السيرفر ({$m[1]} ثانية) قبل أن تكتمل. "
            . 'الملف وصل للسيرفر لكن معالجته استغرقت وقتاً أطول من المسموح (max_execution_time).',
            'server_timeout',
            504
        );
    }

    /**
     * The limits that really apply on this server, so the frontend can check a file before uploading it
     * (instead of uploading for minutes and then failing). Sizes in MB, times in seconds (0 = no limit).
     */
    public static function limits(): array
    {
        $upload = self::iniBytes('upload_max_filesize');
        $post = self::iniBytes('post_max_size');
        $pdf = self::PDF_MAX_KB * 1024;
        $effective = min(array_filter([$pdf, $upload, $post]) ?: [$pdf]);

        return [
            'pdf_max_mb' => (int) round($pdf / 1048576),
            // What a single PDF can really be on this server right now (the smallest of the limits).
            'pdf_effective_max_mb' => (int) floor($effective / 1048576),
            'server' => [
                'upload_max_filesize_mb' => (int) round($upload / 1048576),
                'post_max_size_mb' => (int) round($post / 1048576),
                'max_input_time_seconds' => (int) ini_get('max_input_time'),
                'max_execution_time_seconds' => (int) ini_get('max_execution_time'),
            ],
        ];
    }

    /**
     * Note added to a failed "max" / type rule of a file: what was actually sent.
     * $what: "size" or "type". Empty when the value is not an uploaded file.
     */
    public static function sentFileNote($validator, string $attribute, string $what): string
    {
        $value = Arr::get($validator->getData(), $attribute);
        if (!$value instanceof UploadedFile || !$value->isValid()) {
            return '';
        }

        if ($what === 'size') {
            return ' (حجم الملف المرفوع: ' . self::mb($value->getSize()) . ')';
        }

        $type = $value->guessExtension() ?: $value->getMimeType();
        return ' (الملف المرفوع: «' . $value->getClientOriginalName() . '»' . ($type ? '، نوعه الفعلي: ' . $type : '') . ')';
    }

    // ---------------------------------------------------------------- small helpers

    /** "612.4 ميجابايت" */
    public static function mb(int|float $bytes): string
    {
        $mb = $bytes / 1048576;
        return ($mb >= 10 ? number_format($mb, 0, '.', '') : rtrim(rtrim(number_format($mb, 1, '.', ''), '0'), '.')) . ' ميجابايت';
    }

    /** A php.ini size ("512M", "1G", "800K") in bytes. 0 means no limit. */
    public static function iniBytes(string $key): int
    {
        $value = trim((string) ini_get($key));
        if ($value === '') {
            return 0;
        }

        $number = (float) $value;
        return (int) match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }

    private static function freeSpaceNote(string $dir): string
    {
        $free = @disk_free_space($dir);
        return $free === false ? '' : ' (المساحة المتاحة في المجلد المؤقت ' . self::mb($free) . ')';
    }

    private static function isAbsolute(string $path): bool
    {
        return str_starts_with($path, '/') || str_starts_with($path, '\\') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }

    /** Server paths shortened to the project folder (no full server paths in messages). */
    private static function hidePaths(string $text): string
    {
        $base = str_replace('\\', '/', base_path());
        return str_replace([$base . '/', $base], '', str_replace('\\', '/', $text));
    }
}
