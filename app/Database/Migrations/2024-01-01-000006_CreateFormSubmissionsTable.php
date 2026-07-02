<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFormSubmissionsTable extends Migration
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
            'submitter_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'submitter_email' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null' => true,
            ],
            'submitted_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null' => true,
            ],
            'user_agent' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['new', 'read', 'archived'],
                'default'    => 'new',
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('form_id', 'forms', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('form_submissions');
    }

    public function down()
    {
        $this->forge->dropTable('form_submissions');
    }
}