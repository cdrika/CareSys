<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class ExtendOperations extends Migration
{
    public function up()
    {
        $common='id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ref CHAR(32) NOT NULL UNIQUE, version INT UNSIGNED NOT NULL DEFAULT 1, created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6), updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6), ';
        $tables=[
            'cs_qualifications'=>"person_id BIGINT UNSIGNED NOT NULL, name VARCHAR(160) NOT NULL, issuer VARCHAR(160) NOT NULL, issued_on DATE NOT NULL, expires_on DATE NULL, state VARCHAR(24) NOT NULL DEFAULT 'self_declared', evidence_ref VARCHAR(200) NOT NULL DEFAULT '', FOREIGN KEY(person_id) REFERENCES cs_people(id)",
            'cs_qualification_acceptances'=>"agency_id BIGINT UNSIGNED NOT NULL, employment_id BIGINT UNSIGNED NOT NULL, qualification_id BIGINT UNSIGNED NOT NULL, reviewed_by INT UNSIGNED NOT NULL, evidence VARCHAR(500) NOT NULL, UNIQUE(agency_id,employment_id,qualification_id), FOREIGN KEY(agency_id,employment_id) REFERENCES cs_employments(agency_id,id), FOREIGN KEY(qualification_id) REFERENCES cs_qualifications(id), FOREIGN KEY(reviewed_by) REFERENCES users(id)",
            'cs_time_corrections'=>"agency_id BIGINT UNSIGNED NOT NULL, timesheet_id BIGINT UNSIGNED NOT NULL, requested_by INT UNSIGNED NOT NULL, proposed_seconds INT UNSIGNED NOT NULL, source_version INT UNSIGNED NOT NULL, reason VARCHAR(1000) NOT NULL, state VARCHAR(24) NOT NULL DEFAULT 'pending', reviewed_by INT UNSIGNED NULL, FOREIGN KEY(agency_id,timesheet_id) REFERENCES cs_timesheets(agency_id,id), FOREIGN KEY(requested_by) REFERENCES users(id)",
            'cs_balance_adjustments'=>"agency_id BIGINT UNSIGNED NOT NULL, employment_id BIGINT UNSIGNED NOT NULL, actor_id INT UNSIGNED NOT NULL, minutes INT NOT NULL, reason VARCHAR(500) NOT NULL, FOREIGN KEY(agency_id,employment_id) REFERENCES cs_employments(agency_id,id), FOREIGN KEY(actor_id) REFERENCES users(id)",
            'cs_credit_notes'=>"agency_id BIGINT UNSIGNED NOT NULL, invoice_id BIGINT UNSIGNED NOT NULL UNIQUE, amount DECIMAL(19,4) NOT NULL, reason VARCHAR(500) NOT NULL, FOREIGN KEY(agency_id,invoice_id) REFERENCES cs_invoices(agency_id,id)",
        ];
        foreach($tables as $name=>$columns)$this->db->query("CREATE TABLE {$name} ({$common}{$columns}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->db->query("ALTER TABLE cs_visits ADD required_qualification VARCHAR(160) NOT NULL DEFAULT ''");
    }
    public function down(){throw new \RuntimeException('Reviewed recovery required.');}
}
