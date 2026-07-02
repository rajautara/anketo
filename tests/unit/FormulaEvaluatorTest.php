<?php

use App\Libraries\FormulaEvaluator;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Safety + correctness coverage for the calc formula evaluator. No DB required.
 *
 * @internal
 */
final class FormulaEvaluatorTest extends CIUnitTestCase
{
    private FormulaEvaluator $f;

    protected function setUp(): void
    {
        parent::setUp();
        $this->f = new FormulaEvaluator();
    }

    #[DataProvider('arithmeticProvider')]
    public function testArithmetic(string $formula, array $answers, string $expected): void
    {
        $this->assertSame($expected, $this->f->evaluate($formula, $answers));
    }

    public static function arithmeticProvider(): array
    {
        return [
            'addition'            => ['1 + 2', [], '3'],
            'precedence'          => ['2 + 3 * 4', [], '14'],
            'parentheses'        => ['(2 + 3) * 4', [], '20'],
            'division decimal'    => ['10 / 4', [], '2.5'],
            'unary minus'         => ['-5 + 2', [], '-3'],
            'unary in parens'     => ['3 * (-2)', [], '-6'],
            'decimals'            => ['0.5 + 0.25', [], '0.75'],
            'token resolve'       => ['{price} * {qty}', ['price' => '10', 'qty' => '3'], '30'],
            'token decimal'       => ['{a} + {b}', ['a' => '1.5', 'b' => '2'], '3.5'],
            'missing token is 0'  => ['{a} + 5', [], '5'],
            'non-numeric is 0'    => ['{a} + 5', ['a' => 'abc'], '5'],
            'array token is 0'    => ['{a} + 5', ['a' => ['x']], '5'],
            'whole number result' => ['{a} / {b}', ['a' => '9', 'b' => '3'], '3'],
        ];
    }

    #[DataProvider('invalidProvider')]
    public function testInvalidYieldsEmpty(string $formula, array $answers): void
    {
        $this->assertSame('', $this->f->evaluate($formula, $answers), "expected '' for: {$formula}");
    }

    public static function invalidProvider(): array
    {
        return [
            'empty formula'       => ['', []],
            'blank formula'       => ['   ', []],
            'divide by zero'      => ['1 / 0', []],
            'divide by zero token' => ['{a} / {b}', ['a' => '5', 'b' => '0']],
            'letters'             => ['2 + abc', []],
            'function call'       => ['phpinfo()', []],
            'exponent op'         => ['2 ^ 3', []],
            'modulo op'           => ['5 % 2', []],
            'underscore import'   => ['__import + 1', []],
            'mismatched paren'    => ['(1 + 2', []],
            'extra rparen'        => ['1 + 2)', []],
            'double operator'     => ['1 * * 2', []],
            'trailing operator'   => ['1 +', []],
            'double dot number'   => ['1.2.3 + 1', []],
            'bare operator'       => ['*', []],
        ];
    }

    public function testCalcReferencingCalcResolvesToZero(): void
    {
        // A calc field that references another field which is itself absent from
        // the submitted answers (calc targets are excluded upstream) → treated 0.
        $this->assertSame('5', $this->f->evaluate('{total} + 5', []));
    }
}
