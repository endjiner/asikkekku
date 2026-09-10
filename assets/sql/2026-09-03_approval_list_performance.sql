ALTER TABLE tb_approval_history
  ADD INDEX idx_approval_history_kegiatan_history (KegiatanID, HistoryID);