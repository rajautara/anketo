<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRolesTable extends Migration
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
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'permissions' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->createTable('roles');
        
        // Insert default roles
        $data = [
            [
                'id' => 1,
                'name' => 'admin',
                'description' => 'Administrator with full access',
                'permissions' => json_encode([
                    'forms.create', 'forms.read', 'forms.update', 'forms.delete', 'forms.publish',
                    'submissions.read', 'submissions.export',
                    'users.manage', 'roles.manage'
                ]),
            ],
            [
                'id' => 2,
                'name' => 'user',
                'description' => 'Regular user with form creation access',
                'permissions' => json_encode([
                    'forms.create', 'forms.read', 'forms.update', 'forms.delete', 'forms.publish',
                    'submissions.read', 'submissions.export'
                ]),
            ],
            [
                'id' => 3,
                'name' => 'viewer',
                'description' => 'Viewer with read-only access',
                'permissions' => json_encode([
                    'forms.read', 'submissions.read'
                ]),
            ],
        ];
        
        $this->db->table('roles')->insertBatch($data);
    }

    public function down()
    {
        $this->forge->dropTable('roles');
    }
}