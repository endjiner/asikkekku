-- =====================================================================
-- Modul Dokumen (form_inapp) — menyimpan NILAI FIELD saja, bukan file.
-- Formulir cetak dirender ulang dari layout + payload ini.
-- Jalankan sekali: mysql -u root asikkekkuv2 < assets/sql/2026-09-04_dokumen.sql
-- =====================================================================

CREATE TABLE IF NOT EXISTS `tb_dokumen` (
  `DokumenID`     INT(11)      NOT NULL AUTO_INCREMENT,
  `KegiatanID`    INT(11)      NOT NULL,
  `Kode`          VARCHAR(40)  NOT NULL COMMENT 'kwitansi|nominatif|riil|spd|sptjb|lembar_periksa',
  `RangkapKey`    VARCHAR(80)  NOT NULL DEFAULT '-' COMMENT 'nama penerima utk dokumen per_penerima, "-" utk per_kegiatan',
  `PayloadJson`   LONGTEXT     NOT NULL,
  `TemplateVersi` VARCHAR(20)  NOT NULL DEFAULT 'v1',
  `UpdatedBy`     INT(11)      DEFAULT NULL,
  `UpdatedAt`     DATETIME     DEFAULT NULL,
  PRIMARY KEY (`DokumenID`),
  UNIQUE KEY `uniq_dokumen` (`KegiatanID`,`Kode`,`RangkapKey`),
  KEY `idx_kegiatan` (`KegiatanID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
