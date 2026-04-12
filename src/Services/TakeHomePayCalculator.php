<?php

declare(strict_types=1);

namespace TakeHomePay\Services;

use TakeHomePay\Data\TaxYears;
use TakeHomePay\Support\TaxCode;

final class TakeHomePayCalculator
{
    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function calculate(array $input): array
    {
        $normalized = $this->normalizeInput($input);
        $taxYearData = TaxYears::for($normalized['tax_year']);
        $taxCode = TaxCode::parse($normalized['tax_code']);
        $region = $taxCode['region_hint'] ?? $normalized['region'];

        $grossAnnual = $this->annualizeSalary($normalized['salary'], $normalized['salary_period']) + $normalized['bonus'];
        $pensionAnnual = round($grossAnnual * ($normalized['pension_percent'] / 100), 2);
        $taxableEarnings = $grossAnnual - ($normalized['pension_method'] === 'post_tax' ? 0.0 : $pensionAnnual);
        $niableEarnings = $grossAnnual - ($normalized['pension_method'] === 'salary_sacrifice' ? $pensionAnnual : 0.0);

        $incomeTax = $this->calculateIncomeTax($taxableEarnings, $taxCode, $taxYearData, $region);
        $nationalInsurance = $this->calculateNationalInsurance($niableEarnings, $taxYearData);
        $studentLoan = $this->calculateStudentLoan(
            $grossAnnual,
            $normalized['student_loan_plan'],
            $normalized['has_postgraduate_loan'],
            $taxYearData
        );
        $postTaxPension = $normalized['pension_method'] === 'post_tax' ? $pensionAnnual : 0.0;
        $totalDeductions = $incomeTax + $nationalInsurance + $studentLoan['total'] + $pensionAnnual;
        $netAnnual = max(0.0, $grossAnnual - $totalDeductions);

        return [
            'inputs' => $normalized,
            'tax_year' => $normalized['tax_year'],
            'tax_year_label' => $taxYearData['label'],
            'region' => $region,
            'tax_code' => $taxCode['code'],
            'gross_annual' => round($grossAnnual, 2),
            'taxable_annual' => round($taxableEarnings, 2),
            'niable_annual' => round($niableEarnings, 2),
            'income_tax' => round($incomeTax, 2),
            'national_insurance' => round($nationalInsurance, 2),
            'student_loan' => round($studentLoan['total'], 2),
            'student_loan_breakdown' => $studentLoan['breakdown'],
            'pension' => round($pensionAnnual, 2),
            'post_tax_pension' => round($postTaxPension, 2),
            'net_annual' => round($netAnnual, 2),
            'net_monthly' => round($netAnnual / 12, 2),
            'net_weekly' => round($netAnnual / 52, 2),
            'total_deductions' => round($totalDeductions, 2),
            'effective_tax_rate' => $grossAnnual > 0 ? round(($totalDeductions / $grossAnnual), 4) : 0.0,
            'assumptions' => [
                'PAYE estimate using annualised earnings.',
                'National Insurance calculated using annual thresholds.',
                'Pension can be treated as salary sacrifice, net pay arrangement, or post-tax deduction.',
                'Student loan repayments use current published thresholds for the selected tax year.',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function normalizeInput(array $input): array
    {
        $salaryPeriod = (string) ($input['salary_period'] ?? 'annual');
        $region = (string) ($input['region'] ?? 'england');
        $pensionMethod = (string) ($input['pension_method'] ?? 'salary_sacrifice');
        $studentLoanPlan = (string) ($input['student_loan_plan'] ?? 'none');

        return [
            'salary' => max(0.0, (float) ($input['salary'] ?? 0)),
            'salary_period' => in_array($salaryPeriod, ['annual', 'monthly', 'weekly'], true)
                ? $salaryPeriod
                : 'annual',
            'bonus' => max(0.0, (float) ($input['bonus'] ?? 0)),
            'tax_year' => (string) ($input['tax_year'] ?? '2026-2027'),
            'region' => in_array($region, ['england', 'wales', 'northern-ireland', 'scotland'], true)
                ? $region
                : 'england',
            'tax_code' => trim((string) ($input['tax_code'] ?? '1257L')),
            'pension_percent' => min(100.0, max(0.0, (float) ($input['pension_percent'] ?? 0))),
            'pension_method' => in_array($pensionMethod, ['salary_sacrifice', 'net_pay', 'post_tax'], true)
                ? $pensionMethod
                : 'salary_sacrifice',
            'student_loan_plan' => in_array($studentLoanPlan, ['none', 'plan1', 'plan2', 'plan4', 'plan5'], true)
                ? $studentLoanPlan
                : 'none',
            'has_postgraduate_loan' => (bool) ($input['has_postgraduate_loan'] ?? false),
        ];
    }

    private function annualizeSalary(float $salary, string $period): float
    {
        return match ($period) {
            'monthly' => $salary * 12,
            'weekly' => $salary * 52,
            default => $salary,
        };
    }

    /**
     * @param array<string, mixed> $taxCode
     * @param array<string, mixed> $taxYearData
     */
    private function calculateIncomeTax(float $taxableEarnings, array $taxCode, array $taxYearData, string $region): float
    {
        if ($taxCode['mode'] === 'no_tax') {
            return 0.0;
        }

        if ($taxCode['mode'] === 'basic_only') {
            return $taxableEarnings * 0.20;
        }

        if ($taxCode['mode'] === 'higher_only') {
            return $taxableEarnings * 0.40;
        }

        if ($taxCode['mode'] === 'additional_only') {
            return $taxableEarnings * 0.45;
        }

        $allowance = $taxCode['allowance'] ?? (float) $taxYearData['income_tax']['personal_allowance'];

        if ($allowance > 0) {
            $taperStart = (float) $taxYearData['income_tax']['allowance_taper_start'];
            if ($taxableEarnings > $taperStart) {
                $allowance = max(0.0, $allowance - floor(($taxableEarnings - $taperStart) / 2));
            }
        }

        $taxableAfterAllowance = max(0.0, $taxableEarnings - $allowance);
        if ($allowance < 0) {
            $taxableAfterAllowance = $taxableEarnings + abs($allowance);
        }

        $bands = $taxYearData['income_tax']['regions'][$region] ?? $taxYearData['income_tax']['regions']['england'];

        return $this->applyBands($taxableAfterAllowance, $bands);
    }

    /**
     * @param array<int, array{limit:?float, rate:float}> $bands
     */
    private function applyBands(float $taxableAmount, array $bands): float
    {
        $remaining = $taxableAmount;
        $lastLimit = 0.0;
        $tax = 0.0;

        foreach ($bands as $band) {
            if ($remaining <= 0) {
                break;
            }

            $limit = $band['limit'];
            $rate = $band['rate'];

            if ($limit === null) {
                $tax += $remaining * $rate;
                break;
            }

            $bandWidth = $limit - $lastLimit;
            $portion = min($remaining, $bandWidth);
            $tax += $portion * $rate;
            $remaining -= $portion;
            $lastLimit = $limit;
        }

        return $tax;
    }

    /**
     * @param array<string, mixed> $taxYearData
     */
    private function calculateNationalInsurance(float $niableEarnings, array $taxYearData): float
    {
        $ni = $taxYearData['ni'];
        $primaryThreshold = (float) $ni['primary_threshold'];
        $upperEarningsLimit = (float) $ni['upper_earnings_limit'];
        $mainBand = max(0.0, min($niableEarnings, $upperEarningsLimit) - $primaryThreshold);
        $additionalBand = max(0.0, $niableEarnings - $upperEarningsLimit);

        return ($mainBand * (float) $ni['main_rate']) + ($additionalBand * (float) $ni['additional_rate']);
    }

    /**
     * @param array<string, mixed> $taxYearData
     * @return array{total:float, breakdown:array<string, float>}
     */
    private function calculateStudentLoan(
        float $grossAnnual,
        string $studentLoanPlan,
        bool $hasPostgraduateLoan,
        array $taxYearData
    ): array {
        $breakdown = [];
        $total = 0.0;
        $plans = $taxYearData['student_loans'];

        if ($studentLoanPlan !== 'none' && isset($plans[$studentLoanPlan])) {
            $undergradThreshold = (float) $plans[$studentLoanPlan]['threshold'];
            $undergrad = max(0.0, ($grossAnnual - $undergradThreshold) * (float) $plans[$studentLoanPlan]['rate']);
            $breakdown[(string) $plans[$studentLoanPlan]['label']] = round($undergrad, 2);
            $total += $undergrad;
        }

        if ($hasPostgraduateLoan) {
            $pgPlan = $plans['postgraduate'];
            $postgraduate = max(0.0, ($grossAnnual - (float) $pgPlan['threshold']) * (float) $pgPlan['rate']);
            $breakdown[(string) $pgPlan['label']] = round($postgraduate, 2);
            $total += $postgraduate;
        }

        return ['total' => round($total, 2), 'breakdown' => $breakdown];
    }
}
