<?php

use App\Libraries\FormAccess;
use App\Models\FormCollaboratorModel;
use App\Models\FormModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class FormAccessTest extends CIUnitTestCase
{
    public function testOwnerHasFullAccess(): void
    {
        $access = (new FormAccess(new StubFormCollaboratorModel(null)))->permissions($this->form(), 10);

        $this->assertTrue($access['isOwner']);
        $this->assertTrue($access['canEditForm']);
        $this->assertTrue($access['canExportSubmissions']);
        $this->assertTrue($access['canResetSubmissions']);
        $this->assertTrue($access['canManageCollaborators']);
    }

    public function testUnassignedUserHasNoAccess(): void
    {
        $access = (new FormAccess(new StubFormCollaboratorModel(null)))->permissions($this->form(), 11);

        $this->assertFalse($access['isOwner']);
        $this->assertFalse($access['canViewForm']);
        $this->assertFalse($access['canViewSubmissions']);
        $this->assertFalse($access['canExportSubmissions']);
    }

    public function testAdminFlagDoesNotGrantFormModelAccess(): void
    {
        $this->assertFalse((new FormModel())->userCanAccess(10, 11, true));
    }

    public function testCollaboratorAccessIsSplitBetweenFormAndSubmissions(): void
    {
        $model = new StubFormCollaboratorModel([
            'form_access'       => 'view',
            'submission_access' => 'export',
        ]);

        $access = (new FormAccess($model))->permissions($this->form(), 11);

        $this->assertTrue($access['canViewForm']);
        $this->assertFalse($access['canEditForm']);
        $this->assertTrue($access['canViewSubmissions']);
        $this->assertTrue($access['canExportSubmissions']);
        $this->assertFalse($access['canResetSubmissions']);
    }

    private function form(): array
    {
        return [
            'id'      => 101,
            'user_id' => 10,
        ];
    }
}

final class StubFormCollaboratorModel extends FormCollaboratorModel
{
    public function __construct(private ?array $row)
    {
    }

    public function findForFormAndUser(int $formId, int $userId): ?array
    {
        return $this->row;
    }
}
