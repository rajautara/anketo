<?php

use App\Libraries\SubmissionAnswerFormatter;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class SubmissionAnswerFormatterTest extends CIUnitTestCase
{
    private SubmissionAnswerFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new SubmissionAnswerFormatter();
    }

    public function testFormatsSelectInternalValueAsLabel(): void
    {
        $this->assertSame('Selangor', $this->formatter->format(
            ['value' => 'option_8', 'file_path' => null],
            $this->choiceField('select', [['value' => 'option_8', 'label' => 'Selangor']])
        ));
    }

    public function testFormatsCheckboxInternalValuesAsLabels(): void
    {
        $this->assertSame('Email, Phone', $this->formatter->format(
            ['value' => '["option_1","option_2"]', 'file_path' => null],
            $this->choiceField('checkbox', [
                ['value' => 'option_1', 'label' => 'Email'],
                ['value' => 'option_2', 'label' => 'Phone'],
            ])
        ));
    }

    public function testStoresChoiceLabelsForFutureSubmissions(): void
    {
        $field = $this->choiceField('radio', [['value' => 'option_2', 'label' => 'Female']]);

        $this->assertSame('Female', $this->formatter->storedValueForField($field, 'option_2'));
    }

    public function testAlreadyStoredLabelIsLeftAlone(): void
    {
        $this->assertSame('Female', $this->formatter->format(
            ['value' => 'Female', 'file_path' => null],
            $this->choiceField('radio', [['value' => 'option_2', 'label' => 'Female']])
        ));
    }

    private function choiceField(string $type, array $options): array
    {
        return [
            'field_type' => $type,
            'options'    => $options,
        ];
    }
}
