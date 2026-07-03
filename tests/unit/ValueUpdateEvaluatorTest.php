<?php

use App\Libraries\ValueUpdateEvaluator;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Pure-logic coverage for automated value-update rules. No DB required.
 *
 * @internal
 */
final class ValueUpdateEvaluatorTest extends CIUnitTestCase
{
    private ValueUpdateEvaluator $e;

    protected function setUp(): void
    {
        parent::setUp();
        $this->e = new ValueUpdateEvaluator();
    }

    public function testCopyFromAnotherScalarField(): void
    {
        $updates = [[
            'match'  => 'all',
            'when'   => [$this->when('same_as_shipping', 'equals', 'yes')],
            'action' => 'copy',
            'source' => 'shipping_address',
        ]];

        $this->assertSame('10 High Street', $this->e->evaluate($updates, [
            'same_as_shipping' => 'yes',
            'shipping_address' => '10 High Street',
        ]));
    }

    public function testSetLiteralValue(): void
    {
        $updates = [[
            'match'  => 'all',
            'when'   => [$this->when('plan', 'equals', 'enterprise')],
            'action' => 'set',
            'value'  => 'Priority',
        ]];

        $this->assertSame('Priority', $this->e->evaluate($updates, ['plan' => 'enterprise']));
    }

    public function testCalculateFormula(): void
    {
        $updates = [[
            'match'   => 'all',
            'when'    => [$this->when('qty', 'gt', '0')],
            'action'  => 'calculate',
            'formula' => '{price} * {qty}',
        ]];

        $this->assertSame('37.5', $this->e->evaluate($updates, [
            'price' => '12.5',
            'qty'   => '3',
        ]));
    }

    public function testCalculateCanUseFormulaSpecificAnswers(): void
    {
        $updates = [[
            'match'   => 'all',
            'when'    => [$this->when('dropdown', 'not_empty')],
            'action'  => 'calculate',
            'formula' => '{Dropdown} + 20',
        ]];

        $this->assertSame('22', $this->e->evaluate(
            $updates,
            ['dropdown' => 'option_2'],
            ['dropdown' => '2', 'Dropdown' => '2']
        ));
    }

    public function testNoMatchReturnsNull(): void
    {
        $updates = [[
            'match'  => 'all',
            'when'   => [$this->when('country', 'equals', 'MY')],
            'action' => 'set',
            'value'  => 'Local',
        ]];

        $this->assertNull($this->e->evaluate($updates, ['country' => 'US']));
    }

    public function testFirstMatchingRuleWins(): void
    {
        $updates = [
            [
                'match'  => 'all',
                'when'   => [$this->when('tier', 'not_empty')],
                'action' => 'set',
                'value'  => 'First',
            ],
            [
                'match'  => 'all',
                'when'   => [$this->when('tier', 'equals', 'gold')],
                'action' => 'set',
                'value'  => 'Second',
            ],
        ];

        $this->assertSame('First', $this->e->evaluate($updates, ['tier' => 'gold']));
    }

    public function testInvalidCopySourceReturnsNull(): void
    {
        $updates = [[
            'match'  => 'all',
            'when'   => [$this->when('copy', 'equals', 'yes')],
            'action' => 'copy',
            'source' => 'missing',
        ]];

        $this->assertNull($this->e->evaluate($updates, ['copy' => 'yes']));
    }

    private function when(string $field, string $op, string $value = ''): array
    {
        return ['field' => $field, 'operator' => $op, 'value' => $value];
    }
}
