<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddReviewBeforeSubmitFieldType extends Migration
{
    private const TYPES_WITH_REVIEW = "'text','email','number','textarea','checkbox','radio','select','date','file','paragraph','page_break','appointment','product_list','review_before_submit'";
    private const TYPES_WITHOUT_REVIEW = "'text','email','number','textarea','checkbox','radio','select','date','file','paragraph','page_break','appointment','product_list'";

    public function up(): void
    {
        $this->db->query('ALTER TABLE `form_fields` MODIFY `field_type` ENUM(' . self::TYPES_WITH_REVIEW . ') NOT NULL');
    }

    public function down(): void
    {
        $this->db->table('form_fields')->where('field_type', 'review_before_submit')->delete();
        $this->db->query('ALTER TABLE `form_fields` MODIFY `field_type` ENUM(' . self::TYPES_WITHOUT_REVIEW . ') NOT NULL');
    }
}
