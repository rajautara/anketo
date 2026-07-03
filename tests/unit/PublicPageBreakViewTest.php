<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class PublicPageBreakViewTest extends CIUnitTestCase
{
    public function testPageBreakSplitsPublicFormWithoutAnswerInput(): void
    {
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
                    'field_type'  => 'text',
                    'label'       => 'First Name',
                    'field_key'   => 'first_name',
                    'placeholder' => '',
                    'help_text'   => null,
                    'options'     => null,
                    'is_required' => true,
                    'conditions'  => null,
                ],
                [
                    'id'          => 2,
                    'form_id'     => 10,
                    'field_type'  => 'page_break',
                    'label'       => 'Contact Details',
                    'field_key'   => 'page_break',
                    'placeholder' => null,
                    'help_text'   => null,
                    'options'     => null,
                    'is_required' => false,
                    'conditions'  => null,
                ],
                [
                    'id'          => 3,
                    'form_id'     => 10,
                    'field_type'  => 'email',
                    'label'       => 'Email',
                    'field_key'   => 'email',
                    'placeholder' => '',
                    'help_text'   => null,
                    'options'     => null,
                    'is_required' => true,
                    'conditions'  => null,
                ],
            ],
            'submitted'   => false,
            'errors'      => [],
            'bookedSlots' => [],
            'formConfig'  => [],
        ]);

        $this->assertStringContainsString('data-ak-paged-form', $html);
        $this->assertStringContainsString('Step 2 of 2', $html);
        $this->assertStringContainsString('Contact Details', $html);
        $this->assertStringContainsString('name="answers[first_name]"', $html);
        $this->assertStringContainsString('name="answers[email]"', $html);
        $this->assertStringNotContainsString('name="answers[page_break]"', $html);
    }
}
