<?php

declare(strict_types=1);

namespace TakeHomePay\Support;

final class Site
{
    public static function originUrl(): string
    {
        $configuredRoot = $_ENV['APP_ROOT_URL']
            ?? $_SERVER['APP_ROOT_URL']
            ?? getenv('APP_ROOT_URL')
            ?: '';

        if ($configuredRoot !== '') {
            return rtrim((string) $configuredRoot, '/');
        }

        $scheme = self::scheme();
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

        return $scheme . '://' . $host;
    }

    public static function siteUrl(string $basePath): string
    {
        $configuredSite = $_ENV['APP_SITE_URL']
            ?? $_SERVER['APP_SITE_URL']
            ?? getenv('APP_SITE_URL')
            ?: '';

        if ($configuredSite !== '') {
            return rtrim((string) $configuredSite, '/');
        }

        return self::originUrl() . BasePath::normalize($basePath);
    }

    public static function absoluteUrl(string $path, string $basePath = ''): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $prefix = str_starts_with($path, '/')
            ? self::originUrl()
            : self::siteUrl($basePath);

        return $prefix . (str_starts_with($path, '/') ? $path : '/' . ltrim($path, '/'));
    }

    private static function scheme(): string
    {
        $forwardedProto = (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '');
        if ($forwardedProto !== '') {
            return strtolower(explode(',', $forwardedProto)[0]);
        }

        if (
            isset($_SERVER['HTTPS']) &&
            $_SERVER['HTTPS'] !== '' &&
            strtolower((string) $_SERVER['HTTPS']) !== 'off'
        ) {
            return 'https';
        }

        if ((int) ($_SERVER['SERVER_PORT'] ?? 80) === 443) {
            return 'https';
        }

        return 'http';
    }
}
