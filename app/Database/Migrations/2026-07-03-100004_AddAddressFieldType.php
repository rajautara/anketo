<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAddressFieldType extends Migration
{
    private const TYPES_WITH_ADDRESS = "'text','email','number','textarea','checkbox','radio','select','date','file','paragraph','page_break','appointment','product_list','review_before_submit','address'";
    private const TYPES_WITHOUT_ADDRESS = "'text','email','number','textarea','checkbox','radio','select','date','file','paragraph','page_break','appointment','product_list','review_before_submit'";

    public function up(): void
    {
        $this->db->query('ALTER TABLE `form_fields` MODIFY `field_type` ENUM(' . self::TYPES_WITH_ADDRESS . ') NOT NULL');
    }

    public function down(): void
    {
        $this->db->table('form_fields')->where('field_type', 'address')->delete();
        $this->db->query('ALTER TABLE `form_fields` MODIFY `field_type` ENUM(' . self::TYPES_WITHOUT_ADDRESS . ') NOT NULL');
    }
}
