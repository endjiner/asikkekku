<?php

namespace App\Controllers;

use App\Models\M_DataTable;
use App\Models\M_Manajemen_approval;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Manajemen_approval extends AppController
{
    private $menu_kode;

    /** @var M_Manajemen_approval */
    private $M_Manajemen_approval;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->menu_kode            = '3000';
        $this->M_Manajemen_approval = model(M_Manajemen_approval::class);
        $this->bootData($this->menu_kode);
    }

    public function index()
    {
        legacy_redirect(base_url());
    }

    // -----------------------------------------------------
    public function list_data()
    {
        $this->menu_kode = '3100';
        $this->Auth->cekMenu($this->menu_kode, 'r');
        if (! role_can('kegiatan_list')) {
            $this->Auth->alert_error_akses('Menu Daftar Pengajuan hanya untuk PJ-Kegiatan / Pemohon.');
            legacy_redirect(base_url('manajemen_approval/list_approval'));
            return;
        }
        $this->data['menu_detail'] = $this->data['menu_list'][$this->menu_kode];
        $this->data['menu_all']    = $this->Menu->GetMenuAll();

        $this->data['JenisList']   = $this->M_Manajemen_approval->JenisPengajuanAktif();
        $defaultJenis               = ! empty($this->data['JenisList']) ? (int) $this->data['JenisList'][0]['JenisID'] : 1;
        $this->data['UserDest']    = $this->M_Manajemen_approval->getUserDestination(1, $defaultJenis);
        $this->data['OutputList']  = $this->M_Manajemen_approval->OutputList();
        $this->data['PegawaiOpts'] = $this->M_Manajemen_approval->PegawaiOptions();
        $currUid                   = (int) $this->session->get('UserID');
        $currUserRow               = $this->db->table('tb_users')->select('UserPhone')->getWhere(['UserID' => $currUid])->getRowArray();
        $this->data['UserPhone']   = $currUserRow['UserPhone'] ?? ($this->session->get('UserPhone') ?? '');
        $this->data['body']        = 'manajemen_approval/ListData';
        $this->data['footer']      = 'manajemen_approval/ListDataFooter';

        return view('main', $this->data);
    }

    /** AJAX: daftar "Petugas Tujuan" untuk jenis tertentu (dependent dropdown). */
    public function UserDestByJenis()
    {
        $this->menu_kode = '3100';
        $this->Auth->cekMenu($this->menu_kode, 'r');
        if (! role_can('kegiatan_create')) {
            $this->Auth->alert_error_response('Posisi Anda tidak berhak menambah data kegiatan.');

            return;
        }
        $JenisID = (int) $this->request->getPost('JenisID');
        if (! $this->M_Manajemen_approval->JenisIsValid($JenisID)) {
            echo json_encode([]);

            return;
        }
        echo json_encode($this->M_Manajemen_approval->getUserDestination(1, $JenisID));
    }

    public function KegiatanGetList()
    {
        $this->menu_kode = '3100';
        $this->Auth->cekMenu($this->menu_kode, 'r');

        // Server-side processing: DB yang paginasi/cari/urutkan, PHP hanya
        // menghias baris di halaman aktif. Skalabel walau data ratusan ribu.
        $sql            = $this->M_Manajemen_approval->KegiatanGetListSql();
        $output         = model(M_DataTable::class)->dtTableGetList($sql);
        $output['data'] = $this->M_Manajemen_approval->KegiatanDecorateRows($output['data']);
        echo json_encode($output);
    }

    public function KegiatanGetData()
    {
        $this->menu_kode = '3100';
        $this->Auth->cekMenu($this->menu_kode, 'r');
        $KegiatanID = legacy_get_post('KegiatanID');
        $data       = $this->M_Manajemen_approval->KegiatanGetData($KegiatanID);
        echo json_encode($data);
    }

    public function KegiatanModify()
    {
        $this->menu_kode = '3100';
        $this->Auth->cekMenu($this->menu_kode, 'c');
        if (! role_can('kegiatan_create')) {
            $this->Auth->alert_error_response('Posisi Anda tidak berhak menambah / mengubah data kegiatan.');
        }
        $data['error']  = $this->M_Manajemen_approval->KegiatanModify();
        $data['status'] = $this->db->transStatus();

        $f = isset($_FILES['KegiatanLampiran']) ? $_FILES['KegiatanLampiran'] : null;
        if ($f && $f['name'] !== '' && (int) $f['error'] === UPLOAD_ERR_OK) {
            $MAX       = 5 * 1024 * 1024; // 5 MB
            $cleanName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', basename($f['name']));
            $ext       = strtolower(pathinfo($cleanName, PATHINFO_EXTENSION));
            $isPdfExt  = ($ext === 'pdf');
            $isPdfMime = false;
            if (is_uploaded_file($f['tmp_name'])) {
                $fh = @fopen($f['tmp_name'], 'rb');
                if ($fh) {
                    $isPdfMime = (fread($fh, 5) === '%PDF-');
                    fclose($fh);
                }
            }
            if (! $isPdfExt || ! $isPdfMime) {
                $data['error'] = ['code' => 1, 'message' => 'Lampiran harus berupa file PDF yang sah.'];
            } elseif ($f['size'] > $MAX) {
                $data['error'] = ['code' => 1, 'message' => 'Ukuran lampiran melebihi 5 MB.'];
            } else {
                move_uploaded_file($f['tmp_name'], FCPATH . 'assets/lampiran/' . $cleanName);
            }
        }
        echo json_encode($data);
    }

    public function KegiatanDelete()
    {
        $this->menu_kode = '3100';
        $this->Auth->cekMenu($this->menu_kode, 'd');
        if ($this->data['UserPosition'] !== 'SuperAdmin') {
            $this->Auth->alert_error_response('Hanya SuperAdmin yang dapat menghapus pengajuan.');

            return;
        }
        $data['error']  = $this->M_Manajemen_approval->KegiatanDelete();
        $data['status'] = $this->db->transStatus();
        echo json_encode($data);
    }

    public function KegiatanSendApproval()
    {
        $this->menu_kode = '3100';
        $this->Auth->cekMenu($this->menu_kode, 'u');
        $this->M_Manajemen_approval->KegiatanSendApproval();
        $data['status'] = $this->db->transStatus();
        echo json_encode($data);
    }

    public function GetFormInfoKegiatan()
    {
        $this->menu_kode = '3100';
        $this->Auth->cekMenu($this->menu_kode, 'r');
        $KegiatanID = (int) legacy_get_post('KegiatanID');
        if (! $KegiatanID) {
            return '<div class="alert alert-warning m-3">ID Kegiatan tidak valid.</div>';
        }
        $kegData = $this->M_Manajemen_approval->KegiatanGetData($KegiatanID);
        if (empty($kegData['Kegiatan'])) {
            return '<div class="alert alert-warning m-3">Data pengajuan tidak ditemukan atau telah dihapus.</div>';
        }
        $data['data']        = $kegData['Kegiatan'][0];
        $data['history']     = $this->M_Manajemen_approval->GetFormInfoKegiatan($KegiatanID)['history'];
        $data['last_status'] = $this->M_Manajemen_approval->GetLastStatus($KegiatanID);
        $data['sla']         = $this->M_Manajemen_approval->KegiatanSlaEval($KegiatanID);

        return view('manajemen_approval/KegiatanInfo', $data);
    }

    // -----------------------------------------------------
    public function list_approval()
    {
        $this->menu_kode = '3200';
        $this->Auth->cekMenu($this->menu_kode, 'r');
        if (! role_can('approval_inbox')) {
            $this->Auth->alert_error_akses('Menu Persetujuan tidak tersedia untuk posisi Anda.');
            legacy_redirect(base_url('dashboard'));
        }
        $this->data['menu_detail'] = $this->data['menu_list'][$this->menu_kode];
        $this->data['menu_all']    = $this->Menu->GetMenuAll();

        $this->data['body']   = 'manajemen_approval/ListApproval';
        $this->data['footer'] = 'manajemen_approval/ListApprovalFooter';

        return view('main', $this->data);
    }

    public function KegiatanApprovalGetList()
    {
        $this->menu_kode = '3200';
        $this->Auth->cekMenu($this->menu_kode, 'r');
        $sql            = $this->M_Manajemen_approval->KegiatanApprovalGetListSql();
        $output         = model(M_DataTable::class)->dtTableGetList($sql);
        $output['data'] = $this->M_Manajemen_approval->KegiatanApprovalDecorateRows($output['data']);
        echo json_encode($output);
    }

    public function GetApprovalFormInfoKegiatan()
    {
        $this->menu_kode = '3200';
        $this->Auth->cekMenu($this->menu_kode, 'c');
        $KegiatanID = (int) legacy_get_post('KegiatanID');
        if (! $KegiatanID) {
            echo '<div class="alert alert-warning m-3">ID Kegiatan tidak valid.</div>';
            return;
        }
        $kegData = $this->M_Manajemen_approval->KegiatanGetData($KegiatanID);
        if (empty($kegData['Kegiatan'])) {
            echo '<div class="alert alert-warning m-3">Data pengajuan tidak ditemukan.</div>';
            return;
        }
        $data['data']        = $kegData['Kegiatan'][0];
        $data['history']     = $this->M_Manajemen_approval->GetFormInfoKegiatan($KegiatanID)['history'];
        $data['last_status'] = $this->M_Manajemen_approval->GetLastStatus($KegiatanID);
        $data['sla']         = $this->M_Manajemen_approval->KegiatanSlaEval($KegiatanID);
        $jenisKeg            = (int) (isset($data['data']['KegiatanJenisID']) ? $data['data']['KegiatanJenisID'] : 1) ?: 1;
        $data['UserDest']    = $this->M_Manajemen_approval->getUserDestination(
            isset($data['last_status'][0]['FlowOrder']) ? $data['last_status'][0]['FlowOrder'] : 0, $jenisKeg);
        // Data untuk form "Kembalikan" (revisi / hentikan proses) + status kegiatan.
        $data['RevisiTargets']   = $this->M_Manajemen_approval->RevisiTargets($KegiatanID);
        $data['kegiatan_status'] = $data['data']['KegiatanStatus'];
        $data['UserPosition']    = $this->session->get('UserPosition');
        $data['UserID']          = $this->session->get('UserID');
        echo view('manajemen_approval/KegiatanApprovalForm', $data);
        echo view('manajemen_approval/KegiatanInfo', $data);
    }

    public function ApprovalFormInfoKegiatanSubmit()
    {
        $this->menu_kode = '3200';
        $this->Auth->cekMenu($this->menu_kode, 'c');
        if (! role_can('approval_inbox')) {
            $this->Auth->alert_error_response('Anda tidak berhak melakukan persetujuan.');
        }
        $data['error']  = $this->M_Manajemen_approval->ApprovalFormInfoKegiatanSubmit();
        $data['status'] = $this->db->transStatus();
        echo json_encode($data);
    }

    // Hentikan Proses (batalkan) -- hanya PJ-Kegiatan / SuperAdmin.
    public function KegiatanTerminate()
    {
        $this->menu_kode = '3100';
        $this->Auth->cekMenu($this->menu_kode, 'u');
        $data['error']  = $this->M_Manajemen_approval->KegiatanTerminate();
        $data['status'] = $this->db->transStatus();
        echo json_encode($data);
    }

    // Kirim ulang setelah revisi -- oleh petugas tujuan (PJ / Staff PPK / SPM).
    public function KegiatanRevisiKirimUlang()
    {
        $this->menu_kode = '3100';
        $this->Auth->cekMenu($this->menu_kode, 'u');
        $data['error']  = $this->M_Manajemen_approval->KegiatanRevisiKirimUlang();
        $data['status'] = $this->db->transStatus();
        echo json_encode($data);
    }

    // -----------------------------------------------------
    public function list_report()
    {
        $this->menu_kode = '3300';
        $this->Auth->cekMenu($this->menu_kode, 'r');
        if (! role_can('report')) {
            $this->Auth->alert_error_akses('Menu Laporan tidak tersedia untuk posisi Anda.');
            legacy_redirect(base_url('dashboard'));
        }
        $this->data['menu_detail'] = $this->data['menu_list'][$this->menu_kode];
        $this->data['menu_all']    = $this->Menu->GetMenuAll();

        $this->data['body']   = 'manajemen_approval/ListReport';
        $this->data['footer'] = 'manajemen_approval/ListReportFooter';

        return view('main', $this->data);
    }

    public function ReportGetList()
    {
        $this->menu_kode = '3200';
        $this->Auth->cekMenu($this->menu_kode, 'r');
        $data['data'] = $this->M_Manajemen_approval->ReportGetList();
        echo json_encode($data);
    }

    // -----------------------------------------------------
    // Peringatan Dini 4HK -- pengingat WhatsApp manual (tombol di dashboard)
    public function EarlyWarningNudgeOne()
    {
        if (! role_can('approval_inbox') && ! role_is_admin()) {
            $this->Auth->alert_error_response('Anda tidak berhak mengirim pengingat.');
        }
        $KegiatanID = (int) $this->request->getPost('KegiatanID');
        if ($KegiatanID <= 0) {
            echo json_encode(['status' => 0, 'message' => 'ID kegiatan tidak valid.']);

            return;
        }
        $res   = $this->M_Manajemen_approval->EarlyWarningKirim('manual', $KegiatanID);
        $gagal = isset($res['gagal']) ? (int) $res['gagal'] : 0;
        $ok    = $res['terkirim'] > 0;
        if ($ok) {
            $message = 'Pengingat WhatsApp terkirim ke ' . $res['terkirim'] . ' nomor'
                . ($gagal > 0
                    ? ', ' . $gagal . ' gagal (cek koneksi internet, langganan Wablas, atau nomor tujuan).'
                    : '.');
        } elseif ($gagal > 0) {
            $message = 'Gagal mengirim ke ' . $gagal . ' nomor. Periksa langganan Wablas, koneksi internet, atau nomor tujuan.';
        } else {
            $message = 'Tidak ada nomor tujuan, atau pengajuan ini tidak sedang terhambat.';
        }
        echo json_encode([
            'status'  => $ok ? 1 : 0,
            'message' => $message,
        ] + $res);
    }
}
