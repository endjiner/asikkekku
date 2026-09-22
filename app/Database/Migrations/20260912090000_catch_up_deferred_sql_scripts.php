<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Catch-up (2026-09-12): sejumlah perubahan skema sempat hanya ditulis
 * sebagai skrip lepas di assets/sql/*.sql (dijalankan manual, di luar
 * `php spark migrate`) dan tidak pernah dipindah jadi migrasi CI4
 * yang sesungguhnya. Akibatnya database yang di-setup lewat migrate saja
 * (mis. instans baru / staging) kehilangan tabel-tabel ini -- termasuk
 * `tb_dokumen`, yang bikin modul Dokumen Pencairan gagal simpan dengan
 * pesan "Tabel tb_dokumen belum ada."
 *
 * Migrasi ini menggabungkan (idempoten, aman dijalankan di DB yang sudah
 * sebagian ter-setup manual):
 *  - assets/sql/2026-09-02_wa_log.sql
 *  - assets/sql/2026-09-02_terminate_revisi.sql
 *  - assets/sql/2026-09-02_menu_indonesia.sql
 *  - assets/sql/2026-09-03_approval_list_performance.sql
 *  - assets/sql/2026-09-04_dokumen.sql
 *  - assets/sql/2026-09-09_dokumen_menu.sql
 *  - assets/sql/2026-09-10_dok_upload.sql
 *  - assets/sql/early_warning.sql
 *
 * (assets/sql/2026-09-08_jenis_pengajuan.sql sengaja dilewati -- sudah
 * tercermin sebagai migrasi 20260908120000_jenis_pengajuan_configurable_flow.)
 */
class Migration_Catch_up_deferred_sql_scripts extends Migration
{
    public function up()
    {
        // 1. tb_wa_log (log pengiriman WhatsApp) + token di tb_vrbl -----------
        if (!$this->db->tableExists('tb_wa_log')) {
            $this->db->query("
                CREATE TABLE `tb_wa_log` (
                  `id`            bigint(20)  NOT NULL AUTO_INCREMENT,
                  `KonteksKirim`  varchar(40) DEFAULT NULL,
                  `KegiatanID`    int(11)     DEFAULT NULL,
                  `Nomor`         varchar(30) DEFAULT NULL,
                  `Pesan`         text        DEFAULT NULL,
                  `HttpCode`      int(11)     DEFAULT NULL,
                  `Status`        varchar(10) DEFAULT NULL,
                  `Respons`       text        DEFAULT NULL,
                  `TanggalKirim`  datetime    NOT NULL,
                  PRIMARY KEY (`id`),
                  KEY `idx_wa_keg` (`KegiatanID`),
                  KEY `idx_wa_tgl` (`TanggalKirim`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }
        if ($this->db->table('tb_vrbl')->getWhere(array('VrblName' => 'wa_token'))->getNumRows() === 0) {
            $this->db->table('tb_vrbl')->insert(array('VrblName' => 'wa_token', 'VrblValue' => 'ISI_TOKEN_WABLAS_DI_SINI'));
        }

        // 2. Fitur "Batalkan / Revisi": jenis pengembalian di riwayat approval -
        if (!$this->db->fieldExists('FlowRejectType', 'tb_approval_history')) {
            $this->db->query("ALTER TABLE `tb_approval_history` ADD `FlowRejectType` varchar(20) NULL AFTER `FlowResult`");
        }

        // 3. Menu sidebar: urutan & nama Bahasa Indonesia ----------------------
        $menuOrder = array('3200' => 0, '3100' => 1, '3300' => 2);
        foreach ($menuOrder as $kode => $order) {
            $this->db->table('tb_menu')->where('MenuKode', $kode)->update(array('MenuOrder' => $order));
        }
        $menuNama = array(
            '1000' => 'Dasbor',
            '2000' => 'Manajemen Aplikasi',
            '2100' => 'Grup Pengguna',
            '2200' => 'Pengguna',
            '2300' => 'Konfigurasi Aplikasi',
            '3000' => 'Manajemen Persetujuan',
            '3200' => 'Persetujuan',
            '3100' => 'Daftar Pengajuan',
            '3300' => 'Laporan',
        );
        foreach ($menuNama as $kode => $nama) {
            $this->db->table('tb_menu')->where('MenuKode', $kode)->update(array('MenuName' => $nama));
        }

        // 4. Index performa antrian persetujuan --------------------------------
        $adaIdx = $this->db->query(
            "SELECT 1 FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = 'tb_approval_history'
               AND index_name = 'idx_approval_history_kegiatan_history' LIMIT 1"
        )->getNumRows() > 0;
        if (!$adaIdx) {
            $this->db->query("ALTER TABLE `tb_approval_history`
                ADD INDEX `idx_approval_history_kegiatan_history` (`KegiatanID`, `HistoryID`)");
        }

        // 5. tb_dokumen (nilai field form_inapp: kwitansi/nominatif/riil/spd/sptjb) -
        if (!$this->db->tableExists('tb_dokumen')) {
            $this->db->query("
                CREATE TABLE `tb_dokumen` (
                  `DokumenID`     int(11)     NOT NULL AUTO_INCREMENT,
                  `KegiatanID`    int(11)     NOT NULL,
                  `Kode`          varchar(40) NOT NULL COMMENT 'kwitansi|nominatif|riil|spd|sptjb|lembar_periksa',
                  `RangkapKey`    varchar(80) NOT NULL DEFAULT '-' COMMENT 'nama penerima utk dokumen per_penerima, \"-\" utk per_kegiatan',
                  `PayloadJson`   longtext    NOT NULL,
                  `TemplateVersi` varchar(20) NOT NULL DEFAULT 'v1',
                  `UpdatedBy`     int(11)     DEFAULT NULL,
                  `UpdatedAt`     datetime    DEFAULT NULL,
                  PRIMARY KEY (`DokumenID`),
                  UNIQUE KEY `uniq_dokumen` (`KegiatanID`,`Kode`,`RangkapKey`),
                  KEY `idx_kegiatan` (`KegiatanID`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            ");
        }
        // Kolom PayloadHash (dari migrasi dokumen_ttd) mengandalkan tabel ini;
        // jaga-jaga kalau urutan migrasi lama sempat lewat duluan tanpa tabelnya.
        if (!$this->db->fieldExists('PayloadHash', 'tb_dokumen')) {
            $this->db->query("ALTER TABLE `tb_dokumen` ADD `PayloadHash` char(64) DEFAULT NULL AFTER `PayloadJson`");
        }

        // 6. Menu sidebar "Dokumen Pencairan" + akses (ikut grup yg akses 3100) -
        if ($this->db->table('tb_menu')->getWhere(array('MenuKode' => '3400'))->getNumRows() === 0) {
            $this->db->table('tb_menu')->insert(array(
                'MenuKode' => '3400', 'MenuName' => 'Dokumen Pencairan', 'MenuLink' => 'dokumen/pilih',
                'MenuClass' => 'fa-file-signature', 'MenuParent' => 3, 'MenuOrder' => '3',
            ));
        }
        $menuDokumen = $this->db->table('tb_menu')->getWhere(array('MenuKode' => '3400'))->getRowArray();
        $menuDaftar  = $this->db->table('tb_menu')->getWhere(array('MenuKode' => '3100'))->getRowArray();
        if ($menuDokumen && $menuDaftar) {
            $grupPunyaAkses = $this->db->table('tb_menu_access')->select('UserGroupID')
                ->where('MenuID', $menuDaftar['MenuID'])->where('Status_R', 1)
                ->get()->getResultArray();
            foreach ($grupPunyaAkses as $g) {
                $sudahAda = $this->db->table('tb_menu_access')->getWhere(array(
                    'MenuID' => $menuDokumen['MenuID'], 'UserGroupID' => $g['UserGroupID'],
                ))->getNumRows() > 0;
                if (!$sudahAda) {
                    $this->db->table('tb_menu_access')->insert(array(
                        'MenuID' => $menuDokumen['MenuID'], 'UserGroupID' => $g['UserGroupID'],
                        'Status_R' => 1, 'Status_C' => 1, 'Status_U' => 1, 'Status_D' => 0,
                    ));
                }
            }
        }

        // 7. Upload dokumen eksternal (LPD/SPPD/SPM/SPP) + TTD on-document -----
        if (!$this->db->tableExists('tb_dok_upload')) {
            $this->db->query("
                CREATE TABLE `tb_dok_upload` (
                  `UploadID`     int(10) unsigned NOT NULL AUTO_INCREMENT,
                  `KegiatanID`   int(10) unsigned NOT NULL,
                  `Tipe`         varchar(30)  NOT NULL DEFAULT 'lain' COMMENT 'lpd|sppd|spm|spp|lain',
                  `FilePath`     varchar(512) NOT NULL COMMENT 'path relatif dari FCPATH',
                  `OriginalName` varchar(255) DEFAULT NULL,
                  `FileSize`     int(10) unsigned DEFAULT NULL COMMENT 'bytes',
                  `UploadedBy`   int(10) unsigned DEFAULT NULL,
                  `UploadedAt`   datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `DeletedAt`    datetime DEFAULT NULL,
                  PRIMARY KEY (`UploadID`),
                  KEY `idx_du_kegiatan` (`KegiatanID`, `DeletedAt`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }
        if (!$this->db->tableExists('tb_dok_upload_ttd')) {
            $this->db->query("
                CREATE TABLE `tb_dok_upload_ttd` (
                  `TtdID`         int(10) unsigned NOT NULL AUTO_INCREMENT,
                  `UploadID`      int(10) unsigned NOT NULL,
                  `Slot`          varchar(30) NOT NULL COMMENT 'ppk|ppspm',
                  `Page`          smallint(5) unsigned NOT NULL DEFAULT 1,
                  `PosX`          decimal(6,4) NOT NULL COMMENT '0-1 relatif lebar halaman',
                  `PosY`          decimal(6,4) NOT NULL COMMENT '0-1 relatif tinggi halaman',
                  `Width`         decimal(6,4) NOT NULL DEFAULT 0.2000,
                  `Height`        decimal(6,4) NOT NULL DEFAULT 0.0600,
                  `ImagePath`     varchar(512) DEFAULT NULL,
                  `SignerUserID`  int(10) unsigned DEFAULT NULL,
                  `SignerNama`    varchar(200) DEFAULT NULL,
                  `SignerRole`    varchar(50) DEFAULT NULL,
                  `SignedAt`      datetime DEFAULT NULL,
                  `SignedIP`      varchar(45) DEFAULT NULL,
                  `InvalidatedAt` datetime DEFAULT NULL,
                  PRIMARY KEY (`TtdID`),
                  KEY `idx_dut_upload` (`UploadID`, `Slot`, `InvalidatedAt`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }

        // 8. Peringatan Dini 4HK: log notifikasi + ambang batas SLA ------------
        if (!$this->db->tableExists('tb_ew_notifikasi')) {
            $this->db->query("
                CREATE TABLE `tb_ew_notifikasi` (
                  `id`           int(11) NOT NULL AUTO_INCREMENT,
                  `KegiatanID`   int(11) DEFAULT NULL,
                  `FlowCode`     varchar(20) DEFAULT NULL,
                  `Level`        varchar(20) DEFAULT NULL,
                  `TanggalKirim` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `idx_kegiatan_flow_tgl` (`KegiatanID`, `FlowCode`, `TanggalKirim`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            ");
        }
        $slaDefault = array(
            'sla_total_hk' => '4', 'sla_warn_total_hk' => '3',
            'sla_warn_stage_hk' => '1', 'ew_cron_key' => 'asik-ew-2026-ganti-nilai-ini',
        );
        foreach ($slaDefault as $nama => $nilai) {
            if ($this->db->table('tb_vrbl')->getWhere(array('VrblName' => $nama))->getNumRows() === 0) {
                $this->db->table('tb_vrbl')->insert(array('VrblName' => $nama, 'VrblValue' => $nilai));
            }
        }
    }

    public function down()
    {
        $this->db->table('tb_vrbl')->delete(array('VrblName' => array('sla_total_hk', 'sla_warn_total_hk', 'sla_warn_stage_hk', 'ew_cron_key', 'wa_token')));
        $this->db->query("DROP TABLE IF EXISTS `tb_ew_notifikasi`");
        $this->db->query("DROP TABLE IF EXISTS `tb_dok_upload_ttd`");
        $this->db->query("DROP TABLE IF EXISTS `tb_dok_upload`");

        $menuDokumen = $this->db->table('tb_menu')->getWhere(array('MenuKode' => '3400'))->getRowArray();
        if ($menuDokumen) {
            $this->db->table('tb_menu_access')->delete(array('MenuID' => $menuDokumen['MenuID']));
            $this->db->table('tb_menu')->delete(array('MenuKode' => '3400'));
        }

        if ($this->db->fieldExists('PayloadHash', 'tb_dokumen')) {
            $this->db->query("ALTER TABLE `tb_dokumen` DROP COLUMN `PayloadHash`");
        }
        $this->db->query("DROP TABLE IF EXISTS `tb_dokumen`");

        $adaIdx = $this->db->query(
            "SELECT 1 FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = 'tb_approval_history'
               AND index_name = 'idx_approval_history_kegiatan_history' LIMIT 1"
        )->getNumRows() > 0;
        if ($adaIdx) {
            $this->db->query("ALTER TABLE `tb_approval_history` DROP INDEX `idx_approval_history_kegiatan_history`");
        }

        if ($this->db->fieldExists('FlowRejectType', 'tb_approval_history')) {
            $this->db->query("ALTER TABLE `tb_approval_history` DROP COLUMN `FlowRejectType`");
        }

        $this->db->query("DROP TABLE IF EXISTS `tb_wa_log`");
        // Nama menu & urutan (langkah 3) sengaja tidak dikembalikan -- sama
        // seperti migrasi lain di proyek ini, down() tidak me-restore data
        // teks yang di-UPDATE (nilai "sebelumnya" tidak disimpan di manapun).
    }
}
