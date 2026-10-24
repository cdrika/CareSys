<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class AlignSessionTimestamp extends Migration
{
    public function up()
    {
        // CI 4.7's database session handler writes NOW(), not Unix epoch integers.
        $this->db->query('ALTER TABLE cs_sessions MODIFY timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }
    public function down(){throw new \RuntimeException('Session schema matches the installed framework.');}
}
