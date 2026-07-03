<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddProductListFieldType extends Migration
{
    private const TYPES_WITH_PRODUCT = "'text','email','number','textarea','checkbox','radio','select','date','file','paragraph','appointment','product_list'";
    private const TYPES_WITHOUT_PRODUCT = "'text','email','number','textarea','checkbox','radio','select','date','file','paragraph','appointment'";

    public function up(): void
    {
        $this->db->query('ALTER TABLE `form_fields` MODIFY `field_type` ENUM(' . self::TYPES_WITH_PRODUCT . ') NOT NULL');
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE `form_fields` MODIFY `field_type` ENUM(' . self::TYPES_WITHOUT_PRODUCT . ') NOT NULL');
    }
}
