<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFormCollaboratorsTable extends Migration
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
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'form_access' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'default'    => 'none',
            ],
            'submission_access' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'default'    => 'none',
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
        $this->forge->addUniqueKey(['form_id', 'user_id']);
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('form_id', 'forms', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('form_collaborators');
    }

    public function down(): void
    {
        $this->forge->dropTable('form_collaborators');
    }
}
