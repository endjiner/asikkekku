<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Log cetak/unduh dokumen (2026-09-11). Log saja, bukan gate.
 */
class Migration_Dokumen_print_log extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('tb_dokumen_print')) {
            $this->db->query("
                CREATE TABLE `tb_dokumen_print` (
                  `id`         int(11) NOT NULL AUTO_INCREMENT,
                  `KegiatanID` int(11) NOT NULL,
                  `Batch`      varchar(40) DEFAULT NULL,
                  `Isi`        text DEFAULT NULL,           -- daftar 'kode:rangkap' yang dicetak (JSON)
                  `DenganTtd`  tinyint(1) NOT NULL DEFAULT 1,
                  `ByUserID`   int(11) DEFAULT NULL,
                  `AtTime`     datetime NOT NULL,
                  PRIMARY KEY (`id`),
                  KEY `idx_dp_keg` (`KegiatanID`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            ");
        }
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS `tb_dokumen_print`");
    }
}
