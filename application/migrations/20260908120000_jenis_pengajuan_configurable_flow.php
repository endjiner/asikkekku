<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Jenis Pengajuan + alur persetujuan konfigurabel (2026-09-08)
 *
 * Membuat pondasi "alur per jenis pengajuan":
 *  - tabel master `tb_jenis_pengajuan` (+ kolom override SLA per jenis, belum dibaca)
 *  - `tb_approval_flow.JenisID`            -> scope tiap baris alur ke satu jenis
 *  - `tb_approval_flow.FlowIsRevisiTarget` -> menggantikan array hardcoded $revisi_stage_codes
 *  - `tb_kegiatan.KegiatanJenisID`         -> jenis yang dipilih PJ saat membuat
 *
 * Alur perjadin (6 tahap: PJK -> PPKS1 -> SPM1 -> VRF2 -> PPK1 -> SLS) DIADOPSI APA
 * ADANYA sebagai JenisID = 1. TIDAK ada penomoran ulang FlowOrder, TIDAK ada
 * aktivasi tahap dorman (VRF1/SPP1/PPSPM1), TIDAK ada perubahan FlowPosition.
 * Karena itu tidak ada data alur yang perlu dikembalikan di down().
 */
class Migration_Jenis_pengajuan_configurable_flow extends CI_Migration
{
	public function up()
	{
		// 1. Master jenis pengajuan --------------------------------------------
		if (!$this->db->table_exists('tb_jenis_pengajuan')) {
			$this->db->query("
				CREATE TABLE `tb_jenis_pengajuan` (
				  `JenisID`             int(11)      NOT NULL AUTO_INCREMENT,
				  `JenisKode`           varchar(30)  NOT NULL,
				  `JenisNama`           varchar(100) NOT NULL,
				  `JenisNote`           text         DEFAULT NULL,
				  `JenisAktif`          tinyint(1)   NOT NULL DEFAULT 1,
				  `JenisUrutan`         int(11)      NOT NULL DEFAULT 0,
				  `JenisSlaTotalHk`     int(11)      DEFAULT NULL,
				  `JenisSlaWarnTotalHk` int(11)      DEFAULT NULL,
				  `JenisSlaWarnStageHk` int(11)      DEFAULT NULL,
				  PRIMARY KEY (`JenisID`),
				  UNIQUE KEY `uq_jenis_kode` (`JenisKode`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
			");
		}

		// 2. Seed jenis 'PERJADIN' (paksa JenisID = 1) ------------------------
		$adaPerjadin = $this->db->get_where('tb_jenis_pengajuan', array('JenisKode' => 'PERJADIN'))->num_rows() > 0;
		if (!$adaPerjadin) {
			$this->db->insert('tb_jenis_pengajuan', array(
				'JenisID'     => 1,
				'JenisKode'   => 'PERJADIN',
				'JenisNama'   => 'Perjalanan Dinas (LS)',
				'JenisNote'   => 'Pencairan LS perjalanan dinas - alur 6 tahap yang berjalan saat ini',
				'JenisAktif'  => 1,
				'JenisUrutan' => 1,
			));
		}

		// 3. tb_approval_flow: kolom scope + flag target revisi --------------
		if (!$this->db->field_exists('JenisID', 'tb_approval_flow')) {
			$this->db->query("ALTER TABLE `tb_approval_flow`
				ADD `JenisID` int(11) NOT NULL DEFAULT 1 AFTER `FlowPosition`");
		}
		if (!$this->db->field_exists('FlowIsRevisiTarget', 'tb_approval_flow')) {
			$this->db->query("ALTER TABLE `tb_approval_flow`
				ADD `FlowIsRevisiTarget` tinyint(1) NOT NULL DEFAULT 0 AFTER `JenisID`");
		}
		// Lebarkan FlowCode agar muat kode ber-prefix jenis lain (LSB_*, UP_*)
		$this->db->query("ALTER TABLE `tb_approval_flow` MODIFY `FlowCode` varchar(40) DEFAULT NULL");

		// Unique (JenisID, FlowCode) -- hanya bila belum ada & tidak ada duplikat
		$adaUq = $this->db->query(
			"SELECT 1 FROM information_schema.statistics
			 WHERE table_schema = DATABASE() AND table_name = 'tb_approval_flow'
			   AND index_name = 'uq_flow_jenis_code' LIMIT 1"
		)->num_rows() > 0;
		if (!$adaUq) {
			$dup = $this->db->query(
				"SELECT COUNT(*) c FROM (
					SELECT JenisID, FlowCode FROM tb_approval_flow
					GROUP BY JenisID, FlowCode HAVING COUNT(*) > 1
				) d"
			)->row()->c;
			if ((int) $dup === 0) {
				$this->db->query("ALTER TABLE `tb_approval_flow`
					ADD UNIQUE KEY `uq_flow_jenis_code` (`JenisID`,`FlowCode`)");
			} else {
				log_message('error', 'Migrasi jenis_pengajuan: tb_approval_flow punya (JenisID,FlowCode) duplikat, uq_flow_jenis_code dilewati.');
			}
		}

		// 4. Isi JenisID + FlowIsRevisiTarget untuk alur perjadin ------------
		//    (semua baris yang ada = perjadin; urutan/posisi TIDAK disentuh)
		$this->db->query("UPDATE `tb_approval_flow` SET `JenisID` = 1");
		$this->db->query("UPDATE `tb_approval_flow`
			SET `FlowIsRevisiTarget` = CASE WHEN `FlowCode` IN ('PJK','PPKS1','SPM1') THEN 1 ELSE 0 END
			WHERE `JenisID` = 1");

		// 5. tb_kegiatan: tautan ke jenis -----------------------------------
		if (!$this->db->field_exists('KegiatanJenisID', 'tb_kegiatan')) {
			$this->db->query("ALTER TABLE `tb_kegiatan`
				ADD `KegiatanJenisID` int(11) DEFAULT NULL AFTER `KegiatanPemohonTipe`");
		}
		$adaIdx = $this->db->query(
			"SELECT 1 FROM information_schema.statistics
			 WHERE table_schema = DATABASE() AND table_name = 'tb_kegiatan'
			   AND index_name = 'idx_keg_jenis' LIMIT 1"
		)->num_rows() > 0;
		if (!$adaIdx) {
			$this->db->query("ALTER TABLE `tb_kegiatan` ADD INDEX `idx_keg_jenis` (`KegiatanJenisID`)");
		}

		// 6. Backfill: semua kegiatan lama = perjadin ----------------------
		$this->db->query("UPDATE `tb_kegiatan` SET `KegiatanJenisID` = 1 WHERE `KegiatanJenisID` IS NULL");
	}

	public function down()
	{
		if ($this->db->field_exists('KegiatanJenisID', 'tb_kegiatan')) {
			@$this->db->query("ALTER TABLE `tb_kegiatan` DROP INDEX `idx_keg_jenis`");
			$this->db->query("ALTER TABLE `tb_kegiatan` DROP COLUMN `KegiatanJenisID`");
		}
		if ($this->db->field_exists('JenisID', 'tb_approval_flow')) {
			@$this->db->query("ALTER TABLE `tb_approval_flow` DROP INDEX `uq_flow_jenis_code`");
			@$this->db->query("ALTER TABLE `tb_approval_flow` DROP COLUMN `FlowIsRevisiTarget`");
			$this->db->query("ALTER TABLE `tb_approval_flow` DROP COLUMN `JenisID`");
		}
		$this->db->query("DROP TABLE IF EXISTS `tb_jenis_pengajuan`");
	}
}
