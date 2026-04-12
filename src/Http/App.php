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
        $validPages = ['home', 'guides', 'faq', 'privacy', 'cookies'];
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
                [
                    'title' => '1. Annualise your pay first',
                    'body' => 'The calculator converts your pay into an annual figure before any deductions are worked out.',
                    'formula' => 'gross_annual = annual salary or (monthly salary × 12) or (weekly salary × 52) + bonus',
                    'steps' => [
                        'Start with the salary amount you entered.',
                        'Convert monthly pay to annual by multiplying by 12, or weekly pay by multiplying by 52.',
                        'Add any bonus or additional income to get gross annual pay.',
                    ],
                ],
                [
                    'title' => '2. Work out taxable pay and deductions',
                    'body' => 'Income Tax, National Insurance, and pension are calculated from slightly different versions of your pay.',
                    'formula' => 'net_annual = gross_annual - income_tax - national_insurance - student_loan - pension',
                    'steps' => [
                        'Pension is gross annual pay multiplied by your pension percentage.',
                        'Taxable pay is reduced by pension for salary sacrifice and net pay arrangements.',
                        'NI-able pay is reduced only for salary sacrifice pension.',
                        'Income Tax is applied band by band after your personal allowance and tax code adjustments.',
                        'National Insurance is charged at the main rate up to the upper earnings limit and the additional rate above it.',
                    ],
                ],
                [
                    'title' => '3. Add student loans and derive take-home pay',
                    'body' => 'Student loan deductions are added after their threshold checks, and then the calculator converts the final net figure into monthly and weekly views.',
                    'formula' => 'student_loan = max(0, gross_annual - threshold) × rate',
                    'steps' => [
                        'For each selected student loan plan, only the earnings above its threshold are charged.',
                        'If a postgraduate loan is selected, it stacks on top of the undergraduate plan.',
                        'Total deductions are added together and subtracted from gross annual pay.',
                        'Monthly net pay is net annual pay divided by 12, and weekly net pay is net annual pay divided by 52.',
                    ],
                ],
            ],
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
            'guides' => 'UK tax guides',
            'faq' => 'Frequently asked questions',
            'privacy' => 'Privacy policy',
            'cookies' => 'Cookie policy',
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
