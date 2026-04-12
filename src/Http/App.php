<?php

declare(strict_types=1);

namespace TakeHomePay\Http;

use TakeHomePay\Data\TaxYears;
use TakeHomePay\Services\TakeHomePayCalculator;
use TakeHomePay\Support\Format;

final class App
{
    /**
     * @param array<string, mixed> $get
     * @param array<string, mixed> $post
     * @return array{status:int, content:string}
     */
    public function handle(array $get, array $post): array
    {
        $page = (string) ($get['page'] ?? 'home');
        $validPages = ['home', 'about', 'guides', 'faq', 'privacy', 'cookies', 'contact'];
        if (!in_array($page, $validPages, true)) {
            $page = 'home';
        }

        $data = [
            'page' => $page,
            'title' => $this->pageTitle($page),
            'taxYears' => TaxYears::all(),
            'form' => $this->defaultFormState(),
            'result' => null,
            'errors' => [],
            'guides' => [
                ['title' => 'How PAYE works', 'body' => 'PAYE deducts Income Tax and National Insurance from employment income throughout the tax year.'],
                ['title' => 'Student loan deductions', 'body' => 'Repayments are based on income over your plan threshold and can stack with postgraduate loans.'],
                ['title' => 'Pension treatment', 'body' => 'Salary sacrifice can reduce both Income Tax and NI, while net pay usually reduces taxable pay only.'],
            ],
            'companyEmail' => 'hello@takehomepay.local',
        ];

        if ($page === 'home' && strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            $data['form'] = array_merge($data['form'], $post);
            $errors = $this->validate($data['form']);
            $data['errors'] = $errors;

            if ($errors === []) {
                $calculator = new TakeHomePayCalculator();
                $data['result'] = $calculator->calculate([
                    'salary' => $data['form']['salary'] ?? 0,
                    'salary_period' => $data['form']['salary_period'] ?? 'annual',
                    'bonus' => $data['form']['bonus'] ?? 0,
                    'tax_year' => $data['form']['tax_year'] ?? '2026-2027',
                    'region' => $data['form']['region'] ?? 'england',
                    'tax_code' => $data['form']['tax_code'] ?? '1257L',
                    'pension_percent' => $data['form']['pension_percent'] ?? 0,
                    'pension_method' => $data['form']['pension_method'] ?? 'salary_sacrifice',
                    'student_loan_plan' => $data['form']['student_loan_plan'] ?? 'none',
                    'has_postgraduate_loan' => isset($data['form']['has_postgraduate_loan']) && $data['form']['has_postgraduate_loan'] === '1',
                ]);
            }
        }

        ob_start();
        $format = new Format();
        extract($data, EXTR_SKIP);
        require dirname(__DIR__, 2) . '/templates/layout.php';
        $content = (string) ob_get_clean();

        return ['status' => 200, 'content' => $content];
    }

    private function pageTitle(string $page): string
    {
        return match ($page) {
            'about' => 'About the calculator',
            'guides' => 'UK tax guides',
            'faq' => 'Frequently asked questions',
            'privacy' => 'Privacy policy',
            'cookies' => 'Cookie policy',
            'contact' => 'Contact',
            default => 'UK Take Home Pay Calculator',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultFormState(): array
    {
        return [
            'salary' => '',
            'salary_period' => 'annual',
            'bonus' => '0',
            'tax_year' => '2026-2027',
            'region' => 'england',
            'tax_code' => '1257L',
            'pension_percent' => '5',
            'pension_method' => 'salary_sacrifice',
            'student_loan_plan' => 'none',
            'has_postgraduate_loan' => '0',
        ];
    }

    /**
     * @param array<string, mixed> $form
     * @return array<int, string>
     */
    private function validate(array $form): array
    {
        $errors = [];

        if (!is_numeric((string) ($form['salary'] ?? '')) || (float) $form['salary'] <= 0) {
            $errors[] = 'Enter a salary greater than zero.';
        }

        if (!is_numeric((string) ($form['bonus'] ?? '0')) || (float) $form['bonus'] < 0) {
            $errors[] = 'Bonus must be zero or more.';
        }

        if (!is_numeric((string) ($form['pension_percent'] ?? '0')) || (float) $form['pension_percent'] < 0 || (float) $form['pension_percent'] > 100) {
            $errors[] = 'Pension contribution must be between 0 and 100 percent.';
        }

        return $errors;
    }
}
