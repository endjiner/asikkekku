<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Dokumen form_inapp (set Perjalanan Dinas).
 *
 * Sistem hanya menyimpan NILAI FIELD (tb_dokumen.PayloadJson), tidak menyimpan
 * file. Formulir cetak dirender saat dibutuhkan dari layout + nilai field
 * (application/views/dokumen/tpl/*). Nama-nama pada bagian tanda tangan
 * TIDAK di-generate ke area ttd (area ttd dibiarkan kosong untuk tanda tangan
 * basah) -- yang di-generate hanya teks nama di bawah garis ttd.
 *
 * Sumber field:
 *   manual  -> input petugas
 *   auto:<k>-> diambil dari data kegiatan / master (read-only di form)
 *   const   -> nilai tetap kantor (read-only)
 */
class M_Dokumen extends CI_Model
{
	const TEMPLATE_VERSI = 'v1';

	/* ---- Konstanta kantor: default di sini, bisa ditimpa lewat tb_vrbl
	   (baris VrblName = 'dok_satker_nama', 'dok_kppn', 'dok_dipa_no', dst). ---- */
	private $konst_default = array(
		'satker_nama'     => 'Balai POM di Pangkalpinang',
		'satker_kode'     => '063.672842',
		'kppn'            => 'KPPN Pangkal Pinang (015)',
		'dipa_no'         => 'DIPA-063.01.2.672842/2026',
		'dipa_tgl'        => '2025-12-01',
		'tahun_anggaran'  => '2026',
		'kota'            => 'Pangkalpinang',
		'ppk_terima_dari' => 'Pejabat Pembuat Komitmen Balai POM di Pangkalpinang',
		'bendahara_nama'  => 'Desy Anindyasari, A.Md.',
		'bendahara_nip'   => '198512022008122002',
	);
	private $konst = null;

	/**
	 * Peta prefix kode Output -> PPK penandatangan. Default di sini; bisa
	 * ditimpa lewat tb_vrbl baris VrblName='dok_ppk_map' berisi JSON:
	 *   {"3165.QIA":{"nama":"…","nip":"…"}, "_default":{"nama":"","nip":""}}
	 * (nanti dipindah ke tabel tb_output_petugas yang diedit admin.)
	 */
	private $ppk_by_output_default = array(
		'3165.QIA' => array('nama' => 'Netty Desi Margaretta Manullang, S.E', 'nip' => '19931203 202012 2 001', 'label' => 'PPK I'),
		'3165.BDB' => array('nama' => 'Netty Desi Margaretta Manullang, S.E', 'nip' => '19931203 202012 2 001', 'label' => 'PPK I'),
		'3165.BKB' => array('nama' => 'Priya Tri Nanda, S.Si.', 'nip' => '19950322 201903 1 004', 'label' => 'PPK II'),
		'_default' => array('nama' => '', 'nip' => '', 'label' => 'PPK'),
	);
	private $ppk_by_output = null;

	/** Muat konstanta (default + override tb_vrbl 'dok_*'), sekali per request. */
	private function konst()
	{
		if ($this->konst !== null) return $this->konst;
		$this->konst = $this->konst_default;
		if ($this->db->table_exists('tb_vrbl')) {
			$rows = $this->db->like('VrblName', 'dok_', 'after')->get('tb_vrbl')->result_array();
			foreach ($rows as $r) {
				$k = substr($r['VrblName'], 4); // buang prefix 'dok_'
				if ($k === 'ppk_map') continue;
				if (array_key_exists($k, $this->konst) && trim((string) $r['VrblValue']) !== '') {
					$this->konst[$k] = $r['VrblValue'];
				}
			}
		}
		return $this->konst;
	}

	private function ppkMap()
	{
		if ($this->ppk_by_output !== null) return $this->ppk_by_output;
		$this->ppk_by_output = $this->ppk_by_output_default;
		if ($this->db->table_exists('tb_vrbl')) {
			$row = $this->db->get_where('tb_vrbl', array('VrblName' => 'dok_ppk_map'))->row_array();
			if (!empty($row['VrblValue'])) {
				$j = json_decode($row['VrblValue'], true);
				if (is_array($j) && !empty($j)) $this->ppk_by_output = $j;
			}
		}
		return $this->ppk_by_output;
	}

	/* ======================================================================
	   REGISTRY TEMPLATE
	   ====================================================================== */
	public function templates()
	{
		$t = array(

			'kwitansi' => array(
				'nama'    => 'Kwitansi',
				'rangkap' => 'per_penerima',
				'fields'  => array(
					array('key' => 'nomor_bukti', 'label' => 'Nomor Bukti', 'tipe' => 'text', 'sumber' => 'manual'),
					array('key' => 'kode_mak', 'label' => 'Kode MAK', 'tipe' => 'text', 'sumber' => 'auto:kode_output'),
					array('key' => 'terima_dari', 'label' => 'Sudah terima dari', 'tipe' => 'text', 'sumber' => 'const:ppk_terima_dari'),
					array('key' => 'jumlah', 'label' => 'Uang sebesar (Rp)', 'tipe' => 'uang', 'sumber' => 'manual'),
					array('key' => 'guna', 'label' => 'Guna pembayaran ongkos/biaya perjalanan', 'tipe' => 'textarea', 'sumber' => 'auto:judul'),
					array('key' => 'tgl_surat_tugas', 'label' => 'Tgl Surat Perintah/Tugas', 'tipe' => 'date', 'sumber' => 'manual'),
					array('key' => 'no_surat_tugas', 'label' => 'No. Surat Perintah/Tugas', 'tipe' => 'text', 'sumber' => 'auto:no_surat'),
					array('key' => 'untuk_perjalanan_dari', 'label' => 'Untuk perjalanan dinas dari', 'tipe' => 'text', 'sumber' => 'auto:asal_tujuan'),
					array('key' => 'rincian', 'label' => 'Rincian biaya perjalanan dinas', 'tipe' => 'rows', 'sumber' => 'manual', 'kolom' => array(
						array('key' => 'uraian', 'label' => 'Uraian', 'tipe' => 'text'),
						array('key' => 'tarif', 'label' => 'Tarif (Rp)', 'tipe' => 'uang'),
						array('key' => 'per', 'label' => 'per', 'tipe' => 'text'),
						array('key' => 'jumlah', 'label' => 'Jumlah (Rp)', 'tipe' => 'uang'),
						array('key' => 'ket', 'label' => 'Keterangan', 'tipe' => 'text'),
					)),
					array('key' => 'penerima_nama', 'label' => 'Nama penerima', 'tipe' => 'text', 'sumber' => 'auto:penerima_nama'),
					array('key' => 'penerima_nip', 'label' => 'NIP penerima', 'tipe' => 'text', 'sumber' => 'auto:penerima_nip'),
					array('key' => 'ppk_nama', 'label' => 'Nama PPK', 'tipe' => 'text', 'sumber' => 'auto:ppk_nama'),
					array('key' => 'ppk_nip', 'label' => 'NIP PPK', 'tipe' => 'text', 'sumber' => 'auto:ppk_nip'),
					array('key' => 'bendahara_nama', 'label' => 'Nama Bendahara', 'tipe' => 'text', 'sumber' => 'const:bendahara_nama'),
					array('key' => 'bendahara_nip', 'label' => 'NIP Bendahara', 'tipe' => 'text', 'sumber' => 'const:bendahara_nip'),
					array('key' => 'tempat', 'label' => 'Tempat', 'tipe' => 'text', 'sumber' => 'const:kota'),
					array('key' => 'tanggal', 'label' => 'Tanggal', 'tipe' => 'date', 'sumber' => 'manual'),
				),
			),

			'nominatif' => array(
				'nama'    => 'Daftar Nominatif Biaya Perjalanan Dinas',
				'rangkap' => 'per_kegiatan',
				'fields'  => array(
					array('key' => 'kode_kegiatan', 'label' => 'Kode Kegiatan/Anggaran', 'tipe' => 'text', 'sumber' => 'auto:kode_output'),
					array('key' => 'tahun_anggaran', 'label' => 'Tahun Anggaran', 'tipe' => 'text', 'sumber' => 'const:tahun_anggaran'),
					array('key' => 'baris', 'label' => 'Daftar petugas', 'tipe' => 'rows', 'sumber' => 'manual', 'kolom' => array(
						array('key' => 'nama', 'label' => 'Nama', 'tipe' => 'text'),
						array('key' => 'nip', 'label' => 'NIP', 'tipe' => 'text'),
						array('key' => 'gol', 'label' => 'Gol', 'tipe' => 'text'),
						array('key' => 'tujuan', 'label' => 'Tujuan', 'tipe' => 'text'),
						array('key' => 'tgl_brgkt', 'label' => 'Tgl Berangkat', 'tipe' => 'text'),
						array('key' => 'lama', 'label' => 'Lama (hari)', 'tipe' => 'number'),
						array('key' => 'jumlah', 'label' => 'Jumlah (Rp)', 'tipe' => 'uang'),
						array('key' => 'ket', 'label' => 'Ket', 'tipe' => 'text'),
					)),
					array('key' => 'ppk_nama', 'label' => 'Nama PPK', 'tipe' => 'text', 'sumber' => 'auto:ppk_nama'),
					array('key' => 'ppk_nip', 'label' => 'NIP PPK', 'tipe' => 'text', 'sumber' => 'auto:ppk_nip'),
					array('key' => 'tempat', 'label' => 'Tempat', 'tipe' => 'text', 'sumber' => 'const:kota'),
				),
			),

			'riil' => array(
				'nama'    => 'Daftar Pengeluaran Riil',
				'rangkap' => 'per_penerima',
				'fields'  => array(
					array('key' => 'nama', 'label' => 'Nama', 'tipe' => 'text', 'sumber' => 'auto:penerima_nama'),
					array('key' => 'nip', 'label' => 'NIP', 'tipe' => 'text', 'sumber' => 'auto:penerima_nip'),
					array('key' => 'jabatan', 'label' => 'Jabatan', 'tipe' => 'text', 'sumber' => 'auto:penerima_jabatan'),
					array('key' => 'spd_tgl', 'label' => 'Tgl SPD/Surat Tugas', 'tipe' => 'date', 'sumber' => 'manual'),
					array('key' => 'spd_no', 'label' => 'No. SPD/Surat Tugas', 'tipe' => 'text', 'sumber' => 'auto:no_surat'),
					array('key' => 'rincian', 'label' => 'Rincian pengeluaran', 'tipe' => 'rows', 'sumber' => 'manual', 'kolom' => array(
						array('key' => 'uraian', 'label' => 'Uraian', 'tipe' => 'text'),
						array('key' => 'tarif', 'label' => 'Tarif (Rp)', 'tipe' => 'uang'),
						array('key' => 'satuan', 'label' => 'Satuan', 'tipe' => 'text'),
						array('key' => 'jumlah', 'label' => 'Jumlah (Rp)', 'tipe' => 'uang'),
						array('key' => 'ket', 'label' => 'Keterangan', 'tipe' => 'text'),
					)),
					array('key' => 'ppk_nama', 'label' => 'Nama PPK', 'tipe' => 'text', 'sumber' => 'auto:ppk_nama'),
					array('key' => 'ppk_nip', 'label' => 'NIP PPK', 'tipe' => 'text', 'sumber' => 'auto:ppk_nip'),
					array('key' => 'tempat', 'label' => 'Tempat', 'tipe' => 'text', 'sumber' => 'const:kota'),
					array('key' => 'bulan_tahun', 'label' => 'Bulan/Tahun', 'tipe' => 'text', 'sumber' => 'manual'),
				),
			),

			'spd' => array(
				'nama'    => 'Surat Perjalanan Dinas (SPD)',
				'rangkap' => 'per_penerima',
				'fields'  => array(
					array('key' => 'lembar_ke', 'label' => 'Lembar Ke', 'tipe' => 'text', 'sumber' => 'manual'),
					array('key' => 'kode', 'label' => 'Kode', 'tipe' => 'text', 'sumber' => 'auto:kode_output'),
					array('key' => 'nomor', 'label' => 'Nomor', 'tipe' => 'text', 'sumber' => 'auto:no_surat'),
					array('key' => 'ppk_nama', 'label' => 'Nama PPK', 'tipe' => 'text', 'sumber' => 'auto:ppk_nama'),
					array('key' => 'ppk_nip', 'label' => 'NIP PPK', 'tipe' => 'text', 'sumber' => 'auto:ppk_nip'),
					array('key' => 'pegawai_nama', 'label' => 'Nama pegawai', 'tipe' => 'text', 'sumber' => 'auto:penerima_nama'),
					array('key' => 'pegawai_nip', 'label' => 'NIP pegawai', 'tipe' => 'text', 'sumber' => 'auto:penerima_nip'),
					array('key' => 'pangkat_gol', 'label' => 'Pangkat / Golongan', 'tipe' => 'text', 'sumber' => 'auto:penerima_gol'),
					array('key' => 'jabatan', 'label' => 'Jabatan / Instansi', 'tipe' => 'text', 'sumber' => 'auto:penerima_jabatan'),
					array('key' => 'maksud', 'label' => 'Maksud perjalanan dinas', 'tipe' => 'textarea', 'sumber' => 'auto:judul'),
					array('key' => 'alat_angkut', 'label' => 'Alat angkutan', 'tipe' => 'text', 'sumber' => 'manual'),
					array('key' => 'tempat_berangkat', 'label' => 'Tempat berangkat', 'tipe' => 'text', 'sumber' => 'const:kota'),
					array('key' => 'tempat_tujuan', 'label' => 'Tempat tujuan', 'tipe' => 'text', 'sumber' => 'manual'),
					array('key' => 'lama_hari', 'label' => 'Lama perjalanan (hari)', 'tipe' => 'number', 'sumber' => 'manual'),
					array('key' => 'tgl_berangkat', 'label' => 'Tgl berangkat', 'tipe' => 'date', 'sumber' => 'manual'),
					array('key' => 'tgl_kembali', 'label' => 'Tgl kembali', 'tipe' => 'date', 'sumber' => 'manual'),
					array('key' => 'pengikut', 'label' => 'Pengikut', 'tipe' => 'rows', 'sumber' => 'manual', 'kolom' => array(
						array('key' => 'nama', 'label' => 'Nama', 'tipe' => 'text'),
						array('key' => 'tgl_lahir', 'label' => 'Tanggal lahir', 'tipe' => 'text'),
						array('key' => 'ket', 'label' => 'Keterangan', 'tipe' => 'text'),
					)),
					array('key' => 'pembebanan_instansi', 'label' => 'Pembebanan - Instansi', 'tipe' => 'text', 'sumber' => 'auto:pembebanan'),
					array('key' => 'dipa_no', 'label' => 'No. DIPA', 'tipe' => 'text', 'sumber' => 'const:dipa_no'),
					array('key' => 'kode_anggaran', 'label' => 'Kode anggaran', 'tipe' => 'text', 'sumber' => 'auto:kode_output'),
					array('key' => 'no_surat_tugas', 'label' => 'No. Surat Tugas', 'tipe' => 'text', 'sumber' => 'auto:no_surat'),
					array('key' => 'tgl_surat_tugas', 'label' => 'Tgl Surat Tugas', 'tipe' => 'date', 'sumber' => 'manual'),
					array('key' => 'dikeluarkan_tempat', 'label' => 'Dikeluarkan di', 'tipe' => 'text', 'sumber' => 'const:kota'),
					array('key' => 'dikeluarkan_tgl', 'label' => 'Tanggal dikeluarkan', 'tipe' => 'date', 'sumber' => 'manual'),
				),
			),

			'sptjb' => array(
				'nama'    => 'Surat Pernyataan Tanggung Jawab Belanja (SPTJB)',
				'rangkap' => 'per_kegiatan',
				'catatan' => 'Konfirmasi dulu: apakah SPTJB di satker ini keluaran SAKTI? Jika ya, dokumen ini tidak perlu form_inapp.',
				'fields'  => array(
					array('key' => 'nomor', 'label' => 'Nomor', 'tipe' => 'text', 'sumber' => 'manual'),
					array('key' => 'kode_satker', 'label' => 'Kode Satker/Program', 'tipe' => 'text', 'sumber' => 'const:satker_kode'),
					array('key' => 'nama_satker', 'label' => 'Nama Satuan Kerja', 'tipe' => 'text', 'sumber' => 'const:satker_nama'),
					array('key' => 'dipa_tgl_no', 'label' => 'Tanggal & No DIPA', 'tipe' => 'text', 'sumber' => 'auto:dipa_tgl_no'),
					array('key' => 'klasifikasi_anggaran', 'label' => 'Klasifikasi Anggaran', 'tipe' => 'text', 'sumber' => 'manual'),
					array('key' => 'baris', 'label' => 'Rincian', 'tipe' => 'rows', 'sumber' => 'manual', 'kolom' => array(
						array('key' => 'akun', 'label' => 'Akun', 'tipe' => 'text'),
						array('key' => 'penerima', 'label' => 'Penerima', 'tipe' => 'text'),
						array('key' => 'uraian', 'label' => 'Uraian', 'tipe' => 'text'),
						array('key' => 'jumlah', 'label' => 'Jumlah (Rp)', 'tipe' => 'uang'),
						array('key' => 'ppn', 'label' => 'PPN', 'tipe' => 'uang'),
						array('key' => 'pph', 'label' => 'PPh', 'tipe' => 'uang'),
					)),
					array('key' => 'ppk_nama', 'label' => 'Nama PPK', 'tipe' => 'text', 'sumber' => 'auto:ppk_nama'),
					array('key' => 'ppk_nip', 'label' => 'NIP PPK', 'tipe' => 'text', 'sumber' => 'auto:ppk_nip'),
					array('key' => 'tempat', 'label' => 'Tempat', 'tipe' => 'text', 'sumber' => 'const:kota'),
					array('key' => 'tanggal', 'label' => 'Tanggal', 'tipe' => 'date', 'sumber' => 'manual'),
				),
			),

			// Check List Kelengkapan Dokumen & Kartu Kendali (1 Halaman Terpadu)
			// Sumber: Screenshot Real User "KARTU KENDALI PERJADIN/HONORARIUM TA 2026"
			'lembar_periksa' => array(
				'nama'    => 'Kartu Kendali & Check List Kelengkapan',
				'rangkap' => 'per_kegiatan',
				'fields'  => array(
					array('key' => 'no_surat_tugas_tgl', 'label' => 'Tgl. Surat / No. Surat Tugas / SK', 'tipe' => 'text',     'sumber' => 'auto:st_tgl_no'),
					array('key' => 'no_sptb_spm',         'label' => 'NO. SPTB/ SPM',                     'tipe' => 'text',     'sumber' => 'auto:no_sptb_spm'),
					array('key' => 'judul_kegiatan',      'label' => 'Judul Kegiatan',                    'tipe' => 'textarea', 'sumber' => 'auto:judul'),
					array('key' => 'petugas_perjadin',    'label' => 'Nama Petugas Perjadin',             'tipe' => 'textarea', 'sumber' => 'auto:pelaksana_list'),
				),
				'checklist' => array(
					// Seksi I: Kelengkapan Berkas (13 item, kolom ADA/TIDAK/KETERANGAN)
					'kelengkapan' => array(
						'judul' => 'KELENGKAPAN BERKAS',
						'kolom' => array('ADA', 'TIDAK', 'KETERANGAN'),
						'item'  => array(
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
						),
					),
					// Seksi II: Verifikasi Kesesuaian Isi (3 item, kolom SESUAI/TIDAK/KETERANGAN)
					'verifikasi' => array(
						'judul' => 'VERIFIKASI KESESUAIAN ISI',
						'kolom' => array('SESUAI', 'TIDAK', 'KETERANGAN'),
						'item'  => array(
							'Daftar Riil',
							'Kwitansi',
							'Daftar Nominatif',
						),
					),
				),
				'alur_steps' => array(
					'Petugas pelaksana kegiatan menyerahkan dokumen ke staf PPK',
					'Staf PPK menyerahkan dokumen yang telah di verifikasi ke Pembuat SPP/SPM',
					'Petugas SPP/SPM menyerahkan dokumen SPP/SPM serta data dukungnya ke Verifikator',
					'Verifikator menyerahkan berkas SPP/SPM dan data dukung yang telah di verifikasi untuk diperiksa dan di setujui PPK',
					'PPK memberikan berkas yang telah diperiksa dan Disetujui (TTE) ke PPSPM',
					'Petugas SPM membuat SPM dan menyerahkan SPM dan kelengkapan data dukung pencairan ke PPSPM untuk diperiksa dan Disetujui (TTE)',
					'PPSPM memeriksa dan Menyetujui (TTE) SPM serta mengembalikan berkas ke petugas SPM',
				),
			),

			// Alias / template kartu_kendali terpadu 1 halaman
			'kartu_kendali' => array(
				'nama'    => 'Kartu Kendali TU',
				'rangkap' => 'per_kegiatan',
				'fields'  => array(
					array('key' => 'no_surat_tugas_tgl', 'label' => 'Tgl. Surat / No. Surat Tugas / SK', 'tipe' => 'text',     'sumber' => 'auto:st_tgl_no'),
					array('key' => 'no_sptb_spm',         'label' => 'NO. SPTB/ SPM',                     'tipe' => 'text',     'sumber' => 'auto:no_sptb_spm'),
					array('key' => 'judul_kegiatan',      'label' => 'Judul Kegiatan',                    'tipe' => 'textarea', 'sumber' => 'auto:judul'),
					array('key' => 'petugas_perjadin',    'label' => 'Nama Petugas Perjadin',             'tipe' => 'textarea', 'sumber' => 'auto:pelaksana_list'),
				),
				'checklist' => array(
					'kelengkapan' => array(
						'judul' => 'KELENGKAPAN BERKAS',
						'kolom' => array('ADA', 'TIDAK', 'KETERANGAN'),
						'item'  => array(
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
						),
					),
					'verifikasi' => array(
						'judul' => 'VERIFIKASI KESESUAIAN ISI',
						'kolom' => array('SESUAI', 'TIDAK', 'KETERANGAN'),
						'item'  => array(
							'Daftar Riil',
							'Kwitansi',
							'Daftar Nominatif',
						),
					),
				),
				'alur_steps' => array(
					'Petugas pelaksana kegiatan menyerahkan dokumen ke staf PPK',
					'Staf PPK menyerahkan dokumen yang telah di verifikasi ke Pembuat SPP/SPM',
					'Petugas SPP/SPM menyerahkan dokumen SPP/SPM serta data dukungnya ke Verifikator',
					'Verifikator menyerahkan berkas SPP/SPM dan data dukung yang telah di verifikasi untuk diperiksa dan di setujui PPK',
					'PPK memberikan berkas yang telah diperiksa dan Disetujui (TTE) ke PPSPM',
					'Petugas SPM membuat SPM dan menyerahkan SPM dan kelengkapan data dukung pencairan ke PPSPM untuk diperiksa dan Disetujui (TTE)',
					'PPSPM memeriksa dan Menyetujui (TTE) SPM serta mengembalikan berkas ke petugas SPM',
				),
			),
		);

		// --- Metadata alur: metode, tahap pembuat, slot ttd, cap, urutan cetak ---
		$meta = array(
			// kode            metode           dibuat_oleh     slot_ttd                  cap    urut
			// Sesuai pembagian nyata (lihat GDRIVE/PJ/): PJ-Kegiatan menyiapkan
			// SEMUA dokumen awal (SPD, Kwitansi, Nominatif, Daftar Riil, SPTJB)
			// pada tahap pertama. Verifikator baru mengisi Lembar Periksa saat
			// gilirannya (tahap VRF2). Tahap PPK-Staff/SPM/PPK/PPSPM meneruskan
			// alur & menandatangani, tidak mengisi ulang dokumen form_inapp ini.
			'kartu_kendali'  => array('kartu_kendali', 'auto',          array(),                  false,  1),
			'kwitansi'       => array('form_inapp',    'PJ-Kegiatan',   array('ppk', 'penerima'), true,  10),
			'spd'            => array('form_inapp',    'PJ-Kegiatan',   array('ppk'),             false, 20),
			'nominatif'      => array('form_inapp',    'PJ-Kegiatan',   array('ppk'),             true,  30),
			'riil'           => array('form_inapp',    'PJ-Kegiatan',   array('ppk', 'penerima'), true,  40),
			'sptjb'          => array('form_inapp',    'PJ-Kegiatan',   array('ppk'),             true,  50),
			'lembar_periksa' => array('checklist',     'Verifikator',   array(),                  false, 60),
		);
		foreach ($t as $kode => &$row) {
			$m = isset($meta[$kode]) ? $meta[$kode] : array('form_inapp', 'PPK-Staff', array('ppk'), false, 99);
			$row['metode']      = $m[0];
			$row['dibuat_oleh'] = $m[1];
			$row['slot_ttd']    = $m[2];
			$row['butuh_cap']   = $m[3];
			$row['urut']        = $m[4];
			$row['jenis']       = array(1); // perjadin; jenis lain menyusul
		}
		unset($row);

		return $t;
	}

	/** Daftar template yang berlaku untuk sebuah jenis pengajuan (default perjadin=1). */
	public function templatesForJenis($jenisID = 1)
	{
		$jenisID = (int) $jenisID ?: 1;
		$out = array();
		foreach ($this->templates() as $kode => $tpl) {
			if (in_array($jenisID, (array) $tpl['jenis'], true)) $out[$kode] = $tpl;
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
		if (empty($keg)) return null;
		$jenisID = (!empty($keg['KegiatanJenisID'])) ? (int) $keg['KegiatanJenisID'] : 1;
		$status  = isset($keg['KegiatanStatus']) ? $keg['KegiatanStatus'] : '';

		if ($status === '' || $status === 'editable') {
			return 1;
		}
		if ($status === 'Approval OnProgress' || $status === 'Perlu Revisi') {
			$this->load->model('M_Manajemen_approval');
			$rows = $this->M_Manajemen_approval->GetLastStatus((int) $KegiatanID);
			return !empty($rows[0]['FlowOrder']) ? (int) $rows[0]['FlowOrder'] : 1;
		}
		$max = $this->db->select_max('FlowOrder', 'mx')->get_where('tb_approval_flow', array('JenisID' => $jenisID))->row_array();
		return (!empty($max['mx']) ? (int) $max['mx'] : 6) + 1;
	}

	/** FlowOrder pertama (terkecil, tahap aktif bukan dorman) milik sebuah posisi. */
	private function _flowOrderOfRole($role, $jenisID = 1)
	{
		if ($role === '' || $role === 'auto') return 0;
		$r = $this->db->select_min('FlowOrder', 'mn')
			->get_where('tb_approval_flow', array('FlowPosition' => $role, 'JenisID' => (int) $jenisID, 'FlowOrder >' => 0))
			->row_array();
		return !empty($r['mn']) ? (int) $r['mn'] : 0;
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
		if (!$tpl) return array('lihat' => false, 'isi' => false);

		$dibuatOleh = $tpl['dibuat_oleh'];
		if ($dibuatOleh === 'auto') return array('lihat' => true, 'isi' => false);
		if ($viewerPosition === 'SuperAdmin') return array('lihat' => true, 'isi' => true);

		$keg = $this->kegiatan((int) $KegiatanID);
		if (empty($keg)) return array('lihat' => false, 'isi' => false);
		$jenisID = (!empty($keg['KegiatanJenisID'])) ? (int) $keg['KegiatanJenisID'] : 1;
		$status  = isset($keg['KegiatanStatus']) ? $keg['KegiatanStatus'] : '';

		$stageAktif   = $this->stageAktif($KegiatanID);
		$stageDokumen = $this->_flowOrderOfRole($dibuatOleh, $jenisID);

		if ($stageAktif === null || $stageDokumen === 0 || $stageDokumen > $stageAktif) {
			return array('lihat' => false, 'isi' => false);
		}

		// Jendela isi: dari tahap pemilik dokumen ini sampai SEBELUM tahap
		// pemilik-dokumen-LAIN berikutnya mulai -- bukan cuma "persis satu
		// tahap". Perlu begini karena tahap PJK selalu langsung FlowResult=1
		// otomatis begitu kegiatan diajukan (lihat M_Manajemen_approval); kalau
		// disyaratkan "stageAktif == stageDokumen" persis, PJ kehilangan akses
		// isi SESAAT setelah mengajukan, sebelum sempat mengisi apa pun.
		$batasAtas = $this->_stageBerikutnyaBerbedaOwner($stageDokumen, $jenisID);
		$sedangBerjalan = in_array($status, array('editable', 'Approval OnProgress', 'Perlu Revisi'), true);
		$isi = $sedangBerjalan && $viewerPosition === $dibuatOleh
			&& ($batasAtas === null || $stageAktif < $batasAtas);

		return array('lihat' => true, 'isi' => $isi);
	}

	/** FlowOrder tahap pemilik-dokumen-LAIN pertama setelah $stageDokumen (null = tak ada / sampai akhir). */
	private function _stageBerikutnyaBerbedaOwner($stageDokumen, $jenisID)
	{
		$stages = array();
		foreach ($this->templatesForJenis($jenisID) as $t) {
			if ($t['dibuat_oleh'] === 'auto') continue;
			$fo = $this->_flowOrderOfRole($t['dibuat_oleh'], $jenisID);
			if ($fo > 0) $stages[$fo] = true;
		}
		$stages = array_keys($stages);
		sort($stages);
		foreach ($stages as $s) {
			if ($s > $stageDokumen) return $s;
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
		$KegiatanID = (int) $KegiatanID;
		$keg = $this->kegiatan($KegiatanID);
		$jenisID = isset($keg['KegiatanJenisID']) && (int) $keg['KegiatanJenisID'] > 0 ? (int) $keg['KegiatanJenisID'] : 1;
		$rangkapNames = $this->rangkapList($KegiatanID);

		$saved = array();
		if ($this->ensureTable()) {
			foreach ($this->db->get_where('tb_dokumen', array('KegiatanID' => $KegiatanID))->result_array() as $r) {
				$saved[$r['Kode']][$r['RangkapKey']] = $r;
			}
		}

		$out = array();
		foreach ($this->templatesForJenis($jenisID) as $kode => $tpl) {
			$perPenerima = (isset($tpl['rangkap']) && $tpl['rangkap'] === 'per_penerima');
			$keys = $perPenerima ? ($rangkapNames ?: array()) : array('-');
			$rangkap = array();
			foreach ($keys as $k) {
				$rangkap[] = array(
					'key'    => $k,
					'label'  => ($k === '-') ? '' : $k,
					'terisi' => isset($saved[$kode][$k]),
					'diperbarui' => isset($saved[$kode][$k]) ? $saved[$kode][$k]['UpdatedAt'] : null,
				);
			}
			$akses = ($viewerPosition !== null) ? $this->aksesDokumen($kode, $KegiatanID, $viewerPosition) : array('lihat' => true, 'isi' => true);
			$out[] = array(
				'kode'        => $kode,
				'nama'        => $tpl['nama'],
				'metode'      => $tpl['metode'],
				'per_penerima' => $perPenerima,
				'dibuat_oleh' => $tpl['dibuat_oleh'],
				'butuh_cap'   => !empty($tpl['butuh_cap']),
				'slot_ttd'    => $tpl['slot_ttd'],
				'rangkap'     => $rangkap,
				'ada'         => count(array_filter($rangkap, function ($x) { return $x['terisi']; })),
				'total'       => count($rangkap),
				'boleh_lihat' => $akses['lihat'],
				'boleh_isi'   => $akses['isi'],
			);
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
		$row = array();
		if ($KegiatanID > 0 && $this->db->table_exists('tb_kegiatan')) {
			$q = $this->db->get_where('tb_kegiatan', array('KegiatanID' => $KegiatanID), 1);
			$row = $q ? ($q->row_array() ?: array()) : array();
		}

		$rows = array();
		if ($KegiatanID > 0 && $this->db->table_exists('tb_kegiatan_pelaksana')) {
			$rows = $this->db->order_by('Urut', 'ASC')->order_by('id', 'ASC')
				->get_where('tb_kegiatan_pelaksana', array('KegiatanID' => $KegiatanID))->result_array();
		}
		// Fallback data lama: belum ter-migrasi -> pecah teks bebas.
		if (empty($rows) && !empty($row['KegiatanNamaPelaksana'])) {
			foreach (preg_split('/\s*[;,]\s*/', $row['KegiatanNamaPelaksana'], -1, PREG_SPLIT_NO_EMPTY) as $p) {
				$p = trim($p);
				if ($p !== '') $rows[] = array('Nama' => $p, 'NIP' => '', 'Gol' => '', 'Jabatan' => '', 'Rekening' => '', 'Bank' => '', 'NPWP' => '');
			}
		}

		$row['_pelaksana_rows'] = $rows;
		$row['_pelaksana'] = array();
		foreach ($rows as $r) {
			if (trim((string) $r['Nama']) !== '') $row['_pelaksana'][] = trim($r['Nama']);
		}
		return $row;
	}

	/** Baris pelaksana yang cocok dengan sebuah rangkap (nama penerima). */
	private function pelaksanaRow($keg, $rangkapKey)
	{
		$rows = isset($keg['_pelaksana_rows']) ? $keg['_pelaksana_rows'] : array();
		if ($rangkapKey !== '-' && $rangkapKey !== '') {
			foreach ($rows as $r) {
				if (trim((string) $r['Nama']) === trim((string) $rangkapKey)) return $r;
			}
		}
		return isset($rows[0]) ? $rows[0] : array();
	}

	private function ppkFor($kodeOutput)
	{
		$kodeOutput = (string) $kodeOutput;
		$map = $this->ppkMap();
		foreach ($map as $prefix => $ppk) {
			if ($prefix !== '_default' && stripos($kodeOutput, $prefix) === 0) return $ppk;
		}
		return isset($map['_default']) ? $map['_default'] : array('nama' => '', 'nip' => '', 'label' => 'PPK');
	}

	/**
	 * Nilai auto untuk sebuah rangkap. $rangkapKey = nama penerima (untuk
	 * dokumen per_penerima) atau '-' (per_kegiatan).
	 */
	public function autofill($kode, $KegiatanID, $rangkapKey = '-')
	{
		$keg = $this->kegiatan($KegiatanID);
		$kodeOutput = isset($keg['KegiatanKodeOutput']) ? (string) $keg['KegiatanKodeOutput'] : '';
		$ppk = $this->ppkFor($kodeOutput);
		$pr  = $this->pelaksanaRow($keg, $rangkapKey);
		$penerima = ($rangkapKey !== '-' && $rangkapKey !== '') ? $rangkapKey
			: (isset($pr['Nama']) ? $pr['Nama'] : (isset($keg['_pelaksana'][0]) ? $keg['_pelaksana'][0] : ''));

		$konst = $this->konst();
		// Auto-fill kartu_kendali: no_urut dari count riwayat, tgl_masuk dari tanggal kirim pertama.
		$noUrut = '';
		$tglMasuk = '';
		if ((int) $KegiatanID > 0 && $this->db->table_exists('tb_approval_history')) {
			$histFirst = $this->db->select('FlowDate')
				->order_by('HistoryID', 'ASC')
				->get_where('tb_approval_history', array('KegiatanID' => (int) $KegiatanID), 1)->row_array();
			if (!empty($histFirst['FlowDate'])) $tglMasuk = substr($histFirst['FlowDate'], 0, 10);
			// no_urut: urutan kegiatan ini di antara semua kegiatan (KegiatanID-rank sederhana)
			$r = $this->db->select('COUNT(*) as cnt')
				->where('KegiatanID <=', (int) $KegiatanID)
				->where('KegiatanDeletedAt IS NULL', null, false)
				->get('tb_kegiatan')->row_array();
			$noUrut = !empty($r['cnt']) ? (string)(int)$r['cnt'] : '';
		}
		// Format ST: no surat / tanggal
		$noSt  = isset($keg['KegiatanNoSuratTugas']) ? trim($keg['KegiatanNoSuratTugas']) : '';
		$tglSt = !empty($keg['KegiatanTanggal']) ? tgl_ind($keg['KegiatanTanggal']) : '';
		$stTglNo = $noSt . ($tglSt ? (' / ' . $tglSt) : '');

		// Pelaksana joined per baris
		$pelaksanaList = '';
		if (!empty($keg['_pelaksana']) && is_array($keg['_pelaksana'])) {
			$pelaksanaList = implode("\n", $keg['_pelaksana']);
		} else {
			$pelaksanaList = isset($keg['KegiatanNamaPelaksana']) ? str_replace(array(';', ','), "\n", $keg['KegiatanNamaPelaksana']) : '';
		}

		// Alur check 7 tahapan approval (identifikasi kemajuan alur ASIKKEKKU)
		$alurCheck = array(0, 0, 0, 0, 0, 0, 0);
		if ((int) $KegiatanID > 0 && $this->db->table_exists('tb_approval_history')) {
			$hist = $this->db->order_by('HistoryID', 'ASC')
				->get_where('tb_approval_history', array('KegiatanID' => (int) $KegiatanID))->result_array();

			$hasPjk = false;
			$hasPpks = false;
			$hasSpp = false;
			$hasVrf = false;
			$hasPpk = false;
			$hasSpmAfterPpk = false;
			$hasPpspm = false;
			$ppkFound = false;

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
				if (($code === 'SPP1' || ($code === 'SPM1' && !$ppkFound)) && $res === 1) {
					$hasSpp = true;
				}
				// 4. Verifikator -> PPK (Verifikator menyetujui dokumen/SPP)
				if (($code === 'VRF2' || $code === 'VRF1') && $res === 1 && ($hasPpks || $hasSpp)) {
					$hasVrf = true;
				}
				// 5. PPK -> PPSPM (PPK menyetujui / TTE)
				if ($code === 'PPK1' && $res === 1) {
					$hasPpk = true;
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
			if ($hasPpks) $alurCheck[1] = 1;
			if ($hasSpp) $alurCheck[2] = 1;
			if ($hasVrf) $alurCheck[3] = 1;
			if ($hasPpk) $alurCheck[4] = 1;
			if ($hasSpmAfterPpk) $alurCheck[5] = 1;
			if ($hasPpspm) $alurCheck[6] = 1;

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

		$map = array(
			'judul'          => isset($keg['KegiatanJudul']) ? $keg['KegiatanJudul'] : '',
			'no_surat'       => isset($keg['KegiatanNoSuratTugas']) ? $keg['KegiatanNoSuratTugas'] : '',
			'tgl_kegiatan'   => isset($keg['KegiatanTanggal']) ? $keg['KegiatanTanggal'] : '',
			'st_tgl_no'      => $stTglNo,
			'no_sptb_spm'    => !empty($keg['KegiatanNoSPTJB']) ? $keg['KegiatanNoSPTJB'] : (!empty($keg['KegiatanNoKwitansi']) ? $keg['KegiatanNoKwitansi'] : '1'),
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
			'no_urut'    => $noUrut,
			'tgl_masuk'  => $tglMasuk,
		);

		$out = array();
		$tpl = $this->template($kode);
		if (!$tpl) return $out;
		foreach ($tpl['fields'] as $f) {
			if (strpos($f['sumber'], 'auto:') === 0) {
				$k = substr($f['sumber'], 5);
				$out[$f['key']] = isset($map[$k]) ? $map[$k] : '';
			} elseif (strpos($f['sumber'], 'const:') === 0) {
				$k = substr($f['sumber'], 6);
				$out[$f['key']] = isset($konst[$k]) ? $konst[$k] : '';
			}
		}

		// Khusus lembar_periksa dan kartu_kendali
		if ($kode === 'lembar_periksa' || $kode === 'kartu_kendali') {
			$savedDocs = array();
			if ($this->ensureTable()) {
				$q = $this->db->select('Kode')->where('KegiatanID', (int) $KegiatanID)->get('tb_dokumen')->result_array();
				foreach ($q as $r) { $savedDocs[$r['Kode']] = true; }
			}
			$uploadedTypes = array();
			if ($this->db->table_exists('tb_dok_upload')) {
				$q2 = $this->db->select('Tipe')->where('KegiatanID', (int) $KegiatanID)->get('tb_dok_upload')->result_array();
				foreach ($q2 as $r2) { $uploadedTypes[strtolower($r2['Tipe'])] = true; }
			}
			$hasST = !empty($keg['KegiatanNoSuratTugas']) || !empty($keg['KegiatanLampiran']) || isset($uploadedTypes['st']) || isset($uploadedTypes['surat_tugas']);
			$hasLPD = isset($uploadedTypes['lpd']);
			$hasSPD = isset($savedDocs['spd']);
			$hasKwitansi = isset($savedDocs['kwitansi']);
			$hasNominatif = isset($savedDocs['nominatif']);
			$hasRiil = isset($savedDocs['riil']);

			$items = array('kelengkapan' => array(), 'verifikasi' => array());
			// 13 item kelengkapan berkas:
			$items['kelengkapan'][0]  = array('status' => $hasST ? 'ya' : '', 'ket' => ''); // ST
			$items['kelengkapan'][1]  = array('status' => '',   'ket' => ''); // Laporan Kegiatan
			$items['kelengkapan'][2]  = array('status' => '',   'ket' => ''); // Daftar Absensi
			$items['kelengkapan'][3]  = array('status' => $hasSPD ? 'ya' : '', 'ket' => ''); // SPD
			$items['kelengkapan'][4]  = array('status' => 'ya', 'ket' => ''); // Tiket Transportasi
			$items['kelengkapan'][5]  = array('status' => 'ya', 'ket' => ''); // Boarding Pass
			$items['kelengkapan'][6]  = array('status' => 'ya', 'ket' => ''); // Kuitansi Penginapan
			$items['kelengkapan'][7]  = array('status' => '',   'ket' => ''); // Kwitansi Taksi / BBM
			$items['kelengkapan'][8]  = array('status' => $hasLPD ? 'ya' : '', 'ket' => ''); // LPD
			$items['kelengkapan'][9]  = array('status' => $hasNominatif ? 'ya' : '', 'ket' => ''); // Nominatif
			$items['kelengkapan'][10] = array('status' => $hasKwitansi ? 'ya' : '', 'ket' => ''); // Kwitansi
			$items['kelengkapan'][11] = array('status' => $hasRiil ? 'ya' : '', 'ket' => ''); // Daftar Riil
			$items['kelengkapan'][12] = array('status' => '',   'ket' => ''); // SSP

			// 3 item verifikasi kesesuaian isi (default SESUAI):
			$items['verifikasi'][0] = array('status' => 'ya', 'ket' => ''); // Daftar Riil
			$items['verifikasi'][1] = array('status' => 'ya', 'ket' => ''); // Kwitansi
			$items['verifikasi'][2] = array('status' => 'ya', 'ket' => ''); // Daftar Nominatif

			$out['items']      = $items;
			$out['alur_check'] = $alurCheck;
		}

		return $out;
	}

	/** Daftar rangkap (penerima) untuk sebuah kegiatan. */
	public function rangkapList($KegiatanID)
	{
		$keg = $this->kegiatan($KegiatanID);
		$list = !empty($keg['_pelaksana']) ? $keg['_pelaksana'] : array();
		return $list ?: array();
	}

	/* ======================================================================
	   SIMPAN / MUAT PAYLOAD
	   ====================================================================== */

	private function ensureTable()
	{
		return $this->db->table_exists('tb_dokumen');
	}

	/** Payload tergabung: default (auto/const) <- tersimpan. */
	public function load($kode, $KegiatanID, $rangkapKey = '-')
	{
		$auto = $this->autofill($kode, $KegiatanID, $rangkapKey);
		$saved = array();
		if ($this->ensureTable()) {
			$q = $this->db->get_where('tb_dokumen', array(
				'KegiatanID' => (int) $KegiatanID, 'Kode' => $kode, 'RangkapKey' => $rangkapKey ?: '-',
			), 1);
			$r = $q ? $q->row_array() : null;
			if ($r && !empty($r['PayloadJson'])) {
				$saved = json_decode($r['PayloadJson'], true) ?: array();
			}
		}
		$out = array_merge($auto, $saved);
		if (isset($saved['items']) && is_array($saved['items']) && !empty($saved['items'])) {
			$out['items'] = $saved['items'];
		}
		if (isset($saved['alur_check']) && is_array($saved['alur_check'])) {
			$out['alur_check'] = $saved['alur_check'];
		}
		return $out;
	}

	public function save($kode, $KegiatanID, $rangkapKey, array $payload, $userID = null)
	{
		if (!$this->ensureTable()) {
			return array('ok' => false, 'msg' => 'Tabel tb_dokumen belum ada. Jalankan assets/sql/2026-09-04_dokumen.sql.');
		}
		$rangkapKey = $rangkapKey ?: '-';
		$row = array(
			'KegiatanID'    => (int) $KegiatanID,
			'Kode'          => $kode,
			'RangkapKey'    => $rangkapKey,
			'PayloadJson'   => json_encode($payload, JSON_UNESCAPED_UNICODE),
			'TemplateVersi' => self::TEMPLATE_VERSI,
			'UpdatedBy'     => $userID ? (int) $userID : null,
			'UpdatedAt'     => date('Y-m-d H:i:s'),
		);
		$row['PayloadHash'] = $this->_hashPayload($payload);
		$ada = $this->db->get_where('tb_dokumen', array(
			'KegiatanID' => (int) $KegiatanID, 'Kode' => $kode, 'RangkapKey' => $rangkapKey,
		), 1)->row_array();
		if ($ada) {
			$this->db->where('DokumenID', $ada['DokumenID'])->update('tb_dokumen', $row);
		} else {
			$this->db->insert('tb_dokumen', $row);
		}

		// Dokumen berubah -> hanguskan tanda tangan yang terikat ke versi lama.
		$hangus = $this->ttdInvalidateOnChange($KegiatanID, $kode, $rangkapKey);
		return array('ok' => true, 'msg' => 'Tersimpan.' . ($hangus ? ' ' . $hangus . ' tanda tangan dihanguskan (dokumen berubah).' : ''));
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
		if (!$this->db->table_exists('tb_dokumen_ttd')) return array();
		$rows = $this->db->where(array(
			'KegiatanID' => (int) $KegiatanID, 'Kode' => $kode,
			'RangkapKey' => $rangkapKey ?: '-', 'InvalidatedAt' => null,
		))->order_by('TtdID', 'DESC')->get('tb_dokumen_ttd')->result_array();
		$out = array();
		foreach ($rows as $r) { if (!isset($out[$r['Slot']])) $out[$r['Slot']] = $r; }
		return $out;
	}

	/** Semua ttd aktif untuk sebuah kegiatan (untuk panel). */
	public function ttdAktifKegiatan($KegiatanID)
	{
		if (!$this->db->table_exists('tb_dokumen_ttd')) return array();
		$rows = $this->db->where(array('KegiatanID' => (int) $KegiatanID, 'InvalidatedAt' => null))
			->get('tb_dokumen_ttd')->result_array();
		$out = array();
		foreach ($rows as $r) { $out[$r['Kode']][$r['RangkapKey']][$r['Slot']] = $r; }
		return $out;
	}

	/**
	 * Simpan tanda tangan sebuah slot. $img = data PNG mentah (binary).
	 * Menghanguskan ttd aktif slot yang sama lebih dulu (satu aktif per slot).
	 */
	public function ttdSimpan($KegiatanID, $kode, $rangkapKey, $slot, $img, $signer)
	{
		if (!$this->db->table_exists('tb_dokumen_ttd')) {
			return array('ok' => false, 'msg' => 'Tabel tb_dokumen_ttd belum ada. Jalankan migrasi.');
		}
		$KegiatanID = (int) $KegiatanID;
		$rangkapKey = $rangkapKey ?: '-';
		$slot = preg_replace('/[^a-z0-9_]/', '', strtolower($slot));
		if ($slot === '') return array('ok' => false, 'msg' => 'Slot tidak valid.');
		if (strncmp($img, "\x89PNG\r\n\x1a\n", 8) !== 0) {
			return array('ok' => false, 'msg' => 'Gambar tanda tangan harus PNG.');
		}
		if (strlen($img) > 800 * 1024) return array('ok' => false, 'msg' => 'Gambar tanda tangan terlalu besar (maks 800 KB).');

		$dir = FCPATH . 'assets/ttd/' . $KegiatanID . '/';
		if (!is_dir($dir)) @mkdir($dir, 0775, true);
		$safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $kode . '_' . $rangkapKey . '_' . $slot);
		$fname = $safe . '_' . time() . '.png';
		if (@file_put_contents($dir . $fname, $img) === false) {
			return array('ok' => false, 'msg' => 'Gagal menyimpan berkas tanda tangan.');
		}
		$rel = 'assets/ttd/' . $KegiatanID . '/' . $fname;

		$this->db->where(array('KegiatanID' => $KegiatanID, 'Kode' => $kode, 'RangkapKey' => $rangkapKey, 'Slot' => $slot, 'InvalidatedAt' => null))
			->update('tb_dokumen_ttd', array('InvalidatedAt' => date('Y-m-d H:i:s'), 'InvalidatedReason' => 'diganti tanda tangan baru'));

		$this->db->insert('tb_dokumen_ttd', array(
			'KegiatanID'   => $KegiatanID,
			'Kode'         => $kode,
			'RangkapKey'   => $rangkapKey,
			'Slot'         => $slot,
			'SignerUserID' => !empty($signer['user_id']) ? (int) $signer['user_id'] : null,
			'SignerNama'   => isset($signer['nama']) ? $signer['nama'] : null,
			'SignerRole'   => isset($signer['role']) ? $signer['role'] : null,
			'ImagePath'    => $rel,
			'DocHash'      => $this->docHash($kode, $KegiatanID, $rangkapKey),
			'SignedAt'     => date('Y-m-d H:i:s'),
			'SignedIP'     => isset($signer['ip']) ? $signer['ip'] : null,
		));
		return array('ok' => true, 'msg' => 'Tanda tangan tersimpan.');
	}

	/** Path relatif gambar cap dinas (tb_vrbl 'dok_cap_image'), atau '' bila belum diunggah. */
	public function capImagePath()
	{
		$r = $this->db->get_where('tb_vrbl', array('VrblName' => 'dok_cap_image'))->row_array();
		$p = !empty($r['VrblValue']) ? trim($r['VrblValue']) : '';
		return ($p !== '' && is_file(FCPATH . $p)) ? $p : '';
	}

	/** Setel / hapus path cap dinas + catat siapa & kapan (tb_vrbl). */
	public function capSet($relPath, $userID = null)
	{
		$set = function ($name, $val) {
			if ($this->db->get_where('tb_vrbl', array('VrblName' => $name))->num_rows() > 0) {
				$this->db->where('VrblName', $name)->update('tb_vrbl', array('VrblValue' => $val));
			} else {
				$this->db->insert('tb_vrbl', array('VrblName' => $name, 'VrblValue' => $val));
			}
		};
		$set('dok_cap_image', (string) $relPath);
		$set('dok_cap_meta', json_encode(array('by' => (int) $userID, 'at' => date('Y-m-d H:i:s'))));
	}

	/** Hanguskan ttd aktif yang DocHash-nya tidak lagi cocok dengan versi dokumen sekarang. */
	public function ttdInvalidateOnChange($KegiatanID, $kode, $rangkapKey = '-')
	{
		if (!$this->db->table_exists('tb_dokumen_ttd')) return 0;
		$rangkapKey = $rangkapKey ?: '-';
		$hashNow = $this->docHash($kode, $KegiatanID, $rangkapKey);
		$this->db->where(array('KegiatanID' => (int) $KegiatanID, 'Kode' => $kode, 'RangkapKey' => $rangkapKey, 'InvalidatedAt' => null))
			->where('DocHash !=', $hashNow)
			->update('tb_dokumen_ttd', array('InvalidatedAt' => date('Y-m-d H:i:s'), 'InvalidatedReason' => 'dokumen diubah'));
		return $this->db->affected_rows();
	}

	/** Gate "Setuju PPK" aktif?  (tb_vrbl 'dok_gate_ppk' = 1). Default OFF. */
	public function dokGatePpkOn()
	{
		$r = $this->db->get_where('tb_vrbl', array('VrblName' => 'dok_gate_ppk'))->row_array();
		return !empty($r) && in_array(strtolower(trim($r['VrblValue'])), array('1', 'on', 'true', 'ya', 'aktif'), true);
	}

	/**
	 * Dokumen yang WAJIB ditandatangani $posisi tapi belum, untuk sebuah kegiatan.
	 * v1: hanya posisi 'PPK' -> slot 'ppk' pada semua form_inapp, per rangkap.
	 * Return: array of {kode, nama, rangkap, label, slot, url_ttd}.
	 */
	public function ttdKurangUntukPosisi($KegiatanID, $posisi)
	{
		$KegiatanID = (int) $KegiatanID;
		if ($posisi !== 'PPK') return array();
		$keg = $this->kegiatan($KegiatanID);
		$jenisID = isset($keg['KegiatanJenisID']) && (int) $keg['KegiatanJenisID'] > 0 ? (int) $keg['KegiatanJenisID'] : 1;
		$rangkapNames = $this->rangkapList($KegiatanID);
		$aktif = $this->ttdAktifKegiatan($KegiatanID);
		$base = base_url();

		$out = array();
		foreach ($this->templatesForJenis($jenisID) as $kode => $tpl) {
			if ($tpl['metode'] !== 'form_inapp') continue;
			if (!in_array('ppk', (array) $tpl['slot_ttd'], true)) continue;
			$perPenerima = (isset($tpl['rangkap']) && $tpl['rangkap'] === 'per_penerima');
			$keys = $perPenerima ? ($rangkapNames ?: array()) : array('-');
			foreach ($keys as $k) {
				if (isset($aktif[$kode][$k]['ppk'])) continue; // sudah ada ttd ppk aktif
				$q = ($k === '-') ? '?' : ('?r=' . rawurlencode($k) . '&');
				$out[] = array(
					'kode'    => $kode,
					'nama'    => $tpl['nama'],
					'rangkap' => $k,
					'label'   => ($k === '-') ? '' : $k,
					'slot'    => 'ppk',
					'url_ttd' => $base . 'dokumen/ttd/' . $kode . '/' . $KegiatanID . $q . 'slot=ppk',
				);
			}
		}
		return $out;
	}

	/** Catat aksi cetak/unduh (log saja, bukan gate). */
	public function printLog($KegiatanID, array $isi, $denganTtd, $userID = null)
	{
		if (!$this->db->table_exists('tb_dokumen_print')) return;
		$this->db->insert('tb_dokumen_print', array(
			'KegiatanID' => (int) $KegiatanID,
			'Batch'      => substr(md5(uniqid('', true)), 0, 12),
			'Isi'        => json_encode(array_values($isi), JSON_UNESCAPED_UNICODE),
			'DenganTtd'  => $denganTtd ? 1 : 0,
			'ByUserID'   => $userID ? (int) $userID : null,
			'AtTime'     => date('Y-m-d H:i:s'),
		));
	}

	/**
	 * Hook: auto-update payload kartu_kendali saat approval maju/mundur.
	 * Memanggil autofill() untuk key 'no_urut' dan 'tgl_masuk', lalu merge
	 * dengan payload yang sudah tersimpan (tidak menimpa field manual).
	 */
	public function kartuKendaliAutoFill($KegiatanID)
	{
		$KegiatanID = (int) $KegiatanID;
		if ($KegiatanID <= 0) return;
		if (!$this->ensureTable()) return;
		$auto = $this->autofill('kartu_kendali', $KegiatanID, '-');
		// Muat payload tersimpan, timpa HANYA key auto (jangan ganggu field manual).
		$saved = array();
		$q = $this->db->get_where('tb_dokumen', array(
			'KegiatanID' => $KegiatanID, 'Kode' => 'kartu_kendali', 'RangkapKey' => '-',
		), 1);
		$r = $q ? $q->row_array() : null;
		if ($r && !empty($r['PayloadJson'])) {
			$saved = json_decode($r['PayloadJson'], true) ?: array();
		}
		// Hanya timpa field auto; biarkan field manual tetap seperti semula.
		foreach ($auto as $k => $v) { $saved[$k] = $v; }
		$this->save('kartu_kendali', $KegiatanID, '-', $saved, null);
	}

	/** Beberapa kegiatan terbaru untuk pemilih. */
	public function kegiatanTerbaru($limit = 40)
	{
		if (!$this->db->table_exists('tb_kegiatan')) return array();
		return $this->db->select('KegiatanID, KegiatanNoSuratTugas, KegiatanJudul, KegiatanTanggal, KegiatanNamaPelaksana')
			->where('KegiatanDeletedAt IS NULL', null, false)
			->order_by('KegiatanID', 'DESC')->get('tb_kegiatan', (int) $limit)->result_array();
	}
}
