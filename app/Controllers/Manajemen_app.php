<?php

namespace App\Controllers;

use App\Models\M_DataTable;
use App\Models\M_Manajemen_app;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Manajemen_app extends AppController
{
    private $menu_kode;

    /** @var M_Manajemen_app */
    private $M_Manajemen_app;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->menu_kode = '2000';
        $this->Auth->cekMenu($this->menu_kode, 'r');
        if (! role_can('manajemen_app')) {
            $this->Auth->alert_error_akses('Menu ini hanya untuk Administrator.');
            legacy_redirect(base_url('dashboard'));
        }
        $this->M_Manajemen_app = model(M_Manajemen_app::class);
        $this->bootData($this->menu_kode);
    }

    public function index()
    {
        legacy_redirect(base_url());
    }

    public function group_user_list()
    {
        $this->menu_kode = '2100';
        $this->Auth->cekMenu($this->menu_kode, 'r');
        $this->data['menu_detail'] = $this->data['menu_list'][$this->menu_kode];
        $this->data['menu_all']    = $this->Menu->GetMenuAll();

        $this->data['body']   = 'manajemen_app/UserGroup';
        $this->data['footer'] = 'manajemen_app/UserGroupFooter';

        return view('main', $this->data);
    }

    public function userGroupGetList()
    {
        $this->menu_kode = '2100';
        $this->Auth->cekMenu($this->menu_kode, 'r');
        $sql                = $this->M_Manajemen_app->userGroupGetListSql();
        $output             = model(M_DataTable::class)->dtTableGetList($sql);
        $output['data']     = $this->M_Manajemen_app->userGroupDecorateRows($output['data']);
        echo json_encode($output);
    }

    public function userGroupGetData()
    {
        $this->menu_kode = '2100';
        $this->Auth->cekMenu($this->menu_kode, 'r');
        $UserGroupID = legacy_get_post('UserGroupID');
        $data        = $this->M_Manajemen_app->userGroupGetData($UserGroupID);
        echo json_encode($data);
    }

    public function userGroupModify()
    {
        $this->menu_kode = '2100';
        $this->Auth->cekMenu($this->menu_kode, 'c');
        $data['error']  = $this->M_Manajemen_app->userGroupModify();
        $data['status'] = $this->db->transStatus();
        echo json_encode($data);
    }

    public function userGroupDelete()
    {
        $this->menu_kode = '2100';
        $this->Auth->cekMenu($this->menu_kode, 'd');
        $this->M_Manajemen_app->userGroupDelete();
        $data['status'] = $this->db->transStatus();
        echo json_encode($data);
    }

    // -----------------------------------------------------
    public function user_list()
    {
        $this->menu_kode = '2200';
        $this->Auth->cekMenu($this->menu_kode, 'r');
        $this->data['menu_detail']   = $this->data['menu_list'][$this->menu_kode];
        $this->data['userGroupList'] = $this->M_Manajemen_app->userGroupGetData()['user_group'];
        $this->data['position']      = $this->Menu->position_list();
        $this->data['body']          = 'manajemen_app/User';
        $this->data['footer']        = 'manajemen_app/UserFooter';

        return view('main', $this->data);
    }

    public function userGetList()
    {
        $this->menu_kode = '2200';
        $this->Auth->cekMenu($this->menu_kode, 'r');
        $sql            = $this->M_Manajemen_app->userGetListSql();
        $output         = model(M_DataTable::class)->dtTableGetList($sql);
        $output['data'] = $this->M_Manajemen_app->userDecorateRows($output['data']);
        echo json_encode($output);
    }

    public function userGetData()
    {
        $this->menu_kode = '2200';
        $this->Auth->cekMenu($this->menu_kode, 'r');
        $UserID = legacy_get_post('UserID');
        $data   = $this->M_Manajemen_app->userGetData($UserID);
        echo json_encode($data);
    }

    public function userModify()
    {
        $this->menu_kode = '2200';
        $this->Auth->cekMenu($this->menu_kode, 'c');
        $data['error']  = $this->M_Manajemen_app->userModify();
        $data['status'] = $this->db->transStatus();
        echo json_encode($data);
    }

    public function userDelete()
    {
        $this->menu_kode = '2200';
        $this->Auth->cekMenu($this->menu_kode, 'd');
        $this->M_Manajemen_app->userDelete();
        $data['status'] = $this->db->transStatus();
        echo json_encode($data);
    }

    // -----------------------------------------------------
    public function konfigurasi_app()
    {
        $this->menu_kode = '2300';
        $this->Auth->cekMenu($this->menu_kode, 'r');
        $this->data['menu_detail'] = $this->data['menu_list'][$this->menu_kode];
        $this->data['data_list']   = $this->M_Manajemen_app->KonfigurasiAppGetData();
        $this->data['jenis_list']  = $this->M_Manajemen_app->JenisPengajuanList();
        $jenisSel = (int) $this->request->getGet('jenis');
        if ($jenisSel <= 0 && ! empty($this->data['jenis_list'])) {
            $jenisSel = (int) $this->data['jenis_list'][0]['JenisID'];
        }
        $this->data['jenis_selected'] = $jenisSel;
        $this->data['flow']           = $this->M_Manajemen_app->ApprovalFlowGetData($jenisSel);
        $this->data['body']           = 'manajemen_app/KonfigurasiApp';
        $this->data['footer']         = 'manajemen_app/KonfigurasiAppFooter';

        return view('main', $this->data);
    }

    public function KonfigurasiAppModify()
    {
        $this->menu_kode = '2300';
        $this->Auth->cekMenu($this->menu_kode, 'u');
        $this->M_Manajemen_app->KonfigurasiAppModify();

        $filename = isset($_FILES['logo_big']['name']) ? $_FILES['logo_big']['name'] : '';
        if ($filename) {
            $location      = 'assets/images/logo_baru.png';
            $imageFileType = pathinfo($location, PATHINFO_EXTENSION);
            if ($imageFileType === 'png') {
                move_uploaded_file($_FILES['logo_big']['tmp_name'], $location);
            }
        }

        $filename = isset($_FILES['logo_small']['name']) ? $_FILES['logo_small']['name'] : '';
        if ($filename) {
            $location      = 'assets/images/logo_baru.png';
            $imageFileType = pathinfo($location, PATHINFO_EXTENSION);
            if ($imageFileType === 'png') {
                move_uploaded_file($_FILES['logo_small']['tmp_name'], $location);
            }
        }

        $filename = isset($_FILES['cover_logo']['name']) ? $_FILES['cover_logo']['name'] : '';
        if ($filename) {
            $location      = 'assets/images/logo_baru.png';
            $imageFileType = pathinfo($location, PATHINFO_EXTENSION);
            if ($imageFileType === 'png') {
                move_uploaded_file($_FILES['cover_logo']['tmp_name'], $location);
            }
        }

        // Dulu redirect+refresh (tanpa umpan balik). Sekarang AJAX -> JSON supaya
        // bisa munculkan pop-up "tersimpan" lalu reload dari sisi klien.
        if ($this->request->isAJAX()) {
            echo json_encode(['ok' => true, 'msg' => 'Konfigurasi aplikasi tersimpan.']);

            return;
        }
        legacy_redirect(base_url('manajemen_app/konfigurasi_app'));
    }

    public function KonfigurasiAppFlowOrder()
    {
        $this->menu_kode = '2300';
        $this->Auth->cekMenu($this->menu_kode, 'c');
        $data['error']  = $this->M_Manajemen_app->KonfigurasiAppFlowOrder();
        $data['status'] = $this->db->transStatus();
        echo json_encode($data);
    }
}
