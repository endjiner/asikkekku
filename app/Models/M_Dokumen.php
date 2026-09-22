<?php

namespace App\Models;

/**
 * Dokumen form_inapp (set Perjalanan Dinas).
 *
 * Sistem hanya menyimpan NILAI FIELD (tb_dokumen.PayloadJson), tidak menyimpan
 * file. Formulir cetak dirender saat dibutuhkan dari layout + nilai field
 * (app/Views/dokumen/tpl/*). Nama-nama pada bagian tanda tangan
 * TIDAK di-generate ke area ttd (area ttd dibiarkan kosong untuk tanda tangan
 * basah) -- yang di-generate hanya teks nama di bawah garis ttd.
 *
 * Sumber field:
 *   manual  -> input petugas
 *   auto:<k>-> diambil dari data kegiatan / master (read-only di form)
 *   const   -> nilai tetap kantor (read-only)
 */
class M_Dokumen extends BaseModel
{
    public const TEMPLATE_VERSI = 'v1';

    /**
     * Helper 'dokumen' (tgl_ind() dkk.) sebelumnya cuma dimuat oleh controller
     * Dokumen. kartuKendaliAutoFill() dipanggil juga dari M_Manajemen_approval
     * (tiap kirim/setujui/kembalikan pengajuan) yang TIDAK memuat helper itu,
     * jadi tgl_ind() di autofill() selalu "Call to undefined function" di sana
     * -- request-nya crash sebelum sempat echo JSON, klien lihat "Gagal
     * memproses persetujuan" padahal datanya sudah tersimpan. Muat sendiri di
     * sini supaya M_Dokumen tidak bergantung controller mana yang memanggilnya.
     */
    public function __construct()
    {
        parent::__construct();
        helper('dokumen');
    }

    /* ---- Konstanta kantor: default di sini, bisa ditimpa lewat tb_vrbl
       (baris VrblName = 'dok_satker_nama', 'dok_kppn', 'dok_dipa_no', dst). ---- */
    private $konst_default = [
        'satker_nama'       => 'Balai POM di Pangkal Pinang',
        'satker_nama_resmi' => 'Balai Besar POM di Pangkal Pinang',
        'satker_kode'       => '063.672842',
        'kppn'              => 'KPPN Pangkal Pinang (015)',
        'dipa_no'           => 'DIPA-063.01.2.672842/2026',
        'dipa_tgl'          => '2025-12-01',
        'tahun_anggaran'    => '2026',
        'kota'              => 'Pangkal Pinang',
        'ppk_terima_dari'   => 'Pejabat Pembuat Komitmen Balai Besar POM di Pangkal Pinang',
        'bendahara_nama'    => 'Desy Anindyasari, A.Md.',
        'bendahara_nip'     => '198512022008122002',
    ];
    private $konst = null;

    /**
     * Peta prefix kode Output -> PPK penandatangan. Default di sini; bisa
     * ditimpa lewat tb_vrbl baris VrblName='dok_ppk_map' berisi JSON:
     *   {"3165.QIA":{"nama":"...","nip":"..."}, "_default":{"nama":"","nip":""}}
     * (nanti dipindah ke tabel tb_output_petugas yang diedit admin.)
     */
    private $ppk_by_output_default = [
        // PPK I Balai POM di Pangkal Pinang: Netty Desi Margaretta Manullang, S.E. (19931203 202012 2 001)
        '3165.AEA'          => ['nama' => 'Netty Desi Margaretta Manullang, S.E.', 'nip' => '19931203 202012 2 001', 'label' => 'PPK I'],
        '3165.BAH'          => ['nama' => 'Netty Desi Margaretta Manullang, S.E.', 'nip' => '19931203 202012 2 001', 'label' => 'PPK I'],
        '3165.BDB'          => ['nama' => 'Netty Desi Margaretta Manullang, S.E.', 'nip' => '19931203 202012 2 001', 'label' => 'PPK I'],
        '3165.BDC'          => ['nama' => 'Netty Desi Margaretta Manullang, S.E.', 'nip' => '19931203 202012 2 001', 'label' => 'PPK I'],
        '3165.BIA'          => ['nama' => 'Netty Desi Margaretta Manullang, S.E.', 'nip' => '19931203 202012 2 001', 'label' => 'PPK I'],
        '3165.BMB'          => ['nama' => 'Netty Desi Margaretta Manullang, S.E.', 'nip' => '19931203 202012 2 001', 'label' => 'PPK I'],
        '3165.PDD'          => ['nama' => 'Netty Desi Margaretta Manullang, S.E.', 'nip' => '19931203 202012 2 001', 'label' => 'PPK I'],
        '3165.QCD'          => ['nama' => 'Netty Desi Margaretta Manullang, S.E.', 'nip' => '19931203 202012 2 001', 'label' => 'PPK I'],
        '3165.QDC'          => ['nama' => 'Netty Desi Margaretta Manullang, S.E.', 'nip' => '19931203 202012 2 001', 'label' => 'PPK I'],
        '3165.QDG'          => ['nama' => 'Netty Desi Margaretta Manullang, S.E.', 'nip' => '19931203 202012 2 001', 'label' => 'PPK I'],
        '3165.QIA'          => ['nama' => 'Netty Desi Margaretta Manullang, S.E.', 'nip' => '19931203 202012 2 001', 'label' => 'PPK I'],
        '3165.QIC'          => ['nama' => 'Netty Desi Margaretta Manullang, S.E.', 'nip' => '19931203 202012 2 001', 'label' => 'PPK I'],
        '6384.EBA.994.001'  => ['nama' => 'Netty Desi Margaretta Manullang, S.E.', 'nip' => '19931203 202012 2 001', 'label' => 'PPK I'],

        // PPK II Balai POM di Pangkal Pinang: Priya Tri Nanda, S.Si. (19950322 201903 1 004)
        '3165.BKB'          => ['nama' => 'Priya Tri Nanda, S.Si.', 'nip' => '19950322 201903 1 004', 'label' => 'PPK II'],
        '3165.RAB'          => ['nama' => 'Priya Tri Nanda, S.Si.', 'nip' => '19950322 201903 1 004', 'label' => 'PPK II'],
        '6384.EBA.994.002'  => ['nama' => 'Priya Tri Nanda, S.Si.', 'nip' => '19950322 201903 1 004', 'label' => 'PPK II'],
        '6384.EBA.956'      => ['nama' => 'Priya Tri Nanda, S.Si.', 'nip' => '19950322 201903 1 004', 'label' => 'PPK II'],

        '_default' => ['nama' => 'Netty Desi Margaretta Manullang, S.E.', 'nip' => '19931203 202012 2 001', 'label' => 'PPK I'],
    ];
    private $ppk_by_output = null;

    /** Muat konstanta (default + override tb_vrbl 'dok_*'), sekali per request. */
    private function konst()
    {
        if ($this->konst !== null) {
            return $this->konst;
        }
        $this->konst = $this->konst_default;
        if ($this->db->tableExists('tb_vrbl')) {
            $rows = $this->db->table('tb_vrbl')->like('VrblName', 'dok_', 'after')->get()->getResultArray();
            foreach ($rows as $r) {
                $k = substr($r['VrblName'], 4); // buang prefix 'dok_'
                if ($k === 'ppk_map') {
                    continue;
                }
                if (array_key_exists($k, $this->konst) && trim((string) $r['VrblValue']) !== '') {
                    $this->konst[$k] = $r['VrblValue'];
                }
            }
        }

        return $this->konst;
    }

    private function ppkMap()
    {
        if ($this->ppk_by_output !== null) {
            return $this->ppk_by_output;
        }
        $this->ppk_by_output = $this->ppk_by_output_default;
        if ($this->db->tableExists('tb_vrbl')) {
            $row = $this->db->table('tb_vrbl')->getWhere(['VrblName' => 'dok_ppk_map'])->getRowArray();
            if (! empty($row['VrblValue'])) {
                $j = json_decode($row['VrblValue'], true);
                if (is_array($j) && ! empty($j)) {
                    $this->ppk_by_output = $j;
                }
            }
        }

        return $this->ppk_by_output;
    }

    /* ======================================================================
       REGISTRY TEMPLATE
       ====================================================================== */
    public function templates()
    {
        $t = [

            'kwitansi' => [
                'nama'    => 'Kwitansi',
                'rangkap' => 'per_penerima',
                'fields'  => [
                    ['key' => 'nomor_bukti', 'label' => 'Nomor Bukti', 'tipe' => 'text', 'sumber' => 'manual'],
                    ['key' => 'kode_mak', 'label' => 'Kode MAK', 'tipe' => 'text', 'sumber' => 'auto:kode_output'],
                    ['key' => 'terima_dari', 'label' => 'Sudah terima dari', 'tipe' => 'text', 'sumber' => 'const:ppk_terima_dari'],
                    ['key' => 'jumlah', 'label' => 'Uang sebesar (Rp)', 'tipe' => 'uang', 'sumber' => 'manual'],
                    ['key' => 'guna', 'label' => 'Guna pembayaran ongkos/biaya perjalanan', 'tipe' => 'textarea', 'sumber' => 'auto:judul'],
                    ['key' => 'tgl_surat_tugas', 'label' => 'Tgl Surat Perintah/Tugas', 'tipe' => 'date', 'sumber' => 'manual'],
                    ['key' => 'no_surat_tugas', 'label' => 'No. Surat Perintah/Tugas', 'tipe' => 'text', 'sumber' => 'auto:no_surat'],
                    ['key' => 'untuk_perjalanan_dari', 'label' => 'Untuk perjalanan dinas dari', 'tipe' => 'text', 'sumber' => 'auto:asal_tujuan'],
                    ['key' => 'rincian', 'label' => 'Rincian biaya perjalanan dinas', 'tipe' => 'rows', 'sumber' => 'manual', 'kolom' => [
                        ['key' => 'uraian', 'label' => 'Uraian', 'tipe' => 'text'],
                        ['key' => 'tarif', 'label' => 'Tarif (Rp)', 'tipe' => 'uang'],
                        ['key' => 'per', 'label' => 'per', 'tipe' => 'text'],
                        ['key' => 'jumlah', 'label' => 'Jumlah (Rp)', 'tipe' => 'uang'],
                        ['key' => 'ket', 'label' => 'Keterangan', 'tipe' => 'text'],
                    ]],
                    ['key' => 'penerima_nama', 'label' => 'Nama penerima', 'tipe' => 'text', 'sumber' => 'auto:penerima_nama'],
                    ['key' => 'penerima_nip', 'label' => 'NIP penerima', 'tipe' => 'text', 'sumber' => 'auto:penerima_nip'],
                    ['key' => 'ppk_nama', 'label' => 'Nama PPK', 'tipe' => 'text', 'sumber' => 'auto:ppk_nama'],
                    ['key' => 'ppk_nip', 'label' => 'NIP PPK', 'tipe' => 'text', 'sumber' => 'auto:ppk_nip'],
                    ['key' => 'bendahara_nama', 'label' => 'Nama Bendahara', 'tipe' => 'text', 'sumber' => 'const:bendahara_nama'],
                    ['key' => 'bendahara_nip', 'label' => 'NIP Bendahara', 'tipe' => 'text', 'sumber' => 'const:bendahara_nip'],
                    ['key' => 'tempat', 'label' => 'Tempat', 'tipe' => 'text', 'sumber' => 'const:kota'],
                    ['key' => 'tanggal', 'label' => 'Tanggal', 'tipe' => 'date', 'sumber' => 'manual'],
                ],
            ],

            'nominatif' => [
                'nama'    => 'Daftar Nominatif Biaya Perjalanan Dinas',
                'rangkap' => 'per_kegiatan',
                'fields'  => [
                    ['key' => 'kode_kegiatan', 'label' => 'Kode Kegiatan/Anggaran', 'tipe' => 'text', 'sumber' => 'auto:kode_output'],
                    ['key' => 'tahun_anggaran', 'label' => 'Tahun Anggaran', 'tipe' => 'text', 'sumber' => 'const:tahun_anggaran'],
                    ['key' => 'baris', 'label' => 'Daftar petugas', 'tipe' => 'rows', 'sumber' => 'manual', 'kolom' => [
                        ['key' => 'nama', 'label' => 'Nama', 'tipe' => 'text'],
                        ['key' => 'nip', 'label' => 'NIP', 'tipe' => 'text'],
                        ['key' => 'gol', 'label' => 'Gol', 'tipe' => 'text'],
                        ['key' => 'tujuan', 'label' => 'Tujuan', 'tipe' => 'text'],
                        ['key' => 'tgl_brgkt', 'label' => 'Tgl Berangkat', 'tipe' => 'text'],
                        ['key' => 'lama', 'label' => 'Lama (hari)', 'tipe' => 'number'],
                        ['key' => 'jumlah', 'label' => 'Jumlah (Rp)', 'tipe' => 'uang'],
                        ['key' => 'ket', 'label' => 'Ket', 'tipe' => 'text'],
                    ]],
                    ['key' => 'ppk_nama', 'label' => 'Nama PPK', 'tipe' => 'text', 'sumber' => 'auto:ppk_nama'],
                    ['key' => 'ppk_nip', 'label' => 'NIP PPK', 'tipe' => 'text', 'sumber' => 'auto:ppk_nip'],
                    ['key' => 'tempat', 'label' => 'Tempat', 'tipe' => 'text', 'sumber' => 'const:kota'],
                ],
            ],

            'riil' => [
                'nama'    => 'Daftar Pengeluaran Riil',
                'rangkap' => 'per_penerima',
                'fields'  => [
                    ['key' => 'nama', 'label' => 'Nama', 'tipe' => 'text', 'sumber' => 'auto:penerima_nama'],
                    ['key' => 'nip', 'label' => 'NIP', 'tipe' => 'text', 'sumber' => 'auto:penerima_nip'],
                    ['key' => 'jabatan', 'label' => 'Jabatan', 'tipe' => 'text', 'sumber' => 'auto:penerima_jabatan'],
                    ['key' => 'spd_tgl', 'label' => 'Tgl SPD/Surat Tugas', 'tipe' => 'date', 'sumber' => 'manual'],
                    ['key' => 'spd_no', 'label' => 'No. SPD/Surat Tugas', 'tipe' => 'text', 'sumber' => 'auto:no_surat'],
                    ['key' => 'rincian', 'label' => 'Rincian pengeluaran', 'tipe' => 'rows', 'sumber' => 'manual', 'kolom' => [
                        ['key' => 'uraian', 'label' => 'Uraian', 'tipe' => 'text'],
                        ['key' => 'tarif', 'label' => 'Tarif (Rp)', 'tipe' => 'uang'],
                        ['key' => 'satuan', 'label' => 'Satuan', 'tipe' => 'text'],
                        ['key' => 'jumlah', 'label' => 'Jumlah (Rp)', 'tipe' => 'uang'],
                        ['key' => 'ket', 'label' => 'Keterangan', 'tipe' => 'text'],
                    ]],
                    ['key' => 'ppk_nama', 'label' => 'Nama PPK', 'tipe' => 'text', 'sumber' => 'auto:ppk_nama'],
                    ['key' => 'ppk_nip', 'label' => 'NIP PPK', 'tipe' => 'text', 'sumber' => 'auto:ppk_nip'],
                    ['key' => 'tempat', 'label' => 'Tempat', 'tipe' => 'text', 'sumber' => 'const:kota'],
                    ['key' => 'bulan_tahun', 'label' => 'Bulan/Tahun', 'tipe' => 'text', 'sumber' => 'manual'],
                ],
            ],

            'spd' => [
                'nama'    => 'Surat Perjalanan Dinas (SPD)',
                'rangkap' => 'per_penerima',
                'fields'  => [
                    ['key' => 'lembar_ke', 'label' => 'Lembar Ke', 'tipe' => 'text', 'sumber' => 'manual'],
                    ['key' => 'kode', 'label' => 'Kode', 'tipe' => 'text', 'sumber' => 'auto:kode_output'],
                    ['key' => 'nomor', 'label' => 'Nomor', 'tipe' => 'text', 'sumber' => 'auto:no_surat'],
                    ['key' => 'ppk_nama', 'label' => 'Nama PPK', 'tipe' => 'text', 'sumber' => 'auto:ppk_nama'],
                    ['key' => 'ppk_nip', 'label' => 'NIP PPK', 'tipe' => 'text', 'sumber' => 'auto:ppk_nip'],
                    ['key' => 'pegawai_nama', 'label' => 'Nama pegawai', 'tipe' => 'text', 'sumber' => 'auto:penerima_nama'],
                    ['key' => 'pegawai_nip', 'label' => 'NIP pegawai', 'tipe' => 'text', 'sumber' => 'auto:penerima_nip'],
                    ['key' => 'pangkat_gol', 'label' => 'Pangkat / Golongan', 'tipe' => 'text', 'sumber' => 'auto:penerima_gol'],
                    ['key' => 'jabatan', 'label' => 'Jabatan / Instansi', 'tipe' => 'text', 'sumber' => 'auto:penerima_jabatan'],
                    ['key' => 'maksud', 'label' => 'Maksud perjalanan dinas', 'tipe' => 'textarea', 'sumber' => 'auto:judul'],
                    ['key' => 'alat_angkut', 'label' => 'Alat angkutan', 'tipe' => 'text', 'sumber' => 'manual'],
                    ['key' => 'tempat_berangkat', 'label' => 'Tempat berangkat', 'tipe' => 'text', 'sumber' => 'const:kota'],
                    ['key' => 'tempat_tujuan', 'label' => 'Tempat tujuan', 'tipe' => 'text', 'sumber' => 'manual'],
                    ['key' => 'lama_hari', 'label' => 'Lama perjalanan (hari)', 'tipe' => 'number', 'sumber' => 'manual'],
                    ['key' => 'tgl_berangkat', 'label' => 'Tgl berangkat', 'tipe' => 'date', 'sumber' => 'manual'],
                    ['key' => 'tgl_kembali', 'label' => 'Tgl kembali', 'tipe' => 'date', 'sumber' => 'manual'],
                    ['key' => 'pengikut', 'label' => 'Pengikut', 'tipe' => 'rows', 'sumber' => 'manual', 'kolom' => [
                        ['key' => 'nama', 'label' => 'Nama', 'tipe' => 'text'],
                        ['key' => 'tgl_lahir', 'label' => 'Tanggal lahir', 'tipe' => 'text'],
                        ['key' => 'ket', 'label' => 'Keterangan', 'tipe' => 'text'],
                    ]],
                    ['key' => 'pembebanan_instansi', 'label' => 'Pembebanan - Instansi', 'tipe' => 'text', 'sumber' => 'auto:pembebanan'],
                    ['key' => 'dipa_no', 'label' => 'No. DIPA', 'tipe' => 'text', 'sumber' => 'const:dipa_no'],
                    ['key' => 'kode_anggaran', 'label' => 'Kode anggaran', 'tipe' => 'text', 'sumber' => 'auto:kode_output'],
                    ['key' => 'no_surat_tugas', 'label' => 'No. Surat Tugas', 'tipe' => 'text', 'sumber' => 'auto:no_surat'],
                    ['key' => 'tgl_surat_tugas', 'label' => 'Tgl Surat Tugas', 'tipe' => 'date', 'sumber' => 'manual'],
                    ['key' => 'dikeluarkan_tempat', 'label' => 'Dikeluarkan di', 'tipe' => 'text', 'sumber' => 'const:kota'],
                    ['key' => 'dikeluarkan_tgl', 'label' => 'Tanggal dikeluarkan', 'tipe' => 'date', 'sumber' => 'manual'],
                ],
            ],

            'sptjb' => [
                'nama'    => 'Surat Pernyataan Tanggung Jawab Belanja (SPTJB)',
                'rangkap' => 'per_kegiatan',
                'catatan' => 'Konfirmasi dulu: apakah SPTJB di satker ini keluaran SAKTI? Jika ya, dokumen ini tidak perlu form_inapp.',
                'fields'  => [
                    ['key' => 'nomor', 'label' => 'Nomor', 'tipe' => 'text', 'sumber' => 'manual'],
                    ['key' => 'kode_satker', 'label' => 'Kode Satker/Program', 'tipe' => 'text', 'sumber' => 'const:satker_kode'],
                    ['key' => 'nama_satker', 'label' => 'Nama Satuan Kerja', 'tipe' => 'text', 'sumber' => 'const:satker_nama_resmi'],
                    ['key' => 'dipa_tgl_no', 'label' => 'Tanggal & No DIPA', 'tipe' => 'text', 'sumber' => 'auto:dipa_tgl_no'],
                    ['key' => 'klasifikasi_anggaran', 'label' => 'Klasifikasi Anggaran', 'tipe' => 'text', 'sumber' => 'manual'],
                    ['key' => 'baris', 'label' => 'Rincian', 'tipe' => 'rows', 'sumber' => 'manual', 'kolom' => [
                        ['key' => 'akun', 'label' => 'Akun', 'tipe' => 'text'],
                        ['key' => 'penerima', 'label' => 'Penerima', 'tipe' => 'text'],
                        ['key' => 'uraian', 'label' => 'Uraian', 'tipe' => 'text'],
                        ['key' => 'jumlah', 'label' => 'Jumlah (Rp)', 'tipe' => 'uang'],
                        ['key' => 'ppn', 'label' => 'PPN', 'tipe' => 'uang'],
                        ['key' => 'pph', 'label' => 'PPh', 'tipe' => 'uang'],
                    ]],
                    ['key' => 'ppk_nama', 'label' => 'Nama PPK', 'tipe' => 'text', 'sumber' => 'auto:ppk_nama'],
                    ['key' => 'ppk_nip', 'label' => 'NIP PPK', 'tipe' => 'text', 'sumber' => 'auto:ppk_nip'],
                    ['key' => 'tempat', 'label' => 'Tempat', 'tipe' => 'text', 'sumber' => 'const:kota'],
                    ['key' => 'tanggal', 'label' => 'Tanggal', 'tipe' => 'date', 'sumber' => 'manual'],
                ],
            ],

            // Check List Kelengkapan Dokumen & Kartu Kendali (1 Halaman Terpadu)
            // Sumber: Screenshot Real User "KARTU KENDALI PERJADIN/HONORARIUM TA 2026"
            'lembar_periksa' => [
                'nama'    => 'Kartu Kendali & Check List Kelengkapan',
                'rangkap' => 'per_kegiatan',
                'fields'  => [
                    ['key' => 'no_surat_tugas_tgl', 'label' => 'Tgl. Surat / No. Surat Tugas / SK', 'tipe' => 'text',     'sumber' => 'auto:st_tgl_no'],
                    ['key' => 'no_sptb_spm',         'label' => 'NO. SPTB/ SPM',                     'tipe' => 'text',     'sumber' => 'auto:no_sptb_spm'],
                    ['key' => 'judul_kegiatan',      'label' => 'Judul Kegiatan',                    'tipe' => 'textarea', 'sumber' => 'auto:judul'],
                    ['key' => 'petugas_perjadin',    'label' => 'Nama Petugas Perjadin',             'tipe' => 'textarea', 'sumber' => 'auto:pelaksana_list'],
                ],
                'checklist' => [
                    // Seksi I: Kelengkapan Berkas (13 item, kolom ADA/TIDAK/KETERANGAN)
                    'kelengkapan' => [
                        'judul' => 'KELENGKAPAN BERKAS',
                        'kolom' => ['ADA', 'TIDAK', 'KETERANGAN'],
                        'item'  => [
                            'Surat Tugas (ST) / Surat Keputusan (SK)',
                            'Laporan Kegiatan (Jika Kegiatan)',
                            'Daftar Absensi (Jika Kegiatan)',
                            'Surat Perintah Perjalanan Dinas (SPD)',
                            'Tiket Transportasi',
                            'Boarding Pass',
                            'Kuitansi Penginapan',
                            'Kwitansi Taksi / BBM / Bukti Sewa Kendaraan',
                            'Laporan Perjalanan Dinas (LPD)',
                            'Nominatif Biaya Perjalanan Dinas',
                            'Tanda Terima (Kwitansi)',
                            'Daftar Pengeluaran Riil',
                            'Surat Setoran Pajak (SSP) (Jika Ada)',
                        ],
                    ],
                    // Seksi II: Verifikasi Kesesuaian Isi (3 item, kolom SESUAI/TIDAK/KETERANGAN)
                    'verifikasi' => [
                        'judul' => 'VERIFIKASI KESESUAIAN ISI',
                        'kolom' => ['SESUAI', 'TIDAK', 'KETERANGAN'],
                        'item'  => [
                            'Daftar Riil',
                            'Kwitansi',
                            'Daftar Nominatif',
                        ],
                    ],
                ],
                'alur_steps' => [
                    'Petugas pelaksana kegiatan menyerahkan dokumen ke staf PPK',
                    'Staf PPK menyerahkan dokumen yang telah di verifikasi ke Pembuat SPP/SPM',
                    'Petugas SPP/SPM menyerahkan dokumen SPP/SPM serta data dukungnya ke Verifikator',
                    'Verifikator menyerahkan berkas SPP/SPM dan data dukung yang telah di verifikasi untuk diperiksa dan di setujui PPK',
                    'PPK memberikan berkas yang telah diperiksa dan Disetujui (TTE) ke PPSPM',
                    'Petugas SPM membuat SPM dan menyerahkan SPM dan kelengkapan data dukung pencairan ke PPSPM untuk diperiksa dan Disetujui (TTE)',
                    'PPSPM memeriksa dan Menyetujui (TTE) SPM serta mengembalikan berkas ke petugas SPM',
                ],
            ],

            // Alias / template kartu_kendali terpadu 1 halaman
            'kartu_kendali' => [
                'nama'    => 'Kartu Kendali TU',
                'rangkap' => 'per_kegiatan',
                'fields'  => [
                    ['key' => 'no_surat_tugas_tgl', 'label' => 'Tgl. Surat / No. Surat Tugas / SK', 'tipe' => 'text',     'sumber' => 'auto:st_tgl_no'],
                    ['key' => 'no_sptb_spm',         'label' => 'NO. SPTB/ SPM',                     'tipe' => 'text',     'sumber' => 'auto:no_sptb_spm'],
                    ['key' => 'judul_kegiatan',      'label' => 'Judul Kegiatan',                    'tipe' => 'textarea', 'sumber' => 'auto:judul'],
                    ['key' => 'petugas_perjadin',    'label' => 'Nama Petugas Perjadin',             'tipe' => 'textarea', 'sumber' => 'auto:pelaksana_list'],
                ],
                'checklist' => [
                    'kelengkapan' => [
                        'judul' => 'KELENGKAPAN BERKAS',
                        'kolom' => ['ADA', 'TIDAK', 'KETERANGAN'],
                        'item'  => [
                            'Surat Tugas (ST) / Surat Keputusan (SK)',
                            'Laporan Kegiatan (Jika Kegiatan)',
                            'Daftar Absensi (Jika Kegiatan)',
                            'Surat Perintah Perjalanan Dinas (SPD)',
                            'Tiket Transportasi',
                            'Boarding Pass',
                            'Kuitansi Penginapan',
                            'Kwitansi Taksi / BBM / Bukti Sewa Kendaraan',
                            'Laporan Perjalanan Dinas (LPD)',
                            'Nominatif Biaya Perjalanan Dinas',
                            'Tanda Terima (Kwitansi)',
                            'Daftar Pengeluaran Riil',
                            'Surat Setoran Pajak (SSP) (Jika Ada)',
                        ],
                    ],
                    'verifikasi' => [
                        'judul' => 'VERIFIKASI KESESUAIAN ISI',
                        'kolom' => ['SESUAI', 'TIDAK', 'KETERANGAN'],
                        'item'  => [
                            'Daftar Riil',
                            'Kwitansi',
                            'Daftar Nominatif',
                        ],
                    ],
                ],
                'alur_steps' => [
                    'Petugas pelaksana kegiatan menyerahkan dokumen ke staf PPK',
                    'Staf PPK menyerahkan dokumen yang telah di verifikasi ke Pembuat SPP/SPM',
                    'Petugas SPP/SPM menyerahkan dokumen SPP/SPM serta data dukungnya ke Verifikator',
                    'Verifikator menyerahkan berkas SPP/SPM dan data dukung yang telah di verifikasi untuk diperiksa dan di setujui PPK',
                    'PPK memberikan berkas yang telah diperiksa dan Disetujui (TTE) ke PPSPM',
                    'Petugas SPM membuat SPM dan menyerahkan SPM dan kelengkapan data dukung pencairan ke PPSPM untuk diperiksa dan Disetujui (TTE)',
                    'PPSPM memeriksa dan Menyetujui (TTE) SPM serta mengembalikan berkas ke petugas SPM',
                ],
            ],
        ];

        // --- Metadata alur: metode, tahap pembuat, slot ttd, cap, urutan cetak ---
        $meta = [
            // kode            metode           dibuat_oleh     slot_ttd                  cap    urut
            // Sesuai pembagian nyata (lihat GDRIVE/PJ/): PJ-Kegiatan menyiapkan
            // SEMUA dokumen awal (SPD, Kwitansi, Nominatif, Daftar Riil, SPTJB)
            // pada tahap pertama. Verifikator baru mengisi Lembar Periksa saat
            // gilirannya (tahap VRF2). Tahap PPK-Staff/SPM/PPK/PPSPM meneruskan
            // alur & menandatangani, tidak mengisi ulang dokumen form_inapp ini.
            'kartu_kendali'  => ['kartu_kendali', 'auto',          [],                  false,  1],
            'kwitansi'       => ['form_inapp',    'PJ-Kegiatan',   ['ppk', 'penerima'], true,  10],
            'spd'            => ['form_inapp',    'PJ-Kegiatan',   ['ppk'],             false, 20],
            'nominatif'      => ['form_inapp',    'PJ-Kegiatan',   ['ppk'],             true,  30],
            'riil'           => ['form_inapp',    'PJ-Kegiatan',   ['ppk', 'penerima'], true,  40],
            'sptjb'          => ['form_inapp',    'PJ-Kegiatan',   ['ppk'],             true,  50],
            'lembar_periksa' => ['checklist',     'Verifikator',   [],                  false, 60],
        ];
        foreach ($t as $kode => &$row) {
            $m = isset($meta[$kode]) ? $meta[$kode] : ['form_inapp', 'PPK-Staff', ['ppk'], false, 99];
            $row['metode']      = $m[0];
            $row['dibuat_oleh'] = $m[1];
            $row['slot_ttd']    = $m[2];
            $row['butuh_cap']   = $m[3];
            $row['urut']        = $m[4];
            $row['jenis']       = [1]; // perjadin; jenis lain menyusul
        }
        unset($row);

        return $t;
    }

    /** Daftar template yang berlaku untuk sebuah jenis pengajuan (default perjadin=1). */
    public function templatesForJenis($jenisID = 1)
    {
        $jenisID = (int) $jenisID ?: 1;
        $out     = [];
        foreach ($this->templates() as $kode => $tpl) {
            if (in_array($jenisID, (array) $tpl['jenis'], true)) {
                $out[$kode] = $tpl;
            }
        }
        uasort($out, function ($a, $b) { return $a['urut'] - $b['urut']; });

        return $out;
    }

    /**
     * FlowOrder tahap yang sedang aktif/terakhir tercapai untuk sebuah kegiatan.
     * Dipakai membatasi: dokumen tahap berikutnya yang belum diisi tidak boleh
     * dilihat/diakses sebelum gilirannya tiba.
     *   - Draft ('editable' / status kosong) -> tahap 1 (PJ baru menyusun).
     *   - OnProgress / Perlu Revisi           -> tahap aktif sekarang
     *     (M_Manajemen_approval::GetLastStatus menangani maju/mundur/revisi).
     *   - Selesai / Dibatalkan / lainnya      -> dianggap semua tahap sudah
     *     lewat, supaya seluruh dokumen tetap bisa dilihat (arsip).
     * null kalau kegiatan tidak ditemukan.
     */
    public function stageAktif($KegiatanID)
    {
        $keg = $this->kegiatan((int) $KegiatanID);
        if (empty($keg)) {
            return null;
        }
        $jenisID = (! empty($keg['KegiatanJenisID'])) ? (int) $keg['KegiatanJenisID'] : 1;
        $status  = isset($keg['KegiatanStatus']) ? $keg['KegiatanStatus'] : '';

        if ($status === '' || $status === 'editable') {
            return 1;
        }
        if ($status === 'Approval OnProgress' || $status === 'Perlu Revisi') {
            $rows = model(M_Manajemen_approval::class)->GetLastStatus((int) $KegiatanID);

            return ! empty($rows[0]['FlowOrder']) ? (int) $rows[0]['FlowOrder'] : 1;
        }
        $max = $this->db->table('tb_approval_flow')->selectMax('FlowOrder', 'mx')->getWhere(['JenisID' => $jenisID])->getRowArray();

        return (! empty($max['mx']) ? (int) $max['mx'] : 6) + 1;
    }

    /** FlowOrder pertama (terkecil, tahap aktif bukan dorman) milik sebuah posisi. */
    private function _flowOrderOfRole($role, $jenisID = 1)
    {
        if ($role === '' || $role === 'auto') {
            return 0;
        }
        $r = $this->db->table('tb_approval_flow')->selectMin('FlowOrder', 'mn')
            ->getWhere(['FlowPosition' => $role, 'JenisID' => (int) $jenisID, 'FlowOrder >' => 0])->getRowArray();

        return ! empty($r['mn']) ? (int) $r['mn'] : 0;
    }

    /**
     * Hak akses viewer atas satu dokumen sebuah kegiatan.
     *   lihat -> boleh melihat/mencetak/mengunduh (dokumen sudah "waktunya").
     *   isi   -> boleh mengisi/menyimpan SEKARANG (persis di tahapnya & perannya).
     * SuperAdmin selalu boleh lihat+isi. Dokumen 'auto' (Kartu Kendali) selalu
     * boleh dilihat, tak ada yang mengisi manual.
     */
    public function aksesDokumen($kode, $KegiatanID, $viewerPosition)
    {
        $tpl = $this->template($kode);
        if (! $tpl) {
            return ['lihat' => false, 'isi' => false];
        }

        $dibuatOleh = $tpl['dibuat_oleh'];
        if ($dibuatOleh === 'auto') {
            return ['lihat' => true, 'isi' => false];
        }
        if ($viewerPosition === 'SuperAdmin') {
            return ['lihat' => true, 'isi' => true];
        }

        $keg = $this->kegiatan((int) $KegiatanID);
        if (empty($keg)) {
            return ['lihat' => false, 'isi' => false];
        }
        $jenisID = (! empty($keg['KegiatanJenisID'])) ? (int) $keg['KegiatanJenisID'] : 1;
        $status  = isset($keg['KegiatanStatus']) ? $keg['KegiatanStatus'] : '';

        $stageAktif   = $this->stageAktif($KegiatanID);
        $stageDokumen = $this->_flowOrderOfRole($dibuatOleh, $jenisID);

        if ($stageAktif === null || $stageDokumen === 0 || $stageDokumen > $stageAktif) {
            return ['lihat' => false, 'isi' => false];
        }

        // Jendela isi: dari tahap pemilik dokumen ini sampai SEBELUM tahap
        // pemilik-dokumen-LAIN berikutnya mulai -- bukan cuma "persis satu
        // tahap". Perlu begini karena tahap PJK selalu langsung FlowResult=1
        // otomatis begitu kegiatan diajukan (lihat M_Manajemen_approval); kalau
        // disyaratkan "stageAktif == stageDokumen" persis, PJ kehilangan akses
        // isi SESAAT setelah mengajukan, sebelum sempat mengisi apa pun.
        $batasAtas      = $this->_stageBerikutnyaBerbedaOwner($stageDokumen, $jenisID);
        $sedangBerjalan = in_array($status, ['editable', 'Approval OnProgress', 'Perlu Revisi'], true);
        $isi            = $sedangBerjalan && $viewerPosition === $dibuatOleh
            && ($batasAtas === null || $stageAktif < $batasAtas);

        return ['lihat' => true, 'isi' => $isi];
    }

    /** FlowOrder tahap pemilik-dokumen-LAIN pertama setelah $stageDokumen (null = tak ada / sampai akhir). */
    private function _stageBerikutnyaBerbedaOwner($stageDokumen, $jenisID)
    {
        $stages = [];
        foreach ($this->templatesForJenis($jenisID) as $t) {
            if ($t['dibuat_oleh'] === 'auto') {
                continue;
            }
            $fo = $this->_flowOrderOfRole($t['dibuat_oleh'], $jenisID);
            if ($fo > 0) {
                $stages[$fo] = true;
            }
        }
        $stages = array_keys($stages);
        sort($stages);
        foreach ($stages as $s) {
            if ($s > $stageDokumen) {
                return $s;
            }
        }

        return null;
    }

    /**
     * Daftar dokumen untuk sebuah kegiatan + status pengisian (dari tb_dokumen).
     * Untuk panel dokumen di layar Persetujuan / Daftar Pengajuan.
     *
     * $viewerPosition: kalau diisi, tiap baris disertai 'boleh_lihat'/'boleh_isi'
     * untuk peran tsb (lihat aksesDokumen()). Kosongkan untuk daftar "mentah"
     * tanpa gating (mis. dipakai backend lain yang sudah menggerbang sendiri).
     */
    public function dokumenUntukKegiatan($KegiatanID, $viewerPosition = null)
    {
        $KegiatanID   = (int) $KegiatanID;
        $keg          = $this->kegiatan($KegiatanID);
        $jenisID      = isset($keg['KegiatanJenisID']) && (int) $keg['KegiatanJenisID'] > 0 ? (int) $keg['KegiatanJenisID'] : 1;
        $rangkapNames = $this->rangkapList($KegiatanID);

        $saved = [];
        if ($this->ensureTable()) {
            foreach ($this->db->table('tb_dokumen')->getWhere(['KegiatanID' => $KegiatanID])->getResultArray() as $r) {
                $saved[$r['Kode']][$r['RangkapKey']] = $r;
            }
        }

        $out = [];
        foreach ($this->templatesForJenis($jenisID) as $kode => $tpl) {
            $perPenerima = (isset($tpl['rangkap']) && $tpl['rangkap'] === 'per_penerima');
            $keys        = $perPenerima ? ($rangkapNames ?: []) : ['-'];
            $rangkap     = [];
            foreach ($keys as $k) {
                $rangkap[] = [
                    'key'        => $k,
                    'label'      => ($k === '-') ? '' : $k,
                    'terisi'     => isset($saved[$kode][$k]),
                    'diperbarui' => isset($saved[$kode][$k]) ? $saved[$kode][$k]['UpdatedAt'] : null,
                ];
            }
            $akses = ($viewerPosition !== null) ? $this->aksesDokumen($kode, $KegiatanID, $viewerPosition) : ['lihat' => true, 'isi' => true];
            $out[] = [
                'kode'         => $kode,
                'nama'         => $tpl['nama'],
                'metode'       => $tpl['metode'],
                'per_penerima' => $perPenerima,
                'dibuat_oleh'  => $tpl['dibuat_oleh'],
                'butuh_cap'    => ! empty($tpl['butuh_cap']),
                'slot_ttd'     => $tpl['slot_ttd'],
                'rangkap'      => $rangkap,
                'ada'          => count(array_filter($rangkap, function ($x) { return $x['terisi']; })),
                'total'        => count($rangkap),
                'boleh_lihat'  => $akses['lihat'],
                'boleh_isi'    => $akses['isi'],
            ];
        }

        return $out;
    }

    public function template($kode)
    {
        $t = $this->templates();

        return isset($t[$kode]) ? $t[$kode] : null;
    }

    /* ======================================================================
       AUTO-FILL
       ====================================================================== */

    /**
     * Data kegiatan + daftar pelaksana (snapshot dari tb_kegiatan_pelaksana).
     *   $row['_pelaksana']      => array nama (untuk axis rangkap)
     *   $row['_pelaksana_rows'] => array baris lengkap (nama, nip, gol, jabatan, rekening, bank, npwp)
     */
    public function kegiatan($KegiatanID)
    {
        $KegiatanID = (int) $KegiatanID;
        $row        = [];
        if ($KegiatanID > 0 && $this->db->tableExists('tb_kegiatan')) {
            $q   = $this->db->table('tb_kegiatan')->getWhere(['KegiatanID' => $KegiatanID], 1);
            $row = $q ? ($q->getRowArray() ?: []) : [];
        }

        $rows = [];
        if ($KegiatanID > 0 && $this->db->tableExists('tb_kegiatan_pelaksana')) {
            $rows = $this->db->table('tb_kegiatan_pelaksana')->orderBy('Urut', 'ASC')->orderBy('id', 'ASC')
                ->getWhere(['KegiatanID' => $KegiatanID])->getResultArray();
        }
        // Fallback data lama: belum ter-migrasi -> pecah teks bebas.
        if (empty($rows) && ! empty($row['KegiatanNamaPelaksana'])) {
            foreach (preg_split('/\s*[;,]\s*/', $row['KegiatanNamaPelaksana'], -1, PREG_SPLIT_NO_EMPTY) as $p) {
                $p = trim($p);
                if ($p !== '') {
                    $rows[] = ['Nama' => $p, 'NIP' => '', 'Gol' => '', 'Jabatan' => '', 'Rekening' => '', 'Bank' => '', 'NPWP' => ''];
                }
            }
        }

        $row['_pelaksana_rows'] = $rows;
        $row['_pelaksana']      = [];
        foreach ($rows as $r) {
            if (trim((string) $r['Nama']) !== '') {
                $row['_pelaksana'][] = trim($r['Nama']);
            }
        }

        return $row;
    }

    /** Baris pelaksana yang cocok dengan sebuah rangkap (nama penerima). */
    private function pelaksanaRow($keg, $rangkapKey)
    {
        $rows = isset($keg['_pelaksana_rows']) ? $keg['_pelaksana_rows'] : [];
        if ($rangkapKey !== '-' && $rangkapKey !== '') {
            foreach ($rows as $r) {
                if (trim((string) $r['Nama']) === trim((string) $rangkapKey)) {
                    return $r;
                }
            }
        }

        return isset($rows[0]) ? $rows[0] : [];
    }

    private function ppkFor($kodeOutput)
    {
        $kodeOutput = (string) $kodeOutput;
        $map        = $this->ppkMap();
        foreach ($map as $prefix => $ppk) {
            if ($prefix !== '_default' && stripos($kodeOutput, $prefix) === 0) {
                return $ppk;
            }
        }

        return isset($map['_default']) ? $map['_default'] : ['nama' => '', 'nip' => '', 'label' => 'PPK'];
    }

    /**
     * Nilai auto untuk sebuah rangkap. $rangkapKey = nama penerima (untuk
     * dokumen per_penerima) atau '-' (per_kegiatan).
     */
    public function autofill($kode, $KegiatanID, $rangkapKey = '-')
    {
        $keg        = $this->kegiatan($KegiatanID);
        $kodeOutput = isset($keg['KegiatanKodeOutput']) ? (string) $keg['KegiatanKodeOutput'] : '';
        $ppk        = $this->ppkFor($kodeOutput);
        $pr         = $this->pelaksanaRow($keg, $rangkapKey);
        $penerima   = ($rangkapKey !== '-' && $rangkapKey !== '') ? $rangkapKey
            : (isset($pr['Nama']) ? $pr['Nama'] : (isset($keg['_pelaksana'][0]) ? $keg['_pelaksana'][0] : ''));

        $konst = $this->konst();
        // Auto-fill kartu_kendali: no_urut dari count riwayat, tgl_masuk dari tanggal kirim pertama.
        $noUrut   = '';
        $tglMasuk = '';
        if ((int) $KegiatanID > 0 && $this->db->tableExists('tb_approval_history')) {
            $histFirst = $this->db->table('tb_approval_history')->select('FlowDate')
                ->orderBy('HistoryID', 'ASC')
                ->getWhere(['KegiatanID' => (int) $KegiatanID], 1)->getRowArray();
            if (! empty($histFirst['FlowDate'])) {
                $tglMasuk = substr($histFirst['FlowDate'], 0, 10);
            }
            // no_urut: urutan kegiatan ini di antara semua kegiatan (KegiatanID-rank sederhana)
            $r = $this->db->table('tb_kegiatan')->select('COUNT(*) as cnt')
                ->where('KegiatanID <=', (int) $KegiatanID)
                ->where('KegiatanDeletedAt IS NULL', null, false)
                ->get()->getRowArray();
            $noUrut = ! empty($r['cnt']) ? (string) (int) $r['cnt'] : '';
        }
        // Format ST: no surat / tanggal
        $noSt    = isset($keg['KegiatanNoSuratTugas']) ? trim($keg['KegiatanNoSuratTugas']) : '';
        $tglSt   = ! empty($keg['KegiatanTanggal']) ? tgl_ind($keg['KegiatanTanggal']) : '';
        $stTglNo = $noSt . ($tglSt ? (' / ' . $tglSt) : '');

        // Pelaksana joined per baris
        $pelaksanaList = '';
        if (! empty($keg['_pelaksana']) && is_array($keg['_pelaksana'])) {
            $pelaksanaList = implode("\n", $keg['_pelaksana']);
        } else {
            $pelaksanaList = isset($keg['KegiatanNamaPelaksana']) ? str_replace([';', ','], "\n", $keg['KegiatanNamaPelaksana']) : '';
        }

        // Alur check 7 tahapan approval (identifikasi kemajuan alur ASIKKEKKU)
        $alurCheck = [0, 0, 0, 0, 0, 0, 0];
        if ((int) $KegiatanID > 0 && $this->db->tableExists('tb_approval_history')) {
            $hist = $this->db->table('tb_approval_history')->orderBy('HistoryID', 'ASC')
                ->getWhere(['KegiatanID' => (int) $KegiatanID])->getResultArray();

            $hasPjk         = false;
            $hasPpks        = false;
            $hasSpp         = false;
            $hasVrf         = false;
            $hasPpk         = false;
            $hasSpmAfterPpk = false;
            $hasPpspm       = false;
            $ppkFound       = false;

            foreach ($hist as $h) {
                $code = $h['FlowCode'];
                $res  = (int) $h['FlowResult'];

                // 1. Pelaksana -> Staf PPK
                if ($code === 'PJK' && $res === 1) {
                    $hasPjk = true;
                }
                // 2. Staf PPK -> Pembuat SPP/SPM
                if ($code === 'PPKS1' && $res === 1) {
                    $hasPpks = true;
                }
                // 3. Petugas SPP/SPM -> Verifikator (pembuatan SPP sebelum Verifikator & PPK)
                if (($code === 'SPP1' || ($code === 'SPM1' && ! $ppkFound)) && $res === 1) {
                    $hasSpp = true;
                }
                // 4. Verifikator -> PPK (Verifikator menyetujui dokumen/SPP)
                if (($code === 'VRF2' || $code === 'VRF1') && $res === 1 && ($hasPpks || $hasSpp)) {
                    $hasVrf = true;
                }
                // 5. PPK -> PPSPM (PPK menyetujui / TTE)
                if ($code === 'PPK1' && $res === 1) {
                    $hasPpk   = true;
                    $ppkFound = true;
                }
                // 6. Petugas SPM -> PPSPM (SPM dibuat/diterbitkan setelah persetujuan PPK)
                if ($ppkFound && $code === 'SPM1' && $res === 1) {
                    $hasSpmAfterPpk = true;
                }
                // 7. PPSPM -> Petugas SPM (Pemeriksaan & TTE SPM oleh PPSPM / Selesai)
                if (($code === 'PPSPM1' || $code === 'SLS') && $res === 1) {
                    $hasPpspm = true;
                }
            }

            if ($hasPjk || count($hist) > 0 || (isset($keg['KegiatanStatus']) && $keg['KegiatanStatus'] !== 'editable')) {
                $alurCheck[0] = 1;
            }
            if ($hasPpks) {
                $alurCheck[1] = 1;
            }
            if ($hasSpp) {
                $alurCheck[2] = 1;
            }
            if ($hasVrf) {
                $alurCheck[3] = 1;
            }
            if ($hasPpk) {
                $alurCheck[4] = 1;
            }
            if ($hasSpmAfterPpk) {
                $alurCheck[5] = 1;
            }
            if ($hasPpspm) {
                $alurCheck[6] = 1;
            }

            // Sifat alur sekuensial (waterfall): jika tahap n sudah selesai,
            // maka semua tahap 0..(n-1) pasti sudah dilewati dan tercentang
            for ($i = 6; $i >= 1; $i--) {
                if ($alurCheck[$i] === 1) {
                    for ($j = $i - 1; $j >= 0; $j--) {
                        $alurCheck[$j] = 1;
                    }
                    break;
                }
            }
        }

        $map = [
            'judul'          => isset($keg['KegiatanJudul']) ? $keg['KegiatanJudul'] : '',
            'no_surat'       => isset($keg['KegiatanNoSuratTugas']) ? $keg['KegiatanNoSuratTugas'] : '',
            'tgl_kegiatan'   => isset($keg['KegiatanTanggal']) ? $keg['KegiatanTanggal'] : '',
            'st_tgl_no'      => $stTglNo,
            'no_sptb_spm'    => ! empty($keg['KegiatanNoSPTJB']) ? $keg['KegiatanNoSPTJB'] : (! empty($keg['KegiatanNoKwitansi']) ? $keg['KegiatanNoKwitansi'] : '1'),
            'pelaksana_list' => $pelaksanaList,
            'kode_output'    => $kodeOutput,
            'asal_tujuan'    => isset($keg['KegiatanAsalTujuan']) ? (string) $keg['KegiatanAsalTujuan'] : '',
            'jml_hari'       => isset($keg['KegiatanJmlHari']) ? $keg['KegiatanJmlHari'] : '',
            'penerima_nama'  => $penerima,
            'penerima_nip'   => isset($pr['NIP']) ? $pr['NIP'] : '',
            'penerima_gol'   => isset($pr['Gol']) ? $pr['Gol'] : '',
            'penerima_jabatan'  => isset($pr['Jabatan']) ? $pr['Jabatan'] : '',
            'penerima_rekening' => isset($pr['Rekening']) ? $pr['Rekening'] : '',
            'penerima_bank'     => isset($pr['Bank']) ? $pr['Bank'] : '',
            'penerima_npwp'     => isset($pr['NPWP']) ? $pr['NPWP'] : '',
            'ppk_nama'       => $ppk['nama'],
            'ppk_nip'        => $ppk['nip'],
            'pembebanan'     => 'DIPA ' . $konst['satker_nama'] . ' TA ' . $konst['tahun_anggaran'],
            'dipa_tgl_no'    => tgl_ind($konst['dipa_tgl']) . ' Nomor: ' . $konst['dipa_no'],
            // Kartu Kendali auto-keys
            'no_urut'   => $noUrut,
            'tgl_masuk' => $tglMasuk,
        ];

        $out = [];
        $tpl = $this->template($kode);
        if (! $tpl) {
            return $out;
        }
        foreach ($tpl['fields'] as $f) {
            if (strpos($f['sumber'], 'auto:') === 0) {
                $k             = substr($f['sumber'], 5);
                $out[$f['key']] = isset($map[$k]) ? $map[$k] : '';
            } elseif (strpos($f['sumber'], 'const:') === 0) {
                $k             = substr($f['sumber'], 6);
                $out[$f['key']] = isset($konst[$k]) ? $konst[$k] : '';
            }
        }

        // Khusus lembar_periksa dan kartu_kendali
        if ($kode === 'lembar_periksa' || $kode === 'kartu_kendali') {
            // Kode => set(RangkapKey tersimpan), bukan sekadar Kode => true --
            // dokumen per_penerima (SPD/Kwitansi/Riil) baru "ADA" kalau SEMUA
            // pelaksana sudah mengisi bagiannya masing-masing, bukan cuma satu
            // orang (kegiatan bisa >1 pelaksana dengan keperluan/nominal beda).
            $savedDocs = [];
            if ($this->ensureTable()) {
                $q = $this->db->table('tb_dokumen')->select('Kode, RangkapKey')->where('KegiatanID', (int) $KegiatanID)->get()->getResultArray();
                foreach ($q as $r) {
                    $savedDocs[$r['Kode']][$r['RangkapKey']] = true;
                }
            }
            $pelaksanaNama      = ! empty($keg['_pelaksana']) ? $keg['_pelaksana'] : [];
            $lengkapPerPenerima = function ($kd) use ($savedDocs, $pelaksanaNama) {
                if (empty($pelaksanaNama)) {
                    return ! empty($savedDocs[$kd]);
                }
                foreach ($pelaksanaNama as $nama) {
                    if (empty($savedDocs[$kd][$nama])) {
                        return false;
                    }
                }

                return true;
            };
            $uploadedTypes = [];
            if ($this->db->tableExists('tb_dok_upload')) {
                $q2 = $this->db->table('tb_dok_upload')->select('Tipe')->where('KegiatanID', (int) $KegiatanID)->get()->getResultArray();
                foreach ($q2 as $r2) {
                    $uploadedTypes[strtolower($r2['Tipe'])] = true;
                }
            }
            $hasST        = ! empty($keg['KegiatanNoSuratTugas']) || ! empty($keg['KegiatanLampiran']) || isset($uploadedTypes['st']) || isset($uploadedTypes['surat_tugas']);
            $hasLPD       = isset($uploadedTypes['lpd']);
            $hasSPD       = $lengkapPerPenerima('spd');
            $hasKwitansi  = $lengkapPerPenerima('kwitansi');
            $hasNominatif = ! empty($savedDocs['nominatif']); // per_kegiatan: satu untuk semua, cukup satu baris
            $hasRiil      = $lengkapPerPenerima('riil');

            $items = ['kelengkapan' => [], 'verifikasi' => []];
            // 13 item kelengkapan berkas:
            $items['kelengkapan'][0]  = ['status' => $hasST ? 'ya' : '', 'ket' => '']; // ST
            $items['kelengkapan'][1]  = ['status' => '',   'ket' => '']; // Laporan Kegiatan
            $items['kelengkapan'][2]  = ['status' => '',   'ket' => '']; // Daftar Absensi
            $items['kelengkapan'][3]  = ['status' => $hasSPD ? 'ya' : '', 'ket' => '']; // SPD
            $items['kelengkapan'][4]  = ['status' => 'ya', 'ket' => '']; // Tiket Transportasi
            $items['kelengkapan'][5]  = ['status' => 'ya', 'ket' => '']; // Boarding Pass
            $items['kelengkapan'][6]  = ['status' => 'ya', 'ket' => '']; // Kuitansi Penginapan
            $items['kelengkapan'][7]  = ['status' => '',   'ket' => '']; // Kwitansi Taksi / BBM
            $items['kelengkapan'][8]  = ['status' => $hasLPD ? 'ya' : '', 'ket' => '']; // LPD
            $items['kelengkapan'][9]  = ['status' => $hasNominatif ? 'ya' : '', 'ket' => '']; // Nominatif
            $items['kelengkapan'][10] = ['status' => $hasKwitansi ? 'ya' : '', 'ket' => '']; // Kwitansi
            $items['kelengkapan'][11] = ['status' => $hasRiil ? 'ya' : '', 'ket' => '']; // Daftar Riil
            $items['kelengkapan'][12] = ['status' => '',   'ket' => '']; // SSP

            // 3 item verifikasi kesesuaian isi (default SESUAI):
            $items['verifikasi'][0] = ['status' => 'ya', 'ket' => '']; // Daftar Riil
            $items['verifikasi'][1] = ['status' => 'ya', 'ket' => '']; // Kwitansi
            $items['verifikasi'][2] = ['status' => 'ya', 'ket' => '']; // Daftar Nominatif

            $out['items']      = $items;
            $out['alur_check'] = $alurCheck;
        }

        return $out;
    }

    /** Daftar rangkap (penerima) untuk sebuah kegiatan. */
    public function rangkapList($KegiatanID)
    {
        $keg  = $this->kegiatan($KegiatanID);
        $list = ! empty($keg['_pelaksana']) ? $keg['_pelaksana'] : [];

        return $list ?: [];
    }

    /* ======================================================================
       SIMPAN / MUAT PAYLOAD
       ====================================================================== */

    private function ensureTable()
    {
        return $this->db->tableExists('tb_dokumen');
    }

    /** Payload tergabung: default (auto/const) <- tersimpan. */
    public function load($kode, $KegiatanID, $rangkapKey = '-')
    {
        $auto  = $this->autofill($kode, $KegiatanID, $rangkapKey);
        $saved = [];
        if ($this->ensureTable()) {
            $q = $this->db->table('tb_dokumen')->getWhere([
                'KegiatanID' => (int) $KegiatanID, 'Kode' => $kode, 'RangkapKey' => $rangkapKey ?: '-',
            ], 1);
            $r = $q ? $q->getRowArray() : null;
            if ($r && ! empty($r['PayloadJson'])) {
                $saved = json_decode($r['PayloadJson'], true) ?: [];
            }
        }
        $out = array_merge($auto, $saved);
        if (isset($saved['items']) && is_array($saved['items']) && ! empty($saved['items'])) {
            $out['items'] = $saved['items'];
        }
        if (isset($saved['alur_check']) && is_array($saved['alur_check'])) {
            $out['alur_check'] = $saved['alur_check'];
        }

        return $out;
    }

    public function saveDokumen($kode, $KegiatanID, $rangkapKey, array $payload, $userID = null)
    {
        if (! $this->ensureTable()) {
            return ['ok' => false, 'msg' => 'Tabel tb_dokumen belum ada. Jalankan assets/sql/2026-09-04_dokumen.sql.'];
        }
        $tpl = $this->template($kode);
        if (! $tpl) {
            return ['ok' => false, 'msg' => 'Jenis dokumen tidak dikenal.'];
        }
        $perPenerima = (isset($tpl['rangkap']) && $tpl['rangkap'] === 'per_penerima');
        if ($perPenerima) {
            // Dokumen per orang: rangkap WAJIB nama pelaksana asli kegiatan ini,
            // bukan '-' atau nama sembarang -- kalau tidak, datanya "nyasar" ke
            // baris yang tidak muncul di form manapun (tidak kelihatan, tidak
            // ikut terhitung lengkap/belum di checklist Kartu Kendali).
            $rangkapKey = trim((string) $rangkapKey);
            $pelaksana  = $this->rangkapList($KegiatanID);
            if ($rangkapKey === '' || $rangkapKey === '-' || ! in_array($rangkapKey, $pelaksana, true)) {
                return ['ok' => false, 'msg' => 'Pilih nama pelaksana (penerima) yang sah untuk dokumen ini.'];
            }
        } else {
            $rangkapKey = '-';
        }
        $row = [
            'KegiatanID'    => (int) $KegiatanID,
            'Kode'          => $kode,
            'RangkapKey'    => $rangkapKey,
            'PayloadJson'   => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'TemplateVersi' => self::TEMPLATE_VERSI,
            'UpdatedBy'     => $userID ? (int) $userID : null,
            'UpdatedAt'     => date('Y-m-d H:i:s'),
        ];
        $row['PayloadHash'] = $this->_hashPayload($payload);
        $ada = $this->db->table('tb_dokumen')->getWhere([
            'KegiatanID' => (int) $KegiatanID, 'Kode' => $kode, 'RangkapKey' => $rangkapKey,
        ], 1)->getRowArray();
        if ($ada) {
            $this->db->table('tb_dokumen')->where('DokumenID', $ada['DokumenID'])->update($row);
        } else {
            $this->db->table('tb_dokumen')->insert($row);
        }

        // Dokumen berubah -> hanguskan tanda tangan yang terikat ke versi lama.
        $hangus = $this->ttdInvalidateOnChange($KegiatanID, $kode, $rangkapKey);

        return ['ok' => true, 'msg' => 'Tersimpan.' . ($hangus ? ' ' . $hangus . ' tanda tangan dihanguskan (dokumen berubah).' : '')];
    }

    /* ======================================================================
       TANDA TANGAN (tangkap-langsung)
       ====================================================================== */

    private function _hashPayload($payload)
    {
        ksort($payload);

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE) . '|' . self::TEMPLATE_VERSI);
    }

    /** Hash payload TERGABUNG (auto + tersimpan) sebuah rangkap -- dipakai saat menandatangani. */
    public function docHash($kode, $KegiatanID, $rangkapKey = '-')
    {
        return $this->_hashPayload($this->load($kode, $KegiatanID, $rangkapKey ?: '-'));
    }

    /** Baris tanda tangan AKTIF (belum hangus) untuk sebuah rangkap, keyed by Slot. */
    public function ttdAktif($KegiatanID, $kode, $rangkapKey = '-')
    {
        if (! $this->db->tableExists('tb_dokumen_ttd')) {
            return [];
        }
        $rows = $this->db->table('tb_dokumen_ttd')->where([
            'KegiatanID' => (int) $KegiatanID, 'Kode' => $kode,
            'RangkapKey' => $rangkapKey ?: '-', 'InvalidatedAt' => null,
        ])->orderBy('TtdID', 'DESC')->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            if (! isset($out[$r['Slot']])) {
                $out[$r['Slot']] = $r;
            }
        }

        return $out;
    }

    /** Semua ttd aktif untuk sebuah kegiatan (untuk panel). */
    public function ttdAktifKegiatan($KegiatanID)
    {
        if (! $this->db->tableExists('tb_dokumen_ttd')) {
            return [];
        }
        $rows = $this->db->table('tb_dokumen_ttd')->getWhere(['KegiatanID' => (int) $KegiatanID, 'InvalidatedAt' => null])->getResultArray();
        $out  = [];
        foreach ($rows as $r) {
            $out[$r['Kode']][$r['RangkapKey']][$r['Slot']] = $r;
        }

        return $out;
    }

    /**
     * Simpan tanda tangan sebuah slot. $img = data PNG mentah (binary).
     * Menghanguskan ttd aktif slot yang sama lebih dulu (satu aktif per slot).
     */
    public function ttdSimpan($KegiatanID, $kode, $rangkapKey, $slot, $img, $signer)
    {
        if (! $this->db->tableExists('tb_dokumen_ttd')) {
            return ['ok' => false, 'msg' => 'Tabel tb_dokumen_ttd belum ada. Jalankan migrasi.'];
        }
        $KegiatanID = (int) $KegiatanID;
        $rangkapKey = $rangkapKey ?: '-';
        $slot       = preg_replace('/[^a-z0-9_]/', '', strtolower($slot));
        if ($slot === '') {
            return ['ok' => false, 'msg' => 'Slot tidak valid.'];
        }
        if (strncmp($img, "\x89PNG\r\n\x1a\n", 8) !== 0) {
            return ['ok' => false, 'msg' => 'Gambar tanda tangan harus PNG.'];
        }
        if (strlen($img) > 800 * 1024) {
            return ['ok' => false, 'msg' => 'Gambar tanda tangan terlalu besar (maks 800 KB).'];
        }

        $dir = FCPATH . 'assets/ttd/' . $KegiatanID . '/';
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $safe  = preg_replace('/[^a-zA-Z0-9_-]/', '_', $kode . '_' . $rangkapKey . '_' . $slot);
        $fname = $safe . '_' . time() . '.png';
        if (@file_put_contents($dir . $fname, $img) === false) {
            return ['ok' => false, 'msg' => 'Gagal menyimpan berkas tanda tangan.'];
        }
        $rel = 'assets/ttd/' . $KegiatanID . '/' . $fname;

        $this->db->table('tb_dokumen_ttd')->where(['KegiatanID' => $KegiatanID, 'Kode' => $kode, 'RangkapKey' => $rangkapKey, 'Slot' => $slot, 'InvalidatedAt' => null])
            ->update(['InvalidatedAt' => date('Y-m-d H:i:s'), 'InvalidatedReason' => 'diganti tanda tangan baru']);

        $this->db->table('tb_dokumen_ttd')->insert([
            'KegiatanID'   => $KegiatanID,
            'Kode'         => $kode,
            'RangkapKey'   => $rangkapKey,
            'Slot'         => $slot,
            'SignerUserID' => ! empty($signer['user_id']) ? (int) $signer['user_id'] : null,
            'SignerNama'   => isset($signer['nama']) ? $signer['nama'] : null,
            'SignerRole'   => isset($signer['role']) ? $signer['role'] : null,
            'ImagePath'    => $rel,
            'DocHash'      => $this->docHash($kode, $KegiatanID, $rangkapKey),
            'SignedAt'     => date('Y-m-d H:i:s'),
            'SignedIP'     => isset($signer['ip']) ? $signer['ip'] : null,
        ]);

        return ['ok' => true, 'msg' => 'Tanda tangan tersimpan.'];
    }

    /** Path relatif gambar cap dinas (tb_vrbl 'dok_cap_image'), atau '' bila belum diunggah. */
    public function capImagePath()
    {
        $r = $this->db->table('tb_vrbl')->getWhere(['VrblName' => 'dok_cap_image'])->getRowArray();
        $p = ! empty($r['VrblValue']) ? trim($r['VrblValue']) : '';

        return ($p !== '' && is_file(FCPATH . $p)) ? $p : '';
    }

    /** Setel / hapus path cap dinas + catat siapa & kapan (tb_vrbl). */
    public function capSet($relPath, $userID = null)
    {
        $set = function ($name, $val) {
            if ($this->db->table('tb_vrbl')->getWhere(['VrblName' => $name])->getNumRows() > 0) {
                $this->db->table('tb_vrbl')->where('VrblName', $name)->update(['VrblValue' => $val]);
            } else {
                $this->db->table('tb_vrbl')->insert(['VrblName' => $name, 'VrblValue' => $val]);
            }
        };
        $set('dok_cap_image', (string) $relPath);
        $set('dok_cap_meta', json_encode(['by' => (int) $userID, 'at' => date('Y-m-d H:i:s')]));
    }

    /** Hanguskan ttd aktif yang DocHash-nya tidak lagi cocok dengan versi dokumen sekarang. */
    public function ttdInvalidateOnChange($KegiatanID, $kode, $rangkapKey = '-')
    {
        if (! $this->db->tableExists('tb_dokumen_ttd')) {
            return 0;
        }
        $rangkapKey = $rangkapKey ?: '-';
        $hashNow    = $this->docHash($kode, $KegiatanID, $rangkapKey);
        $this->db->table('tb_dokumen_ttd')->where(['KegiatanID' => (int) $KegiatanID, 'Kode' => $kode, 'RangkapKey' => $rangkapKey, 'InvalidatedAt' => null])
            ->where('DocHash !=', $hashNow)
            ->update(['InvalidatedAt' => date('Y-m-d H:i:s'), 'InvalidatedReason' => 'dokumen diubah']);

        return $this->db->affectedRows();
    }

    /** Gate "Setuju PPK" aktif?  (tb_vrbl 'dok_gate_ppk' = 1). Default OFF. */
    public function dokGatePpkOn()
    {
        $r = $this->db->table('tb_vrbl')->getWhere(['VrblName' => 'dok_gate_ppk'])->getRowArray();

        return ! empty($r) && in_array(strtolower(trim($r['VrblValue'])), ['1', 'on', 'true', 'ya', 'aktif'], true);
    }

    /**
     * Dokumen yang WAJIB ditandatangani $posisi tapi belum, untuk sebuah kegiatan.
     * v1: hanya posisi 'PPK' -> slot 'ppk' pada semua form_inapp, per rangkap.
     * Return: array of {kode, nama, rangkap, label, slot, url_ttd}.
     */
    public function ttdKurangUntukPosisi($KegiatanID, $posisi)
    {
        $KegiatanID = (int) $KegiatanID;
        if ($posisi !== 'PPK') {
            return [];
        }
        $keg          = $this->kegiatan($KegiatanID);
        $jenisID      = isset($keg['KegiatanJenisID']) && (int) $keg['KegiatanJenisID'] > 0 ? (int) $keg['KegiatanJenisID'] : 1;
        $rangkapNames = $this->rangkapList($KegiatanID);
        $aktif        = $this->ttdAktifKegiatan($KegiatanID);
        $base         = base_url();

        $out = [];
        foreach ($this->templatesForJenis($jenisID) as $kode => $tpl) {
            if ($tpl['metode'] !== 'form_inapp') {
                continue;
            }
            if (! in_array('ppk', (array) $tpl['slot_ttd'], true)) {
                continue;
            }
            $perPenerima = (isset($tpl['rangkap']) && $tpl['rangkap'] === 'per_penerima');
            $keys        = $perPenerima ? ($rangkapNames ?: []) : ['-'];
            foreach ($keys as $k) {
                if (isset($aktif[$kode][$k]['ppk'])) {
                    continue; // sudah ada ttd ppk aktif
                }
                $q     = ($k === '-') ? '?' : ('?r=' . rawurlencode($k) . '&');
                $out[] = [
                    'kode'    => $kode,
                    'nama'    => $tpl['nama'],
                    'rangkap' => $k,
                    'label'   => ($k === '-') ? '' : $k,
                    'slot'    => 'ppk',
                    'url_ttd' => $base . 'dokumen/ttd/' . $kode . '/' . $KegiatanID . $q . 'slot=ppk',
                ];
            }
        }

        return $out;
    }

    /** Catat aksi cetak/unduh (log saja, bukan gate). */
    public function printLog($KegiatanID, array $isi, $denganTtd, $userID = null)
    {
        if (! $this->db->tableExists('tb_dokumen_print')) {
            return;
        }
        $this->db->table('tb_dokumen_print')->insert([
            'KegiatanID' => (int) $KegiatanID,
            'Batch'      => substr(md5(uniqid('', true)), 0, 12),
            'Isi'        => json_encode(array_values($isi), JSON_UNESCAPED_UNICODE),
            'DenganTtd'  => $denganTtd ? 1 : 0,
            'ByUserID'   => $userID ? (int) $userID : null,
            'AtTime'     => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Hook: auto-update payload kartu_kendali saat approval maju/mundur.
     * Memanggil autofill() untuk key 'no_urut' dan 'tgl_masuk', lalu merge
     * dengan payload yang sudah tersimpan (tidak menimpa field manual).
     */
    public function kartuKendaliAutoFill($KegiatanID)
    {
        $KegiatanID = (int) $KegiatanID;
        if ($KegiatanID <= 0) {
            return;
        }
        if (! $this->ensureTable()) {
            return;
        }
        $auto = $this->autofill('kartu_kendali', $KegiatanID, '-');
        // Muat payload tersimpan, timpa HANYA key auto (jangan ganggu field manual).
        $saved = [];
        $q     = $this->db->table('tb_dokumen')->getWhere([
            'KegiatanID' => $KegiatanID, 'Kode' => 'kartu_kendali', 'RangkapKey' => '-',
        ], 1);
        $r = $q ? $q->getRowArray() : null;
        if ($r && ! empty($r['PayloadJson'])) {
            $saved = json_decode($r['PayloadJson'], true) ?: [];
        }
        // Hanya timpa field auto; biarkan field manual tetap seperti semula.
        foreach ($auto as $k => $v) {
            $saved[$k] = $v;
        }
        $this->saveDokumen('kartu_kendali', $KegiatanID, '-', $saved, null);
    }

    /** Beberapa kegiatan terbaru untuk pemilih. */
    public function kegiatanTerbaru($limit = 40)
    {
        if (! $this->db->tableExists('tb_kegiatan')) {
            return [];
        }

        return $this->db->table('tb_kegiatan')->select('KegiatanID, KegiatanNoSuratTugas, KegiatanJudul, KegiatanTanggal, KegiatanNamaPelaksana')
            ->where('KegiatanDeletedAt IS NULL', null, false)
            ->orderBy('KegiatanID', 'DESC')->get((int) $limit)->getResultArray();
    }

    /* ======================================================================
       UNIFIED BUNDLE & DOKUMEN REORDER
       ====================================================================== */

    /** Ambil urutan kustom dokumen untuk suatu kegiatan dari tb_vrbl. */
    public function getDokumenUrutan($KegiatanID)
    {
        $KegiatanID = (int) $KegiatanID;
        if ($KegiatanID <= 0 || ! $this->db->tableExists('tb_vrbl')) {
            return [];
        }
        $row = $this->db->table('tb_vrbl')->getWhere(['VrblName' => 'dok_urutan_' . $KegiatanID])->getRowArray();
        if ($row && ! empty($row['VrblValue'])) {
            $arr = json_decode($row['VrblValue'], true);
            if (is_array($arr)) {
                return $arr;
            }
        }

        return [];
    }

    /** Simpan urutan kustom dokumen untuk suatu kegiatan ke tb_vrbl. */
    public function saveDokumenUrutan($KegiatanID, array $keys)
    {
        $KegiatanID = (int) $KegiatanID;
        if ($KegiatanID <= 0) {
            return ['ok' => false, 'msg' => 'ID Kegiatan tidak valid.'];
        }
        $clean = array_values(array_filter(array_map('trim', $keys)));
        $val   = json_encode($clean, JSON_UNESCAPED_UNICODE);
        $name  = 'dok_urutan_' . $KegiatanID;

        $ada = $this->db->table('tb_vrbl')->getWhere(['VrblName' => $name])->getRowArray();
        if ($ada) {
            $this->db->table('tb_vrbl')->where('VrblName', $name)->update(['VrblValue' => $val]);
        } else {
            $this->db->table('tb_vrbl')->insert(['VrblName' => $name, 'VrblValue' => $val]);
        }

        return ['ok' => true, 'msg' => 'Urutan dokumen berhasil disimpan.'];
    }

    /**
     * Membangun bundle pratinjau terpadu (seluruh dokumen in-app + berkas upload).
     * Disertai urutan yang tersimpan (jika ada) dan metadata lengkap untuk in-page preview reader.
     */
    public function buildUnifiedBundle($KegiatanID, $viewerPosition = null)
    {
        $KegiatanID = (int) $KegiatanID;
        $kegiatan   = $this->kegiatan($KegiatanID);
        if (empty($kegiatan)) {
            return ['kegiatan' => [], 'items' => [], 'total' => 0];
        }

        $jenisID      = isset($kegiatan['KegiatanJenisID']) && (int) $kegiatan['KegiatanJenisID'] > 0 ? (int) $kegiatan['KegiatanJenisID'] : 1;
        $templates    = $this->templatesForJenis($jenisID);
        $rangkapNames = $this->rangkapList($KegiatanID);

        $iconMap = [
            'kartu_kendali'  => 'clipboard-check',
            'lembar_periksa' => 'tasks',
            'kwitansi'       => 'receipt',
            'spd'            => 'car',
            'nominatif'      => 'users',
            'riil'           => 'file-invoice-dollar',
            'sptjb'          => 'file-contract',
        ];

        $items = [];

        // 1. Dokumen In-App (Kwitansi, SPD, Nominatif, Riil, SPTJB, Kartu Kendali)
        foreach ($templates as $kode => $tpl) {
            $perPenerima = (isset($tpl['rangkap']) && $tpl['rangkap'] === 'per_penerima');
            $keys        = $perPenerima ? ($rangkapNames ?: []) : ['-'];

            foreach ($keys as $rk) {
                $itemKey  = 'doc:' . $kode . ':' . $rk;
                $subTitle = ($rk !== '-') ? $rk : '';
                $fullNama = $tpl['nama'] . ($subTitle !== '' ? ' (' . $subTitle . ')' : '');
                $akses    = ($viewerPosition !== null) ? $this->aksesDokumen($kode, $KegiatanID, $viewerPosition) : ['lihat' => true, 'isi' => true];

                $items[$itemKey] = [
                    'key'         => $itemKey,
                    'type'        => 'inapp',
                    'kode'        => $kode,
                    'rangkap'     => $rk,
                    'nama'        => $fullNama,
                    'short_title' => $tpl['nama'],
                    'sub_title'   => $subTitle,
                    'icon'        => isset($iconMap[$kode]) ? $iconMap[$kode] : 'file-alt',
                    'tpl'         => $tpl,
                    'd'           => $this->load($kode, $KegiatanID, $rk),
                    'ttd'         => $this->ttdAktif($KegiatanID, $kode, $rk),
                    'boleh_lihat' => $akses['lihat'],
                    'boleh_isi'   => $akses['isi'],
                    'url_cetak'   => base_url('dokumen/cetak/' . $kode . '/' . $KegiatanID . ($rk !== '-' ? '?r=' . rawurlencode($rk) : '')),
                    'url_unduh'   => base_url('dokumen/unduh/' . $kode . '/' . $KegiatanID . ($rk !== '-' ? '?r=' . rawurlencode($rk) : '')),
                ];
            }
        }

        // 2. Berkas Lampiran Utama Surat Tugas (jika diunggah saat buat pengajuan)
        if (! empty($kegiatan['KegiatanLampiran'])) {
            $lampKey = 'lampiran_st';
            $items[$lampKey] = [
                'key'         => $lampKey,
                'type'        => 'upload',
                'tipe_kode'   => 'st_lampiran',
                'nama'        => 'Lampiran Surat Tugas (' . $kegiatan['KegiatanLampiran'] . ')',
                'short_title' => 'Lampiran Surat Tugas',
                'sub_title'   => $kegiatan['KegiatanLampiran'],
                'file_url'    => base_url('assets/lampiran/' . $kegiatan['KegiatanLampiran']),
                'icon'        => 'paperclip',
                'boleh_lihat' => true,
                'boleh_isi'   => false,
            ];
        }

        // 3. Berkas Upload Eksternal (LPD, SPPD, SPM, SPP, dll.)
        $mDokUpload = model(M_DokUpload::class);
        $uploads    = $mDokUpload->listUpload($KegiatanID);
        foreach ($uploads as $u) {
            $upKey = 'upload:' . $u['UploadID'];
            $items[$upKey] = [
                'key'         => $upKey,
                'type'        => 'upload',
                'upload_id'   => $u['UploadID'],
                'tipe_kode'   => $u['Tipe'],
                'nama'        => $u['label'] . ' - ' . $u['OriginalName'],
                'short_title' => $u['label'],
                'sub_title'   => $u['OriginalName'],
                'file_url'    => base_url($u['FilePath']),
                'icon'        => 'file-pdf',
                'ttd'         => isset($u['ttd']) ? $u['ttd'] : [],
                'url_ttd_ppk' => base_url('dokumen/ttdUpload/' . $u['UploadID'] . '?slot=ppk'),
                'url_ttd_ppspm' => base_url('dokumen/ttdUpload/' . $u['UploadID'] . '?slot=ppspm'),
                'boleh_lihat' => true,
                'boleh_isi'   => false,
            ];
        }

        // 4. Susun ulang sesuai urutan kustom yang tersimpan (jika ada)
        $customOrder = $this->getDokumenUrutan($KegiatanID);
        if (! empty($customOrder)) {
            $ordered = [];
            foreach ($customOrder as $k) {
                if (isset($items[$k])) {
                    $ordered[$k] = $items[$k];
                    unset($items[$k]);
                }
            }
            // Tambahkan sisa item baru yang belum ada di custom order ke urutan paling bawah
            foreach ($items as $k => $item) {
                $ordered[$k] = $item;
            }
            $items = $ordered;
        }

        return [
            'kegiatan' => $kegiatan,
            'items'    => array_values($items),
            'total'    => count($items),
        ];
    }
}
