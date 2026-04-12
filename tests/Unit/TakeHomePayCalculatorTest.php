<?php

declare(strict_types=1);

namespace TakeHomePay\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TakeHomePay\Services\TakeHomePayCalculator;

final class TakeHomePayCalculatorTest extends TestCase
{
    private TakeHomePayCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new TakeHomePayCalculator();
    }

    public function testCalculatesStandardEnglishSalary(): void
    {
        $result = $this->calculator->calculate([
            'salary' => 50000,
            'salary_period' => 'annual',
            'bonus' => 0,
            'tax_year' => '2026-2027',
            'region' => 'england',
            'tax_code' => '1257L',
            'pension_percent' => 0,
            'pension_method' => 'salary_sacrifice',
            'student_loan_plan' => 'none',
            'has_postgraduate_loan' => false,
        ]);

        self::assertSame(50000.0, $result['gross_annual']);
        self::assertEqualsWithDelta(7486.0, $result['income_tax'], 0.01);
        self::assertEqualsWithDelta(2994.4, $result['national_insurance'], 0.01);
        self::assertEqualsWithDelta(39519.6, $result['net_annual'], 0.01);
    }

    public function testScotlandAndScottishTaxCodeUseScottishBands(): void
    {
        $result = $this->calculator->calculate([
            'salary' => 50000,
            'tax_year' => '2026-2027',
            'region' => 'england',
            'tax_code' => 'S1257L',
            'pension_percent' => 0,
            'student_loan_plan' => 'none',
            'has_postgraduate_loan' => false,
        ]);

        self::assertSame('scotland', $result['region']);
        self::assertEqualsWithDelta(8982.05, $result['income_tax'], 0.01);
    }

    public function testHighIncomeTapersAllowance(): void
    {
        $result = $this->calculator->calculate([
            'salary' => 110000,
            'tax_year' => '2026-2027',
            'region' => 'england',
            'tax_code' => '1257L',
            'pension_percent' => 0,
            'student_loan_plan' => 'none',
            'has_postgraduate_loan' => false,
        ]);

        self::assertEqualsWithDelta(33432.0, $result['income_tax'], 0.01);
        self::assertEqualsWithDelta(4210.6, $result['national_insurance'], 0.01);
    }

    public function testSpecialTaxCodesAreHandled(): void
    {
        $br = $this->calculator->calculate([
            'salary' => 40000,
            'tax_code' => 'BR',
            'tax_year' => '2026-2027',
        ]);
        $nt = $this->calculator->calculate([
            'salary' => 40000,
            'tax_code' => 'NT',
            'tax_year' => '2026-2027',
        ]);

        self::assertEqualsWithDelta(8000.0, $br['income_tax'], 0.01);
        self::assertEqualsWithDelta(0.0, $nt['income_tax'], 0.01);
    }

    public function testPensionMethodsChangeTaxAndNiDifferently(): void
    {
        $salarySacrifice = $this->calculator->calculate([
            'salary' => 60000,
            'tax_year' => '2026-2027',
            'pension_percent' => 5,
            'pension_method' => 'salary_sacrifice',
        ]);
        $netPay = $this->calculator->calculate([
            'salary' => 60000,
            'tax_year' => '2026-2027',
            'pension_percent' => 5,
            'pension_method' => 'net_pay',
        ]);
        $postTax = $this->calculator->calculate([
            'salary' => 60000,
            'tax_year' => '2026-2027',
            'pension_percent' => 5,
            'pension_method' => 'post_tax',
        ]);

        self::assertLessThan($netPay['national_insurance'], $salarySacrifice['national_insurance']);
        self::assertSame($netPay['income_tax'], $salarySacrifice['income_tax']);
        self::assertGreaterThan($netPay['income_tax'], $postTax['income_tax']);
    }

    public function testStudentLoansSupportUndergraduateAndPostgraduateTogether(): void
    {
        $result = $this->calculator->calculate([
            'salary' => 30000,
            'tax_year' => '2026-2027',
            'student_loan_plan' => 'plan2',
            'has_postgraduate_loan' => true,
        ]);

        self::assertEqualsWithDelta(55.35, $result['student_loan_breakdown']['Plan 2'], 0.01);
        self::assertEqualsWithDelta(540.0, $result['student_loan_breakdown']['Postgraduate Loan'], 0.01);
        self::assertEqualsWithDelta(595.35, $result['student_loan'], 0.01);
    }

    public function testMonthlyAndWeeklyInputPeriodsAreAnnualised(): void
    {
        $monthly = $this->calculator->calculate([
            'salary' => 2500,
            'salary_period' => 'monthly',
            'tax_year' => '2026-2027',
        ]);
        $weekly = $this->calculator->calculate([
            'salary' => 500,
            'salary_period' => 'weekly',
            'tax_year' => '2026-2027',
        ]);

        self::assertSame(30000.0, $monthly['gross_annual']);
        self::assertSame(26000.0, $weekly['gross_annual']);
    }
}
