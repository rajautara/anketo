<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class PublicReviewBeforeSubmitViewTest extends CIUnitTestCase
{
    public function testReviewBeforeSubmitRendersWithoutAnswerInput(): void
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
                    'field_type'  => 'review_before_submit',
                    'label'       => 'Review Before Submit',
                    'field_key'   => 'review_before_submit',
                    'placeholder' => null,
                    'help_text'   => null,
                    'options'     => ['show_hidden_text' => true],
                    'is_required' => false,
                    'conditions'  => null,
                ],
            ],
            'submitted'   => false,
            'errors'      => [],
            'bookedSlots' => [],
            'formConfig'  => [
                [
                    'key'           => 'first_name',
                    'label'         => 'First Name',
                    'type'          => 'text',
                    'is_required'   => true,
                    'conditions'    => null,
                    'option_values' => [],
                    'option_labels' => [],
                ],
            ],
        ]);

        $this->assertStringContainsString('data-ak-review-before-submit', $html);
        $this->assertStringContainsString('data-ak-review-show-hidden-text="1"', $html);
        $this->assertStringContainsString('data-ak-review-summary', $html);
        $this->assertStringContainsString('name="answers[first_name]"', $html);
        $this->assertStringNotContainsString('name="answers[review_before_submit]"', $html);
    }

    public function testHiddenTextFieldStillRendersAnswerInput(): void
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
                    'label'       => 'Internal Status',
                    'field_key'   => 'internal_status',
                    'placeholder' => '',
                    'help_text'   => null,
                    'options'     => ['is_hidden' => true],
                    'is_required' => false,
                    'conditions'  => null,
                ],
            ],
            'submitted'   => false,
            'errors'      => [],
            'bookedSlots' => [],
            'formConfig'  => [
                [
                    'key'           => 'internal_status',
                    'label'         => 'Internal Status',
                    'type'          => 'text',
                    'is_required'   => false,
                    'conditions'    => null,
                    'options'       => ['is_hidden' => true],
                    'option_values' => [],
                    'option_labels' => [],
                ],
            ],
        ]);

        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('name="answers[internal_status]"', $html);
        $this->assertStringContainsString('data-ak-hidden-text="1"', $html);
        $this->assertStringNotContainsString('class="form-control"', $html);
    }
}
