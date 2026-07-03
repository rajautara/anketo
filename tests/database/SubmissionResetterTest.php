<?php

use App\Libraries\SubmissionResetter;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * @internal
 */
final class SubmissionResetterTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate = false;
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
    }

    protected function tearDown(): void
    {
        $this->removeDirectory(WRITEPATH . 'uploads/forms/101');
        $this->removeDirectory(WRITEPATH . 'uploads/paragraph/101');
        $this->removeDirectory(WRITEPATH . 'uploads/products/101');

        parent::tearDown();
    }

    public function testResetDeletesSubmissionsAnswersAndSubmissionFilesOnly(): void
    {
        $this->seedFormGraph();

        $submissionFile = WRITEPATH . 'uploads/forms/101/submission.txt';
        $unrelatedFormFile = WRITEPATH . 'uploads/forms/101/unrelated.txt';
        $paragraphImage = WRITEPATH . 'uploads/paragraph/101/0123456789abcdef.jpg';
        $productImage = WRITEPATH . 'uploads/products/101/0123456789abcdef.jpg';

        $this->writeFile($submissionFile, 'submission');
        $this->writeFile($unrelatedFormFile, 'keep');
        $this->writeFile($paragraphImage, 'paragraph');
        $this->writeFile($productImage, 'product');

        $result = (new SubmissionResetter($this->db))->resetForm(101);

        $this->assertSame(['submissions' => 2, 'files' => 1, 'file_failures' => 0], $result);
        $this->seeNumRecords(0, 'form_submissions', ['form_id' => 101]);
        $this->seeNumRecords(0, 'submission_data', ['submission_id' => 1001]);
        $this->seeNumRecords(0, 'submission_data', ['submission_id' => 1002]);

        $this->assertFileDoesNotExist($submissionFile);
        $this->assertFileExists($unrelatedFormFile);
        $this->assertFileExists($paragraphImage);
        $this->assertFileExists($productImage);
    }

    public function testResetRestoresProductListStock(): void
    {
        $this->seedFormGraph();

        (new SubmissionResetter($this->db))->resetForm(101);

        $field = $this->db->table('form_fields')->where('id', 501)->get()->getRowArray();
        $options = json_decode($field['options'], true);

        $this->assertSame(5, $options['products'][0]['stock']);
    }

    public function testResetRouteDoesNotAllowAnotherRegularUser(): void
    {
        $this->seedFormGraph();
        $this->db->table('users')->insert([
            'id'         => 11,
            'username'   => 'other',
            'active'     => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->withSession(['user' => ['id' => 11]]);

        try {
            $this->post('forms/101/submissions/reset', [
                csrf_token() => csrf_hash(),
            ]);

            $this->fail('Expected a not found exception for an inaccessible form.');
        } catch (PageNotFoundException $exception) {
            $this->assertSame('Form not found.', $exception->getMessage());
        }

        $this->seeNumRecords(2, 'form_submissions', ['form_id' => 101]);
    }

    private function seedFormGraph(): void
    {
        $now = date('Y-m-d H:i:s');

        $this->db->table('users')->insert([
            'id'         => 10,
            'username'   => 'owner',
            'active'     => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->db->table('forms')->insert([
            'id'          => 101,
            'user_id'     => 10,
            'title'       => 'Orders',
            'description' => '',
            'share_token' => 'token101',
            'status'      => 'published',
            'created_at'  => $now,
            'updated_at'  => $now,
            'deleted_at'  => null,
        ]);

        $fieldOptions = [
            'products' => [
                ['id' => 'product_a', 'name' => 'Shirt', 'description' => '', 'price' => 10.0, 'stock' => 2, 'image' => null],
            ],
        ];

        $this->db->table('form_fields')->insert([
            'id'               => 501,
            'form_id'          => 101,
            'field_type'       => 'product_list',
            'label'            => 'Products',
            'field_key'        => 'products',
            'placeholder'      => null,
            'help_text'        => null,
            'options'          => json_encode($fieldOptions),
            'is_required'      => 0,
            'validation_rules' => null,
            'conditions'       => null,
            'sort_order'       => 0,
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);

        $this->db->table('form_submissions')->insertBatch([
            ['id' => 1001, 'form_id' => 101, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'created_at' => $now],
            ['id' => 1002, 'form_id' => 101, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'created_at' => $now],
        ]);

        $selection = json_encode([
            'items' => [
                ['id' => 'product_a', 'name' => 'Shirt', 'price' => 10.0, 'quantity' => 3, 'line_total' => 30.0],
            ],
            'total' => 30.0,
        ], JSON_UNESCAPED_SLASHES);

        $this->db->table('submission_data')->insertBatch([
            [
                'id'            => 2001,
                'submission_id' => 1001,
                'field_id'      => 501,
                'field_key'     => 'products',
                'field_label'   => 'Products',
                'value'         => $selection,
                'file_path'     => null,
                'created_at'    => $now,
            ],
            [
                'id'            => 2002,
                'submission_id' => 1002,
                'field_id'      => null,
                'field_key'     => 'attachment',
                'field_label'   => 'Attachment',
                'value'         => 'submission.txt',
                'file_path'     => 'forms/101/submission.txt',
                'created_at'    => $now,
            ],
        ]);
    }

    private function createSchema(): void
    {
        $forge = Database::forge();

        foreach (['submission_data', 'form_submissions', 'form_fields', 'forms', 'auth_groups_users', 'users', 'settings'] as $table) {
            $forge->dropTable($table, true);
        }

        $forge->addField([
            'id'         => ['type' => 'INTEGER', 'auto_increment' => true],
            'class'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'key'        => ['type' => 'VARCHAR', 'constraint' => 255],
            'value'      => ['type' => 'TEXT', 'null' => true],
            'type'       => ['type' => 'VARCHAR', 'constraint' => 31, 'default' => 'string'],
            'context'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $forge->addKey('id', true);
        $forge->createTable('settings');

        $forge->addField([
            'id'         => ['type' => 'INTEGER', 'auto_increment' => true],
            'username'   => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'active'     => ['type' => 'INTEGER', 'default' => 0],
            'last_active' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->createTable('users');

        $forge->addField([
            'id'         => ['type' => 'INTEGER', 'auto_increment' => true],
            'user_id'    => ['type' => 'INTEGER'],
            'group'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $forge->addKey('id', true);
        $forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $forge->createTable('auth_groups_users');

        $forge->addField([
            'id'                     => ['type' => 'INTEGER', 'auto_increment' => true],
            'user_id'                => ['type' => 'INTEGER'],
            'title'                  => ['type' => 'VARCHAR', 'constraint' => 255],
            'description'            => ['type' => 'TEXT', 'null' => true],
            'share_token'            => ['type' => 'VARCHAR', 'constraint' => 32],
            'status'                 => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
            'submit_button_text'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'success_message'        => ['type' => 'TEXT', 'null' => true],
            'notify_on_submission'   => ['type' => 'INTEGER', 'default' => 0],
            'notification_email'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'             => ['type' => 'DATETIME', 'null' => true],
            'updated_at'             => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'             => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $forge->createTable('forms');

        $forge->addField([
            'id'               => ['type' => 'INTEGER', 'auto_increment' => true],
            'form_id'          => ['type' => 'INTEGER'],
            'field_type'       => ['type' => 'VARCHAR', 'constraint' => 30],
            'label'            => ['type' => 'VARCHAR', 'constraint' => 255],
            'field_key'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'placeholder'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'help_text'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'options'          => ['type' => 'TEXT', 'null' => true],
            'is_required'      => ['type' => 'INTEGER', 'default' => 0],
            'validation_rules' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'conditions'       => ['type' => 'TEXT', 'null' => true],
            'sort_order'       => ['type' => 'INTEGER', 'default' => 0],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->addForeignKey('form_id', 'forms', 'id', 'CASCADE', 'CASCADE');
        $forge->createTable('form_fields');

        $forge->addField([
            'id'         => ['type' => 'INTEGER', 'auto_increment' => true],
            'form_id'    => ['type' => 'INTEGER'],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->addForeignKey('form_id', 'forms', 'id', 'CASCADE', 'CASCADE');
        $forge->createTable('form_submissions');

        $forge->addField([
            'id'            => ['type' => 'INTEGER', 'auto_increment' => true],
            'submission_id' => ['type' => 'INTEGER'],
            'field_id'      => ['type' => 'INTEGER', 'null' => true],
            'field_key'     => ['type' => 'VARCHAR', 'constraint' => 100],
            'field_label'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'value'         => ['type' => 'TEXT', 'null' => true],
            'file_path'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->addForeignKey('submission_id', 'form_submissions', 'id', 'CASCADE', 'CASCADE');
        $forge->addForeignKey('field_id', 'form_fields', 'id', 'SET NULL', 'CASCADE');
        $forge->createTable('submission_data');
    }

    private function writeFile(string $path, string $contents): void
    {
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, $contents);
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }
}
