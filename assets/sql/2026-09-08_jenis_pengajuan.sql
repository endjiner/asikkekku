-- ============================================================================
-- 2026-09-08  Jenis Pengajuan + alur persetujuan per jenis
-- ----------------------------------------------------------------------------
-- Cermin dari migrasi CI3 application/migrations/20260908120000_jenis_pengajuan_configurable_flow.php
-- Aman dijalankan ulang (IF NOT EXISTS / guard). Untuk apply manual DBA / import fresh.
--
-- Alur perjadin (6 tahap: PJK -> PPKS1 -> SPM1 -> VRF2 -> PPK1 -> SLS) DIADOPSI
-- APA ADANYA sebagai JenisID = 1. TIDAK menomori ulang FlowOrder, TIDAK
-- mengaktifkan tahap dorman (VRF1/SPP1/PPSPM1), TIDAK mengubah FlowPosition.
-- ============================================================================

-- 1. Master jenis pengajuan --------------------------------------------------
CREATE TABLE IF NOT EXISTS `tb_jenis_pengajuan` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Seed jenis 'PERJADIN' (JenisID = 1) -----------------------------------
INSERT INTO `tb_jenis_pengajuan`
  (`JenisID`,`JenisKode`,`JenisNama`,`JenisNote`,`JenisAktif`,`JenisUrutan`)
SELECT 1,'PERJADIN','Perjalanan Dinas (LS)',
       'Pencairan LS perjalanan dinas - alur 6 tahap yang berjalan saat ini',1,1
WHERE NOT EXISTS (SELECT 1 FROM `tb_jenis_pengajuan` WHERE `JenisKode` = 'PERJADIN');

-- 3. tb_approval_flow: kolom scope + flag target revisi -------------------
-- (MariaDB 10.4+ mendukung ADD COLUMN IF NOT EXISTS)
ALTER TABLE `tb_approval_flow`
  ADD COLUMN IF NOT EXISTS `JenisID` int(11) NOT NULL DEFAULT 1 AFTER `FlowPosition`,
  ADD COLUMN IF NOT EXISTS `FlowIsRevisiTarget` tinyint(1) NOT NULL DEFAULT 0 AFTER `JenisID`,
  MODIFY `FlowCode` varchar(40) DEFAULT NULL;

ALTER TABLE `tb_approval_flow`
  ADD UNIQUE KEY IF NOT EXISTS `uq_flow_jenis_code` (`JenisID`,`FlowCode`);

-- 4. Isi JenisID + FlowIsRevisiTarget untuk alur perjadin ---------------
UPDATE `tb_approval_flow` SET `JenisID` = 1;
UPDATE `tb_approval_flow`
  SET `FlowIsRevisiTarget` = CASE WHEN `FlowCode` IN ('PJK','PPKS1','SPM1') THEN 1 ELSE 0 END
  WHERE `JenisID` = 1;

-- 5. tb_kegiatan: tautan ke jenis --------------------------------------
ALTER TABLE `tb_kegiatan`
  ADD COLUMN IF NOT EXISTS `KegiatanJenisID` int(11) DEFAULT NULL AFTER `KegiatanPemohonTipe`,
  ADD INDEX IF NOT EXISTS `idx_keg_jenis` (`KegiatanJenisID`);

-- 6. Backfill: semua kegiatan lama = perjadin -------------------------
UPDATE `tb_kegiatan` SET `KegiatanJenisID` = 1 WHERE `KegiatanJenisID` IS NULL;
