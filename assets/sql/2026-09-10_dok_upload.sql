-- Migration: tb_dok_upload + tb_dok_upload_ttd
-- Upload dokumen eksternal (LPD, SPPD, SPM, SPP) + TTD on-document

CREATE TABLE IF NOT EXISTS `tb_dok_upload` (
  `UploadID`     INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `KegiatanID`   INT UNSIGNED NOT NULL,
  `Tipe`         VARCHAR(30)  NOT NULL DEFAULT 'lain'
                 COMMENT 'lpd|sppd|spm|spp|lain',
  `FilePath`     VARCHAR(512) NOT NULL COMMENT 'path relatif dari FCPATH',
  `OriginalName` VARCHAR(255) DEFAULT NULL,
  `FileSize`     INT UNSIGNED DEFAULT NULL COMMENT 'bytes',
  `UploadedBy`   INT UNSIGNED DEFAULT NULL,
  `UploadedAt`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `DeletedAt`    DATETIME     DEFAULT NULL,
  INDEX `idx_du_kegiatan` (`KegiatanID`, `DeletedAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tb_dok_upload_ttd` (
  `TtdID`        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `UploadID`     INT UNSIGNED NOT NULL,
  `Slot`         VARCHAR(30)  NOT NULL COMMENT 'ppk|ppspm',
  `Page`         SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `PosX`         DECIMAL(6,4) NOT NULL COMMENT '0-1 relatif lebar halaman',
  `PosY`         DECIMAL(6,4) NOT NULL COMMENT '0-1 relatif tinggi halaman',
  `Width`        DECIMAL(6,4) NOT NULL DEFAULT 0.2000,
  `Height`       DECIMAL(6,4) NOT NULL DEFAULT 0.0600,
  `ImagePath`    VARCHAR(512) DEFAULT NULL,
  `SignerUserID` INT UNSIGNED DEFAULT NULL,
  `SignerNama`   VARCHAR(200) DEFAULT NULL,
  `SignerRole`   VARCHAR(50)  DEFAULT NULL,
  `SignedAt`     DATETIME     DEFAULT NULL,
  `SignedIP`     VARCHAR(45)  DEFAULT NULL,
  `InvalidatedAt` DATETIME   DEFAULT NULL,
  INDEX `idx_dut_upload` (`UploadID`, `Slot`, `InvalidatedAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
