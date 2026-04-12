<?php

declare(strict_types=1);

namespace TakeHomePay\Data;

final class TaxYears
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            '2026-2027' => [
                'label' => '2026/27',
                'income_tax' => [
                    'personal_allowance' => 12570.0,
                    'allowance_taper_start' => 100000.0,
                    'allowance_zero_point' => 125140.0,
                    'regions' => [
                        'england' => [
                            ['limit' => 37700.0, 'rate' => 0.20],
                            ['limit' => 125140.0, 'rate' => 0.40],
                            ['limit' => null, 'rate' => 0.45],
                        ],
                        'wales' => [
                            ['limit' => 37700.0, 'rate' => 0.20],
                            ['limit' => 125140.0, 'rate' => 0.40],
                            ['limit' => null, 'rate' => 0.45],
                        ],
                        'northern-ireland' => [
                            ['limit' => 37700.0, 'rate' => 0.20],
                            ['limit' => 125140.0, 'rate' => 0.40],
                            ['limit' => null, 'rate' => 0.45],
                        ],
                        'scotland' => [
                            ['limit' => 3967.0, 'rate' => 0.19],
                            ['limit' => 16956.0, 'rate' => 0.20],
                            ['limit' => 31092.0, 'rate' => 0.21],
                            ['limit' => 62430.0, 'rate' => 0.42],
                            ['limit' => 125140.0, 'rate' => 0.45],
                            ['limit' => null, 'rate' => 0.48],
                        ],
                    ],
                ],
                'ni' => [
                    'primary_threshold' => 12570.0,
                    'upper_earnings_limit' => 50270.0,
                    'main_rate' => 0.08,
                    'additional_rate' => 0.02,
                ],
                'student_loans' => [
                    'plan1' => ['threshold' => 26900.0, 'rate' => 0.09, 'label' => 'Plan 1'],
                    'plan2' => ['threshold' => 29385.0, 'rate' => 0.09, 'label' => 'Plan 2'],
                    'plan4' => ['threshold' => 33795.0, 'rate' => 0.09, 'label' => 'Plan 4'],
                    'plan5' => ['threshold' => 25000.0, 'rate' => 0.09, 'label' => 'Plan 5'],
                    'postgraduate' => ['threshold' => 21000.0, 'rate' => 0.06, 'label' => 'Postgraduate Loan'],
                ],
            ],
            '2025-2026' => [
                'label' => '2025/26',
                'income_tax' => [
                    'personal_allowance' => 12570.0,
                    'allowance_taper_start' => 100000.0,
                    'allowance_zero_point' => 125140.0,
                    'regions' => [
                        'england' => [
                            ['limit' => 37700.0, 'rate' => 0.20],
                            ['limit' => 125140.0, 'rate' => 0.40],
                            ['limit' => null, 'rate' => 0.45],
                        ],
                        'wales' => [
                            ['limit' => 37700.0, 'rate' => 0.20],
                            ['limit' => 125140.0, 'rate' => 0.40],
                            ['limit' => null, 'rate' => 0.45],
                        ],
                        'northern-ireland' => [
                            ['limit' => 37700.0, 'rate' => 0.20],
                            ['limit' => 125140.0, 'rate' => 0.40],
                            ['limit' => null, 'rate' => 0.45],
                        ],
                        'scotland' => [
                            ['limit' => 2827.0, 'rate' => 0.19],
                            ['limit' => 14921.0, 'rate' => 0.20],
                            ['limit' => 31092.0, 'rate' => 0.21],
                            ['limit' => 62430.0, 'rate' => 0.42],
                            ['limit' => 125140.0, 'rate' => 0.45],
                            ['limit' => null, 'rate' => 0.48],
                        ],
                    ],
                ],
                'ni' => [
                    'primary_threshold' => 12570.0,
                    'upper_earnings_limit' => 50270.0,
                    'main_rate' => 0.08,
                    'additional_rate' => 0.02,
                ],
                'student_loans' => [
                    'plan1' => ['threshold' => 26065.0, 'rate' => 0.09, 'label' => 'Plan 1'],
                    'plan2' => ['threshold' => 28470.0, 'rate' => 0.09, 'label' => 'Plan 2'],
                    'plan4' => ['threshold' => 32745.0, 'rate' => 0.09, 'label' => 'Plan 4'],
                    'plan5' => ['threshold' => 25000.0, 'rate' => 0.09, 'label' => 'Plan 5'],
                    'postgraduate' => ['threshold' => 21000.0, 'rate' => 0.06, 'label' => 'Postgraduate Loan'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function for(string $taxYear): array
    {
        $years = self::all();

        return $years[$taxYear] ?? $years['2026-2027'];
    }
}
