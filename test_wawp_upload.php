<?php
// test_wawp_upload.php
$phone = '201014520135';
$appkey = '7bf0ec89-5f88-4486-ad2f-3ea7f4068edb';
$authkey = 'xDpQrRsEJRwARvrGeoTqSVCHF4QpF5i4Dp3fdgVVv0fO2xZGH5';

// Create a small temporary text file renamed as .pdf for testing API acceptance
$tempFile = __DIR__ . '/test_temp_upload.pdf';
file_put_contents($tempFile, 'Dummy PDF content for upload test');

$curl = curl_init();
$postFields = [
    'appkey'  => $appkey,
    'authkey' => $authkey,
    'to'      => $phone,
    'message' => 'Testing PDF Upload with custom filename',
    'file'    => new CURLFile($tempFile, 'application/pdf', 'جدول_مذاكرة_سيف_upload.pdf'),
    'sandbox' => 'false'
];

curl_setopt_array($curl, [
    CURLOPT_URL            => 'https://waapi.octopusteam.net/api/create-message',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST  => 'POST',
    CURLOPT_POSTFIELDS     => $postFields,
]);

$res = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

@unlink($tempFile);

echo "HTTP Code: $httpCode\n";
echo "Response: $res\n";
