<?php

declare(strict_types=1);

namespace TakeHomePay\Support;

final class BasePath
{
    /** @var array<string, string> */
    private const PAGE_SLUGS = [
        'home' => '',
        'guides' => 'guides',
        'faq' => 'faq',
        'privacy' => 'privacy-policy',
        'cookies' => 'cookie-policy',
    ];

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
        $slug = self::PAGE_SLUGS[$page] ?? '';

        if ($slug === '') {
            return $prefix . '/';
        }

        return $prefix . '/' . $slug . '/';
    }

    public static function sitemap(string $basePath): string
    {
        $normalizedBasePath = self::normalize($basePath);
        $prefix = $normalizedBasePath === '' ? '' : $normalizedBasePath;

        return $prefix . '/sitemap.xml';
    }

    public static function pageFromPath(string $path): ?string
    {
        $normalizedPath = trim($path, '/');

        if ($normalizedPath === '') {
            return 'home';
        }

        foreach (self::PAGE_SLUGS as $page => $slug) {
            if ($slug !== '' && $normalizedPath === $slug) {
                return $page;
            }
        }

        return null;
    }
}
