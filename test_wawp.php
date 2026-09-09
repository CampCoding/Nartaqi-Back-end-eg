<?php
require_once __DIR__ . '/Modules/Authentication/smsfile.php';

$res = sendWawpMessage('+201000000000', 'Test message');
print_r($res);
