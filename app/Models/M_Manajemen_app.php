<?php

namespace App\Models;

class M_Manajemen_app extends BaseModel
{
    private $UserID;
    private $UserName;
    private $serGroupID;
    private $date_time_now;

    public function __construct()
    {
        parent::__construct();
        $this->UserID        = $this->session->get('UserID');
        $this->UserName      = $this->session->get('UserName');
        $this->serGroupID    = $this->session->get('serGroupID');
        $this->date_time_now = date('Y-m-d H:i:s');
    }

    /** SELECT dasar (alias kolom = key DataTables) untuk server-side paging. */
    public function userGroupGetListSql()
    {
        return 'SELECT ROW_NUMBER() OVER (ORDER BY UserGroupID) `No`,
                    UserGroupID,
                    UserGroupName AS Nama,
                    UserGroupNote AS Note
                FROM tb_users_group g';
    }

    /** Tambahkan kolom Action untuk baris di halaman aktif. */
    public function userGroupDecorateRows($rows)
    {
        $out = [];
        foreach ($rows as $value) {
            $value['Action'] =
                '<button type="button" class="btn btn-primary btn-xs mr-1 GroupUserEdit"
                    data-toggle="modal" data-target="#modal-xl-UserGroup" data="' . $value['UserGroupID'] . '">
                    ' . svgico('edit', 14) . '
                </button>'
                . '<button type="button" class="btn btn-danger btn-xs mr-1 GroupUserDelete"
                    data="' . $value['UserGroupID'] . '">
                    ' . svgico('trash', 14) . '
                </button>';
            $out[] = $value;
        }

        return $out;
    }

    /** Daftar lengkap tanpa paging -- dipertahankan untuk pemakai lama. */
    public function userGroupGetList()
    {
        $rows = $this->db->query($this->userGroupGetListSql())->getResultArray();

        return $this->userGroupDecorateRows($rows);
    }

    public function userGroupGetData($UserGroupID = null)
    {
        $filter = '';
        $bind   = [];
        if (! is_null($UserGroupID)) {
            $filter .= 'AND UserGroupID = ? ';
            $bind[]  = $UserGroupID;
        }

        $sql   = 'SELECT ROW_NUMBER() OVER (PARTITION BY UserGroupName ) row_num, ug.*
        FROM tb_users_group ug WHERE 1=1  ' . $filter;
        $query = $this->db->query($sql, $bind);
        $result['user_group'] = $query->getResultArray();

        $sql   = 'SELECT * FROM tb_menu_access WHERE 1=1 ' . $filter;
        $query = $this->db->query($sql, $bind);
        $result['menu_access'] = $query->getResultArray();

        return $result;
    }

    public function userGroupModify()
    {
        $this->db->transStart();

        $UserGroupID    = $this->request->getPost('UserGroupID');
        $UserGroupName  = $this->request->getPost('UserGroupName');
        $UserGroupNote  = $this->request->getPost('UserGroupNote');
        $menu_access_id = $this->request->getPost('menu_access_id');
        $MenuID         = $this->request->getPost('MenuID');
        $Status_R       = $this->request->getPost('Status_R');
        $Status_C       = $this->request->getPost('Status_C');
        $Status_U       = $this->request->getPost('Status_U');
        $Status_D       = $this->request->getPost('Status_D');

        $data = [
            'UserGroupName' => ucwords($UserGroupName),
            'UserGroupNote' => strtoupper($UserGroupNote),
        ];
        if ($UserGroupID == '') {
            $this->db->table('tb_users_group')->insert($data);
            $UserGroupID = $this->db->insertID();
        } else {
            $this->Auth->cekMenu('2100', 'u');
            $this->db->table('tb_users_group')->where('UserGroupID', $UserGroupID)->update($data);
        }

        for ($i = 0; $i < count($menu_access_id); $i++) {
            $data = [
                'UserGroupID' => $UserGroupID,
                'MenuID'      => $MenuID[$i],
                'Status_C'    => $Status_C[$i],
                'Status_R'    => $Status_R[$i],
                'Status_U'    => $Status_U[$i],
                'Status_D'    => $Status_D[$i],
            ];

            if ($menu_access_id[$i] == '0') {
                $this->db->table('tb_menu_access')->insert($data);
            } else {
                $this->db->table('tb_menu_access')->where('MenuAccessID', $menu_access_id[$i])->update($data);
            }
        }

        if ($this->db->error()['code'] != 0) {
            return $this->db->error();
        }
        $this->db->transComplete();
    }

    public function userGroupDelete()
    {
        $this->db->transStart();

        $UserGroupID = $this->request->getPost('UserGroupID');
        $rows        = $this->db->table('tb_users')->getWhere(['UserGroupID' => $UserGroupID])->getResultArray();
        if (count($rows) > 0) {
            $this->Auth->alert_error_response('gagal delete,<br>ada data user dengan group ini !');
        }

        $this->db->table('tb_users_group')->where('UserGroupID', $UserGroupID)->delete();
        $this->db->table('tb_menu_access')->where('UserGroupID', $UserGroupID)->delete();

        $this->db->transComplete();
    }

    // -------------------------------------------------
    /** SELECT dasar (alias kolom = key DataTables) untuk server-side paging. */
    public function userGetListSql()
    {
        return 'SELECT ROW_NUMBER() OVER (ORDER BY u.UserID) `No`,
                    u.UserID, u.UserName, u.UserFullName, u.UserPosition,
                    u.UserPhone, u.UserNote, u.UserAktif
                FROM tb_users u';
    }

    /** Tambahkan kolom Status (ikon aktif/nonaktif) + Action. */
    public function userDecorateRows($rows)
    {
        $out = [];
        foreach ($rows as $value) {
            $value['Status'] = ($value['UserAktif'] == 1)
                ? svgico('approval-check', 18, 'text-green')
                : svgico('reject', 18);
            $value['Action'] =
                '<button type="button" class="btn btn-primary btn-xs mr-1 UserEdit" title="Ubah Data"
                    data-toggle="modal" data-target="#modal-l-User" data="' . $value['UserID'] . '">
                    ' . svgico('edit', 14) . '
                </button>';
            $out[] = $value;
        }

        return $out;
    }

    /** Daftar lengkap tanpa paging -- dipertahankan untuk pemakai lama. */
    public function userGetList()
    {
        $rows = $this->db->query($this->userGetListSql())->getResultArray();

        return $this->userDecorateRows($rows);
    }

    public function userGetData($UserID = null)
    {
        $filter = '';
        $bind   = [];
        if (! is_null($UserID)) {
            $filter .= 'AND UserID = ? ';
            $bind[]  = $UserID;
        }

        $sql   = 'SELECT ROW_NUMBER() OVER (ORDER BY u.UserID) row_num,
                u.*, u.UserFullName AS PegawaiNama
                FROM tb_users u
                WHERE 1=1 ' . $filter;
        $query = $this->db->query($sql, $bind);
        $result['user'] = $query->getResultArray();

        return $result;
    }

    public function userModify()
    {
        $this->db->transStart();

        $UserID       = $this->request->getPost('UserID');
        $UserName     = $this->request->getPost('UserName');
        $UserFullName = $this->request->getPost('UserFullName');
        $UserPosition = $this->request->getPost('UserPosition');
        $UserNote     = $this->request->getPost('UserNote');
        $UserPassword = $this->request->getPost('UserPassword');
        $UserPhone    = $this->request->getPost('UserPhone');
        $UserAktif    = $this->request->getPost('UserAktif');
        $PegawaiID    = $this->request->getPost('PegawaiID');

        $data = [
            'UserID'       => $UserID,
            'UserName'     => $UserName,
            'UserFullName' => $UserFullName,
            'UserPosition' => $UserPosition,
            'UserNote'     => $UserNote,
            'UserPhone'    => $UserPhone,
            'UserAktif'    => $UserAktif,
            'PegawaiID'    => $PegawaiID,
        ];
        if ($UserPassword != '') {
            // Verifikasi 2 langkah: "Ulangi Kata Sandi Baru" wajib sama.
            $verify = (string) $this->request->getPost('UserPasswordVerify');
            if (strlen($UserPassword) < 4) {
                $this->db->transComplete();

                return ['code' => 1, 'message' => 'Kata sandi baru minimal 4 karakter.'];
            }
            if ($UserPassword !== $verify) {
                $this->db->transComplete();

                return ['code' => 1, 'message' => 'Verifikasi kata sandi baru tidak cocok.'];
            }
            // md5() lama gampang di-brute-force; M_Login::PasswordMatches() sudah
            // membedakan hash bcrypt (prefiks '$2') dari md5 lama, jadi password
            // BARU yang di-set lewat sini aman langsung pakai bcrypt -- tidak
            // perlu "upgrade on login" seperti hash lama yang sudah kadung ada.
            $data['UserPassword'] = password_hash($UserPassword, PASSWORD_DEFAULT);
        }

        if ($UserID == '') {
            $this->db->table('tb_users')->insert($data);
        } else {
            $this->Auth->cekMenu('2200', 'u');
            $this->db->table('tb_users')->where('UserID', $UserID)->update($data);
        }

        if ($this->db->error()['code'] != 0) {
            return $this->db->error();
        }
        $this->db->transComplete();
    }

    public function userDelete()
    {
        $this->db->transStart();

        $UserID = $this->request->getPost('UserID');
        $this->db->table('tb_users')->where('UserID', $UserID)->delete();

        $this->db->transComplete();
    }

    // -------------------------------------------------
    public function KonfigurasiAppGetData()
    {
        $vrbl = [
            'app_title',
            'app_description',
            'logo_big',
            'logo_small',
            'cover_logo',
            'cover_description',
            'link_panduan',
            'link_anggaran',
            'sla_total_hk',
            'sla_warn_total_hk',
            'sla_warn_stage_hk',
        ];
        $query  = $this->db->table('tb_vrbl')->whereIn('VrblName', $vrbl)->get()->getResultArray();
        $result = [];
        foreach ($query as $key => $value) {
            $result[$value['VrblName']] = $value['VrblValue'];
        }

        return $result;
    }

    public function KonfigurasiAppModify()
    {
        $this->db->transStart();

        $app_title         = $this->request->getPost('app_title');
        $app_description   = $this->request->getPost('app_description');
        $cover_description = $this->request->getPost('cover_description');
        $link_panduan      = $this->request->getPost('link_panduan');
        $link_anggaran     = $this->request->getPost('link_anggaran');

        $this->db->table('tb_vrbl')->where('VrblName', 'app_title')->update(['VrblValue' => $app_title]);
        $this->db->table('tb_vrbl')->where('VrblName', 'app_description')->update(['VrblValue' => $app_description]);
        $this->db->table('tb_vrbl')->where('VrblName', 'cover_description')->update(['VrblValue' => $cover_description]);
        $this->db->table('tb_vrbl')->where('VrblName', 'link_panduan')->update(['VrblValue' => $link_panduan]);
        $this->db->table('tb_vrbl')->where('VrblName', 'link_anggaran')->update(['VrblValue' => $link_anggaran]);

        // Ambang batas Peringatan Dini (SLA 4HK). Baris mungkin belum ada
        // di tb_vrbl (tabel tanpa PK), jadi cek dulu lalu insert/update.
        $sla = [
            'sla_total_hk'      => (int) $this->request->getPost('sla_total_hk'),
            'sla_warn_total_hk' => (int) $this->request->getPost('sla_warn_total_hk'),
            'sla_warn_stage_hk' => (int) $this->request->getPost('sla_warn_stage_hk'),
        ];
        foreach ($sla as $name => $val) {
            if ($val <= 0) {
                continue;
            }
            $this->_vrbl_set($name, $val);
        }

        $this->db->transComplete();
    }

    private function _vrbl_set($name, $value)
    {
        $exists = $this->db->table('tb_vrbl')->getWhere(['VrblName' => $name])->getNumRows() > 0;
        if ($exists) {
            $this->db->table('tb_vrbl')->where('VrblName', $name)->update(['VrblValue' => $value]);
        } else {
            $this->db->table('tb_vrbl')->insert(['VrblName' => $name, 'VrblValue' => $value]);
        }
    }

    public function ApprovalFlowGetData($jenisID = 1)
    {
        return $this->db->table('tb_approval_flow')->where('JenisID', (int) $jenisID)
            ->orderBy('FlowOrder', 'ASC')->get()->getResultArray();
    }

    /** Semua jenis pengajuan (aktif + nonaktif) untuk selector editor alur. */
    public function JenisPengajuanList()
    {
        if (! $this->db->tableExists('tb_jenis_pengajuan')) {
            return [['JenisID' => 1, 'JenisNama' => 'Perjalanan Dinas (LS)', 'JenisAktif' => 1]];
        }

        return $this->db->table('tb_jenis_pengajuan')
            ->orderBy('JenisUrutan', 'ASC')->orderBy('JenisNama', 'ASC')->get()->getResultArray();
    }

    public function KonfigurasiAppFlowOrder()
    {
        $this->db->transStart();

        $jenisID = (int) $this->request->getPost('JenisID');
        if ($jenisID <= 0) {
            $jenisID = 1;
        }
        $id     = $this->request->getPost('ID');
        $active = $this->request->getPost('active');

        $no = 0;
        for ($i = 0; $i < count($id); $i++) {
            $b = $this->db->table('tb_approval_flow');
            if ($active[$i] != '0') {
                $no += 1;
                $b->set('FlowOrder', $no);
            } else {
                $b->set('FlowOrder', 0);
            }
            // Batasi ke jenis yang sedang diedit -> ID asing hasil tamper tak bisa dinomori ulang.
            $b->where('ID', $id[$i]);
            $b->where('JenisID', $jenisID);
            $b->update();
        }

        $this->db->transComplete();
    }
}
