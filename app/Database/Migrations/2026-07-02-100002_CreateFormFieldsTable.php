<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFormFieldsTable extends Migration
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
            'form_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'field_type' => [
                'type'       => 'ENUM',
                'constraint' => ['text', 'email', 'number', 'textarea', 'checkbox', 'radio', 'select', 'date', 'file'],
            ],
            'label' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'field_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'placeholder' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'help_text' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'options' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'is_required' => [
                'type'    => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'validation_rules' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'sort_order' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['form_id', 'field_key'], false, true);
        $this->forge->addKey(['form_id', 'sort_order']);
        $this->forge->addForeignKey('form_id', 'forms', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('form_fields');
    }

    public function down(): void
    {
        $this->forge->dropTable('form_fields');
    }
}
