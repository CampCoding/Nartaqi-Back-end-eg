<?php

return [

    /*
    | Printed QR codes contain {base_url}/{serial}. This is printed in books and can never change,
    | so it is set here on purpose instead of being derived from APP_URL.
    */
    'base_url' => rtrim(env('QR_BASE_URL', 'https://eg.nartaqi.net/q'), '/'),

    // Serial = prefix + random digits, e.g. EG48271930. The Saudi platform uses SA, so codes never clash.
    'serial_prefix' => env('QR_SERIAL_PREFIX', 'EG'),
    'serial_digits' => (int) env('QR_SERIAL_DIGITS', 8),

    // Batches bigger than this are created by a queued job (runs inline when QUEUE_CONNECTION=sync).
    'queue_threshold' => (int) env('QR_QUEUE_THRESHOLD', 2000),
    'max_batch' => (int) env('QR_MAX_BATCH', 10000),

    // Public /q/{serial}: requests per minute per IP, and how long a lookup is cached (seconds).
    'per_minute' => (int) env('QR_PER_MINUTE', 60),
    'cache_ttl' => (int) env('QR_CACHE_TTL', 600),
];
