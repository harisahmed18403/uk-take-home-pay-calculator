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
        $html = $this->request('GET', '/index.php');

        self::assertStringContainsString('Calculate your UK take-home pay in seconds.', $html);
        self::assertStringContainsString('Calculator', $html);
        self::assertStringContainsString('728 x 90 leaderboard', $html);
        self::assertStringContainsString('300 x 250 in-content slot', $html);
        self::assertStringContainsString('data-calculator-form', $html);
        self::assertStringContainsString('Results update instantly as you edit the form.', $html);
        self::assertStringContainsString('assets/js/take-home-pay-calculator.js', $html);
        self::assertStringContainsString('assets/js/calculator-form.js', $html);
        self::assertStringContainsString('window.takeHomePayCalculatorConfig =', $html);
        self::assertStringContainsString('"2026-2027"', $html);
        self::assertStringContainsString('data-result-field="net_annual"', $html);
        self::assertStringContainsString('data-mobile-results-bar', $html);
        self::assertStringContainsString('data-mobile-result="net_monthly"', $html);
        self::assertLessThan(
            strpos($html, 'Calculate your UK take-home pay in seconds.'),
            strpos($html, '<section class="panel panel--form">')
        );
    }

    public function testCalculatorSubmissionRendersResults(): void
    {
        $html = $this->request('POST', '/index.php', [
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

        self::assertStringContainsString('Net annual pay', $html);
        self::assertStringContainsString('£39,519.60', $html);
        self::assertStringContainsString('Income Tax', $html);
        self::assertStringContainsString('National Insurance', $html);
    }

    public function testValidationErrorsRender(): void
    {
        $html = $this->request('POST', '/index.php', [
            'salary' => '0',
            'bonus' => '-1',
            'pension_percent' => '150',
        ]);

        self::assertStringContainsString('Check your inputs', $html);
        self::assertStringContainsString('Enter a salary greater than zero.', $html);
        self::assertStringContainsString('Bonus must be zero or more.', $html);
        self::assertStringContainsString('Pension contribution must be between 0 and 100 percent.', $html);
    }

    public function testJavaScriptAssetsAreServed(): void
    {
        $calculatorClass = $this->request('GET', '/assets/js/take-home-pay-calculator.js');
        $calculatorForm = $this->request('GET', '/assets/js/calculator-form.js');

        self::assertStringContainsString('class TakeHomePayCalculator', $calculatorClass);
        self::assertStringContainsString('window.TakeHomePayCalculator = TakeHomePayCalculator', $calculatorClass);
        self::assertStringContainsString('Results update instantly as you edit the form.', $this->request('GET', '/index.php'));
        self::assertStringContainsString('new Calculator(config)', $calculatorForm);
        self::assertStringContainsString('form.addEventListener("input", update);', $calculatorForm);
        self::assertStringContainsString('scrollIntoView', $calculatorForm);
        self::assertStringContainsString('syncMobileResultsBar', $calculatorForm);
        self::assertStringContainsString('mobileResultsBar.hidden = true;', $calculatorForm);
    }

    public function testGuidesPageShowsFormulasAndStepByStepWalkthrough(): void
    {
        $html = $this->request('GET', '/index.php?page=guides');

        self::assertStringContainsString('Annualise your pay first', $html);
        self::assertStringContainsString('gross_annual = annual salary or (monthly salary × 12) or (weekly salary × 52) + bonus', $html);
        self::assertStringContainsString('net_annual = gross_annual - income_tax - national_insurance - student_loan - pension', $html);
        self::assertStringContainsString('For each selected student loan plan, only the earnings above its threshold are charged.', $html);
    }

    #[DataProvider('secondaryPages')]
    public function testSecondaryPagesRender(string $page, string $expectedHeading): void
    {
        $html = $this->request('GET', '/index.php?page=' . urlencode($page));

        self::assertStringContainsString($expectedHeading, $html);
    }

    /**
     * @return array<int, array{string, string}>
     */
    public static function secondaryPages(): array
    {
        return [
            ['guides', 'UK tax guides'],
            ['faq', 'Frequently asked questions'],
            ['privacy', 'Privacy policy'],
            ['cookies', 'Cookie policy'],
        ];
    }

    /**
     * @param array<string, string> $data
     */
    private function request(string $method, string $path, array $data = []): string
    {
        $options = [
            'http' => [
                'method' => $method,
                'ignore_errors' => true,
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            ],
        ];

        if ($method === 'POST') {
            $options['http']['content'] = http_build_query($data);
        }

        $context = stream_context_create($options);
        $html = file_get_contents(self::$baseUrl . $path, false, $context);

        self::assertNotFalse($html, 'Expected HTTP response from local PHP server.');

        return (string) $html;
    }
}
