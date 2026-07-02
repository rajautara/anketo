<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFormsTable extends Migration
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
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'published', 'archived'],
                'default'    => 'draft',
            ],
            'theme_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null' => true,
            ],
            'allow_anonymous' => [
                'type'       => 'BOOLEAN',
                'default'    => false,
            ],
            'require_login' => [
                'type'       => 'BOOLEAN',
                'default'    => false,
            ],
            'limit_submissions' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'expiry_date' => [
                'type' => 'DATETIME',
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
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('theme_id', 'form_themes', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('forms');
    }

    public function down()
    {
        $this->forge->dropTable('forms');
    }
}