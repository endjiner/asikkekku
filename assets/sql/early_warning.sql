-- =====================================================================
-- ASIKKEKKU — Peringatan Dini 4HK (Early Warning)
-- Jalankan sekali lewat phpMyAdmin / mysql CLI pada database aplikasi.
-- Aman dijalankan ulang (IF NOT EXISTS + pembersihan baris duplikat).
-- =====================================================================

-- 1) Tabel log notifikasi (anti-spam: maksimal 1 kirim per kegiatan+tahap per hari)
CREATE TABLE IF NOT EXISTS `tb_ew_notifikasi` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `KegiatanID` INT(11) DEFAULT NULL,
  `FlowCode` VARCHAR(20) DEFAULT NULL,
  `Level` VARCHAR(20) DEFAULT NULL,
  `TanggalKirim` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_kegiatan_flow_tgl` (`KegiatanID`, `FlowCode`, `TanggalKirim`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2) Ambang batas SLA + kunci cron di tb_vrbl (tabel key-value tanpa PK).
--    Hapus dulu bila sudah ada supaya tidak dobel, lalu isi ulang.
DELETE FROM `tb_vrbl` WHERE `VrblName` IN
  ('sla_total_hk', 'sla_warn_total_hk', 'sla_warn_stage_hk', 'ew_cron_key');

INSERT INTO `tb_vrbl` (`VrblName`, `VrblValue`) VALUES
  ('sla_total_hk',      '4'),
  ('sla_warn_total_hk', '3'),
  ('sla_warn_stage_hk', '1'),
  ('ew_cron_key',       'asik-ew-2026-ganti-nilai-ini');