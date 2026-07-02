<?php

use App\Libraries\ConditionEvaluator;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Pure-logic coverage for conditional-rule evaluation. No DB required.
 *
 * @internal
 */
final class ConditionEvaluatorTest extends CIUnitTestCase
{
    private ConditionEvaluator $e;

    protected function setUp(): void
    {
        parent::setUp();
        $this->e = new ConditionEvaluator();
    }

    private function rule(string $action, array $when, string $match = 'all'): array
    {
        return ['action' => $action, 'match' => $match, 'when' => $when];
    }

    private function when(string $field, string $op, string $value = ''): array
    {
        return ['field' => $field, 'operator' => $op, 'value' => $value];
    }

    // ---- compare() operators -------------------------------------------------

    #[DataProvider('operatorProvider')]
    public function testCompare(string $op, $answer, string $value, bool $expected): void
    {
        $this->assertSame($expected, $this->e->compare($op, $answer, $value));
    }

    public static function operatorProvider(): array
    {
        return [
            'equals scalar hit'          => ['equals', 'US', 'US', true],
            'equals scalar miss'         => ['equals', 'CA', 'US', false],
            'equals trims'               => ['equals', '  US ', 'US', true],
            'not_equals scalar'          => ['not_equals', 'CA', 'US', true],
            'contains scalar hit'        => ['contains', 'hello world', 'world', true],
            'contains scalar miss'       => ['contains', 'hello', 'world', false],
            'not_contains scalar'        => ['not_contains', 'hello', 'world', true],
            'empty on empty string'      => ['empty', '', 'x', true],
            'empty on null'              => ['empty', null, 'x', true],
            'empty on value'             => ['empty', 'a', 'x', false],
            'not_empty on value'         => ['not_empty', 'a', 'x', true],
            'gt numeric hit'             => ['gt', '10', '5', true],
            'gt numeric miss'            => ['gt', '3', '5', false],
            'lt numeric hit'            => ['lt', '3', '5', true],
            'gt non-numeric answer'      => ['gt', 'abc', '5', false],
            'gt non-numeric value'       => ['gt', '5', 'abc', false],
            // checkbox arrays
            'array contains hit'         => ['contains', ['a', 'b'], 'b', true],
            'array contains miss'        => ['contains', ['a', 'b'], 'c', false],
            'array not_contains'         => ['not_contains', ['a'], 'c', true],
            'array empty'                => ['empty', [], 'x', true],
            'array not_empty'            => ['not_empty', ['a'], 'x', true],
            'array equals single'        => ['equals', ['a'], 'a', true],
            'array equals multi is false' => ['equals', ['a', 'b'], 'a', false],
            'array gt is false'          => ['gt', ['1'], '0', false],
            'unknown operator'           => ['whatever', 'a', 'a', false],
        ];
    }

    // ---- match all / any -----------------------------------------------------

    public function testMatchAllRequiresEvery(): void
    {
        $rules   = [$this->rule('show', [$this->when('a', 'equals', '1'), $this->when('b', 'equals', '2')], 'all')];
        $this->assertTrue($this->e->evaluate($rules, ['a' => '1', 'b' => '2'])['visible']);
        $this->assertFalse($this->e->evaluate($rules, ['a' => '1', 'b' => 'x'])['visible']);
    }

    public function testMatchAnyRequiresOne(): void
    {
        $rules = [$this->rule('show', [$this->when('a', 'equals', '1'), $this->when('b', 'equals', '2')], 'any')];
        $this->assertTrue($this->e->evaluate($rules, ['a' => 'x', 'b' => '2'])['visible']);
        $this->assertFalse($this->e->evaluate($rules, ['a' => 'x', 'b' => 'y'])['visible']);
    }

    // ---- base-state semantics ------------------------------------------------

    public function testNoRulesIsVisibleAndUsesBaseRequired(): void
    {
        $this->assertSame(['visible' => true, 'required' => false, 'disabled' => false], $this->e->evaluate([], []));
        $this->assertTrue($this->e->evaluate([], [], true)['required']);
    }

    public function testShowHiddenUntilMatched(): void
    {
        $rules = [$this->rule('show', [$this->when('a', 'equals', 'yes')])];
        $this->assertFalse($this->e->evaluate($rules, ['a' => 'no'])['visible']);
        $this->assertTrue($this->e->evaluate($rules, ['a' => 'yes'])['visible']);
    }

    public function testHideForcesHiddenEvenWhenShownByBase(): void
    {
        $rules = [$this->rule('hide', [$this->when('a', 'equals', 'x')])];
        $this->assertFalse($this->e->evaluate($rules, ['a' => 'x'])['visible']);
        $this->assertTrue($this->e->evaluate($rules, ['a' => 'y'])['visible']);
    }

    public function testHideWinsOverShow(): void
    {
        $rules = [
            $this->rule('show', [$this->when('a', 'equals', 'x')]),
            $this->rule('hide', [$this->when('b', 'equals', 'x')]),
        ];
        // show matches, but hide also matches → hidden.
        $this->assertFalse($this->e->evaluate($rules, ['a' => 'x', 'b' => 'x'])['visible']);
    }

    public function testRequireComposesWithBase(): void
    {
        $rules = [$this->rule('require', [$this->when('a', 'equals', 'yes')])];
        $this->assertFalse($this->e->evaluate($rules, ['a' => 'no'])['required']);
        $this->assertTrue($this->e->evaluate($rules, ['a' => 'yes'])['required']);
        // base required stays required even when the rule doesn't match.
        $this->assertTrue($this->e->evaluate($rules, ['a' => 'no'], true)['required']);
    }

    public function testDisableWhenMatched(): void
    {
        $rules = [$this->rule('disable', [$this->when('a', 'equals', 'lock')])];
        $this->assertFalse($this->e->evaluate($rules, ['a' => 'open'])['disabled']);
        $this->assertTrue($this->e->evaluate($rules, ['a' => 'lock'])['disabled']);
    }

    public function testCheckboxAnswerDrivesVisibility(): void
    {
        $rules = [$this->rule('show', [$this->when('topics', 'contains', 'sales')])];
        $this->assertTrue($this->e->evaluate($rules, ['topics' => ['sales', 'support']])['visible']);
        $this->assertFalse($this->e->evaluate($rules, ['topics' => ['support']])['visible']);
    }

    public function testInvalidRulesAreIgnored(): void
    {
        $rules = ['nonsense', ['action' => 'bogus', 'when' => []], $this->rule('show', [$this->when('a', 'equals', '1')])];
        $this->assertTrue($this->e->evaluate($rules, ['a' => '1'])['visible']);
    }
}
