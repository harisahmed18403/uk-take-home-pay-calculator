<?php

declare(strict_types=1);

$autoload = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($autoload)) {
    fwrite(STDERR, "Composer dependencies are missing. Run composer install.\n");
    exit(1);
}

require $autoload;
