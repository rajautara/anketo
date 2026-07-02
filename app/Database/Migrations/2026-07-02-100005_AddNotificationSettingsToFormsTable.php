<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNotificationSettingsToFormsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('forms', [
            'notify_on_submission' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'success_message',
            ],
            'notification_email' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'notify_on_submission',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('forms', ['notify_on_submission', 'notification_email']);
    }
}
