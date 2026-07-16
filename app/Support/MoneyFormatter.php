<?php

namespace App\Support;

use InvalidArgumentException;

final class MoneyFormatter
{
    public static function format(string|int $amount, string $currency = '₽'): string
    {
        $amount = trim((string) $amount);

        if (! preg_match('/^-?\d+(?:\.\d{1,2})?$/', $amount)) {
            throw new InvalidArgumentException('Некорректное значение цены.');
        }

        $isNegative = str_starts_with($amount, '-');
        $unsignedAmount = ltrim($amount, '-');
        [$integer, $fraction] = array_pad(explode('.', $unsignedAmount, 2), 2, '');

        $integer = ltrim($integer, '0') ?: '0';
        $integer = preg_replace('/\B(?=(\d{3})+(?!\d))/', ' ', $integer) ?? $integer;
        $fraction = substr(str_pad($fraction, 2, '0'), 0, 2);

        $formatted = ($isNegative ? '-' : '').$integer;

        if ($fraction !== '00') {
            $formatted .= ','.$fraction;
        }

        return $formatted.' '.$currency;
    }
}
