<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\FrenchNumber;
use App\Support\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FrenchNumberTest extends TestCase
{
    /** @return array<string, array{int, string}> */
    public static function amounts(): array
    {
        return [
            '150 000' => [150000, 'cent cinquante mille'],
            '1 250 000' => [1250000, 'un million deux cent cinquante mille'],
            '923 400' => [923400, 'neuf cent vingt-trois mille quatre cents'],
            '71' => [71, 'soixante et onze'],
            '80' => [80, 'quatre-vingts'],
            '95 000' => [95000, 'quatre-vingt-quinze mille'],
            '200 000 000' => [200000000, 'deux cents millions'],
        ];
    }

    #[DataProvider('amounts')]
    public function test_amount_in_words(int $amount, string $expected): void
    {
        $this->assertSame($expected, FrenchNumber::toWords($amount));
    }

    public function test_money_rules(): void
    {
        $this->assertSame(95000, Money::prorata(150000, 19, 30));
        $this->assertSame(21600, Money::percent(120000, 18));
        $this->assertSame("1\u{00A0}250\u{00A0}000\u{00A0}FCFA", Money::format(1250000));
        $this->assertSame(1250000, Money::parse('1 250 000'));
    }
}
