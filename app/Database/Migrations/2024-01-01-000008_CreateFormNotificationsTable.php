<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFormNotificationsTable extends Migration
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
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'notification_type' => [
                'type'       => 'ENUM',
                'constraint' => ['on_submit', 'daily_digest', 'weekly_digest'],
                'default'    => 'on_submit',
            ],
            'subject_template' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'body_template' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'is_active' => [
                'type'       => 'BOOLEAN',
                'default'    => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('form_id', 'forms', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('form_notifications');
    }

    public function down()
    {
        $this->forge->dropTable('form_notifications');
    }
}