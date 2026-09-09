<?php

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

    // 2. Application Credentials (New Strategy)
    $applications = [
        [
            'appkey'  => '7bf0ec89-5f88-4486-ad2f-3ea7f4068edb',
            'authkey' => 'xDpQrRsEJRwARvrGeoTqSVCHF4QpF5i4Dp3fdgVVv0fO2xZGH5'
        ]
    ];

    $count = count($applications);
    $randomIndex = time() % $count; 
    $randomApp = $applications[$randomIndex];

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
        return [
            "status"  => "error",
            "message" => "Message sending failed",
            "details" => $error ?: ($result['message'] ?? 'Unknown API error')
        ];
    }

    return [
        "status"  => "success",
        "message" => "Sent successfully",
        "data"    => $result
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
    file_put_contents($logFile, $logMessage, FILE_APPEND);

    // 2. Application Credentials (New Strategy)
    $applications = [
        [
            'appkey'  => '7bf0ec89-5f88-4486-ad2f-3ea7f4068edb',
            'authkey' => 'xDpQrRsEJRwARvrGeoTqSVCHF4QpF5i4Dp3fdgVVv0fO2xZGH5'
        ]
    ];

    $count = count($applications);
    $randomIndex = time() % $count; 
    $randomApp = $applications[$randomIndex];

    // 3. Initialize CURL for create-message with media URL
    $curl = curl_init();

    $postFields = [
        'appkey'  => $randomApp['appkey'],
        'authkey' => $randomApp['authkey'],
        'to'      => $phone,
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
    $logResult = '[' . date('Y-m-d H:i:s') . '] HTTP ' . $httpCode . ' Response: ' . $curlResponse . PHP_EOL;
    file_put_contents($logFile, $logResult, FILE_APPEND);

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

    if ($apiErrorMessage !== null) {
        return [
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

    return [
        "status"  => "success",
        "message" => "File sent successfully",
        "data"    => $result
    ];
}
