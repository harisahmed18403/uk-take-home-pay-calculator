<?php

declare(strict_types=1);

namespace TakeHomePay\Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class WebsiteTest extends TestCase
{
    private static int $serverPid = 0;
    private static string $stdoutFile;
    private static string $stderrFile;
    private static string $baseUrl = 'http://127.0.0.1:8099';

    public static function setUpBeforeClass(): void
    {
        self::$stdoutFile = tempnam(sys_get_temp_dir(), 'take-home-pay-out-');
        self::$stderrFile = tempnam(sys_get_temp_dir(), 'take-home-pay-err-');
        $root = dirname(__DIR__, 2);
        $command = sprintf(
            'php -S 127.0.0.1:8099 -t %s %s > %s 2> %s & echo $!',
            escapeshellarg($root),
            escapeshellarg($root . '/index.php'),
            escapeshellarg(self::$stdoutFile),
            escapeshellarg(self::$stderrFile)
        );

        $output = [];
        exec($command, $output);
        self::$serverPid = isset($output[0]) ? (int) $output[0] : 0;

        $started = false;
        for ($i = 0; $i < 20; $i++) {
            usleep(100000);
            $home = @file_get_contents(self::$baseUrl . '/index.php');
            if ($home !== false) {
                $started = true;
                break;
            }
        }

        if (!$started) {
            self::fail('PHP test server did not start. STDERR: ' . file_get_contents(self::$stderrFile));
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$serverPid > 0) {
            exec('kill ' . self::$serverPid);
        }

        if (isset(self::$stdoutFile) && file_exists(self::$stdoutFile)) {
            unlink(self::$stdoutFile);
        }

        if (isset(self::$stderrFile) && file_exists(self::$stderrFile)) {
            unlink(self::$stderrFile);
        }
    }

    public function testHomePageRendersCalculatorAndAdSlots(): void
    {
        $response = $this->request('GET', '/');
        $html = $response['body'];

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('UK take-home pay calculator for salary after tax, pension, and student loan estimates.', $html);
        self::assertStringContainsString('Calculator', $html);
        self::assertStringContainsString('300 x 250 above-the-fold feature ad', $html);
        self::assertStringContainsString('320 x 100 sticky companion', $html);
        self::assertStringContainsString('Responsive in-results ad slot', $html);
        self::assertStringContainsString('300 x 250 in-content slot', $html);
        self::assertStringContainsString('data-calculator-form', $html);
        self::assertStringContainsString('Results update instantly as you edit the form.', $html);
        self::assertStringContainsString('/assets/js/take-home-pay-calculator.js', $html);
        self::assertStringContainsString('/assets/js/calculator-form.js', $html);
        self::assertStringContainsString('window.takeHomePayCalculatorConfig =', $html);
        self::assertStringContainsString('"2026-2027"', $html);
        self::assertStringContainsString('data-result-field="net_annual"', $html);
        self::assertStringContainsString('data-mobile-results-bar', $html);
        self::assertStringContainsString('Current take-home pay', $html);
        self::assertStringContainsString('Full breakdown', $html);
        self::assertStringContainsString('data-mobile-result="net_monthly"', $html);
        self::assertStringContainsString('data-results-heading', $html);
        self::assertStringContainsString('href="/guides/"', $html);
        self::assertStringContainsString('action="/"', $html);
        self::assertStringContainsString('<link rel="canonical" href="http://127.0.0.1:8099/">', $html);
        self::assertStringContainsString('"@type":"SoftwareApplication"', $html);
        self::assertStringContainsString('property="og:image"', $html);
    }

    public function testCalculatorSubmissionRendersResults(): void
    {
        $response = $this->request('POST', '/index.php', [
            'salary' => '50000',
            'salary_period' => 'annual',
            'bonus' => '0',
            'tax_year' => '2026-2027',
            'region' => 'england',
            'tax_code' => '1257L',
            'pension_percent' => '0',
            'pension_method' => 'salary_sacrifice',
            'student_loan_plan' => 'none',
            'has_postgraduate_loan' => '0',
        ]);
        $html = $response['body'];

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('Net annual pay', $html);
        self::assertStringContainsString('£39,519.60', $html);
        self::assertStringContainsString('Income Tax', $html);
        self::assertStringContainsString('National Insurance', $html);
        self::assertStringContainsString('Responsive in-results ad slot', $html);
    }

    public function testValidationErrorsRender(): void
    {
        $response = $this->request('POST', '/index.php', [
            'salary' => '0',
            'bonus' => '-1',
            'pension_percent' => '150',
        ]);
        $html = $response['body'];

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('Check your inputs', $html);
        self::assertStringContainsString('Enter a salary greater than zero.', $html);
        self::assertStringContainsString('Bonus must be zero or more.', $html);
        self::assertStringContainsString('Pension contribution must be between 0 and 100 percent.', $html);
    }

    public function testJavaScriptAssetsAreServed(): void
    {
        $calculatorClass = $this->request('GET', '/assets/js/take-home-pay-calculator.js')['body'];
        $calculatorForm = $this->request('GET', '/assets/js/calculator-form.js')['body'];
        $styles = $this->request('GET', '/assets/css/styles.css')['body'];

        self::assertStringContainsString('class TakeHomePayCalculator', $calculatorClass);
        self::assertStringContainsString('window.TakeHomePayCalculator = TakeHomePayCalculator', $calculatorClass);
        self::assertStringContainsString('Results update instantly as you edit the form.', $this->request('GET', '/index.php')['body']);
        self::assertStringContainsString('new Calculator(config)', $calculatorForm);
        self::assertStringContainsString('form.addEventListener("input", update);', $calculatorForm);
        self::assertStringContainsString('scrollIntoView', $calculatorForm);
        self::assertStringContainsString('syncMobileResultsBar', $calculatorForm);
        self::assertStringContainsString('mobileResultsBar.hidden = true;', $calculatorForm);
        self::assertStringContainsString('IntersectionObserver', $calculatorForm);
        self::assertStringContainsString('resultsObserver.observe(resultsHeading);', $calculatorForm);
        self::assertStringContainsString('.mobile-results-bar[hidden]', $styles);
        self::assertStringContainsString('position: sticky;', $styles);
    }

    public function testHomePageSupportsConfiguredBasePath(): void
    {
        $response = $this->request('GET', '/uk-take-home-pay-calculator/', [], [
            'APP_BASE_PATH' => '/uk-take-home-pay-calculator',
        ]);
        $html = $response['body'];

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('href="/uk-take-home-pay-calculator/guides/"', $html);
        self::assertStringContainsString('href="/uk-take-home-pay-calculator/assets/css/styles.css"', $html);
        self::assertStringContainsString('src="/uk-take-home-pay-calculator/assets/js/calculator-form.js"', $html);
        self::assertStringContainsString('action="/uk-take-home-pay-calculator/"', $html);
    }

    public function testGuidesPageShowsFormulasAndStepByStepWalkthrough(): void
    {
        $response = $this->request('GET', '/guides/');
        $html = $response['body'];

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('Annualise your pay first', $html);
        self::assertStringContainsString('gross_annual = annual salary or (monthly salary × 12) or (weekly salary × 52) + bonus', $html);
        self::assertStringContainsString('net_annual = gross_annual - income_tax - national_insurance - student_loan - pension', $html);
        self::assertStringContainsString('For each selected student loan plan, only the earnings above its threshold are charged.', $html);
        self::assertStringContainsString('<link rel="canonical" href="http://127.0.0.1:8099/guides/">', $html);
    }

    #[DataProvider('secondaryPages')]
    public function testSecondaryPagesRender(string $path, string $expectedHeading): void
    {
        $response = $this->request('GET', $path);

        self::assertSame(200, $response['status']);
        self::assertStringContainsString($expectedHeading, $response['body']);
    }

    /**
     * @return array<int, array{string, string}>
     */
    public static function secondaryPages(): array
    {
        return [
            ['/guides/', 'UK tax guides'],
            ['/faq/', 'Frequently asked questions'],
            ['/privacy-policy/', 'Privacy policy'],
            ['/cookie-policy/', 'Cookie policy'],
        ];
    }

    public function testLegacyQueryRouteRedirectsToCleanUrl(): void
    {
        $response = $this->request('GET', '/index.php?page=guides');

        self::assertSame(301, $response['status']);
        self::assertContains('Location: /guides/', $response['headers']);
    }

    public function testUnknownRouteReturns404(): void
    {
        $response = $this->request('GET', '/missing-page/');

        self::assertSame(404, $response['status']);
        self::assertStringContainsString('Page not found', $response['body']);
        self::assertStringContainsString('noindex,follow', $response['body']);
    }

    public function testSitemapXmlIsServed(): void
    {
        $response = $this->request('GET', '/sitemap.xml');

        self::assertSame(200, $response['status']);
        self::assertContains('Content-Type: application/xml; charset=UTF-8', $response['headers']);
        self::assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $response['body']);
        self::assertStringContainsString('<loc>http://127.0.0.1:8099/guides/</loc>', $response['body']);
        self::assertStringContainsString('<lastmod>', $response['body']);
    }

    public function testRobotsTxtIsServed(): void
    {
        $response = $this->request('GET', '/robots.txt');

        self::assertSame(200, $response['status']);
        self::assertContains('Content-Type: text/plain; charset=UTF-8', $response['headers']);
        self::assertStringContainsString('User-agent: *', $response['body']);
        self::assertStringContainsString('Sitemap: http://127.0.0.1:8099/sitemap.xml', $response['body']);
    }

    /**
     * @param array<string, string> $data
     */
    private function request(string $method, string $path, array $data = [], array $headers = []): array
    {
        $options = [
            'http' => [
                'method' => $method,
                'ignore_errors' => true,
                'header' => $this->headers($headers),
            ],
        ];

        if ($method === 'POST') {
            $options['http']['content'] = http_build_query($data);
        }

        $context = stream_context_create($options);
        $html = file_get_contents(self::$baseUrl . $path, false, $context);

        self::assertNotFalse($html, 'Expected HTTP response from local PHP server.');

        $responseHeaders = $http_response_header ?? [];
        $statusLine = $responseHeaders[0] ?? 'HTTP/1.1 200 OK';
        preg_match('/\s(\d{3})\s/', $statusLine, $matches);

        return [
            'body' => (string) $html,
            'headers' => $responseHeaders,
            'status' => isset($matches[1]) ? (int) $matches[1] : 200,
        ];
    }

    /**
     * @param array<string, string> $headers
     */
    private function headers(array $headers): string
    {
        $headerLines = [
            "Content-Type: application/x-www-form-urlencoded",
        ];

        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        return implode("\r\n", $headerLines) . "\r\n";
    }
}
