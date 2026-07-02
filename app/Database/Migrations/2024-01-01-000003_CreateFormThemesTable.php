<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFormThemesTable extends Migration
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
                'constraint' => 100,
            ],
            'css_config' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'preview_image' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'is_default' => [
                'type'       => 'BOOLEAN',
                'default'    => false,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->createTable('form_themes');
        
        // Insert default themes
        $defaultTheme = [
            'name' => 'Default',
            'css_config' => json_encode([
                'primary_color' => '#0d6efd',
                'background_color' => '#ffffff',
                'font_family' => 'Arial, sans-serif',
                'border_radius' => '4px',
                'input_padding' => '10px',
            ]),
            'is_default' => true,
        ];
        
        $this->db->table('form_themes')->insert($defaultTheme);
    }

    public function down()
    {
        $this->forge->dropTable('form_themes');
    }
}