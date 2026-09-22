<?php

namespace App\Controllers;

use App\Models\M_Dokumen;
use App\Models\M_DokUpload;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Dokumen extends AppController
{
    private $peran_alur = ['SuperAdmin', 'PJ-Kegiatan', 'PPK-Staff', 'Verifikator', 'SPP', 'PPK', 'SPM', 'PPSPM'];

    /** @var M_Dokumen */
    private $M_Dokumen;

    /** @var M_DokUpload|null */
    private $M_DokUpload;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->M_Dokumen = model(M_Dokumen::class);
        helper('dokumen');
        $this->bootData();
        $this->data['menu_detail'] = ['MenuName' => 'Dokumen Pencairan', 'MenuKode' => '3000'];
        if (! in_array($this->session->get('UserPosition'), $this->peran_alur, true)) {
            show_error('Anda tidak berhak mengakses modul Dokumen Pencairan.', 403);
            exit;
        }
    }

    public function index()
    {
        legacy_redirect(base_url('dokumen/pilih'));
    }

    /** Hak akses (lihat/isi) user yang login sekarang atas sebuah dokumen kegiatan. */
    private function _akses($kode, $KegiatanID)
    {
        return $this->M_Dokumen->aksesDokumen($kode, $KegiatanID, $this->session->get('UserPosition'));
    }

    /** ?r= dari querystring, default '-' kalau kosong/tidak ada. */
    private function _rangkapGet(): string
    {
        $rangkap = $this->request->getGet('r');

        return ($rangkap === null || $rangkap === '') ? '-' : $rangkap;
    }

    /** rangkap dari POST, default '-' kalau kosong/tidak ada. */
    private function _rangkapPost(): string
    {
        return $this->request->getPost('rangkap') ?: '-';
    }

    /** ?ttd=0|1 dari querystring -- default TRUE (tampilkan ttd), hanya '0' yang mematikan. */
    private function _withTtdGet(): bool
    {
        return $this->request->getGet('ttd') !== '0';
    }

    /** Resolusi template + show_404() kalau tidak ada. Panggilan: $tpl = $this->_templateOr404($kode); if ($tpl === null) return; */
    private function _templateOr404($kode)
    {
        $tpl = $this->M_Dokumen->template($kode);
        if (! $tpl) {
            show_404();

            return null;
        }

        return $tpl;
    }

    /** Hak lihat + show_error(403) kalau tidak boleh. Panggilan: if (! $this->_requireLihat($kode, $KegiatanID)) return; */
    private function _requireLihat($kode, $KegiatanID): bool
    {
        if (! $this->_akses($kode, $KegiatanID)['lihat']) {
            show_error('Dokumen ini belum bisa diakses, menunggu tahap sebelumnya selesai.', 403);

            return false;
        }

        return true;
    }

    /**
     * Selektor 'd[]=kode:rangkap' default kalau user tidak memilih apa-apa --
     * semua dokumen inapp dari bundel unified (dipakai paketUnduh/paketCetak).
     *
     * @return list<string>
     */
    private function _defaultSelection(int $KegiatanID): array
    {
        $bundle = $this->M_Dokumen->buildUnifiedBundle($KegiatanID, $this->session->get('UserPosition'));
        $sel    = [];
        foreach ($bundle['items'] as $it) {
            if ($it['type'] === 'inapp') {
                $sel[] = $it['kode'] . ':' . $it['rangkap'];
            }
        }

        return $sel;
    }

    /**
     * Uraikan satu selektor 'kode:rangkap', lalu resolusi template + cek hak
     * lihat. Null kalau kode/rangkap tidak valid atau tidak boleh diakses
     * (pemanggil `continue` loop-nya).
     *
     * @return array{kode: string, rangkap: string, tpl: array}|null
     */
    private function _resolveSelItem(string $s, int $KegiatanID): ?array
    {
        $p       = explode(':', $s, 2);
        $kode    = preg_replace('/[^a-z_]/', '', strtolower($p[0]));
        $rangkap = isset($p[1]) && $p[1] !== '' ? $p[1] : '-';
        $tpl     = $this->M_Dokumen->template($kode);
        if (! $tpl || ! $this->_akses($kode, $KegiatanID)['lihat']) {
            return null;
        }

        return ['kode' => $kode, 'rangkap' => $rangkap, 'tpl' => $tpl];
    }

    /** Boleh menandatangani slot upload ini? ppk -> hanya PPK; ppspm -> hanya PPSPM/SuperAdmin. */
    private function _uploadSlotBoleh(string $slot, string $pos): bool
    {
        if ($slot === 'ppk' && $pos === 'PPK') {
            return true;
        }

        return $slot === 'ppspm' && in_array($pos, ['PPSPM', 'SuperAdmin'], true);
    }

    public function panel($KegiatanID = 0)
    {
        $KegiatanID = (int) $KegiatanID;
        $userPos    = $this->session->get('UserPosition');

        return view('dokumen/panel', [
            'KegiatanID'   => $KegiatanID,
            'kegiatan'     => $this->M_Dokumen->kegiatan($KegiatanID),
            'dokumen'      => $this->M_Dokumen->dokumenUntukKegiatan($KegiatanID, $userPos),
            'ttdmap'       => $this->M_Dokumen->ttdAktifKegiatan($KegiatanID),
            'userPosition' => $userPos,
        ]);
    }

    public function pilih()
    {
        $this->data['kegiatan_list'] = $this->M_Dokumen->kegiatanTerbaru(60);
        $this->data['templates']     = $this->M_Dokumen->templates();
        $this->data['body']          = 'dokumen/pilih';

        return view('main', $this->data);
    }

    public function form($kode = '', $KegiatanID = 0)
    {
        $tpl = $this->_templateOr404($kode);
        if ($tpl === null) {
            return;
        }
        $KegiatanID = (int) $KegiatanID;
        $rangkap    = $this->_rangkapGet();

        // Cuma yang memang bagiannya, tepat di tahapnya, boleh mengisi. Kalau
        // dokumennya sudah "waktunya" tapi bukan bagian user ini (mis. tahap
        // lain, atau sudah lewat), arahkan ke tampilan cetak (baca saja).
        // Kalau belum waktunya sama sekali, tolak total -- ini yang bikin
        // user tidak bisa mengintip dokumen tahap berikutnya yang belum diisi.
        $akses = $this->_akses($kode, $KegiatanID);
        if (! $akses['isi']) {
            if ($akses['lihat']) {
                $q = ($rangkap !== '-') ? ('?r=' . rawurlencode($rangkap)) : '';
                legacy_redirect(base_url('dokumen/cetak/' . $kode . '/' . $KegiatanID . $q));
            } else {
                $this->session->setFlashdata('error', 'Dokumen ini belum bisa diakses, menunggu tahap sebelumnya selesai.');
                legacy_redirect(base_url('dokumen/pilih'));
            }

            return;
        }

        $this->data['kode']         = $kode;
        $this->data['tpl']          = $tpl;
        $this->data['KegiatanID']   = $KegiatanID;
        $this->data['kegiatan']     = $this->M_Dokumen->kegiatan($KegiatanID);
        $this->data['rangkap']      = $rangkap;
        $this->data['rangkap_list'] = $this->M_Dokumen->rangkapList($KegiatanID);
        $this->data['payload']      = $this->M_Dokumen->load($kode, $KegiatanID, $rangkap);
        $this->data['body']         = 'dokumen/form';

        return view('main', $this->data);
    }

    public function pratinjau()
    {
        $kode       = $this->request->getPost('kode');
        $KegiatanID = (int) $this->request->getPost('KegiatanID');
        $rangkap    = $this->_rangkapPost();
        $payloadIn  = json_decode($this->request->getPost('payload'), true) ?: [];

        $tpl = $this->M_Dokumen->template($kode);
        if (! $tpl || ! $this->_akses($kode, $KegiatanID)['isi']) {
            show_404();

            return;
        }

        $auto    = $this->M_Dokumen->autofill($kode, $KegiatanID, $rangkap);
        $payload = array_merge($auto, $payloadIn);

        return view('dokumen/_render', [
            'kode' => $kode, 'tpl' => $tpl, 'd' => $payload,
            'kegiatan' => $this->M_Dokumen->kegiatan($KegiatanID),
            'ttd' => $this->_ttdMap($KegiatanID, $kode, $rangkap),
            'tampilkan_ttd' => true,
        ]);
    }

    private function _ttdMap($KegiatanID, $kode, $rangkap)
    {
        $map = $this->M_Dokumen->ttdAktif($KegiatanID, $kode, $rangkap);
        $cap = $this->M_Dokumen->capImagePath();
        if ($cap) {
            $map['_cap_path'] = $cap;
        }

        return $map;
    }

    public function simpan()
    {
        $kode       = $this->request->getPost('kode');
        $KegiatanID = (int) $this->request->getPost('KegiatanID');
        $rangkap    = $this->_rangkapPost();
        $payload    = json_decode($this->request->getPost('payload'), true);
        if (! $this->M_Dokumen->template($kode) || ! is_array($payload)) {
            return $this->jsonResponse(['ok' => false, 'msg' => 'Data tidak valid.']);
        }
        if (! $this->_akses($kode, $KegiatanID)['isi']) {
            return $this->jsonResponse(['ok' => false, 'msg' => 'Bukan bagian Anda / belum waktunya mengisi dokumen ini.']);
        }
        $res = $this->M_Dokumen->saveDokumen($kode, $KegiatanID, $rangkap, $payload, $this->data['UserID']);

        return $this->jsonResponse($res);
    }

    public function cetak($kode = '', $KegiatanID = 0)
    {
        $tpl = $this->_templateOr404($kode);
        if ($tpl === null) {
            return;
        }
        if (! $this->_requireLihat($kode, (int) $KegiatanID)) {
            return;
        }
        $rangkap = $this->_rangkapGet();
        $withTtd = $this->_withTtdGet();

        return view('dokumen/cetak', [
            'kode'     => $kode,
            'tpl'      => $tpl,
            'd'        => $this->M_Dokumen->load($kode, (int) $KegiatanID, $rangkap),
            'kegiatan' => $this->M_Dokumen->kegiatan((int) $KegiatanID),
            'ttd'      => $withTtd ? $this->_ttdMap((int) $KegiatanID, $kode, $rangkap) : [],
            'tampilkan_ttd' => $withTtd,
            'AppConfig' => $this->data['AppConfig'],
        ]);
    }

    /* ---------------- UNDUH PDF (mPDF, server-side) ---------------- */

    private function _mpdf()
    {
        $tmp = FCPATH . 'assets/uploads/tmp';
        if (! is_dir($tmp)) {
            @mkdir($tmp, 0775, true);
        }

        return new \Mpdf\Mpdf([
            'mode' => 'utf-8', 'format' => 'A4', 'tempDir' => $tmp,
            'margin_left' => 18, 'margin_right' => 18, 'margin_top' => 15, 'margin_bottom' => 15,
            'default_font' => 'dejavuserif',
        ]);
    }

    private function _docCss()
    {
        $f = FCPATH . 'assets/css/dokumen.css';

        return is_file($f) ? file_get_contents($f) : '';
    }

    /**
     * Untuk dokumen dengan tabel bersarang (kwitansi, spd) atau <ol>/<li>
     * (riil), mPDF menandai tiap objek inline lalu unserialize() potongan
     * setelahnya. Begitu ada >1 objek begitu di buffer yang sama, unserialize()
     * tetap balikin nilai yang benar TAPI juga E_WARNING "Extra data ..."
     * (perilaku unserialize() sejak PHP 7). CI menangkap warning non-fatal itu
     * dan langsung meng-echo HTML error di tengah output PDF, bikin header
     * "Content-Disposition" gagal terkirim -> unduhan rusak jadi teks HTML.
     * mPDF sendiri tidak terganggu oleh warning ini, jadi aman diredam di sini
     * saja (bukan global) supaya error PHP lain tetap kelihatan.
     */
    private function _quietMpdfWarnings()
    {
        return error_reporting(error_reporting() & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
    }

    /** Fragmen HTML _render sebuah dokumen (untuk mPDF). */
    private function _renderHtml($kode, $tpl, $d, $kegiatan, $ttd, $withTtd)
    {
        return view('dokumen/_render', [
            'kode' => $kode, 'tpl' => $tpl, 'd' => $d, 'kegiatan' => $kegiatan,
            'ttd' => $ttd, 'tampilkan_ttd' => $withTtd,
        ]);
    }

    /** Unduh satu dokumen sebagai PDF A4. ?r=&ttd=0|1 */
    public function unduh($kode = '', $KegiatanID = 0)
    {
        $tpl = $this->_templateOr404($kode);
        if ($tpl === null) {
            return;
        }
        $KegiatanID = (int) $KegiatanID;
        if (! $this->_requireLihat($kode, $KegiatanID)) {
            return;
        }
        $rangkap = $this->_rangkapGet();
        $withTtd = $this->_withTtdGet();

        $html = $this->_renderHtml($kode, $tpl, $this->M_Dokumen->load($kode, $KegiatanID, $rangkap),
            $this->M_Dokumen->kegiatan($KegiatanID),
            $withTtd ? $this->_ttdMap($KegiatanID, $kode, $rangkap) : [], $withTtd);

        $this->M_Dokumen->printLog($KegiatanID, [$kode . ':' . $rangkap], $withTtd, $this->data['UserID']);

        $name = $kode . '_' . $KegiatanID . ($rangkap !== '-' ? '_' . preg_replace('/[^A-Za-z0-9]+/', '-', $rangkap) : '')
            . ($withTtd ? '' : '_tanpa-ttd') . '.pdf';
        $prevReporting = $this->_quietMpdfWarnings();
        try {
            $mpdf = $this->_mpdf();
            $mpdf->WriteHTML($this->_docCss(), \Mpdf\HTMLParserMode::HEADER_CSS);
            $mpdf->WriteHTML('<div class="a4">' . $html . '</div>', \Mpdf\HTMLParserMode::HTML_BODY);
            $pdf = $mpdf->Output($name, \Mpdf\Output\Destination::STRING);
        } finally {
            error_reporting($prevReporting);
        }

        // Lewat $this->response (bukan header()+echo mentah): Response CI4
        // selalu punya Content-Type default 'text/html' yang di-kirim ulang
        // saat framework mengirim respons -- header() mentah akan tertimpa.
        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $name . '"')
            ->setBody($pdf);
    }

    /** Unduh beberapa dokumen sebagai SATU PDF. ?d[]=kode:rangkap & ttd=0|1 */
    public function unifiedPreview($KegiatanID = 0)
    {
        $KegiatanID = (int) $KegiatanID;
        $bundle     = $this->M_Dokumen->buildUnifiedBundle($KegiatanID, $this->session->get('UserPosition'));
        $bundle['KegiatanID'] = $KegiatanID;
        $bundle['userPos']    = $this->session->get('UserPosition');
        $bundle['AppConfig']  = isset($this->data['AppConfig']) ? $this->data['AppConfig'] : [];

        return view('dokumen/unified_preview', $bundle);
    }

    public function simpanUrutan()
    {
        $KegiatanID = (int) $this->request->getPost('KegiatanID');
        $order      = (array) $this->request->getPost('order');
        $res        = $this->M_Dokumen->saveDokumenUrutan($KegiatanID, $order);

        return $this->jsonResponse($res);
    }

    /** Unduh beberapa dokumen sebagai SATU PDF. ?d[]=kode:rangkap & ttd=0|1 */
    public function paketUnduh($KegiatanID = 0)
    {
        $KegiatanID = (int) $KegiatanID;
        $withTtd    = $this->_withTtdGet();
        $sel        = (array) $this->request->getGet('d');
        $kegiatan   = $this->M_Dokumen->kegiatan($KegiatanID);

        if (empty($sel)) {
            $sel = $this->_defaultSelection($KegiatanID);
        }

        $prevReporting = $this->_quietMpdfWarnings();
        $pdf  = null;
        $name = 'paket_' . $KegiatanID . ($withTtd ? '' : '_tanpa-ttd') . '.pdf';
        try {
            $mpdf = $this->_mpdf();
            $mpdf->WriteHTML($this->_docCss(), \Mpdf\HTMLParserMode::HEADER_CSS);
            $n   = 0;
            $isi = [];
            foreach ($sel as $s) {
                $item = $this->_resolveSelItem($s, $KegiatanID);
                if ($item === null) {
                    continue;
                }
                ['kode' => $kode, 'rangkap' => $rangkap, 'tpl' => $tpl] = $item;
                if ($n > 0) {
                    $mpdf->AddPage();
                }
                $html = $this->_renderHtml($kode, $tpl, $this->M_Dokumen->load($kode, $KegiatanID, $rangkap),
                    $kegiatan, $withTtd ? $this->_ttdMap($KegiatanID, $kode, $rangkap) : [], $withTtd);
                $mpdf->WriteHTML('<div class="a4">' . $html . '</div>', \Mpdf\HTMLParserMode::HTML_BODY);
                $isi[] = $kode . ':' . $rangkap;
                $n++;
            }
            if (! $n) {
                show_error('Tidak ada dokumen dipilih.', 400);

                return;
            }
            $this->M_Dokumen->printLog($KegiatanID, $isi, $withTtd, $this->data['UserID']);
            $pdf = $mpdf->Output($name, \Mpdf\Output\Destination::STRING);
        } finally {
            error_reporting($prevReporting);
        }

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $name . '"')
            ->setBody($pdf);
    }

    /* ---------------- PAKET CETAK / UNDUH (browser-print) ---------------- */

    /** Layar pilih dokumen untuk dicetak/diunduh sekaligus. */
    public function paket($KegiatanID = 0)
    {
        $KegiatanID                = (int) $KegiatanID;
        $this->data['KegiatanID']  = $KegiatanID;
        $this->data['kegiatan']    = $this->M_Dokumen->kegiatan($KegiatanID);
        $this->data['dokumen']     = $this->M_Dokumen->dokumenUntukKegiatan($KegiatanID, $this->session->get('UserPosition'));
        $this->data['ttdmap']      = $this->M_Dokumen->ttdAktifKegiatan($KegiatanID);
        $this->data['body']        = 'dokumen/paket';

        return view('main', $this->data);
    }

    /**
     * Gabungan cetak: satu halaman berisi beberapa dokumen (page-break antar
     * dokumen), auto window.print() -> user simpan sebagai satu PDF.
     * ?d[]=kode:rangkap ... & ttd=0|1
     */
    public function paketCetak($KegiatanID = 0)
    {
        $KegiatanID = (int) $KegiatanID;
        $withTtd    = $this->_withTtdGet();
        $sel        = (array) $this->request->getGet('d');

        if (empty($sel)) {
            $sel = $this->_defaultSelection($KegiatanID);
        }

        $items      = [];
        foreach ($sel as $s) {
            $item = $this->_resolveSelItem($s, $KegiatanID);
            if ($item === null) {
                continue;
            }
            ['kode' => $kode, 'rangkap' => $rangkap, 'tpl' => $tpl] = $item;
            $items[] = [
                'kode'    => $kode,
                'nama'    => $tpl['nama'],
                'tpl'     => $tpl,
                'rangkap' => $rangkap,
                'd'       => $this->M_Dokumen->load($kode, $KegiatanID, $rangkap),
                'ttd'     => $withTtd ? $this->_ttdMap($KegiatanID, $kode, $rangkap) : [],
            ];
        }
        if (! $items) {
            show_error('Tidak ada dokumen dipilih.', 400);

            return;
        }

        $this->M_Dokumen->printLog($KegiatanID, array_map(function ($x) {
            return $x['kode'] . ':' . $x['rangkap'];
        }, $items), $withTtd, $this->data['UserID']);

        return view('dokumen/paket_cetak', [
            'KegiatanID'    => $KegiatanID,
            'kegiatan'      => $this->M_Dokumen->kegiatan($KegiatanID),
            'items'         => $items,
            'tampilkan_ttd' => $withTtd,
        ]);
    }

    /** JSON: dokumen yang masih perlu ditandatangani posisi user saat ini (untuk gate "Setuju"). */
    public function ttdKurang($KegiatanID = 0)
    {
        $pos    = $this->session->get('UserPosition');
        $on     = $this->M_Dokumen->dokGatePpkOn();
        $kurang = ($on && $pos !== 'SuperAdmin')
            ? $this->M_Dokumen->ttdKurangUntukPosisi((int) $KegiatanID, $pos)
            : [];

        return $this->jsonResponse(['on' => $on, 'posisi' => $pos, 'kurang' => $kurang, 'lengkap' => empty($kurang)]);
    }

    /* ---------------- CAP DINAS (scan, dikelola admin) ---------------- */

    /** Unggah gambar cap dinas (PNG transparan). Hanya pengelola Konfigurasi Aplikasi. */
    public function capUpload()
    {
        $this->Auth->cekMenu('2300', 'u');
        if (empty($_FILES['cap']['name']) || (int) $_FILES['cap']['error'] !== UPLOAD_ERR_OK) {
            return $this->jsonResponse(['ok' => false, 'msg' => 'Tidak ada berkas.']);
        }
        $tmp = $_FILES['cap']['tmp_name'];
        if (! is_uploaded_file($tmp)) {
            return $this->jsonResponse(['ok' => false, 'msg' => 'Berkas tidak sah.']);
        }
        if (filesize($tmp) > 600 * 1024) {
            return $this->jsonResponse(['ok' => false, 'msg' => 'Maksimal 600 KB.']);
        }
        $head = @file_get_contents($tmp, false, null, 0, 8);
        if ($head !== "\x89PNG\r\n\x1a\n") {
            return $this->jsonResponse(['ok' => false, 'msg' => 'Harus file PNG (disarankan transparan).']);
        }
        $info = @getimagesize($tmp);
        if (! $info || $info[0] > 1200 || $info[1] > 1200) {
            return $this->jsonResponse(['ok' => false, 'msg' => 'Dimensi maksimal 1200x1200 px.']);
        }
        $dir = FCPATH . 'assets/uploads/';
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $rel = 'assets/uploads/cap_dinas.png';
        if (! move_uploaded_file($tmp, FCPATH . $rel)) {
            return $this->jsonResponse(['ok' => false, 'msg' => 'Gagal menyimpan berkas.']);
        }
        $this->M_Dokumen->capSet($rel, $this->data['UserID']);

        return $this->jsonResponse(['ok' => true, 'msg' => 'Cap dinas tersimpan.', 'src' => base_url($rel) . '?v=' . time()]);
    }

    /** Aktif/nonaktifkan gate "Setuju PPK". */
    public function gatePpkSet()
    {
        $this->Auth->cekMenu('2300', 'u');
        $on = ((string) $this->request->getPost('on') === '1') ? '1' : '0';
        if ($this->db->table('tb_vrbl')->getWhere(['VrblName' => 'dok_gate_ppk'])->getNumRows() > 0) {
            $this->db->table('tb_vrbl')->where('VrblName', 'dok_gate_ppk')->update(['VrblValue' => $on]);
        } else {
            $this->db->table('tb_vrbl')->insert(['VrblName' => 'dok_gate_ppk', 'VrblValue' => $on]);
        }

        return $this->jsonResponse(['ok' => true, 'on' => $on === '1']);
    }

    public function capHapus()
    {
        $this->Auth->cekMenu('2300', 'u');
        $this->M_Dokumen->capSet('', $this->data['UserID']);
        @unlink(FCPATH . 'assets/uploads/cap_dinas.png');

        return $this->jsonResponse(['ok' => true, 'msg' => 'Cap dinas dihapus.']);
    }

    /* ---------------- TANDA TANGAN (tangkap-langsung) ---------------- */

    /** Halaman kanvas tanda tangan untuk satu slot. */
    public function ttd($kode = '', $KegiatanID = 0)
    {
        $tpl = $this->_templateOr404($kode);
        if ($tpl === null) {
            return;
        }
        $KegiatanID = (int) $KegiatanID;
        if (! $this->_requireLihat($kode, $KegiatanID)) {
            return;
        }
        $rangkap = $this->_rangkapGet();
        $slot    = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $this->request->getGet('slot')));
        if (! in_array($slot, (array) $tpl['slot_ttd'], true)) {
            show_error('Slot tanda tangan tidak dikenal.', 400);

            return;
        }

        $gate                       = $this->_ttdBoleh($KegiatanID, $slot);
        $this->data['kode']         = $kode;
        $this->data['tpl']          = $tpl;
        $this->data['KegiatanID']   = $KegiatanID;
        $this->data['rangkap']      = $rangkap;
        $this->data['slot']         = $slot;
        $this->data['kegiatan']     = $this->M_Dokumen->kegiatan($KegiatanID);
        $this->data['aktif']        = $this->M_Dokumen->ttdAktif($KegiatanID, $kode, $rangkap);
        $this->data['boleh']        = $gate['ok'];
        $this->data['boleh_pesan']  = $gate['msg'];
        $this->data['body']         = 'dokumen/ttd';

        return view('main', $this->data);
    }

    public function ttdSimpan()
    {
        $kode       = $this->request->getPost('kode');
        $KegiatanID = (int) $this->request->getPost('KegiatanID');
        $rangkap    = $this->_rangkapPost();
        $slot       = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $this->request->getPost('slot')));
        $tpl        = $this->M_Dokumen->template($kode);
        if (! $tpl || ! in_array($slot, (array) $tpl['slot_ttd'], true)) {
            return $this->jsonResponse(['ok' => false, 'msg' => 'Slot / dokumen tidak valid.']);
        }
        if (! $this->_akses($kode, $KegiatanID)['lihat']) {
            return $this->jsonResponse(['ok' => false, 'msg' => 'Dokumen ini belum bisa diakses.']);
        }
        $gate = $this->_ttdBoleh($KegiatanID, $slot);
        if (! $gate['ok']) {
            return $this->jsonResponse(['ok' => false, 'msg' => $gate['msg']]);
        }

        $dataUrl = (string) $this->request->getPost('image');
        if (! preg_match('#^data:image/png;base64,#', $dataUrl)) {
            return $this->jsonResponse(['ok' => false, 'msg' => 'Format gambar tidak valid (harus PNG).']);
        }
        $img = base64_decode(substr($dataUrl, strlen('data:image/png;base64,')), true);
        if ($img === false) {
            return $this->jsonResponse(['ok' => false, 'msg' => 'Gambar rusak.']);
        }

        $res = $this->M_Dokumen->ttdSimpan($KegiatanID, $kode, $rangkap, $slot, $img, [
            'user_id' => $this->data['UserID'],
            'nama'    => isset($this->data['UserFullName']) ? $this->data['UserFullName'] : $this->session->get('UserFullName'),
            'role'    => isset($this->data['UserPosition']) ? $this->data['UserPosition'] : $this->session->get('UserPosition'),
            'ip'      => $this->request->getIPAddress(),
        ]);

        return $this->jsonResponse($res);
    }

    /**
     * Boleh menandatangani slot ini?
     *   penerima -> PJ pemilik pengajuan (mengumpulkan ttd pelaksana) / SuperAdmin.
     *   ppk      -> user berposisi PPK yang MEMANG petugas tujuan tahap berjalan
     *               (FlowDestUser baris riwayat terakhir) / SuperAdmin.
     */
    private function _ttdBoleh($KegiatanID, $slot)
    {
        $pos = $this->session->get('UserPosition');
        $uid = (int) $this->session->get('UserID');
        if ($pos === 'SuperAdmin') {
            return ['ok' => true, 'msg' => ''];
        }

        $keg = $this->db->table('tb_kegiatan')->select('KegiatanUserID, KegiatanStatus')
            ->getWhere(['KegiatanID' => (int) $KegiatanID])->getRowArray();
        if (empty($keg)) {
            return ['ok' => false, 'msg' => 'Kegiatan tidak ditemukan.'];
        }

        if ($slot === 'penerima') {
            return ((int) $keg['KegiatanUserID'] === $uid && $pos === 'PJ-Kegiatan')
                ? ['ok' => true, 'msg' => '']
                : ['ok' => false, 'msg' => 'Tanda tangan penerima dikumpulkan oleh PJ pembuat pengajuan.'];
        }
        if ($slot === 'ppk') {
            if ($pos !== 'PPK') {
                return ['ok' => false, 'msg' => 'Hanya PPK yang menandatangani slot ini.'];
            }
            $last = $this->db->table('tb_approval_history')->select('FlowDestUser')
                ->orderBy('HistoryID', 'DESC')
                ->getWhere(['KegiatanID' => (int) $KegiatanID], 1)->getRowArray();
            if (! empty($last) && (int) $last['FlowDestUser'] === $uid) {
                return ['ok' => true, 'msg' => ''];
            }

            return ['ok' => false, 'msg' => 'Pengajuan ini belum / bukan pada antrian Anda sebagai PPK.'];
        }

        return ['ok' => false, 'msg' => 'Slot tidak dikenal.'];
    }

    /* ================================================================
       UPLOAD DOKUMEN EKSTERNAL (LPD, SPPD, SPM, SPP, dll.)
       ================================================================ */

    private function _loadDokUpload()
    {
        if ($this->M_DokUpload === null) {
            $this->M_DokUpload = model(M_DokUpload::class);
        }

        return $this->M_DokUpload;
    }

    /** AJAX: daftar file terupload untuk sebuah kegiatan. */
    public function uploadEksternalList($KegiatanID = 0)
    {
        $list = $this->_loadDokUpload()->listUpload((int) $KegiatanID);
        $base = base_url();
        foreach ($list as &$r) {
            $r['url_ttd_ppk']   = $base . 'dokumen/ttdUpload/' . $r['UploadID'] . '?slot=ppk';
            $r['url_ttd_ppspm'] = $base . 'dokumen/ttdUpload/' . $r['UploadID'] . '?slot=ppspm';
            $r['url_unduh']     = $base . 'dokumen/unduhBerTtd/' . $r['UploadID'];
        }
        unset($r);

        return $this->jsonResponse($list);
    }

    /** POST: upload PDF eksternal. */
    public function uploadEksternal()
    {
        $mDokUpload = $this->_loadDokUpload();
        $pos        = $this->session->get('UserPosition');
        $userID     = $this->session->get('UserID');
        $KegiatanID = (int) $this->request->getPost('KegiatanID');
        $tipe       = strtolower(trim((string) $this->request->getPost('tipe')));

        // Validasi hak upload
        if (! $mDokUpload->bolehUpload($tipe, $pos)) {
            return $this->jsonResponse(['ok' => false, 'msg' => 'Anda tidak berwenang upload tipe dokumen ini.']);
        }

        // Validasi file
        if (empty($_FILES['file']['tmp_name'])) {
            return $this->jsonResponse(['ok' => false, 'msg' => 'Tidak ada file yang diunggah.']);
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $_FILES['file']['tmp_name']);
        finfo_close($finfo);
        if ($mime !== 'application/pdf') {
            return $this->jsonResponse(['ok' => false, 'msg' => 'Hanya file PDF yang diizinkan.']);
        }
        if ($_FILES['file']['size'] > 10 * 1024 * 1024) {
            return $this->jsonResponse(['ok' => false, 'msg' => 'Ukuran file maksimal 10 MB.']);
        }

        $dir = FCPATH . 'assets/uploads/dok/' . $KegiatanID . '/';
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $ext   = 'pdf';
        $safe  = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($_FILES['file']['name'], PATHINFO_FILENAME));
        $fname = $tipe . '_' . $safe . '_' . time() . '.' . $ext;
        if (! move_uploaded_file($_FILES['file']['tmp_name'], $dir . $fname)) {
            return $this->jsonResponse(['ok' => false, 'msg' => 'Gagal memindahkan file.']);
        }
        $rel = 'assets/uploads/dok/' . $KegiatanID . '/' . $fname;
        $res = $mDokUpload->simpanUpload($KegiatanID, $tipe, $rel, $_FILES['file']['name'], $_FILES['file']['size'], $userID);

        return $this->jsonResponse($res);
    }

    /** POST: hapus upload (hanya uploader / SuperAdmin, sebelum ada TTD). */
    public function uploadEksternalHapus()
    {
        $mDokUpload = $this->_loadDokUpload();
        $UploadID   = (int) $this->request->getPost('UploadID');
        $userID     = (int) $this->session->get('UserID');
        $pos        = $this->session->get('UserPosition');
        $row        = $mDokUpload->getUpload($UploadID);
        if (! $row) {
            return $this->jsonResponse(['ok' => false, 'msg' => 'File tidak ditemukan.']);
        }
        if ($pos !== 'SuperAdmin' && (int) $row['UploadedBy'] !== $userID) {
            return $this->jsonResponse(['ok' => false, 'msg' => 'Anda tidak berwenang menghapus file ini.']);
        }
        $res = $mDokUpload->hapusUpload($UploadID, $userID);

        return $this->jsonResponse($res);
    }

    /* ================================================================
       TTD ON-DOCUMENT
       ================================================================ */

    /** GET: halaman preview PDF + pilih area TTD + kanvas TTD. */
    public function ttdUpload($UploadID = 0)
    {
        $mDokUpload = $this->_loadDokUpload();
        $pos        = $this->session->get('UserPosition');
        $row        = $mDokUpload->getUpload((int) $UploadID);
        if (! $row) {
            $this->session->setFlashdata('error', 'Dokumen upload tidak ditemukan atau ID tidak valid.');
            legacy_redirect(base_url('dokumen/pilih'));

            return;
        }

        $slot = $this->request->getGet('slot') ?: 'ppk';
        $slot = preg_replace('/[^a-z0-9_]/', '', strtolower($slot));

        $boleh      = $this->_uploadSlotBoleh($slot, $pos);
        $bolehPesan = $boleh ? '' : 'Anda tidak berwenang menandatangani slot ini.';

        $this->data['body']        = 'dokumen/ttd_upload';
        $this->data['row']         = $row;
        $this->data['slot']        = $slot;
        $this->data['boleh']       = $boleh;
        $this->data['boleh_pesan'] = $bolehPesan;

        return view('main', $this->data);
    }

    /** POST AJAX: simpan posisi + gambar TTD ke PDF yang di-upload. */
    public function ttdUploadSimpan()
    {
        $mDokUpload = $this->_loadDokUpload();
        $pos        = $this->session->get('UserPosition');
        $userID     = $this->session->get('UserID');
        $UploadID   = (int) $this->request->getPost('UploadID');
        $slot       = $this->request->getPost('slot');
        $slotChk    = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $slot));

        // Halaman GET ttdUpload() sudah menampilkan gate _uploadSlotBoleh()
        // untuk UI, tapi endpoint simpan ini tetap harus memvalidasi ulang di
        // server -- kalau tidak, siapa pun yang login bisa POST langsung ke
        // sini dan "menandatangani" slot PPK/PPSPM memakai akunnya sendiri.
        if (! $this->_uploadSlotBoleh($slotChk, $pos)) {
            return $this->jsonResponse(['ok' => false, 'msg' => 'Anda tidak berwenang menandatangani slot ini.']);
        }

        // Decode image base64
        $imgB64 = $this->request->getPost('image');
        if (strpos($imgB64, 'data:image/png;base64,') === 0) {
            $imgB64 = substr($imgB64, strlen('data:image/png;base64,'));
        }
        $img = base64_decode($imgB64);
        if ($img === false) {
            return $this->jsonResponse(['ok' => false, 'msg' => 'Gambar tidak valid.']);
        }

        $pos_arr = [
            'page' => (int) $this->request->getPost('page') ?: 1,
            'x'    => (float) $this->request->getPost('x'),
            'y'    => (float) $this->request->getPost('y'),
            'w'    => (float) $this->request->getPost('w') ?: 0.2,
            'h'    => (float) $this->request->getPost('h') ?: 0.06,
        ];
        $signer = [
            'user_id' => $userID,
            'nama'    => $this->session->get('UserFullName'),
            'role'    => $pos,
            'ip'      => $this->request->getIPAddress(),
        ];

        $res = $mDokUpload->simpanTtd($UploadID, $slot, $pos_arr, $img, $signer);

        return $this->jsonResponse($res);
    }

    /** GET: unduh PDF yang sudah di-embed TTD (FPDI overlay). */
    public function unduhBerTtd($UploadID = 0)
    {
        $mDokUpload = $this->_loadDokUpload();
        $res        = $mDokUpload->buatPdfBerTtd((int) $UploadID);
        if (! $res['ok']) {
            show_error($res['msg'], 500);

            return;
        }
        $name = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $res['name']);

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $name . '"')
            ->setBody($res['pdf']);
    }
}
