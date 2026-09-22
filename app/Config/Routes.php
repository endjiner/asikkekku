<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

/*
 | --------------------------------------------------------------------------
 | Rute eksplisit -- porting 1:1 dari CI3 auto-routing (application/config/
 | routes.php, translate_uri_dashes = FALSE). Auto-routing CI4 dimatikan
 | (Config\Routing::$autoRoute = false, default sejak 4.x) sehingga tiap
 | endpoint yang dulu "otomatis" kebentuk dari URI didaftarkan di sini.
 |
 | Case-insensitive: CI3 lama TIDAK punya tabel rute -- ia langsung panggil
 | $controller->$method() dari segmen URI apa adanya, dan pemanggilan method
 | PHP case-insensitive, jadi URL lama beredar dengan ejaan controller/method
 | yang tidak konsisten (mis. 'FrontPage/ListResult' vs 'frontpage/listresult',
 | 'MainPage/Login' vs 'mainpage/login'). Tabel rute eksplisit CI4 dicocokkan
 | via preg_match() case-SENSITIVE by default -- beda dari CI3 -- jadi kalau
 | didaftarkan apa adanya, ejaan yang tidak dipakai akan 404. Daripada
 | mendaftarkan tiap variasi huruf satu-satu (rawan kelewatan, sudah pernah
 | kejadian), $add() di bawah menambahkan modifier regex inline '(?i)' di
 | depan tiap pola supaya cocok berapa pun campuran huruf besar/kecilnya --
 | menyamai perilaku asli CI3 apa adanya. Sisi kanan ('Controller::method')
 | tetap ditulis dengan ejaan PHP yang benar; pemanggilan method PHP sendiri
 | sudah case-insensitive.
 */

$add = static function (string $from, string $to) use ($routes): void {
    $routes->add('(?i)' . $from, $to);
};

$routes->setDefaultController('FrontPage');
$routes->setDefaultMethod('Index');
// set404Override() TIDAK memakai default namespace (beda dari $routes->add()
// biasa) -- harus FQCN penuh, kalau tidak createController() mencari class
// 'MainPage' di namespace global dan gagal ("Class not found").
$routes->set404Override('\App\Controllers\MainPage::NotFound');

// ---------------------------------------------------------------------
// FrontPage (default controller, publik -- lihat status pengajuan)
// ---------------------------------------------------------------------
$routes->get('/', 'FrontPage::Index');
$add('frontpage', 'FrontPage::Index');
$add('frontpage/Index', 'FrontPage::Index');
$add('frontpage/ListResult', 'FrontPage::ListResult');
$add('frontpage/ListStatus', 'FrontPage::ListStatus');

// ---------------------------------------------------------------------
// MainPage (login / logout)
// ---------------------------------------------------------------------
$add('MainPage', 'MainPage::Index');
$add('MainPage/Index', 'MainPage::Index');
$add('MainPage/Login', 'MainPage::Login');
$add('MainPage/VerifyLogin', 'MainPage::VerifyLogin');
$add('MainPage/Logout', 'MainPage::Logout');
$add('MainPage/NotFound', 'MainPage::NotFound');

// ---------------------------------------------------------------------
// Dashboard
// ---------------------------------------------------------------------
$add('Dashboard', 'Dashboard::index');
$add('Dashboard/index', 'Dashboard::index');

// ---------------------------------------------------------------------
// Cron (dipanggil dari luar, dikecualikan dari CSRF -- lihat Filters.php)
// ---------------------------------------------------------------------
$add('Cron', 'Cron::index');
$add('Cron/index', 'Cron::index');
$add('Cron/early_warning', 'Cron::early_warning');

// ---------------------------------------------------------------------
// Migrate (jalankan migrasi dari browser di host lokal, atau CLI 'spark migrate')
// ---------------------------------------------------------------------
$add('Migrate', 'Migrate::index');
$add('Migrate/index', 'Migrate::index');

// ---------------------------------------------------------------------
// Profil (ganti password, dipakai modal di semua halaman)
// ---------------------------------------------------------------------
$add('profil/PasswordModify', 'Profil::PasswordModify');

// ---------------------------------------------------------------------
// Manajemen_app -- Grup Pengguna / Pengguna / Konfigurasi Aplikasi
// ---------------------------------------------------------------------
$add('manajemen_app', 'Manajemen_app::index');
$add('manajemen_app/index', 'Manajemen_app::index');
$add('manajemen_app/group_user_list', 'Manajemen_app::group_user_list');
$add('manajemen_app/UserGroupGetList', 'Manajemen_app::userGroupGetList');
$add('manajemen_app/UserGroupGetData', 'Manajemen_app::userGroupGetData');
$add('manajemen_app/UserGroupModify', 'Manajemen_app::userGroupModify');
$add('manajemen_app/UserGroupDelete', 'Manajemen_app::userGroupDelete');
$add('manajemen_app/user_list', 'Manajemen_app::user_list');
$add('manajemen_app/UserGetList', 'Manajemen_app::userGetList');
$add('manajemen_app/UserGetData', 'Manajemen_app::userGetData');
$add('manajemen_app/UserModify', 'Manajemen_app::userModify');
$add('manajemen_app/UserDelete', 'Manajemen_app::userDelete');
$add('manajemen_app/konfigurasi_app', 'Manajemen_app::konfigurasi_app');
$add('manajemen_app/KonfigurasiAppModify', 'Manajemen_app::KonfigurasiAppModify');
$add('manajemen_app/KonfigurasiAppFlowOrder', 'Manajemen_app::KonfigurasiAppFlowOrder');

// ---------------------------------------------------------------------
// Manajemen_approval -- Daftar Pengajuan / Persetujuan / Laporan
// ---------------------------------------------------------------------
$add('manajemen_approval', 'Manajemen_approval::index');
$add('manajemen_approval/index', 'Manajemen_approval::index');
$add('manajemen_approval/list_data', 'Manajemen_approval::list_data');
$add('manajemen_approval/UserDestByJenis', 'Manajemen_approval::UserDestByJenis');
$add('manajemen_approval/KegiatanGetList', 'Manajemen_approval::KegiatanGetList');
$add('manajemen_approval/KegiatanGetData', 'Manajemen_approval::KegiatanGetData');
$add('manajemen_approval/KegiatanModify', 'Manajemen_approval::KegiatanModify');
$add('manajemen_approval/KegiatanDelete', 'Manajemen_approval::KegiatanDelete');
$add('manajemen_approval/KegiatanSendApproval', 'Manajemen_approval::KegiatanSendApproval');
$add('manajemen_approval/GetFormInfoKegiatan', 'Manajemen_approval::GetFormInfoKegiatan');
$add('manajemen_approval/list_approval', 'Manajemen_approval::list_approval');
$add('manajemen_approval/KegiatanApprovalGetList', 'Manajemen_approval::KegiatanApprovalGetList');
$add('manajemen_approval/GetApprovalFormInfoKegiatan', 'Manajemen_approval::GetApprovalFormInfoKegiatan');
$add('manajemen_approval/ApprovalFormInfoKegiatanSubmit', 'Manajemen_approval::ApprovalFormInfoKegiatanSubmit');
$add('manajemen_approval/KegiatanTerminate', 'Manajemen_approval::KegiatanTerminate');
$add('manajemen_approval/KegiatanRevisiKirimUlang', 'Manajemen_approval::KegiatanRevisiKirimUlang');
$add('manajemen_approval/list_report', 'Manajemen_approval::list_report');
$add('manajemen_approval/ReportGetList', 'Manajemen_approval::ReportGetList');
$add('manajemen_approval/EarlyWarningNudgeOne', 'Manajemen_approval::EarlyWarningNudgeOne');

// ---------------------------------------------------------------------
// Dokumen -- form_inapp, cetak/unduh PDF, tanda tangan, upload eksternal.
// Banyak method menerima 1-2 parameter posisi opsional (default di PHP),
// jadi tiap endpoint didaftarkan dengan & tanpa parameter di URL.
// ---------------------------------------------------------------------
$add('dokumen', 'Dokumen::index');
$add('dokumen/index', 'Dokumen::index');
$add('dokumen/pilih', 'Dokumen::pilih');
$add('dokumen/pratinjau', 'Dokumen::pratinjau');
$add('dokumen/simpan', 'Dokumen::simpan');
$add('dokumen/capUpload', 'Dokumen::capUpload');
$add('dokumen/gatePpkSet', 'Dokumen::gatePpkSet');
$add('dokumen/capHapus', 'Dokumen::capHapus');
$add('dokumen/ttdSimpan', 'Dokumen::ttdSimpan');
$add('dokumen/simpanUrutan', 'Dokumen::simpanUrutan');
$add('dokumen/uploadEksternal', 'Dokumen::uploadEksternal');
$add('dokumen/uploadEksternalHapus', 'Dokumen::uploadEksternalHapus');
$add('dokumen/ttdUploadSimpan', 'Dokumen::ttdUploadSimpan');

// method => jumlah parameter posisi (1 = (:num) KegiatanID/UploadID saja,
// 2 = (:segment) kode + (:num) KegiatanID)
$dokumenParamRoutes = [
    'panel'               => 1,
    'unifiedPreview'      => 1,
    'form'                => 2,
    'cetak'                => 2,
    'unduh'               => 2,
    'paketUnduh'          => 1,
    'paket'               => 1,
    'paketCetak'          => 1,
    'ttdKurang'           => 1,
    'ttd'                 => 2,
    'uploadEksternalList' => 1,
    'ttdUpload'           => 1,
    'unduhBerTtd'         => 1,
];
foreach ($dokumenParamRoutes as $dokMethod => $arity) {
    $add('dokumen/' . $dokMethod, 'Dokumen::' . $dokMethod);
    if ($arity === 1) {
        $add('dokumen/' . $dokMethod . '/(:num)', 'Dokumen::' . $dokMethod . '/$1');
    } else {
        $add('dokumen/' . $dokMethod . '/(:segment)', 'Dokumen::' . $dokMethod . '/$1');
        $add('dokumen/' . $dokMethod . '/(:segment)/(:num)', 'Dokumen::' . $dokMethod . '/$1/$2');
    }
}
unset($dokumenParamRoutes, $dokMethod, $arity, $add);
