<?php

// Define the remote root URL as a constant
define('REMOTE_ROOT_URL', 'http://localhost:8300/');

function proxy() {
    // Extract the path and query parameters from the client request (e.g., /resource?param=value)
    $clientPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $clientQuery = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);

    // Create the full remote URL by combining the remote root, client path, and query parameters
    $remoteUrl = REMOTE_ROOT_URL . ltrim($clientPath, '/') . ($clientQuery ? '?' . $clientQuery : '');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $remoteUrl);
    curl_setopt($ch, CURLOPT_HEADER, true);

    // If there was a POST request, then forward that as well.
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST);
    }

    curl_exec($ch);
    curl_close($ch);
}

proxy();
