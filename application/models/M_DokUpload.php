<?php
defined("BASEPATH") or exit("No direct script access allowed");

/**
 * M_DokUpload — Manajemen upload dokumen eksternal (LPD, SPPD, SPM, SPP, dll.)
 * dan penempelan tanda tangan (TTD) on-document menggunakan FPDI.
 *
 * Hak upload:
 *   lpd, sppd          -> PJ-Kegiatan
 *   spm, spp, lamp16   -> SPM / SPP (dulu ditulis "Operator SPP-SPM", nama
 *                          folder GDRIVE -- tidak pernah cocok dengan
 *                          UserPosition asli 'SPM'/'SPP' di tb_users)
 *   lain               -> semuanya
 */
class M_DokUpload extends CI_Model
{
    /** Tipe dokumen yang boleh di-upload oleh masing-masing peran. */
    const HAK_UPLOAD = array(
        "PJ-Kegiatan" => array("lpd", "sppd", "lain"),
        "SPM"         => array("spm", "spp", "lamp16", "lain"),
        "SPP"         => array("spm", "spp", "lamp16", "lain"),
    );

    const TIPE_LABEL = array(
        "lpd"    => "Laporan Perjalanan Dinas (LPD)",
        "sppd"   => "Surat Perintah Perjalanan Dinas",
        "spm"    => "Surat Perintah Membayar (SPM)",
        "spp"    => "Surat Permintaan Pembayaran (SPP)",
        "lamp16" => "Lampiran 16 Segmen",
        "lain"   => "Lain-lain",
    );

    const SLOT_TTD = array(
        "ppk"   => "PPK",
        "ppspm" => "PPSPM",
    );

    private function tbl()  { return "tb_dok_upload"; }
    private function tblT() { return "tb_dok_upload_ttd"; }

    /* =========================================================
       UPLOAD
       ========================================================= */

    public function bolehUpload($tipe, $peran)
    {
        $tipe = strtolower($tipe);
        $hak  = isset(self::HAK_UPLOAD[$peran]) ? self::HAK_UPLOAD[$peran] : array("lain");
        return in_array($tipe, $hak, true) || in_array("lain", $hak, true) && $tipe === "lain";
    }

    /** Simpan file yang sudah divalidasi. Kembalikan UploadID atau array error. */
    public function simpanUpload($KegiatanID, $tipe, $filePath, $originalName, $fileSize, $userID)
    {
        if (!$this->db->table_exists($this->tbl())) {
            return array("ok" => false, "msg" => "Tabel tb_dok_upload belum ada. Jalankan assets/sql/2026-09-10_dok_upload.sql.");
        }
        $this->db->insert($this->tbl(), array(
            "KegiatanID"   => (int) $KegiatanID,
            "Tipe"         => strtolower($tipe),
            "FilePath"     => $filePath,
            "OriginalName" => $originalName,
            "FileSize"     => (int) $fileSize,
            "UploadedBy"   => $userID ? (int) $userID : null,
            "UploadedAt"   => date("Y-m-d H:i:s"),
        ));
        return array("ok" => true, "UploadID" => $this->db->insert_id());
    }

    /** Daftar upload aktif untuk satu kegiatan. */
    public function listUpload($KegiatanID)
    {
        if (!$this->db->table_exists($this->tbl())) return array();
        $rows = $this->db->where(array(
            "KegiatanID" => (int) $KegiatanID,
            "DeletedAt"  => null,
        ))->order_by("UploadID", "ASC")->get($this->tbl())->result_array();

        // Embed TTD status
        $ttdAll = $this->ttdAktifBatch(array_column($rows, "UploadID"));
        foreach ($rows as &$r) {
            $r["label"] = isset(self::TIPE_LABEL[$r["Tipe"]]) ? self::TIPE_LABEL[$r["Tipe"]] : $r["Tipe"];
            $r["ttd"]   = isset($ttdAll[$r["UploadID"]]) ? $ttdAll[$r["UploadID"]] : array();
        }
        unset($r);
        return $rows;
    }

    public function getUpload($UploadID)
    {
        if (!$this->db->table_exists($this->tbl())) return null;
        $r = $this->db->get_where($this->tbl(), array("UploadID" => (int) $UploadID, "DeletedAt" => null))->row_array();
        if (!$r) return null;
        $r["label"] = isset(self::TIPE_LABEL[$r["Tipe"]]) ? self::TIPE_LABEL[$r["Tipe"]] : $r["Tipe"];
        $r["ttd"]   = $this->ttdAktif((int) $UploadID);
        return $r;
    }

    public function hapusUpload($UploadID, $userID)
    {
        if (!$this->db->table_exists($this->tbl())) return array("ok" => false, "msg" => "Tabel tidak ada.");
        $row = $this->getUpload($UploadID);
        if (!$row) return array("ok" => false, "msg" => "File tidak ditemukan.");
        // Jangan hapus jika sudah ada TTD aktif
        if (!empty($row["ttd"])) return array("ok" => false, "msg" => "File sudah ditandatangani, tidak bisa dihapus.");
        $this->db->where("UploadID", (int) $UploadID)->update($this->tbl(), array("DeletedAt" => date("Y-m-d H:i:s")));
        return array("ok" => true, "msg" => "File dihapus.");
    }

    /* =========================================================
       TTD on-document
       ========================================================= */

    public function ttdAktif($UploadID)
    {
        if (!$this->db->table_exists($this->tblT())) return array();
        $rows = $this->db->where(array(
            "UploadID"     => (int) $UploadID,
            "InvalidatedAt"=> null,
        ))->order_by("TtdID", "DESC")->get($this->tblT())->result_array();
        $out = array();
        foreach ($rows as $r) { if (!isset($out[$r["Slot"]])) $out[$r["Slot"]] = $r; }
        return $out;
    }

    private function ttdAktifBatch(array $ids)
    {
        if (empty($ids) || !$this->db->table_exists($this->tblT())) return array();
        $rows = $this->db->where_in("UploadID", $ids)->where("InvalidatedAt", null)->get($this->tblT())->result_array();
        $out  = array();
        foreach ($rows as $r) {
            if (!isset($out[$r["UploadID"]][$r["Slot"]])) {
                $out[$r["UploadID"]][$r["Slot"]] = $r;
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
        if (!$this->db->table_exists($this->tblT())) {
            return array("ok" => false, "msg" => "Tabel tb_dok_upload_ttd belum ada.");
        }
        $slot = preg_replace("/[^a-z0-9_]/", "", strtolower($slot));
        if (!isset(self::SLOT_TTD[$slot])) return array("ok" => false, "msg" => "Slot tidak valid.");
        if (strncmp($img, "\x89PNG\r\n\x1a\n", 8) !== 0) return array("ok" => false, "msg" => "Gambar harus format PNG.");
        if (strlen($img) > 800 * 1024) return array("ok" => false, "msg" => "Gambar terlalu besar (maks 800 KB).");

        $UploadID = (int) $UploadID;
        $dir = FCPATH . "assets/ttd_upload/" . $UploadID . "/";
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $fname = $slot . "_" . time() . ".png";
        if (@file_put_contents($dir . $fname, $img) === false) {
            return array("ok" => false, "msg" => "Gagal menyimpan gambar TTD.");
        }
        $rel = "assets/ttd_upload/" . $UploadID . "/" . $fname;

        // Invalidate TTD lama slot yang sama
        $this->db->where(array("UploadID" => $UploadID, "Slot" => $slot, "InvalidatedAt" => null))
            ->update($this->tblT(), array("InvalidatedAt" => date("Y-m-d H:i:s")));

        $this->db->insert($this->tblT(), array(
            "UploadID"     => $UploadID,
            "Slot"         => $slot,
            "Page"         => isset($pos["page"]) ? max(1, (int) $pos["page"]) : 1,
            "PosX"         => round(floatval($pos["x"]), 4),
            "PosY"         => round(floatval($pos["y"]), 4),
            "Width"        => round(floatval($pos["w"]), 4),
            "Height"       => round(floatval($pos["h"]), 4),
            "ImagePath"    => $rel,
            "SignerUserID" => isset($signer["user_id"]) ? (int) $signer["user_id"] : null,
            "SignerNama"   => isset($signer["nama"])    ? $signer["nama"]           : null,
            "SignerRole"   => isset($signer["role"])    ? $signer["role"]           : null,
            "SignedAt"     => date("Y-m-d H:i:s"),
            "SignedIP"     => isset($signer["ip"])      ? $signer["ip"]             : null,
        ));
        return array("ok" => true, "msg" => "Tanda tangan tersimpan.");
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
        if (!$row) return array("ok" => false, "msg" => "File tidak ditemukan.");
        $pdfPath = FCPATH . $row["FilePath"];
        if (!is_file($pdfPath)) return array("ok" => false, "msg" => "File PDF tidak ditemukan di server.");

        require_once FCPATH . "vendor/autoload.php";

        try {
            $fpdi = new \setasign\Fpdi\Fpdi();
            $fpdi->setSourceFile($pdfPath);
            $pageCount = $fpdi->setSourceFile($pdfPath);

            $ttd = $row["ttd"]; // keyed by slot

            for ($pg = 1; $pg <= $pageCount; $pg++) {
                $tplId = $fpdi->importPage($pg);
                $size  = $fpdi->getTemplateSize($tplId);
                $fpdi->AddPage($size["orientation"] ?? "P", array($size["width"], $size["height"]));
                $fpdi->useTemplate($tplId);

                // Tempel semua TTD yang ada di halaman ini
                foreach ($ttd as $slot => $t) {
                    if ((int) $t["Page"] !== $pg) continue;
                    if (empty($t["ImagePath"]) || !is_file(FCPATH . $t["ImagePath"])) continue;
                    // Konversi posisi relatif (0-1) ke mm (unit default FPDI)
                    $xMm = $t["PosX"] * $size["width"];
                    $yMm = $t["PosY"] * $size["height"];
                    $wMm = $t["Width"]  * $size["width"];
                    $hMm = $t["Height"] * $size["height"];
                    $fpdi->Image(FCPATH . $t["ImagePath"], $xMm, $yMm, $wMm, $hMm, "PNG");
                }
            }

            return array("ok" => true, "pdf" => $fpdi->Output("S"), "name" => $row["OriginalName"] ?: "dokumen.pdf");
        } catch (\Exception $e) {
            return array("ok" => false, "msg" => "Gagal memproses PDF: " . $e->getMessage());
        }
    }
}
