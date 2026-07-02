<?php

namespace App\Libraries;

/**
 * Evaluates a calculation formula for a "calc" field, server-side and safely.
 *
 * Supports only: numeric literals, {field_key} references (resolved from the
 * submitted answers, coerced to float; missing/non-numeric => 0), the binary
 * operators + - * /, unary minus, and parentheses. Anything else (letters,
 * ^, %, function calls, etc.) is a parse error and yields ''.
 *
 * There is NO eval() and no regex-based math — a hand-written tokenizer +
 * shunting-yard + RPN evaluator. The formula is authored by the (authenticated)
 * form owner and stored server-side; the public visitor supplies only numeric
 * operand values, so there is no code-injection surface.
 */
class FormulaEvaluator
{
    /**
     * @param string              $formula e.g. "{price} * {qty} - 5"
     * @param array<string,mixed> $answers field_key => submitted value
     *
     * @return string Numeric string, or '' on parse error / divide-by-zero / empty formula.
     */
    public function evaluate(string $formula, array $answers): string
    {
        $formula = trim($formula);
        if ($formula === '') {
            return '';
        }

        $substituted = $this->substituteTokens($formula, $answers);
        if ($substituted === null) {
            return '';
        }

        $tokens = $this->tokenize($substituted);
        if ($tokens === null) {
            return '';
        }

        $rpn = $this->toRpn($tokens);
        if ($rpn === null) {
            return '';
        }

        $result = $this->evalRpn($rpn);
        if ($result === null || ! is_finite($result)) {
            return '';
        }

        // Trim a trailing ".0" for whole numbers; keep decimals otherwise.
        if ($result == (int) $result) {
            return (string) (int) $result;
        }

        return rtrim(rtrim(sprintf('%.10F', $result), '0'), '.');
    }

    /**
     * Replace every {field_key} with the resolved numeric value (else 0).
     * Returns null if a token is malformed.
     */
    private function substituteTokens(string $formula, array $answers): ?string
    {
        return preg_replace_callback('/\{([^}]*)\}/', static function (array $m) use ($answers): string {
            $key = trim($m[1]);
            if ($key === '') {
                return '0';
            }
            $val = $answers[$key] ?? null;
            if (is_array($val) || $val === null || ! is_numeric(trim((string) $val))) {
                return '0';
            }

            return '(' . (float) trim((string) $val) . ')';
        }, $formula);
    }

    /**
     * @return list<array{type:string,value:mixed}>|null
     */
    private function tokenize(string $expr): ?array
    {
        $tokens = [];
        $len    = strlen($expr);
        $i      = 0;

        while ($i < $len) {
            $ch = $expr[$i];

            if (ctype_space($ch)) {
                $i++;
                continue;
            }

            if ($ch === '+' || $ch === '-' || $ch === '*' || $ch === '/') {
                $tokens[] = ['type' => 'op', 'value' => $ch];
                $i++;
                continue;
            }

            if ($ch === '(' || $ch === ')') {
                $tokens[] = ['type' => $ch === '(' ? 'lparen' : 'rparen', 'value' => $ch];
                $i++;
                continue;
            }

            // Number: digits with optional single decimal point.
            if (ctype_digit($ch) || $ch === '.') {
                $num  = '';
                $dots = 0;
                while ($i < $len && (ctype_digit($expr[$i]) || $expr[$i] === '.')) {
                    if ($expr[$i] === '.' && ++$dots > 1) {
                        return null;
                    }
                    $num .= $expr[$i];
                    $i++;
                }
                if ($num === '.' || $num === '') {
                    return null;
                }
                $tokens[] = ['type' => 'num', 'value' => (float) $num];
                continue;
            }

            // Any other character is illegal.
            return null;
        }

        return $tokens;
    }

    /**
     * Shunting-yard → RPN, resolving unary minus to a 'neg' operator.
     *
     * @param list<array{type:string,value:mixed}> $tokens
     *
     * @return list<array{type:string,value:mixed}>|null
     */
    private function toRpn(array $tokens): ?array
    {
        $output   = [];
        $stack    = [];
        $prevType = null; // to distinguish unary minus

        $prec = ['neg' => 3, '*' => 2, '/' => 2, '+' => 1, '-' => 1];

        foreach ($tokens as $tok) {
            $type = $tok['type'];

            if ($type === 'num') {
                $output[] = $tok;
                $prevType = 'num';
                continue;
            }

            if ($type === 'op') {
                $op = $tok['value'];

                // Unary minus/plus: at start, after another op, or after '('.
                if (($op === '-' || $op === '+') && ($prevType === null || $prevType === 'op' || $prevType === 'lparen')) {
                    if ($op === '-') {
                        $stack[] = ['type' => 'op', 'value' => 'neg'];
                    }
                    // unary plus is a no-op
                    $prevType = 'op';
                    continue;
                }

                while ($stack !== []) {
                    $top = end($stack);
                    if ($top['type'] === 'op' && $prec[$top['value']] >= $prec[$op]) {
                        $output[] = array_pop($stack);
                    } else {
                        break;
                    }
                }
                $stack[]  = ['type' => 'op', 'value' => $op];
                $prevType = 'op';
                continue;
            }

            if ($type === 'lparen') {
                $stack[]  = $tok;
                $prevType = 'lparen';
                continue;
            }

            if ($type === 'rparen') {
                $found = false;
                while ($stack !== []) {
                    $top = array_pop($stack);
                    if ($top['type'] === 'lparen') {
                        $found = true;
                        break;
                    }
                    $output[] = $top;
                }
                if (! $found) {
                    return null; // mismatched parens
                }
                $prevType = 'rparen';
                continue;
            }
        }

        while ($stack !== []) {
            $top = array_pop($stack);
            if ($top['type'] === 'lparen' || $top['type'] === 'rparen') {
                return null;
            }
            $output[] = $top;
        }

        return $output;
    }

    /**
     * @param list<array{type:string,value:mixed}> $rpn
     */
    private function evalRpn(array $rpn): ?float
    {
        $stack = [];

        foreach ($rpn as $tok) {
            if ($tok['type'] === 'num') {
                $stack[] = (float) $tok['value'];
                continue;
            }

            $op = $tok['value'];

            if ($op === 'neg') {
                if ($stack === []) {
                    return null;
                }
                $stack[] = -array_pop($stack);
                continue;
            }

            if (count($stack) < 2) {
                return null;
            }
            $b = array_pop($stack);
            $a = array_pop($stack);

            switch ($op) {
                case '+': $stack[] = $a + $b; break;
                case '-': $stack[] = $a - $b; break;
                case '*': $stack[] = $a * $b; break;
                case '/':
                    if ($b == 0.0) {
                        return null;
                    }
                    $stack[] = $a / $b;
                    break;
                default:
                    return null;
            }
        }

        return count($stack) === 1 ? $stack[0] : null;
    }
}
