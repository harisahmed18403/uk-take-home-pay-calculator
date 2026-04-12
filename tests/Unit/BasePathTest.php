<?php

declare(strict_types=1);

namespace TakeHomePay\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TakeHomePay\Support\BasePath;

final class BasePathTest extends TestCase
{
    public function testNormalizeReturnsEmptyStringForRootLikeValues(): void
    {
        self::assertSame('', BasePath::normalize(''));
        self::assertSame('', BasePath::normalize('/'));
    }

    public function testNormalizeAddsLeadingSlashAndRemovesTrailingSlash(): void
    {
        self::assertSame('/uk-take-home-pay-calculator', BasePath::normalize('uk-take-home-pay-calculator/'));
    }

    public function testStripFromRequestPathRemovesConfiguredPrefix(): void
    {
        self::assertSame(
            '/assets/js/calculator-form.js',
            BasePath::stripFromRequestPath(
                '/uk-take-home-pay-calculator/assets/js/calculator-form.js',
                '/uk-take-home-pay-calculator'
            )
        );
        self::assertSame(
            '/',
            BasePath::stripFromRequestPath('/uk-take-home-pay-calculator', '/uk-take-home-pay-calculator')
        );
    }

    public function testRouteAndAssetUseConfiguredBasePath(): void
    {
        self::assertSame(
            '/uk-take-home-pay-calculator/assets/css/styles.css',
            BasePath::asset('assets/css/styles.css', '/uk-take-home-pay-calculator')
        );
        self::assertSame(
            '/uk-take-home-pay-calculator/faq/',
            BasePath::route('faq', '/uk-take-home-pay-calculator')
        );
        self::assertSame(
            '/uk-take-home-pay-calculator/sitemap.xml',
            BasePath::sitemap('/uk-take-home-pay-calculator')
        );
        self::assertSame('guides', BasePath::pageFromPath('/guides/'));
    }
}
