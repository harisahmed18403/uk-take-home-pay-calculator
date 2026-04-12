<?php

declare(strict_types=1);

require __DIR__ . '/src/Support/BasePath.php';

$basePath = \TakeHomePay\Support\BasePath::current();
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$relativePath = $requestPath !== false
    ? \TakeHomePay\Support\BasePath::stripFromRequestPath($requestPath, $basePath)
    : null;
$publicFile = $relativePath !== null ? __DIR__ . $relativePath : null;
$extension = $publicFile !== null ? pathinfo($publicFile, PATHINFO_EXTENSION) : '';

if ($publicFile !== null && $relativePath !== '/' && is_file($publicFile) && $extension !== 'php') {
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

foreach ($response['headers'] ?? [] as $header) {
    header($header);
}

http_response_code($response['status']);
if ($response['content'] !== '') {
    echo $response['content'];
}
