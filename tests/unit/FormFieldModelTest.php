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
}
