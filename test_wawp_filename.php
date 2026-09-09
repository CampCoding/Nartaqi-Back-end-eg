<?php
// test_wawp_filename.php
$phone = '201014520135';
$url = 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf';

$appkey = '7bf0ec89-5f88-4486-ad2f-3ea7f4068edb';
$authkey = 'xDpQrRsEJRwARvrGeoTqSVCHF4QpF5i4Dp3fdgVVv0fO2xZGH5';

function testSend($filenameParam, $filenameValue) {
    global $appkey, $authkey, $phone, $url;
    $curl = curl_init();
    $postFields = [
        'appkey'  => $appkey,
        'authkey' => $authkey,
        'to'      => $phone,
        'message' => "Testing with param $filenameParam = $filenameValue",
        'file'    => $url,
        'sandbox' => 'false'
    ];
    if ($filenameParam) {
        $postFields[$filenameParam] = $filenameValue;
    }
    
    curl_setopt_array($curl, [
        CURLOPT_URL            => 'https://waapi.octopusteam.net/api/create-message',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_POSTFIELDS     => $postFields,
    ]);
    
    $res = curl_exec($curl);
    curl_close($curl);
    echo "Param: $filenameParam -> Response: $res\n";
}

// Test different parameters
testSend('filename', 'test_filename.pdf');
testSend('fileName', 'test_fileName.pdf');
testSend('file_name', 'test_file_name.pdf');
testSend('name', 'test_name.pdf');
