<?php

namespace App\Libraries;

use App\Models\FormFieldModel;

class SubmissionAnswerFormatter
{
    public function format(?array $answer, ?array $field = null): string
    {
        if ($answer === null) {
            return '';
        }

        if (! empty($answer['file_path'])) {
            return (string) ($answer['value'] ?? $answer['file_path']);
        }

        $value = $answer['value'] ?? null;
        $productAnswer = ProductList::formatAnswer($value);
        if ($productAnswer !== null) {
            return $productAnswer;
        }

        if ($value === null || $value === '') {
            return '';
        }

        if (is_array($field) && ($field['field_type'] ?? '') === 'checkbox') {
            $decoded = json_decode((string) $value, true);
            if (is_array($decoded)) {
                return implode(', ', $this->mapOptionLabels($field, array_map('strval', $decoded)));
            }
        }

        if (is_array($field) && in_array($field['field_type'] ?? '', ['radio', 'select'], true)) {
            return $this->optionLabel($field, (string) $value);
        }

        if (is_string($value) && str_starts_with(trim($value), '[')) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return implode(', ', array_map('strval', $decoded));
            }
        }

        return (string) $value;
    }

    public function storedValueForField(array $field, $submitted): ?string
    {
        $type = (string) ($field['field_type'] ?? '');

        if (in_array($type, ['radio', 'select'], true)) {
            $value = trim((string) $submitted);

            return $value !== '' ? $this->optionLabel($field, $value) : null;
        }

        if ($type === 'checkbox') {
            $submitted = is_array($submitted) ? array_map('strval', $submitted) : [];
            $labels = $this->mapOptionLabels($field, $submitted);

            return $labels !== [] ? json_encode($labels) : null;
        }

        $value = trim((string) $submitted);

        return $value !== '' ? $value : null;
    }

    private function optionLabel(array $field, string $value): string
    {
        foreach ($this->optionMap($field) as $storedValue => $label) {
            if ($storedValue === $value) {
                return $label;
            }
        }

        return $value;
    }

    /**
     * @param list<string> $values
     *
     * @return list<string>
     */
    private function mapOptionLabels(array $field, array $values): array
    {
        $map = $this->optionMap($field);

        return array_values(array_map(
            static fn (string $value): string => $map[$value] ?? $value,
            $values
        ));
    }

    /**
     * @return array<string,string>
     */
    private function optionMap(array $field): array
    {
        if (! in_array($field['field_type'] ?? '', FormFieldModel::OPTION_FIELD_TYPES, true)) {
            return [];
        }

        $map = [];
        foreach (is_array($field['options'] ?? null) ? $field['options'] : [] as $option) {
            if (! is_array($option) || ! isset($option['value'], $option['label'])) {
                continue;
            }
            $map[(string) $option['value']] = (string) $option['label'];
        }

        return $map;
    }
}
