<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFormFieldsTable extends Migration
{
    public function up()
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
                'constraint' => ['text', 'email', 'number', 'textarea', 'checkbox', 'radio', 'select', 'file', 'date', 'time', 'datetime', 'password', 'hidden', 'paragraph', 'divider'],
            ],
            'label' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'placeholder' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'default_value' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'options' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'required' => [
                'type'       => 'BOOLEAN',
                'default'    => false,
            ],
            'validation_rules' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'width' => [
                'type'       => 'INT',
                'constraint' => 3,
                'default'    => 100,
            ],
            'order_index' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'conditional_logic' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('form_id', 'forms', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('form_fields');
    }

    public function down()
    {
        $this->forge->dropTable('form_fields');
    }
}