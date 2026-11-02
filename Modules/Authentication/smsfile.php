<?php

/**
 * WAAPI apps used for sending, one per WhatsApp number (the app decides which number sends). Every
 * message starts from a random app so both numbers share the load, and moves to the next app when the
 * first one rejects it. A number whose queue got stuck is detected afterwards (wawpCheckPending).
 */
function wawpApplications(): array
{
    return [
        // nartaqi-sk -> device "nartaqi-sk" (966543916646)
        [
            'appkey'  => 'de03e6f5-6e4e-409b-acf7-430a80fafcea',
            'authkey' => 'xDpQrRsEJRwARvrGeoTqSVCHF4QpF5i4Dp3fdgVVv0fO2xZGH5'
        ],
        // nartaqi-eg -> device "nartaqi-eg" (201098286080)
        [
            'appkey'  => 'e181a49b-e66b-438c-8d08-eab02e0231e7',
            'authkey' => 'xDpQrRsEJRwARvrGeoTqSVCHF4QpF5i4Dp3fdgVVv0fO2xZGH5'
        ],
    ];
}

/**
 * The apps in the order to try them: a random one first, then the rest. An app that recently failed
 * to send is moved to the end for a few minutes, so messages don't keep waiting on a stuck number.
 */
function wawpApplicationsInOrder(): array
{
    $applications = wawpApplications();
    $start = random_int(0, count($applications) - 1);
    $ordered = array_merge(array_slice($applications, $start), array_slice($applications, 0, $start));

    foreach ($ordered as $app) {
        wawpCheckPending($app);
    }

    $healthy = array_filter($ordered, fn ($app) => !wawpAppIsUnhealthy($app));
    $unhealthy = array_filter($ordered, fn ($app) => wawpAppIsUnhealthy($app));

    return array_values(array_merge($healthy, $unhealthy));
}

function wawpAppIsUnhealthy(array $app): bool
{
    try {
        // File store on purpose: sending must not depend on (or wait for) the database.
        return (bool) \Illuminate\Support\Facades\Cache::store('file')->get('waapi:unhealthy:' . $app['appkey']);
    } catch (\Throwable $e) {
        return false;
    }
}

function wawpMarkApp(array $app, bool $healthy): void
{
    try {
        if ($healthy) {
            \Illuminate\Support\Facades\Cache::store('file')->forget('waapi:unhealthy:' . $app['appkey']);
        } else {
            \Illuminate\Support\Facades\Cache::store('file')->put('waapi:unhealthy:' . $app['appkey'], true, now()->addMinutes(10));
        }
    } catch (\Throwable $e) {
        // No cache outside the Laravel app (e.g. plain scripts): just skip the bookkeeping.
    }
}

/**
 * WAAPI answers "sent" as soon as it queues a message, even when the number behind the app has stopped
 * sending (that's how OTPs silently stopped arriving). A few seconds in the queue is normal, WAAPI spaces
 * a number's messages ~20s apart, so the check happens later instead of making the student wait: each
 * app remembers one message it accepted, and once that message is a few minutes old its real status is
 * looked up. Still queued means the number is stuck, so the app goes to the end of the order.
 */
function wawpRememberPending(array $app, $messageId): void
{
    if (!$messageId) {
        return;
    }

    try {
        $cache = \Illuminate\Support\Facades\Cache::store('file');
        $key = 'waapi:pending:' . $app['appkey'];
        if (!$cache->has($key)) {
            $cache->put($key, ['id' => (int) $messageId, 'at' => time()], now()->addHour());
        }
    } catch (\Throwable $e) {
        // No cache outside the Laravel app: skip the stuck-queue detection.
    }
}

function wawpCheckPending(array $app): void
{
    try {
        $cache = \Illuminate\Support\Facades\Cache::store('file');
        $key = 'waapi:pending:' . $app['appkey'];
        $pending = $cache->get($key);

        if (!$pending || time() - $pending['at'] < 180) {
            return;
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => 'https://waapi.octopusteam.net/api/messages/' . $pending['id'] . '/status',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_HTTPHEADER     => ['X-Auth-Key: ' . $app['authkey'], 'Accept: application/json'],
        ]);
        $response = json_decode((string) curl_exec($curl), true);
        curl_close($curl);

        $status = $response['data']['status'] ?? null;
        if ($status === null) {
            return; // couldn't tell, look again on the next message
        }

        $cache->forget($key);
        if ($status === 'queued') {
            wawpMarkApp($app, false);
        }
    } catch (\Throwable $e) {
        // Never let the health check block sending.
    }
}

/**
 * Send WhatsApp message using the Octopus Team API strategy.
 * 
 * @param string $phone
 * @param string $message
 * @return array
 */
function sendWawpMessage(string $phone, string $message): array
{
    // 1. Format Phone Number based on new strategy
    $phone = trim($phone);
    $phone = ltrim($phone, '+');
    $phone = preg_replace('/[^0-9]/', '', $phone);

    // If starts with 0, replace 0 with 2 (e.g., 010 -> 2010)
    if (strpos($phone, '0') === 0) {
        $phone = '2' . $phone;
    } 
    // If 10 digits only and doesn't start with 2, add 20
    elseif (strlen($phone) == 10 && strpos($phone, '2') !== 0) {
        $phone = '20' . $phone;
    }

    if (empty($phone) || empty($message)) {
        return ['status' => 'error', 'message' => 'Phone and message are required'];
    }

    // 2. Try the apps one after the other until one accepts the message
    $errorMessage = 'Unknown API error';

    foreach (wawpApplicationsInOrder() as $randomApp) {
        // 3. Initialize CURL
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => 'https://waapi.octopusteam.net/api/create-message',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => [
                'appkey'  => $randomApp['appkey'],
                'authkey' => $randomApp['authkey'],
                'to'      => $phone,
                'message' => $message,
                'sandbox' => 'false'
            ],
        ]);

        $curlResponse = curl_exec($curl);
        $error        = curl_error($curl);
        $httpCode     = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        // 4. Handle Response
        $result = json_decode($curlResponse, true);

        if (
            $error ||
            $httpCode < 200 ||
            $httpCode >= 300 ||
            !$curlResponse ||
            (
                is_array($result) &&
                (
                    (isset($result['status']) && $result['status'] === 'error') ||
                    (isset($result['success']) && $result['success'] === false)
                )
            )
        ) {
            $errorMessage = $error ?: ($result['message'] ?? $result['error'] ?? (isset($result['errors'][0]) ? $result['errors'][0] : 'Unknown API error'));
            wawpMarkApp($randomApp, false);
            continue;
        }

        wawpRememberPending($randomApp, $result['data']['message_id'] ?? null);

        return [
            "status"  => "success",
            "message" => "Sent successfully",
            "data"    => $result
        ];
    }

    return [
        "status"  => "error",
        "error"   => $errorMessage,
        "message" => "Message sending failed",
        "details" => $errorMessage
    ];
}

/**
 * Send WhatsApp PDF file using the Octopus Team API strategy.
 * 
 * @param string $phone
 * @param string $fileUrl - Public URL to the PDF file
 * @param string $fileName - Name of the file
 * @param string $caption - Caption/message to send with the file
 * @return array
 */
function sendWawpPdf(string $phone, string $fileUrl, string $fileName, string $caption = ''): array
{
    // 1. Format Phone Number based on new strategy
    $phone = trim($phone);
    $phone = ltrim($phone, '+');
    $phone = preg_replace('/[^0-9]/', '', $phone);

    // If starts with 0, replace 0 with 2 (e.g., 010 -> 2010)
    if (strpos($phone, '0') === 0) {
        $phone = '2' . $phone;
    } 
    // If 10 digits only and doesn't start with 2, add 20
    elseif (strlen($phone) == 10 && strpos($phone, '2') !== 0) {
        $phone = '20' . $phone;
    }

    if (empty($phone) || empty($fileUrl)) {
        return ['status' => 'error', 'message' => 'Phone and file URL are required'];
    }

    // Log the start of the operation
    $logFile = base_path('Modules/Authentication/wawp_api.log');
    $logMessage = '[' . date('Y-m-d H:i:s') . '] Starting sendWawpPdf for phone: ' . $phone . ', file: ' . $fileName . ', url: ' . $fileUrl . PHP_EOL;
    @file_put_contents($logFile, $logMessage, FILE_APPEND);

    // 2. Try the apps one after the other until one accepts the file
    $failure = null;

    foreach (wawpApplicationsInOrder() as $randomApp) {
        // 3. Initialize CURL for create-message with media URL
        $curl = curl_init();

        $postFields = [
            'appkey'  => $randomApp['appkey'],
            'authkey' => $randomApp['authkey'],
            'to'      => $phone,
            'type'    => 'media',
            'message' => $caption,
            'file'    => $fileUrl,
            'sandbox' => 'false'
        ];

        curl_setopt_array($curl, [
            CURLOPT_URL            => 'https://waapi.octopusteam.net/api/create-message',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $postFields,
        ]);

        $curlResponse = curl_exec($curl);
        $error        = curl_error($curl);
        $httpCode     = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        // 4. Handle Response
        $result = json_decode($curlResponse, true);

        // Log the result
        $logResult = '[' . date('Y-m-d H:i:s') . '] app ' . $randomApp['appkey'] . ' HTTP ' . $httpCode . ' Response: ' . $curlResponse . PHP_EOL;
        @file_put_contents($logFile, $logResult, FILE_APPEND);

        $apiErrorMessage = null;
        if (!empty($error)) {
            $apiErrorMessage = $error;
        } elseif ($httpCode < 200 || $httpCode >= 300) {
            $apiErrorMessage = 'HTTP ' . $httpCode . ' response';
        } elseif (!$curlResponse) {
            $apiErrorMessage = 'Empty API response';
        } elseif ($result === null && json_last_error() !== JSON_ERROR_NONE) {
            $apiErrorMessage = 'Invalid JSON response: ' . json_last_error_msg();
        } elseif (is_array($result)) {
            if (isset($result['status']) && $result['status'] === 'error') {
                $apiErrorMessage = $result['message'] ?? 'API returned error status';
            } elseif (isset($result['success']) && $result['success'] === false) {
                $apiErrorMessage = $result['message'] ?? 'API returned success=false';
            } elseif (isset($result['code']) && $result['code'] !== 'success') {
                $apiErrorMessage = $result['message'] ?? 'API returned non-success code';
            }
        }

        if ($apiErrorMessage === null) {
            wawpRememberPending($randomApp, $result['data']['message_id'] ?? null);

            return [
                "status"  => "success",
                "message" => "File sent successfully",
                "data"    => $result
            ];
        }

        $failure = [
            "status"  => "error",
            "error"   => $apiErrorMessage,
            "message" => "File sending failed",
            "details" => [
                'http_code' => $httpCode,
                'raw_response' => $curlResponse,
                'parsed_response' => $result,
            ],
        ];
    }

    return $failure;
}
