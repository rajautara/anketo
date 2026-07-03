<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPageBreakFieldType extends Migration
{
    private const TYPES_WITH_PAGE_BREAK = "'text','email','number','textarea','checkbox','radio','select','date','file','paragraph','page_break','appointment','product_list'";
    private const TYPES_WITHOUT_PAGE_BREAK = "'text','email','number','textarea','checkbox','radio','select','date','file','paragraph','appointment','product_list'";

    public function up(): void
    {
        $this->db->query('ALTER TABLE `form_fields` MODIFY `field_type` ENUM(' . self::TYPES_WITH_PAGE_BREAK . ') NOT NULL');
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE `form_fields` MODIFY `field_type` ENUM(' . self::TYPES_WITHOUT_PAGE_BREAK . ') NOT NULL');
    }
}
