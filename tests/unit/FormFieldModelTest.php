<?php

use App\Models\FormFieldModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class FormFieldModelTest extends CIUnitTestCase
{
    public function testPageBreakIsSupportedAndDisplayOnly(): void
    {
        $this->assertContains('page_break', FormFieldModel::FIELD_TYPES);
        $this->assertContains('page_break', FormFieldModel::DISPLAY_ONLY_TYPES);
        $this->assertNotContains('page_break', FormFieldModel::OPTION_FIELD_TYPES);
        $this->assertNotContains('page_break', FormFieldModel::CONFIG_FIELD_TYPES);
    }

    public function testPageBreakDefaultLabel(): void
    {
        $model = new FormFieldModel();

        $this->assertSame('Page Break', $model->defaultLabelFor('page_break'));
    }

    public function testReviewBeforeSubmitIsSupportedAndDisplayOnly(): void
    {
        $this->assertContains('review_before_submit', FormFieldModel::FIELD_TYPES);
        $this->assertContains('review_before_submit', FormFieldModel::DISPLAY_ONLY_TYPES);
        $this->assertNotContains('review_before_submit', FormFieldModel::OPTION_FIELD_TYPES);
        $this->assertContains('review_before_submit', FormFieldModel::CONFIG_FIELD_TYPES);
    }

    public function testReviewBeforeSubmitDefaultLabel(): void
    {
        $model = new FormFieldModel();

        $this->assertSame('Review Before Submit', $model->defaultLabelFor('review_before_submit'));
    }

    public function testTextFieldSupportsConfigOptions(): void
    {
        $this->assertContains('text', FormFieldModel::CONFIG_FIELD_TYPES);
    }

    public function testAddressIsSupportedAsInputField(): void
    {
        $this->assertContains('address', FormFieldModel::FIELD_TYPES);
        $this->assertNotContains('address', FormFieldModel::DISPLAY_ONLY_TYPES);
        $this->assertNotContains('address', FormFieldModel::OPTION_FIELD_TYPES);
        $this->assertNotContains('address', FormFieldModel::CONFIG_FIELD_TYPES);
    }

    public function testAddressDefaultLabel(): void
    {
        $model = new FormFieldModel();

        $this->assertSame('Address', $model->defaultLabelFor('address'));
    }
}
