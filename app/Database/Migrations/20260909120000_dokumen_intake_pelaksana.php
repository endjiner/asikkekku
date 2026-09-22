<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Data intake untuk autofill dokumen (2026-09-09)
 *
 *  - tb_users            : kolom profil pegawai (Gol/Jabatan/Rekening/Bank/NPWP)
 *  - tb_kegiatan         : KodeOutput, AsalTujuan, JmlHari
 *  - tb_kegiatan_pelaksana : snapshot per kegiatan (ganti teks bebas KegiatanNamaPelaksana)
 *  - tb_vrbl 'dok_output_list' : daftar Kode Output (JSON, admin-editable)
 *
 * Data lama: KegiatanNamaPelaksana dipecah best-effort jadi baris tb_kegiatan_pelaksana
 * supaya modul Dokumen tetap jalan untuk kegiatan lama.
 */
class Migration_Dokumen_intake_pelaksana extends Migration
{
    public function up()
    {
        // 1. tb_users -- kolom profil pegawai --------------------------------
        $userCols = array(
            'UserGol'      => "varchar(20)  DEFAULT NULL",
            'UserJabatan'  => "varchar(150) DEFAULT NULL",
            'UserRekening' => "varchar(40)  DEFAULT NULL",
            'UserBank'     => "varchar(60)  DEFAULT NULL",
            'UserNPWP'     => "varchar(30)  DEFAULT NULL",
        );
        foreach ($userCols as $col => $def) {
            if (!$this->db->fieldExists($col, 'tb_users')) {
                $this->db->query("ALTER TABLE `tb_users` ADD `$col` $def");
            }
        }

        // 2. tb_kegiatan -- kolom intake -----------------------------------
        $kegCols = array(
            'KegiatanKodeOutput' => "varchar(60)  DEFAULT NULL",
            'KegiatanAsalTujuan' => "varchar(255) DEFAULT NULL",
            'KegiatanJmlHari'    => "int(11)      DEFAULT NULL",
        );
        foreach ($kegCols as $col => $def) {
            if (!$this->db->fieldExists($col, 'tb_kegiatan')) {
                $this->db->query("ALTER TABLE `tb_kegiatan` ADD `$col` $def AFTER `KegiatanJenisID`");
            }
        }

        // 3. tb_kegiatan_pelaksana -- snapshot per kegiatan ---------------
        if (!$this->db->tableExists('tb_kegiatan_pelaksana')) {
            $this->db->query("
                CREATE TABLE `tb_kegiatan_pelaksana` (
                  `id`         int(11) NOT NULL AUTO_INCREMENT,
                  `KegiatanID` int(11) NOT NULL,
                  `UserID`     int(11) DEFAULT NULL,
                  `Nama`       varchar(150) DEFAULT NULL,
                  `NIP`        varchar(30)  DEFAULT NULL,
                  `Gol`        varchar(20)  DEFAULT NULL,
                  `Jabatan`    varchar(150) DEFAULT NULL,
                  `Rekening`   varchar(40)  DEFAULT NULL,
                  `Bank`       varchar(60)  DEFAULT NULL,
                  `NPWP`       varchar(30)  DEFAULT NULL,
                  `Urut`       int(11) NOT NULL DEFAULT 0,
                  PRIMARY KEY (`id`),
                  KEY `idx_kp_kegiatan` (`KegiatanID`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            ");
        }

        // 4. Seed daftar Kode Output (JSON) -- admin bisa edit di Konfigurasi
        $ada = $this->db->table('tb_vrbl')->getWhere(array('VrblName' => 'dok_output_list'))->getNumRows() > 0;
        if (!$ada) {
            $list = json_encode(array(
                '3165.QIA.001.055.524113.A',
                '3165.BDB.001.052.524111.C',
                '3165.BKB.001.052.524111.C',
                '3165.QDB.001.052.524114.F',
                '3165.QDB.001.051.524111.A',
            ), JSON_UNESCAPED_SLASHES);
            $this->db->table('tb_vrbl')->insert(array('VrblName' => 'dok_output_list', 'VrblValue' => $list));
        }

        // 5. Backfill: pecah KegiatanNamaPelaksana lama -> baris pelaksana
        if ($this->db->fieldExists('KegiatanNamaPelaksana', 'tb_kegiatan')) {
            $rows = $this->db->query("
                SELECT k.KegiatanID, k.KegiatanNamaPelaksana
                FROM tb_kegiatan k
                LEFT JOIN tb_kegiatan_pelaksana p ON p.KegiatanID = k.KegiatanID
                WHERE p.id IS NULL AND k.KegiatanNamaPelaksana IS NOT NULL AND k.KegiatanNamaPelaksana <> ''
            ")->getResultArray();
            foreach ($rows as $r) {
                $parts = preg_split('/\s*[;,]\s*/', $r['KegiatanNamaPelaksana'], -1, PREG_SPLIT_NO_EMPTY);
                $urut = 0;
                foreach ($parts as $nama) {
                    $nama = trim($nama);
                    if ($nama === '') continue;
                    // coba tautkan ke tb_users berdasarkan nama lengkap
                    $u = $this->db->query(
                        "SELECT UserID, UserName, UserGol, UserJabatan, UserRekening, UserBank, UserNPWP
                         FROM tb_users WHERE UserFullName = ? LIMIT 1", array($nama))->getRowArray();
                    $this->db->table('tb_kegiatan_pelaksana')->insert(array(
                        'KegiatanID' => (int) $r['KegiatanID'],
                        'UserID'     => !empty($u['UserID']) ? (int) $u['UserID'] : null,
                        'Nama'       => $nama,
                        'NIP'        => !empty($u['UserName']) ? $u['UserName'] : null,
                        'Gol'        => isset($u['UserGol']) ? $u['UserGol'] : null,
                        'Jabatan'    => isset($u['UserJabatan']) ? $u['UserJabatan'] : null,
                        'Rekening'   => isset($u['UserRekening']) ? $u['UserRekening'] : null,
                        'Bank'       => isset($u['UserBank']) ? $u['UserBank'] : null,
                        'NPWP'       => isset($u['UserNPWP']) ? $u['UserNPWP'] : null,
                        'Urut'       => $urut++,
                    ));
                }
            }
        }
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS `tb_kegiatan_pelaksana`");
        foreach (array('KegiatanKodeOutput', 'KegiatanAsalTujuan', 'KegiatanJmlHari') as $c) {
            if ($this->db->fieldExists($c, 'tb_kegiatan')) $this->db->query("ALTER TABLE `tb_kegiatan` DROP COLUMN `$c`");
        }
        foreach (array('UserGol', 'UserJabatan', 'UserRekening', 'UserBank', 'UserNPWP') as $c) {
            if ($this->db->fieldExists($c, 'tb_users')) $this->db->query("ALTER TABLE `tb_users` DROP COLUMN `$c`");
        }
        $this->db->table('tb_vrbl')->delete(array('VrblName' => 'dok_output_list'));
    }
}
