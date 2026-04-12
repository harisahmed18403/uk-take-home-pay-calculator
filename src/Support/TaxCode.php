<?php

declare(strict_types=1);

namespace TakeHomePay\Support;

final class TaxCode
{
    /**
     * @return array{code:string, allowance:float|null, mode:string, region_hint:?string}
     */
    public static function parse(string $rawCode): array
    {
        $code = strtoupper(trim($rawCode));
        $regionHint = null;

        if ($code === '') {
            $code = '1257L';
        }

        if (str_starts_with($code, 'S')) {
            $regionHint = 'scotland';
            $code = substr($code, 1);
        } elseif (str_starts_with($code, 'C')) {
            $regionHint = 'wales';
            $code = substr($code, 1);
        }

        return match ($code) {
            'BR' => ['code' => $rawCode !== '' ? strtoupper(trim($rawCode)) : '1257L', 'allowance' => 0.0, 'mode' => 'basic_only', 'region_hint' => $regionHint],
            'D0' => ['code' => $rawCode !== '' ? strtoupper(trim($rawCode)) : '1257L', 'allowance' => 0.0, 'mode' => 'higher_only', 'region_hint' => $regionHint],
            'D1' => ['code' => $rawCode !== '' ? strtoupper(trim($rawCode)) : '1257L', 'allowance' => 0.0, 'mode' => 'additional_only', 'region_hint' => $regionHint],
            'NT' => ['code' => $rawCode !== '' ? strtoupper(trim($rawCode)) : '1257L', 'allowance' => 0.0, 'mode' => 'no_tax', 'region_hint' => $regionHint],
            default => self::parseStandard($code, $regionHint),
        };
    }

    /**
     * @return array{code:string, allowance:float|null, mode:string, region_hint:?string}
     */
    private static function parseStandard(string $code, ?string $regionHint): array
    {
        if (preg_match('/^K(\d+)[A-Z]*$/', $code, $matches) === 1) {
            return [
                'code' => ($regionHint === 'scotland' ? 'S' : ($regionHint === 'wales' ? 'C' : '')) . $code,
                'allowance' => -((float) $matches[1] * 10),
                'mode' => 'standard',
                'region_hint' => $regionHint,
            ];
        }

        if (preg_match('/^(\d+)[A-Z]*$/', $code, $matches) === 1) {
            return [
                'code' => ($regionHint === 'scotland' ? 'S' : ($regionHint === 'wales' ? 'C' : '')) . $code,
                'allowance' => (float) $matches[1] * 10,
                'mode' => 'standard',
                'region_hint' => $regionHint,
            ];
        }

        return [
            'code' => '1257L',
            'allowance' => 12570.0,
            'mode' => 'standard',
            'region_hint' => $regionHint,
        ];
    }
}
