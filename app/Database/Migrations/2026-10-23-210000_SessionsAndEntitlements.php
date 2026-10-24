<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class SessionsAndEntitlements extends Migration
{
    public function up()
    {
        $this->db->query('CREATE TABLE cs_sessions (id VARCHAR(128) NOT NULL PRIMARY KEY, ip_address VARCHAR(45) NOT NULL, timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, data BLOB NOT NULL, KEY ix_session_timestamp(timestamp)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->db->query('ALTER TABLE cs_agencies ADD entitlement_until DATE NULL');
        $this->db->query("CREATE TABLE cs_entitlement_events (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,ref CHAR(32) NOT NULL UNIQUE,version INT UNSIGNED NOT NULL DEFAULT 1,created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),agency_id BIGINT UNSIGNED NOT NULL,actor_id INT UNSIGNED NOT NULL,modules JSON NOT NULL,state VARCHAR(24) NOT NULL,valid_until DATE NOT NULL,basis VARCHAR(500) NOT NULL,FOREIGN KEY(agency_id) REFERENCES cs_agencies(id),FOREIGN KEY(actor_id) REFERENCES users(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    public function down(){throw new \RuntimeException('Reviewed recovery required.');}
}
