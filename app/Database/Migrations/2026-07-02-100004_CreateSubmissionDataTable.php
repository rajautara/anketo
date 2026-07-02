<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSubmissionDataTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'submission_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'field_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'field_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'field_label' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'value' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'file_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('submission_id');
        $this->forge->addForeignKey('submission_id', 'form_submissions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('field_id', 'form_fields', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('submission_data');
    }

    public function down(): void
    {
        $this->forge->dropTable('submission_data');
    }
}
