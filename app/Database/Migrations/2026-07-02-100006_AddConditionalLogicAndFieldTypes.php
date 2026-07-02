<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddConditionalLogicAndFieldTypes extends Migration
{
    private const TYPES_WITH_NEW = "'text','email','number','textarea','checkbox','radio','select','date','file','paragraph','appointment'";
    private const TYPES_ORIGINAL = "'text','email','number','textarea','checkbox','radio','select','date','file'";

    public function up(): void
    {
        // Add the two new field types to the ENUM. A raw ALTER is the most
        // portable way to redefine a MySQL ENUM in place.
        $this->db->query('ALTER TABLE `form_fields` MODIFY `field_type` ENUM(' . self::TYPES_WITH_NEW . ') NOT NULL');

        // Per-field conditional-logic rules + optional calculation formula.
        $this->forge->addColumn('form_fields', [
            'conditions' => [
                'type' => 'JSON',
                'null' => true,
                'after' => 'validation_rules',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('form_fields', 'conditions');

        // Reverting the ENUM will fail if any paragraph/appointment rows exist;
        // acceptable for a development down-migration.
        $this->db->query('ALTER TABLE `form_fields` MODIFY `field_type` ENUM(' . self::TYPES_ORIGINAL . ') NOT NULL');
    }
}
