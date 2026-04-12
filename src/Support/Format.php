<?php

declare(strict_types=1);

namespace TakeHomePay\Support;

final class Format
{
    public static function currency(float $amount): string
    {
        return '£' . number_format($amount, 2);
    }

    public static function percent(float $decimal): string
    {
        return number_format($decimal * 100, 1) . '%';
    }
}
