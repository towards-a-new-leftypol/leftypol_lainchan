<?php

require __DIR__ . '/inc/functions.php';

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
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response as a string

    // If there was a POST request, then forward that as well.
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST);
    }

    $response = curl_exec($ch);

    if ($response === false) {
        header("HTTP/1.1 502 Bad Gateway");
        echo "Proxy Error: Unable to communicate with the backend server.";
        exit;
    }

    // Split the response into headers and body
    list($headers, $body) = explode("\r\n\r\n", $response, 2);

    // Set the headers from the backend response
    $headerLines = explode("\r\n", $headers);
    foreach ($headerLines as $headerLine) {
        header($headerLine);
    }

    // Output the body content
    echo $body;

    curl_close($ch);
}

function main() {
    global $mod;

    check_login(true);

    if (!$mod) {
        // Get the current URL, including query parameters
        $currentUrl = $_SERVER['REQUEST_URI'];
        $status = 303;
        $redirectUrl = '/mod.php?r=' . urlencode($currentUrl) . '&status=' . $status;
        header('Location: ' . $redirectUrl, true, $status);
        exit;
    }

    #echo json_encode($mod);
    proxy();
}

main();
