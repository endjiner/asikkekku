<?php

namespace App\Models;

/**
 * M_DokUpload -- Manajemen upload dokumen eksternal (LPD, SPPD, SPM, SPP, dll.)
 * dan penempelan tanda tangan (TTD) on-document menggunakan FPDI.
 *
 * Hak upload:
 *   lpd, sppd          -> PJ-Kegiatan
 *   spm, spp, lamp16   -> SPM / SPP (dulu ditulis "Operator SPP-SPM", nama
 *                          folder GDRIVE -- tidak pernah cocok dengan
 *                          UserPosition asli 'SPM'/'SPP' di tb_users)
 *   lain               -> semuanya
 */
class M_DokUpload extends BaseModel
{
    /** Tipe dokumen yang boleh di-upload oleh masing-masing peran. */
    public const HAK_UPLOAD = [
        'PJ-Kegiatan' => ['lpd', 'sppd', 'lain'],
        'SPM'         => ['spm', 'spp', 'lamp16', 'lain'],
        'SPP'         => ['spm', 'spp', 'lamp16', 'lain'],
    ];

    public const TIPE_LABEL = [
        'lpd'    => 'Laporan Perjalanan Dinas (LPD)',
        'sppd'   => 'Surat Perintah Perjalanan Dinas',
        'spm'    => 'Surat Perintah Membayar (SPM)',
        'spp'    => 'Surat Permintaan Pembayaran (SPP)',
        'lamp16' => 'Lampiran 16 Segmen',
        'lain'   => 'Lain-lain',
    ];

    public const SLOT_TTD = [
        'ppk'   => 'PPK',
        'ppspm' => 'PPSPM',
    ];

    private const TBL     = 'tb_dok_upload';
    private const TBL_TTD = 'tb_dok_upload_ttd';

    /* =========================================================
       UPLOAD
       ========================================================= */

    public function bolehUpload($tipe, $peran)
    {
        $tipe = strtolower($tipe);
        $hak  = isset(self::HAK_UPLOAD[$peran]) ? self::HAK_UPLOAD[$peran] : ['lain'];

        return in_array($tipe, $hak, true);
    }

    /** Simpan file yang sudah divalidasi. Kembalikan UploadID atau array error. */
    public function simpanUpload($KegiatanID, $tipe, $filePath, $originalName, $fileSize, $userID)
    {
        if (! $this->db->tableExists(self::TBL)) {
            return ['ok' => false, 'msg' => 'Tabel tb_dok_upload belum ada. Jalankan assets/sql/2026-09-10_dok_upload.sql.'];
        }
        $this->db->table(self::TBL)->insert([
            'KegiatanID'   => (int) $KegiatanID,
            'Tipe'         => strtolower($tipe),
            'FilePath'     => $filePath,
            'OriginalName' => $originalName,
            'FileSize'     => (int) $fileSize,
            'UploadedBy'   => $userID ? (int) $userID : null,
            'UploadedAt'   => date('Y-m-d H:i:s'),
        ]);

        return ['ok' => true, 'UploadID' => $this->db->insertID()];
    }

    /** Daftar upload aktif untuk satu kegiatan. */
    public function listUpload($KegiatanID)
    {
        if (! $this->db->tableExists(self::TBL)) {
            return [];
        }
        $rows = $this->db->table(self::TBL)->where([
            'KegiatanID' => (int) $KegiatanID,
            'DeletedAt'  => null,
        ])->orderBy('UploadID', 'ASC')->get()->getResultArray();

        // Embed TTD status
        $ttdAll = $this->ttdAktifBatch(array_column($rows, 'UploadID'));
        foreach ($rows as &$r) {
            $r['label'] = isset(self::TIPE_LABEL[$r['Tipe']]) ? self::TIPE_LABEL[$r['Tipe']] : $r['Tipe'];
            $r['ttd']   = isset($ttdAll[$r['UploadID']]) ? $ttdAll[$r['UploadID']] : [];
        }
        unset($r);

        return $rows;
    }

    public function getUpload($UploadID)
    {
        if (! $this->db->tableExists(self::TBL)) {
            return null;
        }
        $r = $this->db->table(self::TBL)->getWhere(['UploadID' => (int) $UploadID, 'DeletedAt' => null])->getRowArray();
        if (! $r) {
            return null;
        }
        $r['label'] = isset(self::TIPE_LABEL[$r['Tipe']]) ? self::TIPE_LABEL[$r['Tipe']] : $r['Tipe'];
        $r['ttd']   = $this->ttdAktif((int) $UploadID);

        return $r;
    }

    public function hapusUpload($UploadID, $userID)
    {
        if (! $this->db->tableExists(self::TBL)) {
            return ['ok' => false, 'msg' => 'Tabel tidak ada.'];
        }
        $row = $this->getUpload($UploadID);
        if (! $row) {
            return ['ok' => false, 'msg' => 'File tidak ditemukan.'];
        }
        // Jangan hapus jika sudah ada TTD aktif
        if (! empty($row['ttd'])) {
            return ['ok' => false, 'msg' => 'File sudah ditandatangani, tidak bisa dihapus.'];
        }
        $this->db->table(self::TBL)->where('UploadID', (int) $UploadID)->update(['DeletedAt' => date('Y-m-d H:i:s')]);

        return ['ok' => true, 'msg' => 'File dihapus.'];
    }

    /* =========================================================
       TTD on-document
       ========================================================= */

    public function ttdAktif($UploadID)
    {
        if (! $this->db->tableExists(self::TBL_TTD)) {
            return [];
        }
        $rows = $this->db->table(self::TBL_TTD)->where([
            'UploadID'      => (int) $UploadID,
            'InvalidatedAt' => null,
        ])->orderBy('TtdID', 'DESC')->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            if (! isset($out[$r['Slot']])) {
                $out[$r['Slot']] = $r;
            }
        }

        return $out;
    }

    private function ttdAktifBatch(array $ids)
    {
        if (empty($ids) || ! $this->db->tableExists(self::TBL_TTD)) {
            return [];
        }
        $rows = $this->db->table(self::TBL_TTD)->whereIn('UploadID', $ids)->where('InvalidatedAt', null)->get()->getResultArray();
        $out  = [];
        foreach ($rows as $r) {
            if (! isset($out[$r['UploadID']][$r['Slot']])) {
                $out[$r['UploadID']][$r['Slot']] = $r;
            }
        }

        return $out;
    }

    /**
     * Simpan TTD: koordinat posisi + gambar PNG.
     * $pos = array(page, x, y, w, h) semua 0-1 relatif.
     * $img = binary PNG.
     */
    public function simpanTtd($UploadID, $slot, array $pos, $img, array $signer)
    {
        if (! $this->db->tableExists(self::TBL_TTD)) {
            return ['ok' => false, 'msg' => 'Tabel tb_dok_upload_ttd belum ada.'];
        }
        $slot = preg_replace('/[^a-z0-9_]/', '', strtolower($slot));
        if (! isset(self::SLOT_TTD[$slot])) {
            return ['ok' => false, 'msg' => 'Slot tidak valid.'];
        }
        if (strncmp($img, "\x89PNG\r\n\x1a\n", 8) !== 0) {
            return ['ok' => false, 'msg' => 'Gambar harus format PNG.'];
        }
        if (strlen($img) > 800 * 1024) {
            return ['ok' => false, 'msg' => 'Gambar terlalu besar (maks 800 KB).'];
        }

        $UploadID = (int) $UploadID;
        $dir      = FCPATH . 'assets/ttd_upload/' . $UploadID . '/';
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $fname = $slot . '_' . time() . '.png';
        if (@file_put_contents($dir . $fname, $img) === false) {
            return ['ok' => false, 'msg' => 'Gagal menyimpan gambar TTD.'];
        }
        $rel = 'assets/ttd_upload/' . $UploadID . '/' . $fname;

        // Invalidate TTD lama slot yang sama
        $this->db->table(self::TBL_TTD)->where(['UploadID' => $UploadID, 'Slot' => $slot, 'InvalidatedAt' => null])
            ->update(['InvalidatedAt' => date('Y-m-d H:i:s')]);

        $this->db->table(self::TBL_TTD)->insert([
            'UploadID'     => $UploadID,
            'Slot'         => $slot,
            'Page'         => isset($pos['page']) ? max(1, (int) $pos['page']) : 1,
            'PosX'         => round((float) $pos['x'], 4),
            'PosY'         => round((float) $pos['y'], 4),
            'Width'        => round((float) $pos['w'], 4),
            'Height'       => round((float) $pos['h'], 4),
            'ImagePath'    => $rel,
            'SignerUserID' => isset($signer['user_id']) ? (int) $signer['user_id'] : null,
            'SignerNama'   => isset($signer['nama']) ? $signer['nama'] : null,
            'SignerRole'   => isset($signer['role']) ? $signer['role'] : null,
            'SignedAt'     => date('Y-m-d H:i:s'),
            'SignedIP'     => isset($signer['ip']) ? $signer['ip'] : null,
        ]);

        return ['ok' => true, 'msg' => 'Tanda tangan tersimpan.'];
    }

    /* =========================================================
       FPDI: Buat PDF ber-TTD (output stream)
       ========================================================= */

    /**
     * Kembalikan binary PDF asli yang sudah ditempel gambar TTD di koordinat tersimpan.
     * Gunakan FPDI (setasign/fpdi) + FPDF.
     */
    public function buatPdfBerTtd($UploadID)
    {
        $row = $this->getUpload($UploadID);
        if (! $row) {
            return ['ok' => false, 'msg' => 'File tidak ditemukan.'];
        }
        $pdfPath = FCPATH . $row['FilePath'];
        if (! is_file($pdfPath)) {
            return ['ok' => false, 'msg' => 'File PDF tidak ditemukan di server.'];
        }

        try {
            $fpdi = new \setasign\Fpdi\Fpdi();
            $pageCount = $fpdi->setSourceFile($pdfPath);

            $ttd = $row['ttd']; // keyed by slot

            for ($pg = 1; $pg <= $pageCount; $pg++) {
                $tplId = $fpdi->importPage($pg);
                $size  = $fpdi->getTemplateSize($tplId);
                $fpdi->AddPage($size['orientation'] ?? 'P', [$size['width'], $size['height']]);
                $fpdi->useTemplate($tplId);

                // Tempel semua TTD yang ada di halaman ini
                foreach ($ttd as $slot => $t) {
                    if ((int) $t['Page'] !== $pg) {
                        continue;
                    }
                    if (empty($t['ImagePath']) || ! is_file(FCPATH . $t['ImagePath'])) {
                        continue;
                    }
                    // Konversi posisi relatif (0-1) ke mm (unit default FPDI)
                    $xMm = $t['PosX'] * $size['width'];
                    $yMm = $t['PosY'] * $size['height'];
                    $wMm = $t['Width'] * $size['width'];
                    $hMm = $t['Height'] * $size['height'];
                    $fpdi->Image(FCPATH . $t['ImagePath'], $xMm, $yMm, $wMm, $hMm, 'PNG');
                }
            }

            return ['ok' => true, 'pdf' => $fpdi->Output('S'), 'name' => $row['OriginalName'] ?: 'dokumen.pdf'];
        } catch (\Exception $e) {
            return ['ok' => false, 'msg' => 'Gagal memproses PDF: ' . $e->getMessage()];
        }
    }
}
