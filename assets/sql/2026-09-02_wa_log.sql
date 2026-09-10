-- Log pengiriman WhatsApp + token pindah ke tb_vrbl.
CREATE TABLE IF NOT EXISTS tb_wa_log (
  id            BIGINT AUTO_INCREMENT PRIMARY KEY,
  KonteksKirim  VARCHAR(40)  NULL,
  KegiatanID    INT          NULL,
  Nomor         VARCHAR(30)  NULL,
  Pesan         TEXT         NULL,
  HttpCode      INT          NULL,
  Status        VARCHAR(10)  NULL,  -- 'ok' | 'gagal'
  Respons       TEXT         NULL,
  TanggalKirim  DATETIME     NOT NULL,
  KEY idx_wa_keg (KegiatanID),
  KEY idx_wa_tgl (TanggalKirim)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Token Wablas: pindahkan dari source ke sini (isi nilai token yang benar).
INSERT INTO tb_vrbl (VrblName, VrblValue)
SELECT 'wa_token', 'ISI_TOKEN_WABLAS_DI_SINI'
WHERE NOT EXISTS (SELECT 1 FROM tb_vrbl WHERE VrblName = 'wa_token');
