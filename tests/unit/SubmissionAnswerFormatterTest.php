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

    public function testStoresAddressAsJson(): void
    {
        $stored = $this->formatter->storedValueForField($this->addressField(), [
            'street_address'    => '123 Jalan Ampang',
            'street_address_2'  => 'Unit 5',
            'city'              => 'Kuala Lumpur',
            'state_province'    => 'Kuala Lumpur',
            'postal_zip_code'   => '50450',
            'country'           => 'Malaysia',
        ]);

        $this->assertIsString($stored);
        $this->assertSame([
            'street_address'    => '123 Jalan Ampang',
            'street_address_2'  => 'Unit 5',
            'city'              => 'Kuala Lumpur',
            'state_province'    => 'Kuala Lumpur',
            'postal_zip_code'   => '50450',
            'country'           => 'Malaysia',
        ], json_decode($stored, true));
    }

    public function testBlankAddressStoresNull(): void
    {
        $this->assertNull($this->formatter->storedValueForField($this->addressField(), [
            'street_address'    => '',
            'street_address_2'  => '',
            'city'              => '',
            'state_province'    => '',
            'postal_zip_code'   => '',
            'country'           => '',
        ]));
    }

    public function testFormatsAddressWithoutBlankLineTwo(): void
    {
        $stored = json_encode([
            'street_address'    => '123 Jalan Ampang',
            'street_address_2'  => '',
            'city'              => 'Kuala Lumpur',
            'state_province'    => 'Kuala Lumpur',
            'postal_zip_code'   => '50450',
            'country'           => 'Malaysia',
        ]);

        $this->assertSame(
            "123 Jalan Ampang\nKuala Lumpur, Kuala Lumpur, 50450\nMalaysia",
            $this->formatter->format(['value' => $stored, 'file_path' => null], $this->addressField())
        );
    }

    private function choiceField(string $type, array $options): array
    {
        return [
            'field_type' => $type,
            'options'    => $options,
        ];
    }

    private function addressField(): array
    {
        return [
            'field_type' => 'address',
            'options'    => null,
        ];
    }
}
