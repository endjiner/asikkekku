-- Fitur "Batalkan / Revisi" pengembalian pengajuan.
-- Jenis pengembalian ('revisi' | 'Batalkan') dicatat di riwayat approval.
ALTER TABLE tb_approval_history ADD COLUMN FlowRejectType VARCHAR(20) NULL AFTER FlowResult;

-- Nilai KegiatanStatus baru yang dipakai fitur ini (kolom sudah VARCHAR(20)):
--   'Perlu Revisi' : dikembalikan ke PJ/Staff PPK/SPM untuk diperbaiki.
--   'Dibatalkan'   : di-Batalkan oleh PJ (dokumen dobel); lampiran dihapus.
