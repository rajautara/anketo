<?php

namespace App\Libraries;

/**
 * Evaluates value-update rules for automated fields.
 *
 * Update rules intentionally reuse ConditionEvaluator comparison semantics and
 * FormulaEvaluator arithmetic, so public JS can mirror one small rule model.
 */
class ValueUpdateEvaluator
{
    public const ACTIONS = ['copy', 'set', 'calculate'];

    public function __construct(
        private ?ConditionEvaluator $conditions = null,
        private ?FormulaEvaluator $formula = null,
    ) {
        $this->conditions ??= new ConditionEvaluator();
        $this->formula ??= new FormulaEvaluator();
    }

    /**
     * @param array<int,array<string,mixed>> $updates
     * @param array<string,mixed>            $answers        field_key => submitted value
     * @param array<string,mixed>|null       $formulaAnswers formula-specific values
     *
     * @return string|null The first matching rule's value, or null when no rule matches.
     */
    public function evaluate(array $updates, array $answers, ?array $formulaAnswers = null): ?string
    {
        $formulaAnswers ??= $answers;

        foreach ($updates as $rule) {
            if (! is_array($rule) || ! $this->matches($rule, $answers)) {
                continue;
            }

            $action = (string) ($rule['action'] ?? '');
            if (! in_array($action, self::ACTIONS, true)) {
                continue;
            }

            if ($action === 'copy') {
                $source = (string) ($rule['source'] ?? '');
                $value  = $answers[$source] ?? null;

                return is_scalar($value) ? (string) $value : null;
            }

            if ($action === 'set') {
                return (string) ($rule['value'] ?? '');
            }

            if ($action === 'calculate') {
                $result = $this->formula->evaluate((string) ($rule['formula'] ?? ''), $formulaAnswers);

                return $result !== '' ? $result : null;
            }
        }

        return null;
    }

    /**
     * @param array<string,mixed> $rule
     * @param array<string,mixed> $answers
     */
    private function matches(array $rule, array $answers): bool
    {
        $when = $rule['when'] ?? [];
        if (! is_array($when) || $when === []) {
            return false;
        }

        $matchAll = ($rule['match'] ?? 'all') !== 'any';

        foreach ($when as $cond) {
            if (! is_array($cond)) {
                continue;
            }

            $ok = $this->conditions->compare(
                (string) ($cond['operator'] ?? ''),
                $answers[(string) ($cond['field'] ?? '')] ?? null,
                (string) ($cond['value'] ?? '')
            );

            if ($matchAll && ! $ok) {
                return false;
            }
            if (! $matchAll && $ok) {
                return true;
            }
        }

        return $matchAll;
    }
}
