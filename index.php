<?php

declare(strict_types=1);

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$publicFile = $requestPath !== false ? __DIR__ . $requestPath : null;
$extension = $publicFile !== null ? pathinfo($publicFile, PATHINFO_EXTENSION) : '';

if ($publicFile !== null && $requestPath !== '/' && is_file($publicFile) && $extension !== 'php') {
    return false;
}

$autoload = __DIR__ . '/vendor/autoload.php';

if (!file_exists($autoload)) {
    http_response_code(500);
    echo 'Composer dependencies are missing. Run composer install.';
    exit;
}

require $autoload;

$app = new \TakeHomePay\Http\App();
$response = $app->handle($_GET, $_POST);

http_response_code($response['status']);
echo $response['content'];
