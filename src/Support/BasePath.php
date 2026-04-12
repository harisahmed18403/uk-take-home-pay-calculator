<?php

declare(strict_types=1);

namespace TakeHomePay\Support;

final class BasePath
{
    public static function current(): string
    {
        $value = $_ENV['APP_BASE_PATH']
            ?? $_SERVER['APP_BASE_PATH']
            ?? $_SERVER['HTTP_APP_BASE_PATH']
            ?? getenv('APP_BASE_PATH')
            ?: '';

        return self::normalize((string) $value);
    }

    public static function normalize(string $path): string
    {
        $trimmed = trim($path);

        if ($trimmed === '' || $trimmed === '/') {
            return '';
        }

        return '/' . trim($trimmed, '/');
    }

    public static function stripFromRequestPath(string $requestPath, string $basePath): string
    {
        $basePath = self::normalize($basePath);
        $normalizedRequestPath = '/' . ltrim($requestPath, '/');

        if ($basePath !== '' && str_starts_with($normalizedRequestPath, $basePath . '/')) {
            $strippedPath = substr($normalizedRequestPath, strlen($basePath));

            return $strippedPath === '' ? '/' : $strippedPath;
        }

        if ($basePath !== '' && $normalizedRequestPath === $basePath) {
            return '/';
        }

        return $normalizedRequestPath;
    }

    public static function asset(string $path, string $basePath): string
    {
        $normalizedBasePath = self::normalize($basePath);

        return ($normalizedBasePath === '' ? '' : $normalizedBasePath) . '/' . ltrim($path, '/');
    }

    public static function route(string $page, string $basePath): string
    {
        $normalizedBasePath = self::normalize($basePath);
        $prefix = $normalizedBasePath === '' ? '' : $normalizedBasePath;
        $url = $prefix . '/';

        if ($page !== '' && $page !== 'home') {
            $url .= '?page=' . rawurlencode($page);
        }

        return $url;
    }
}
