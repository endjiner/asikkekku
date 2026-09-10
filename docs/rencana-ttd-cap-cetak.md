# Rencana Build — Tanda Tangan (tangkap‑langsung) + Cap + Cetak dengan/tanpa TTD

**Status:** Fase 0 + A + B + C + D + E **SELESAI & terverifikasi** (smoke/ttd/gate test hijau).

Pasca-fase (2026-09):
- **mPDF terpasang** (`composer install --no-dev --ignore-platform-reqs` → `mpdf/mpdf v8.0.5`,
  jalan di PHP 8.2). `dokumen/unduh/<kode>/<KID>?r=&ttd=0|1` = unduh PDF A4 asli; `dokumen/paketUnduh`
  = gabung beberapa dokumen jadi satu PDF. Tombol "PDF" di panel + "Unduh PDF gabungan" di paket.
  (Dev deps phpunit dll. dibuang saat itu — perlu `composer update` bila mau jalankan test unit.)
- **Gate `ppk` diperketat**: `_ttdBoleh` sekarang cek user = `FlowDestUser` baris riwayat terakhir
  (bukan sekadar berposisi PPK). Controller `Dokumen` di‑gate ke peran alur (`__construct` tolak
  posisi di luar PJ/PPK-Staff/Verifikator/SPP/PPK/SPM/PPSPM/SuperAdmin).
- **SPD halaman 2** ditambahkan (grid tiba/berangkat 4 baris + "Catatan Lain-lain" + "PERHATIAN").

Sisa: penghalusan piksel/kapitalisasi 6 template (butuh perbandingan visual user), jenis
pengajuan lain (butuh keputusan peran baru yang ditunda).
**Terkait:** modul `Dokumen` (`application/controllers/Dokumen.php`, `application/models/M_Dokumen.php`,
`application/views/dokumen/`), alur `JenisID` (sudah dibangun, migrasi `20260908120000`).

---

## 1. Keadaan sekarang

- **Modul Dokumen = demo berdiri sendiri.** Tidak ada di `tb_menu`, tidak tersambung ke alur
  persetujuan. Dibuka manual di `<base_url>/dokumen/pilih`.
- **Penyimpanan:** `tb_dokumen` = `PayloadJson` per `(KegiatanID, Kode, RangkapKey)`. Tidak
  menyimpan file. Tidak ada tabel tanda tangan / kunci versi / log cetak.
- **Renderer:** `views/dokumen/_render.php` → `views/dokumen/tpl/<kode>.php`. Satu renderer untuk
  pratinjau & cetak. Tiap template punya area kosong `<div class="ds-sign"></div>` + nama tercetak
  di bawahnya. Cetak sekarang lewat `window.print()` browser.
- **6 template:** `kwitansi`, `nominatif`, `riil`, `spd`, `sptjb`, `lembar_periksa` — **perkiraan
  HTML ~85%**, belum 100% sesuai form KPPN.
- **Library sudah terpasang** (`composer.json`): `mpdf/mpdf ^8` (HTML→PDF server‑side),
  `setasign/fpdi` (gabung/impor halaman PDF), `phpoffice/phpspreadsheet` (baca `.xlsx`),
  `picqer/php-barcode-generator` (QR strip verifikasi). **Tidak perlu tambah dependensi.**

## 2. Keputusan yang sudah dikunci (dari diskusi)

| Hal | Keputusan |
|---|---|
| Tanda tangan | **Tangkap langsung saat approve** (coret di signature pad ATAU foto baris yang di‑ttd). Diikat ke `{user, dokumen, versi, hash, waktu}` + dicatat. **Bukan** PNG spesimen tersimpan yang dipakai ulang. |
| TTD penerima/pelaksana | Dikumpulkan di **tahap 1** oleh PJ (pelaksana coret di perangkat PJ / PJ unggah foto baris). Mereka bukan peserta alur. |
| Cap dinas | **Satu file scan PNG**, dikelola admin (Konfigurasi Aplikasi), ditempel sistem **hanya menindih TTD PPK**, tiap penempelan dicatat. |
| Aksi tanda tangan | **Terpisah per dokumen**, tapi tombol **"Setuju" dikunci** sampai semua dokumen yang butuh ttd user itu di tahap ini sudah ditandatangani. Ada wizard **"Tanda tangani semua & Setuju"**. |
| Cetak / unduh | Dua opsi: **dengan tanda tangan** / **tanpa tanda tangan** (mark TTD + cap disembunyikan, watermark "SALINAN TANPA TANDA TANGAN"). Toggle di layar cetak selektif. |
| Ke KPPN | Kirim **file**, tidak ada hardcopy. |
| SPP/SPM/Lampiran COA | Keluaran SAKTI (BSrE TTE + QR). ASIKKEKKU **hanya unggah**, tidak menempel apa pun. |
| BSrE untuk form kantor | **Dicoret** — jalur BSrE lewat Srikandi terlalu berat per dokumen. |

## 3. Dokumen mana yang butuh TTD / cap (jenis "perjadin")

Diambil dari foto berkas asli (`C:\Users\lenovo\Downloads\asikkekku\`):

| Dokumen | Mode | TTD | Cap | Catatan |
|---|---|---|---|---|
| Kwitansi | `form_inapp` | PPK + penerima (per rangkap) | ✔ **2 titik** di blok PPK | "TELAH DIBAYAR LUNAS" + "Telah dibayar sejumlah" |
| Daftar Pengeluaran Riil | `form_inapp` | PPK + pelaksana | ✔ 1 titik (PPK) | |
| Daftar Nominatif | `form_inapp` | PPK | ✔ 1 titik (PPK) | |
| SPTJB | `form_inapp` | PPK | ✔ 1 titik (PPK) | |
| SPD | `form_inapp` | PPK | grid cap hal. 2 — **perlu konfirmasi** | |
| Lembar Pemeriksaan SPP | `form_inapp` | paraf verifikator/SPM | – | paraf, bukan cap |
| Kartu Kendali / Check List | `checklist` | paraf tiap serah‑terima | – | |
| SPP, SPM, Lampiran COA 16 seg | `upload` | (BSrE SAKTI) | – | ASIKKEKKU hanya sediakan slot unggah |
| Surat Tugas, LPD | `upload` | (TTE eksternal) | – | |

## 4. Perubahan skema (usulan)

```sql
-- Registry dokumen per jenis + tahap (v1: tetap array PHP di M_Dokumen, kolom
-- tambahan di bawah dulu dipegang di array; tabel ini opsional untuk admin-edit nanti)
-- tb_dokumen_jenis (JenisID, Kode, MetodeDok ENUM('form_inapp','upload','checklist'),
--   PosisiPembuat, WajibTtdSlot, Urutan)

-- Tanda tangan tangkap-langsung
CREATE TABLE `tb_dokumen_ttd` (
  `TtdID`            int AUTO_INCREMENT PRIMARY KEY,
  `KegiatanID`       int NOT NULL,
  `Kode`             varchar(40) NOT NULL,
  `RangkapKey`       varchar(80) NOT NULL DEFAULT '-',
  `Slot`            varchar(24) NOT NULL,           -- 'ppk' | 'penerima' | 'pelaksana' | 'verifikator' ...
  `SignerUserID`     int NULL,
  `SignerNama`       varchar(150) NULL,             -- snapshot nama saat ttd
  `SignerRole`       varchar(40) NULL,
  `ImagePath`        varchar(255) NOT NULL,         -- assets/ttd/<KegiatanID>/<kode>_<rangkap>_<slot>.png
  `DocHash`          char(64) NOT NULL,             -- sha256(PayloadJson + TemplateVersi) saat ttd
  `SignedAt`         datetime NOT NULL,
  `SignedIP`         varchar(45) NULL,
  `InvalidatedAt`    datetime NULL,
  `InvalidatedReason` varchar(120) NULL,
  UNIQUE KEY `uq_ttd_slot` (`KegiatanID`,`Kode`,`RangkapKey`,`Slot`,`InvalidatedAt`),
  KEY `idx_ttd_keg` (`KegiatanID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Log penempelan cap (log saja, bukan gate)
CREATE TABLE `tb_dokumen_cap_log` (
  `id` int AUTO_INCREMENT PRIMARY KEY,
  `KegiatanID` int NOT NULL, `Kode` varchar(40) NOT NULL, `RangkapKey` varchar(80) NOT NULL DEFAULT '-',
  `TemplateVersi` varchar(20) NULL, `ByUserID` int NULL, `AtTime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Log cetak/unduh (log saja)
CREATE TABLE `tb_dokumen_print` (
  `id` int AUTO_INCREMENT PRIMARY KEY,
  `KegiatanID` int NOT NULL, `Kode` varchar(40) NULL, `Batch` varchar(40) NULL,
  `DenganTtd` tinyint(1) NOT NULL DEFAULT 1, `ByUserID` int NULL, `AtTime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

- `tb_vrbl` baris baru `dok_cap_image` = path PNG cap (diunggah admin).
- `tb_dokumen` tetap; tambah kolom `PayloadHash` (cache sha256) supaya invalidasi ttd cepat.

## 4b. Jawaban blocker §7 (dikunci)

1. **SPI/KPPN:** anggap salinan digital ber‑TTD tangkap‑langsung DITERIMA. Bangun penuh; user
   konfirmasi ke SPI/KPPN paralel. Sediakan mode "salinan kerja" sebagai fallback bila ditolak.
2. **Format template:** **bangun ulang `tpl/*` 100% dari `.xlsx` di `GDRIVE/` LEBIH DULU**
   (= Fase 0 baru), baru pasang mekanik ttd.
3. **Kapan PPK ttd:** di tahap PPK (`PPK1`, tahap 5), satu sesi, "Setuju" PPK dikunci sampai lengkap.
4. **TTD penerima:** dikumpulkan di perangkat PJ pada tahap 1 (tanpa tautan jarak jauh dulu).
5. **Registry:** v1 tetap array PHP di `M_Dokumen` (kolom `metode/slot_ttd/butuh_cap`); tabel
   `tb_dokumen_jenis` admin‑editable menyusul.

## 4c. Data intake PJ + autofill (dikunci)

**Tidak bikin master pegawai** — `tb_users` sudah jadi daftar pegawai (`UserName` = NIP,
`UserFullName` = nama). Perubahan:

- `tb_users` + kolom: `UserGol`, `UserJabatan`, `UserRekening`, `UserBank`, `UserNPWP`
  (default profil, diisi di Manajemen Pengguna).
- **`tb_kegiatan_pelaksana`** (tabel anak, snapshot per kegiatan) — mengganti teks bebas
  `KegiatanNamaPelaksana`:
  `id, KegiatanID, UserID (nullable), Nama, NIP, Gol, Jabatan, Rekening, Bank, NPWP, Urut`.
  PJ pilih user → field prefill dari `tb_users`, **tetap bisa diedit per kegiatan** (no rekening
  bisa beda tiap kegiatan).
- `tb_kegiatan` + kolom: `KegiatanKodeOutput`, `KegiatanAsalTujuan`, `KegiatanJmlHari`.
- Daftar Kode Output = 1 baris JSON di `tb_vrbl` (bukan tabel `tb_output`). PPK/Verifikator dari
  peta prefix yang sudah ada (`M_Dokumen::$ppk_by_output_default`).
- **Pembagian isian:** PJ isi pokok (kegiatan + Kode Output + daftar pelaksana + asal/tujuan +
  jml hari). **Staf PPK** isi rincian biaya (tarif) di tahap 2.
- `M_Dokumen::autofill()` + `kegiatan()` / `rangkapList()` dibaca ulang dari `tb_kegiatan_pelaksana`
  (bukan lagi pecah string `KegiatanNamaPelaksana`).

## 5. Fase implementasi

### Fase 0 — Bangun ulang template 100% dari `.xlsx` GDRIVE  ← SEDANG DIKERJAKAN
Sumber (`GDRIVE/`): `PJ/1. Kuitansi.xlsx`, `PJ/2 Daftar Riil.xlsx`, `PJ/4. Nominatif.xlsx`,
`PJ/SPTB Tahun 2026.xlsx`, `PJ/kartu kendali TU.xlsx`, `Verifikator/Check List Kelengkapan.xlsx`.
SPD tidak ada `.xlsx` — pakai foto berkas (`C:\Users\lenovo\Downloads\asikkekku\`).
- Ekstrak layout tiap `.xlsx` (via `phpspreadsheet`): teks sel, merge, lebar kolom, border,
  blok tanda tangan, footer ("TELAH DIBAYAR LUNAS", "Perhitungan SPPD rampung", grid cap SPD hal.2).
- Tulis ulang `views/dokumen/tpl/<kode>.php` + `assets/css/dokumen.css` agar output cetak
  identik dengan form kantor (bukan lagi perkiraan). Sinkronkan skema field `M_Dokumen::templates()`.
- Uji cetak tiap dokumen berdampingan dengan foto/`.xlsx` asli.

Progres:
- [x] **Data intake** — migrasi `20260909120000` (kolom `tb_users`/`tb_kegiatan`, tabel
      `tb_kegiatan_pelaksana`, `dok_output_list`). Form buat pengajuan: repeater Pelaksana
      (pilih user → prefill, norek editable), Kode Output, Asal→Tujuan, Jml Hari.
      `M_Dokumen::autofill()` baca dari tabel pelaksana. Smoke test lulus.
- [x] **A4** — `cetak.php` + CSS lembar A4 (210×297mm); pratinjau diperkecil proporsional.

Progres Fase 0 (template):
- [x] **Kwitansi** — `tpl/kwitansi.php` + CSS `.kw-*` ditulis ulang sesuai berkas 2026 LS:
      kop BBPOM, blok Nomor Bukti/Kode MAK, "SUDAH TERIMA DARI / UANG SEBESAR (terbilang) /
      Guna pembayaran", "Surat Perintah dari…", Tanggal+Nomor+"Untuk perjalanan dinas dari",
      kotak TERBILANG, "TELAH DIBAYAR LUNAS", blok ttd-1 (PPK | Yang menerima), catatan
      Lampiran PMK 45/2007, tabel RINCIAN **tanpa kolom "Satuan"** (NO|URAIAN|JUMLAH|KET,
      tarif inline), blok "Telah dibayar / Telah menerima", blok ttd-2 (+"Dengan catatan…"),
      footer "Perhitungan SPPD rampung". `.ds-sign` diberi `data-slot` (`ppk`/`penerima`) +
      `data-cap="1"` di slot PPK untuk modul TTD nanti. Render OK di `/dokumen/cetak/kwitansi/<id>`.
- [x] **Daftar Pengeluaran Riil** — `tp/riil.php`: kop, "Yang bertanda tangan dibawah ini"
      (Nama/NIP/Jabatan auto dari pelaksana), paragraf "Berdasarkan SPD/Surat Tugas…", butir 1
      + tabel NO|URAIAN|Jumlah|Keterangan (tanpa kolom Satuan), butir 2 (setor Kas Negara),
      penutup, 2 ttd (Mengetahui/Menyetujui PPK + cap | Pejabat Negara/Pegawai Negeri).
- [x] **Daftar Nominatif** — `tpl/nominatif.php`: header tabel bertingkat
      ("Petugas yang melakukan Perjalanan": Nama/NIP/Gol · "Biaya Perjalanan": Lama/Jumlah),
      baris **auto-prefill dari daftar pelaksana** bila kosong, baris JUMLAH, ttd PPK + cap.
- [x] **SPTJB** — `tpl/sptjb.php`: "SURAT PERNYATAAN TANGGUNG JAWAB BELANJA", 4 baris kepala
      (Kode Satker/Nama Satker/Tgl-No DIPA/Klasifikasi Anggaran), paragraf pernyataan KPA,
      tabel NO(a)|AKUN(b)|PENERIMA(c)|URAIAN(d)|JUMLAH(e)|Pajak dipungut[P.P.N(f)|P.Ph(g)],
      total, paragraf simpan bukti + penutup, ttd PPK + cap.
- [x] **SPD** — `tpl/spd.php` (hal. 1): meta Lembar Ke/Kode/Nomor, tabel 10 baris bernomor
      persis form (1 PPK … 10 Keterangan Lain-lain), sub-tabel Pengikut 5 baris, "*) coret yang
      tidak perlu", blok "Dikeluarkan di / Tanggal / Pejabat Pembuat Komitmen" + ttd (tanpa cap
      di hal. 1). Hal. 2 (grid cap tiba/berangkat) menyusul.
- [x] **Checklist oranye** (`lembar_periksa`) — sudah (lihat atas).

**Fase 0 template: 6/6 selesai** (semua A4, struktur sesuai foto). Sisa penghalusan: sub-grup
label kolom, hal. 2 SPD, kapitalisasi detail — sambil user membandingkan cetakan.

### Fase A — Sambungkan modul Dokumen ke alur  ✅ SELESAI
- `M_Dokumen::templates()` tiap entri kini punya `metode` (`form_inapp`/`checklist`),
  `dibuat_oleh`, `slot_ttd` (`['ppk','penerima']`), `butuh_cap`, `urut`, `jenis` (`[1]`).
- `templatesForJenis($jenisID)` + `dokumenUntukKegiatan($KegiatanID)` (daftar dokumen +
  status pengisian per rangkap dari `tb_dokumen`).
- `Dokumen::panel($KegiatanID)` → fragmen `views/dokumen/panel.php` (tabel dokumen, tombol
  Isi/Cetak per rangkap, titik status, badge cap/metode).
- Panel di‑embed (lazy AJAX) di popup **"Informasi Pengajuan"** (`KegiatanInfo.php`) — muncul
  di List Data & List Approval.
- Menu sidebar **"Dokumen Pencairan"** (`tb_menu` 3400 → `dokumen/pilih`), akses = grup yang
  punya akses "Daftar Pengajuan". Mirror: `assets/sql/2026-09-09_dokumen_menu.sql`.
- Smoke test tetap lulus.

### Fase B — Tanda tangan tangkap‑langsung  ✅ SELESAI
- Migrasi `20260910120000`: `tb_dokumen_ttd` + `tb_dokumen.PayloadHash`.
- Kanvas coret sendiri (`views/dokumen/ttd.php`, ~60 baris JS, tanpa library) + opsi
  **unggah foto** (di‑fit ke kanvas, latar putih dibuang → transparan) → PNG base64.
- `Dokumen::ttd()` / `ttdSimpan()` + `_ttdBoleh()` (penerima→PJ pemilik · ppk→user PPK ·
  SuperAdmin selalu; Fase E perketat ke FlowDestUser).
- `M_Dokumen`: `docHash()` (sha256 payload tergabung + versi), `ttdSimpan()` (validasi PNG,
  simpan `assets/ttd/<KID>/…png`, satu aktif per slot), `ttdAktif()` / `ttdAktifKegiatan()`,
  `ttdInvalidateOnChange()` — dipanggil dari `save()`: **dokumen diubah → ttd versi lama hangus**.
- `_render.php` menempel `<img class="ds-sign-img">` (+ `ds-cap-img` bila cap ada) ke
  `.ds-sign[data-slot]`; flag `$tampilkan_ttd` (default true). `?ttd=0` → tanpa ttd + watermark
  "SALINAN TANPA TANDA TANGAN".
- Panel dokumen: tombol `ttd:<slot>` per rangkap (hijau bila sudah, ✓).
- Uji `ttd_test.sh`: PJ boleh penerima / ditolak ppk · PPK boleh ppk · img muncul di cetak ·
  ttd=0 watermark tanpa img · edit dokumen → 2 ttd hangus. Smoke test tetap lulus.

_(catatan lama:)_
- Vendor `signature_pad` (~5 KB) di `assets/js/`, dimuat **hanya** di halaman tanda tangan.
- `dokumen/ttd/<kode>/<KegiatanID>?r=&slot=` → kanvas coret **atau** unggah foto baris.
- Submit → server: decode PNG base64 / proses foto (validasi magic byte, cap ukuran, downscale),
  simpan ke `assets/ttd/...`, insert `tb_dokumen_ttd` dengan `DocHash` saat itu.
- **Kunci slot:** slot `ppk` hanya bisa oleh user yang sedang bertindak di tahap PPK
  (posisi cocok + `FlowDestUser`); slot `penerima`/`pelaksana` oleh PJ pemilik di tahap 1.
- **Invalidasi:** saat `tb_dokumen.PayloadJson` berubah → `PayloadHash` baru; semua
  `tb_dokumen_ttd` untuk `(KegiatanID,Kode,RangkapKey)` yang `DocHash`‑nya beda → set
  `InvalidatedAt` + alasan "dokumen diubah". UI tandai "TTD hangus, perlu ttd ulang".

### Fase C — Cap dinas  ✅ SELESAI
- Kartu **"Cap Dinas"** di Konfigurasi Aplikasi: unggah PNG transparan (maks 600 KB / 1200px),
  pratinjau + tombol Hapus. `Dokumen::capUpload()` / `capHapus()` (izin `2300 u`), validasi
  PNG‑magic + `getimagesize`; simpan `assets/uploads/cap_dinas.png` → `tb_vrbl.dok_cap_image`
  (+ `dok_cap_meta` = {by,at}).
- `_render.php`: cap (`ds-cap-img`) ditempel **hanya di `.ds-sign[data-cap="1"]` yang slotnya
  sudah ditandatangani** (menindih ttd PPK). Tidak muncul di `?ttd=0`.
- Uji: Kwitansi ttd PPK → 2× cap (dua blok PPK) · Nominatif belum ttd → 0 cap · `ttd=0` bersih.

_(catatan lama:)_
- Upload cap PNG transparan di Konfigurasi Aplikasi (validasi dimensi/ukuran) → `tb_vrbl.dok_cap_image`.
- Saat render dengan ttd & dokumen `butuh_cap` & slot `ppk` sudah di‑ttd (valid) → tempel cap PNG
  di koordinat slot PPK (absolute positioning dalam sel `.ds-ttd`; Kwitansi 2 titik). Catat ke
  `tb_dokumen_cap_log`.

### Fase D — Cetak/unduh + paket  ✅ SELESAI (via browser-print, bukan mPDF)
**Catatan penting:** `mpdf/mpdf` ADA di `composer.json`/`.lock` tapi **belum ter-install** di
`vendor/` (tak ada `vendor/mpdf/`), dan tak ada engine HTML→PDF lain. Jadi "unduh PDF" =
**cetak browser → Simpan sebagai PDF** (A4 sudah diatur `@page`). Merge server-side mPDF/FPDI
ditunda sampai `composer install`.
- Migrasi `20260911120000`: `tb_dokumen_print` (log cetak, bukan gate).
- `Dokumen::paket($KID)` → `views/dokumen/paket.php`: checklist dokumen per rangkap (pra-centang
  yang sudah terisi), radio **dengan / tanpa tanda tangan**, "pilih semua/kosongkan" → buka
  `paketCetak` di tab baru.
- `Dokumen::paketCetak($KID)?d[]=kode:rangkap&ttd=0|1` → `views/dokumen/paket_cetak.php`:
  semua dokumen terpilih di satu halaman, `page-break-after` antar dokumen, auto `window.print()`
  → satu PDF. `printLog()` mencatat batch.
- Tombol **"Cetak / Unduh Paket"** di panel Dokumen. `dokumen/cetak/…?ttd=0` tetap ada untuk
  satu dokumen tanpa ttd + watermark.
- Uji: picker render OK · paketCetak 2 dok → 2 blok A4 + page-break · `tb_dokumen_print` tercatat.

_(catatan lama:)_
- `_render.php` + `tpl/*`: baca `$tampilkan_ttd` (default `true`).
  - `true`: tiap `.ds-sign` diisi PNG dari `tb_dokumen_ttd` (valid) + nama tercetak; sel PPK +
    overlay cap; footer strip verifikasi (nama/peran + hash + QR `picqer` → halaman verifikasi).
  - `false`: `.ds-sign` kosong, tanpa cap, watermark diagonal "SALINAN TANPA TANDA TANGAN".
- `dokumen/unduh/<kode>/<KegiatanID>?r=&ttd=1|0` → render HTML `_render` → **mPDF** → unduh PDF.
  Path `window.print()` browser tetap untuk lihat cepat.
- **Layar cetak selektif** `dokumen/paket/<KegiatanID>`: checklist semua dokumen (form_inapp +
  upload), pilih + toggle ttd on/off, "Unduh terpilih" → mPDF untuk form_inapp + **FPDI**
  concat PDF hasil upload → satu PDF + daftar isi. Catat ke `tb_dokumen_print` (log, bukan gate).

### Fase E — Gate "Setuju" PPK  ✅ SELESAI
- Flag `tb_vrbl.dok_gate_ppk` (default **OFF** — alur & smoke test tak terpengaruh). Toggle di
  Konfigurasi Aplikasi (kartu Cap Dinas) → `Dokumen::gatePpkSet()`.
- `M_Dokumen::ttdKurangUntukPosisi($KID,'PPK')` → dokumen `form_inapp` slot `ppk` × rangkap yang
  belum ada ttd aktif. `Dokumen::ttdKurang($KID)` JSON `{on,posisi,kurang[],lengkap}`.
- **Gate server** di `_approvalTeruskan()`: bila `UserPosition==='PPK'` & flag ON & masih ada
  kurang → tolak ("Belum bisa disetujui: N dokumen belum Anda tanda tangani").
- **UI** di `KegiatanApprovalForm.php` (khusus PPK): blok daftar dokumen belum‑ttd + link
  "tanda tangani" + tombol **"Buka semua & tanda tangani"**; tombol Setuju di‑disable sampai
  lengkap; tombol "Cek ulang".
- Uji `gate_test.sh`: sampai tahap PPK → Setuju ditolak → ttd 5 dokumen → `lengkap:true` →
  Setuju lolos. Smoke test (gate OFF) tetap lulus.

_(catatan lama:)_
- Di `KegiatanApprovalForm.php`, sebelum "Setuju": daftar dokumen yang butuh ttd user ini di
  tahap ini + status (belum / sudah / hangus). "Setuju" ter‑disable sampai semua beres.
- Tombol "Tanda tangani semua & Setuju" → wizard menuntun tiap dokumen belum‑ttd lalu approve.

## 6. Perkiraan sentuhan file

- Baru: `application/migrations/2026XXXX_dokumen_ttd_cap.php`, `assets/sql/2026-XX-XX_dokumen_ttd_cap.sql`
- `application/controllers/Dokumen.php` (+ route `ttd`, `unduh`, `paket`, `verifikasi`)
- `application/models/M_Dokumen.php` (registry per jenis/tahap, `hashPayload`, `ttdSimpan`,
  `ttdList`, `ttdInvalidateOnChange`, `capImage`, `renderPdf` via mPDF, `paketMerge` via FPDI)
- `application/views/dokumen/_render.php` + semua `tpl/*.php` (slot ttd + cap + watermark + flag)
- `application/views/dokumen/{ttd,paket,verifikasi}.php` (baru), `assets/js/signature_pad.min.js` (baru)
- `application/views/manajemen_approval/KegiatanApprovalForm.php` (gate "Setuju" + daftar ttd)
- `application/controllers/Manajemen_app.php` + `KonfigurasiApp.php` (upload cap)
- `assets/css/dokumen.css` (posisi cap/ttd, watermark, strip verifikasi)

## 7. Blocker — HARUS diputuskan sebelum koding

1. **SPI Satker / KPPN Pangkal Pinang:** apakah Kwitansi & SPD yang ditandatangani cara
   tangkap‑langsung (TTE tidak tersertifikasi) **diterima** di paket file? Ini penentu — kalau
   tidak, tinta basah tetap wajib untuk dokumen itu dan fitur ini jadi sekadar "salinan kerja".
2. **Kesesuaian format template.** `tpl/*` masih perkiraan ~85% (kolom "Satuan" berlebih, blok
   footer Kwitansi & grid cap SPD hal. 2 hilang, dst). Untuk paket yang benar‑benar dikirim KPPN,
   apakah perlu dibangun ulang dulu 100% dari file `.xlsx` di GDRIVE? (Effort besar & terpisah.)
3. **Kapan PPK menandatangani.** Alur perjadin live: PPK di tahap 5 (`PPK1`). Kwitansi dll.
   disiapkan di tahap 1. PPK ttd saat berkas sampai kepadanya (tahap 5), atau ada checkpoint
   "PPK ttd dokumen" lebih awal?
4. **TTD penerima:** dikumpulkan sekali di tahap 1 di perangkat PJ, atau pelaksana boleh ttd
   lewat tautan jarak jauh?
5. **Registry template:** v1 tetap array PHP di `M_Dokumen`, atau langsung bikin tabel
   `tb_dokumen_template` yang bisa diedit admin?

## 8. Risiko

- **Kesesuaian format** adalah blocker nyata untuk "hapus tinta basah"; mekanik tanda tangan itu
  bagian yang mudah.
- Tangkap‑langsung bukan kriptografis — mitigasi: ikat per‑versi, hangus saat dokumen diubah,
  log audit lengkap, slot terkunci ke user yang bertindak, strip verifikasi + QR.
- Menyambungkan modul demo ke alur menyentuh layar Persetujuan — perlu kehati‑hatian setara
  pekerjaan alur `JenisID`; jalankan `smoke_flow.sh` sesudahnya.
