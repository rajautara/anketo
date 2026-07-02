<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFormSubmissionsTable extends Migration
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
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'user_agent' => [
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
        $this->forge->addKey('form_id');
        $this->forge->addForeignKey('form_id', 'forms', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('form_submissions');
    }

    public function down(): void
    {
        $this->forge->dropTable('form_submissions');
    }
}
