<?php

require_once 'inc/functions.php';

header('Content-Type: application/json');
echo json_encode([
    'captcha' => $config['securimage'],
    'flags' => $config['user_flags']
]);