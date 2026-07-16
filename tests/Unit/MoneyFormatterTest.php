<?php

namespace Tests\Unit;

use App\Support\MoneyFormatter;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MoneyFormatterTest extends TestCase
{
    public function test_it_formats_decimal_strings_without_float_conversion(): void
    {
        $this->assertSame('1 000 ₽', MoneyFormatter::format('1000.00'));
        $this->assertSame('1 000,50 ₽', MoneyFormatter::format('1000.50'));
        $this->assertSame('999 ₽', MoneyFormatter::format(999));
    }

    public function test_it_rejects_invalid_amounts(): void
    {
        $this->expectException(InvalidArgumentException::class);

        MoneyFormatter::format('1000,50');
    }
}
