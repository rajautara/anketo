<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class PublicAddressViewTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        session()->remove('_ci_old_input');
        parent::tearDown();
    }

    public function testAddressFieldRendersSubfieldsAndOldInput(): void
    {
        session()->set('_ci_old_input', [
            'post' => [
                'answers' => [
                    'address' => [
                        'street_address'    => '123 Jalan Ampang',
                        'street_address_2'  => 'Unit 5',
                        'city'              => 'Kuala Lumpur',
                        'state_province'    => 'Kuala Lumpur',
                        'postal_zip_code'   => '50450',
                        'country'           => 'Malaysia',
                    ],
                ],
            ],
        ]);

        $html = view('public/show', [
            'form' => [
                'id'                 => 10,
                'title'              => 'Signup',
                'description'        => '',
                'share_token'        => 'abc123',
                'submit_button_text' => 'Send',
                'success_message'    => '',
            ],
            'fields' => [
                [
                    'id'          => 1,
                    'form_id'     => 10,
                    'field_type'  => 'address',
                    'label'       => 'Address',
                    'field_key'   => 'address',
                    'placeholder' => null,
                    'help_text'   => 'Use your mailing address.',
                    'options'     => null,
                    'is_required' => true,
                    'conditions'  => null,
                ],
            ],
            'submitted'   => false,
            'errors'      => ['address' => 'Address is required.'],
            'bookedSlots' => [],
            'formConfig'  => [
                [
                    'key'           => 'address',
                    'label'         => 'Address',
                    'type'          => 'address',
                    'is_required'   => true,
                    'conditions'    => null,
                    'options'       => [],
                    'option_values' => [],
                    'option_labels' => [],
                ],
            ],
        ]);
        $decodedHtml = html_entity_decode($html, ENT_QUOTES | ENT_HTML5);

        $this->assertStringContainsString('name="answers[address][street_address]"', $html);
        $this->assertStringContainsString('name="answers[address][street_address_2]"', $html);
        $this->assertStringContainsString('name="answers[address][city]"', $html);
        $this->assertStringContainsString('name="answers[address][state_province]"', $html);
        $this->assertStringContainsString('name="answers[address][postal_zip_code]"', $html);
        $this->assertStringContainsString('name="answers[address][country]"', $html);

        $this->assertMatchesRegularExpression('/name="answers\[address\]\[street_address\]"[^>]*required/', $html);
        $this->assertDoesNotMatchRegularExpression('/name="answers\[address\]\[street_address_2\]"[^>]*required/', $html);
        $this->assertMatchesRegularExpression('/name="answers\[address\]\[city\]"[^>]*required/', $html);
        $this->assertMatchesRegularExpression('/name="answers\[address\]\[state_province\]"[^>]*required/', $html);
        $this->assertMatchesRegularExpression('/name="answers\[address\]\[postal_zip_code\]"[^>]*required/', $html);
        $this->assertMatchesRegularExpression('/name="answers\[address\]\[country\]"[^>]*required/', $html);

        $this->assertStringContainsString('value="123 Jalan Ampang"', $decodedHtml);
        $this->assertStringContainsString('value="Unit 5"', $decodedHtml);
        $this->assertStringContainsString('value="50450"', $html);
        $this->assertStringContainsString('<option value="">Please Select</option>', $html);
        $this->assertStringContainsString('value="Malaysia" selected', $html);
        $this->assertStringContainsString('is-invalid', $html);
        $this->assertStringContainsString('Address is required.', $html);
    }
}
