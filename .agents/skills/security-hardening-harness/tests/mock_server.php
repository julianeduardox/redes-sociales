<?php
// Mock HTTP Server para pruebas unitarias de transporte real en SafeHttpClient
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($uri === '/ok') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'method' => $method]);
    exit;
}

if ($uri === '/large-body') {
    header('Content-Type: text/plain');
    echo str_repeat('A', 150 * 1024); // 150 KB
    exit;
}

if ($uri === '/large-headers') {
    // Generar cabeceras que sumen más de 35 KB
    for ($i = 0; $i < 350; $i++) {
        header("X-Custom-Header-{$i}: " . str_repeat('B', 100));
    }
    echo "Headers Sent";
    exit;
}

if ($uri === '/redirect-same-origin') {
    header('Location: /ok', true, 302);
    exit;
}

if (str_starts_with($uri, '/redirect-to-target')) {
    // Redirige al receptor de destino pasado por query o por defecto
    $target = $_GET['target'] ?? '/ok';
    header("Location: {$target}", true, 302);
    exit;
}

if (str_starts_with($uri, '/redirect-307-to-target')) {
    $target = $_GET['target'] ?? '/ok';
    header("Location: {$target}", true, 307);
    exit;
}

if ($uri === '/cross-receiver') {
    header('Content-Type: application/json');
    $receivedHeaders = [];
    foreach ($_SERVER as $k => $v) {
        if (str_starts_with($k, 'HTTP_')) {
            $headerName = strtolower(str_replace('_', '-', substr($k, 5)));
            $receivedHeaders[$headerName] = $v;
        }
    }
    $rawInput = file_get_contents('php://input');
    echo json_encode([
        'headers' => $receivedHeaders,
        'body'    => $rawInput
    ]);
    exit;
}

http_response_code(404);
echo "Not Found";
