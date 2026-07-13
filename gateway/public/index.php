<?php

// Single entry point: every request, whatever its path, is decided here. This
// is the strangler fig facade: it forwards to whichever backend currently
// owns a given path, and does not know or care whether that backend is the
// legacy gestionale or EventHub. It copies bytes across, it does not
// translate them: that is the anti-corruption layer's job, not this one.

$routes = require __DIR__.'/../routes.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$backend = $routes['default'];
foreach ($routes['rules'] as $prefix => $target) {
    if (str_starts_with($path, $prefix)) {
        $backend = $target;
        break;
    }
}

$url = $backend.$_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

$forwardedHeaders = [];
foreach (getallheaders() as $name => $value) {
    if (strtolower($name) === 'host') {
        continue;
    }
    $forwardedHeaders[] = "$name: $value";
}

$request = curl_init($url);
curl_setopt($request, CURLOPT_CUSTOMREQUEST, $method);
curl_setopt($request, CURLOPT_HTTPHEADER, $forwardedHeaders);
curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
curl_setopt($request, CURLOPT_HEADER, true);

if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
    curl_setopt($request, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
}

$response = curl_exec($request);
$statusCode = curl_getinfo($request, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($request, CURLINFO_HEADER_SIZE);
curl_close($request);

$responseHeaders = substr($response, 0, $headerSize);
$responseBody = substr($response, $headerSize);

http_response_code($statusCode);
foreach (explode("\r\n", $responseHeaders) as $headerLine) {
    if (! str_contains($headerLine, ':')) {
        continue;
    }
    if (stripos($headerLine, 'Transfer-Encoding:') === 0) {
        continue;
    }
    header($headerLine, false);
}

echo $responseBody;
