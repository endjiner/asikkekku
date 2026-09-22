<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tanda tangan tangkap-langsung untuk dokumen (2026-09-10)
 *
 *  - tb_dokumen_ttd     : gambar coretan ttd, diikat ke {user, dokumen, versi, hash, waktu}
 *  - tb_dokumen.PayloadHash : cache sha256 payload -> untuk deteksi "dokumen berubah, ttd hangus"
 */
class Migration_Dokumen_ttd extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('tb_dokumen_ttd')) {
            $this->db->query("
                CREATE TABLE `tb_dokumen_ttd` (
                  `TtdID`             int(11) NOT NULL AUTO_INCREMENT,
                  `KegiatanID`        int(11) NOT NULL,
                  `Kode`              varchar(40) NOT NULL,
                  `RangkapKey`        varchar(80) NOT NULL DEFAULT '-',
                  `Slot`              varchar(24) NOT NULL,           -- 'ppk' | 'penerima' | ...
                  `SignerUserID`      int(11) DEFAULT NULL,
                  `SignerNama`        varchar(150) DEFAULT NULL,
                  `SignerRole`        varchar(40) DEFAULT NULL,
                  `ImagePath`         varchar(255) NOT NULL,          -- relatif FCPATH, mis. assets/ttd/1996/kwitansi_Sony_ppk_1699999.png
                  `DocHash`           char(64) NOT NULL,
                  `SignedAt`          datetime NOT NULL,
                  `SignedIP`          varchar(45) DEFAULT NULL,
                  `InvalidatedAt`     datetime DEFAULT NULL,
                  `InvalidatedReason` varchar(120) DEFAULT NULL,
                  PRIMARY KEY (`TtdID`),
                  KEY `idx_ttd_doc` (`KegiatanID`,`Kode`,`RangkapKey`),
                  KEY `idx_ttd_aktif` (`KegiatanID`,`Kode`,`RangkapKey`,`Slot`,`InvalidatedAt`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            ");
        }
        if ($this->db->tableExists('tb_dokumen') && !$this->db->fieldExists('PayloadHash', 'tb_dokumen')) {
            $this->db->query("ALTER TABLE `tb_dokumen` ADD `PayloadHash` char(64) DEFAULT NULL AFTER `PayloadJson`");
        }
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS `tb_dokumen_ttd`");
        if ($this->db->fieldExists('PayloadHash', 'tb_dokumen')) {
            $this->db->query("ALTER TABLE `tb_dokumen` DROP COLUMN `PayloadHash`");
        }
    }
}
