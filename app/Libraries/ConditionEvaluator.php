<?php

namespace App\Libraries;

/**
 * Evaluates a single field's conditional-logic rules against a map of submitted
 * answers, producing the field's effective {visible, required, disabled} state.
 *
 * Pure logic (no framework/DB dependencies) so it can be unit-tested directly
 * and its `compare()` semantics can be mirrored verbatim by the public-form JS.
 *
 * Rule shape (see FormFieldModel `conditions` JSON):
 *   ['action' => 'show|hide|require|disable', 'match' => 'all|any',
 *    'when' => [['field' => key, 'operator' => op, 'value' => str], ...]]
 *
 * $answers maps field_key => submitted value (string for scalar fields,
 * array for checkbox fields).
 */
class ConditionEvaluator
{
    public const ACTIONS   = ['show', 'hide', 'require', 'disable'];
    public const OPERATORS = ['equals', 'not_equals', 'contains', 'not_contains', 'empty', 'not_empty', 'gt', 'lt'];

    /**
     * @param array<int,array<string,mixed>> $rules        The field's conditions['rules'] list.
     * @param array<string,mixed>            $answers      field_key => submitted value.
     * @param bool                           $baseRequired The field's stored is_required flag.
     *
     * @return array{visible:bool, required:bool, disabled:bool}
     */
    public function evaluate(array $rules, array $answers, bool $baseRequired = false): array
    {
        $hasShow    = false;
        $showMatch  = false;
        $hideMatch  = false;
        $required   = $baseRequired;
        $disabled   = false;

        foreach ($rules as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $action = $rule['action'] ?? '';
            if (! in_array($action, self::ACTIONS, true)) {
                continue;
            }

            $matched = $this->groupMatches($rule, $answers);

            switch ($action) {
                case 'show':
                    $hasShow = true;
                    $showMatch = $showMatch || $matched;
                    break;
                case 'hide':
                    $hideMatch = $hideMatch || $matched;
                    break;
                case 'require':
                    $required = $required || $matched;
                    break;
                case 'disable':
                    $disabled = $disabled || $matched;
                    break;
            }
        }

        // Base state: hidden-until-matched when a show rule exists; a matched
        // hide rule always forces hidden.
        $visible = $hasShow ? $showMatch : true;
        if ($hideMatch) {
            $visible = false;
        }

        return [
            'visible'  => $visible,
            'required' => $required,
            'disabled' => $disabled,
        ];
    }

    /**
     * Whether a rule's `when` conditions are satisfied under its match mode.
     */
    private function groupMatches(array $rule, array $answers): bool
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

            $op     = (string) ($cond['operator'] ?? '');
            $field  = (string) ($cond['field'] ?? '');
            $value  = (string) ($cond['value'] ?? '');
            $answer = $answers[$field] ?? null;

            $ok = $this->compare($op, $answer, $value);

            if ($matchAll && ! $ok) {
                return false;
            }
            if (! $matchAll && $ok) {
                return true;
            }
        }

        // all → every condition passed; any → none passed.
        return $matchAll;
    }

    /**
     * Compare a submitted answer against a rule value. Semantics MUST match the
     * public-form JS (public/assets/js/conditions.js) exactly.
     *
     * @param mixed $answer scalar string, array (checkbox), or null.
     */
    public function compare(string $operator, $answer, string $value): bool
    {
        $isList  = is_array($answer);
        $scalar  = $isList ? '' : trim((string) ($answer ?? ''));
        $isEmpty = $isList ? ($answer === []) : ($scalar === '');

        switch ($operator) {
            case 'empty':
                return $isEmpty;
            case 'not_empty':
                return ! $isEmpty;

            case 'equals':
                return $isList
                    ? in_array($value, array_map('strval', $answer), true)
                    : ($scalar === $value);
            case 'not_equals':
                return $isList
                    ? ! in_array($value, array_map('strval', $answer), true)
                    : ($scalar !== $value);

            case 'contains':
                return $isList
                    ? in_array($value, array_map('strval', $answer), true)
                    : ($value !== '' && str_contains($scalar, $value));
            case 'not_contains':
                return $isList
                    ? ! in_array($value, array_map('strval', $answer), true)
                    : ! ($value !== '' && str_contains($scalar, $value));

            case 'gt':
            case 'lt':
                if ($isList || ! is_numeric($scalar) || ! is_numeric($value)) {
                    return false;
                }
                return $operator === 'gt'
                    ? ((float) $scalar > (float) $value)
                    : ((float) $scalar < (float) $value);
        }

        return false;
    }
}
