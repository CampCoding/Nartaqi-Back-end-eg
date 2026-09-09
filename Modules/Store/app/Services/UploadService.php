<?php

namespace Modules\Store\Services;

use Illuminate\Support\Facades\Storage;

class UploadService
{
    /**
     * Handle single or multiple file uploads.
     * 
     * @param mixed $files Single file or array of files
     * @param string $path Target directory
     * @return array|string List of full URLs or single URL
     */
    public static function upload($files, string $path)
    {
        if (is_array($files)) {
            $urls = [];
            foreach ($files as $file) {
                $urls[] = self::save($file, $path);
            }
            return $urls;
        }

        return self::save($files, $path);
    }

    /**
     * Save a single file and return its URL.
     */
    private static function save($file, string $path)
    {
        $savedPath = $file->store($path, 'public');
        $url = rtrim(config('app.url'), '/') . '/storage/app/public/' . $savedPath;
        return str_replace('camp-coding.site', 'nartaqi.net', $url);
    }
}
