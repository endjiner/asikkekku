<?php

namespace App\Models;

use App\Libraries\Wa;

class M_Dashboard extends BaseModel
{
    private $UserID;
    private $UserGroupID;
    private $UserName;
    private $UserPosition;
    private $date_time_now;

    public function __construct()
    {
        parent::__construct();
        $this->UserID        = $this->session->get('UserID');
        $this->UserGroupID   = $this->session->get('UserGroupID');
        $this->UserName      = $this->session->get('UserName');
        $this->UserPosition  = $this->session->get('UserPosition');
        $this->date_time_now = date('Y-m-d H:i:s');
    }

    /**
     * Diresolve lambat (bukan di constructor): M_Manajemen_approval juga
     * memuat M_Dashboard di constructor-nya, jadi memuat eager di sini bikin
     * keduanya saling tunggu selesai construct (infinite recursion) --
     * beda dari CI3 dulu, `Factories` CI4 baru men-cache instance SETELAH
     * constructor-nya selesai, bukan sebelum seperti `CI_Loader::model()`.
     */
    private function manajemenApproval(): M_Manajemen_approval
    {
        return model(M_Manajemen_approval::class);
    }

    public function GetDataAll()
    {
        // Hitung langsung di database (COUNT(*)) -- jangan tarik seluruh baris
        // lalu count() di PHP. Ini yang bikin dashboard tetap ringan walau
        // data tb_kegiatan sudah ribuan baris.
        $data['data_kegiatan'] = (int) $this->db->query(
            'SELECT COUNT(*) c FROM tb_kegiatan WHERE KegiatanDeletedAt IS NULL')->getRow()->c;
        $data['data_on_progress'] = (int) $this->db->query(
            "SELECT COUNT(*) c FROM tb_kegiatan WHERE KegiatanDeletedAt IS NULL AND KegiatanStatus = 'Approval OnProgress'")->getRow()->c;
        $data['data_complete'] = (int) $this->db->query(
            "SELECT COUNT(*) c FROM tb_kegiatan WHERE KegiatanDeletedAt IS NULL AND KegiatanStatus = 'Approval Selesai'")->getRow()->c;
        // Antrian persetujuan difilter per posisi lewat query kompleks di model
        // approval; sementara tetap pakai method itu (lihat catatan optimasi).
        $data['data_need_approve'] = count($this->manajemenApproval()->KegiatanApprovalGetList());

        return $data;
    }

    /**
     * Daftar hal yang perlu ditindak oleh user yang sedang login,
     * disesuaikan dengan posisinya (quick action di dashboard).
     */
    public function GetActionItems($limit = 8, &$total = null)
    {
        $pos   = $this->UserPosition;
        $items = [];

        if ($pos === 'PJ-Kegiatan') {
            // Draf milik sendiri yang belum diajukan
            $rows = $this->db->query(
                "SELECT KegiatanID, KegiatanJudul, KegiatanNoSuratTugas, KegiatanStatus, KegiatanKeteranganTerakhir
                 FROM tb_kegiatan
                 WHERE KegiatanUserID = ? AND KegiatanStatus = 'editable' AND KegiatanDeletedAt IS NULL
                 ORDER BY KegiatanID DESC", [$this->UserID])->getResultArray();
            foreach ($rows as $r) {
                $items[] = $this->_actionRow($r, 'Lengkapi & Kirim', base_url('manajemen_approval/list_data'), 'draft');
            }
        } else {
            // Petugas alur / admin: antrian persetujuan (sudah difilter per posisi di model)
            $rows = $this->manajemenApproval()->KegiatanApprovalGetList();
            foreach ($rows as $r) {
                $items[] = $this->_actionRow($r, 'Tinjau', base_url('manajemen_approval/list_approval'), 'review');
            }
        }
        $total = count($items);

        return ($limit > 0) ? array_slice($items, 0, $limit) : $items;
    }

    private function _actionRow($r, $cta, $url, $kind)
    {
        return [
            'KegiatanID' => $r['KegiatanID'],
            'judul'      => $r['KegiatanJudul'],
            'no_surat'   => $r['KegiatanNoSuratTugas'],
            'ket'        => isset($r['KegiatanKeteranganTerakhir']) ? $r['KegiatanKeteranganTerakhir'] : '',
            'kind'       => $kind,
            'cta'        => $cta,
            'url'        => $url,
        ];
    }

    /**
     * Peringatan Dini 4HK untuk dashboard: daftar pengajuan yang mulai
     * terhambat menuju pencairan, disaring sesuai peran user login.
     */
    public function GetEarlyWarnings($limit = 20)
    {
        $pos         = $this->UserPosition;
        $isAdmin     = ($this->UserGroupID == 1 || $pos === 'SuperAdmin');
        $petugasAlur = ['PPK-Staff', 'Verifikator', 'SPP', 'SPM', 'PPK', 'PPSPM'];

        $map = $this->manajemenApproval()->SlaEvalBatch();

        $summary = ['aman' => 0, 'mepet' => 0, 'terlambat' => 0, 'dikembalikan' => 0];
        $warn    = [];

        foreach ($map as $kid => $eval) {
            $r = $eval['_row'];

            if ($isAdmin) {
                // lihat semua
            } elseif ($pos === 'PJ-Kegiatan') {
                if ((int) $r['KegiatanUserID'] !== (int) $this->UserID) {
                    continue;
                }
            } elseif (in_array($pos, $petugasAlur, true)) {
                if ($r['PendingPosition'] !== $pos) {
                    continue;
                }
                if ((int) $r['FlowDestUser'] !== (int) $this->UserID) {
                    continue;
                }
            } else {
                continue;
            }

            $lv = $eval['level'];
            if (isset($summary[$lv])) {
                $summary[$lv]++;
            }

            if (in_array($lv, ['mepet', 'terlambat', 'dikembalikan'], true)) {
                $warn[] = [
                    'KegiatanID'  => $kid,
                    'judul'       => $r['KegiatanJudul'],
                    'no_surat'    => $r['KegiatanNoSuratTugas'],
                    'stage'       => $eval['stage_position'],
                    'level'       => $lv,
                    'level_text'  => $eval['level_text'],
                    'badge_class' => $eval['badge_class'],
                    'elapsed_hk'  => $eval['elapsed_hk'],
                    'sisa_hk'     => $eval['sisa_hk'],
                    'stage_hk'    => $eval['stage_elapsed_hk'],
                    'deadline'    => $eval['deadline'],
                ];
            }
        }

        usort($warn, function ($a, $b) {
            $rank = ['terlambat' => 0, 'dikembalikan' => 1, 'mepet' => 2];
            if ($rank[$a['level']] !== $rank[$b['level']]) {
                return $rank[$a['level']] - $rank[$b['level']];
            }

            return $b['elapsed_hk'] - $a['elapsed_hk'];
        });

        return [
            'items'     => array_slice($warn, 0, $limit),
            'summary'   => $summary,
            'can_nudge' => ($isAdmin || in_array($pos, $petugasAlur, true)),
        ];
    }

    public function GetQuickActions()
    {
        $pos      = $this->UserPosition;
        $approval = base_url('manajemen_approval/list_approval');
        $data     = base_url('manajemen_approval/list_data');
        $report   = base_url('manajemen_approval/list_report');

        if (role_can('manajemen_app')) {
            return [
                ['label' => 'Kelola Pengguna',   'url' => base_url('manajemen_app/user_list'),        'icon' => 'users',  'tone' => 'primary'],
                ['label' => 'Konfigurasi App',   'url' => base_url('manajemen_app/konfigurasi_app'),  'icon' => 'config', 'tone' => 'primary'],
                ['label' => 'Semua Pengajuan',   'url' => $data,     'icon' => 'activity',       'tone' => 'ghost'],
                ['label' => 'Laporan Bulanan',   'url' => $report,   'icon' => 'report',         'tone' => 'ghost'],
            ];
        }

        if (role_can('kegiatan_create') && ! role_can('approval_inbox')) {
            return [
                ['label' => 'Buat Pengajuan Baru', 'url' => $data . '?new=1', 'icon' => 'add',      'tone' => 'primary'],
                ['label' => 'Data Kegiatan Saya',  'url' => $data,            'icon' => 'activity', 'tone' => 'ghost'],
            ];
        }

        if (! role_can('approval_inbox')) {
            return [
                ['label' => 'Data Kegiatan', 'url' => $data, 'icon' => 'activity', 'tone' => 'primary'],
            ];
        }

        // Petugas alur
        $labelAntrian = 'Antrian Persetujuan Saya';
        switch ($pos) {
            case 'Verifikator': $labelAntrian = 'Antrian Verifikasi'; break;
            case 'PPK-Staff':   $labelAntrian = 'Antrian Staf PPK'; break;
            case 'PPK':         $labelAntrian = 'Antrian PPK'; break;
            case 'SPP':         $labelAntrian = 'Antrian SPP'; break;
            case 'SPM':         $labelAntrian = 'Antrian SPM'; break;
            case 'PPSPM':       $labelAntrian = 'Antrian PPSPM'; break;
        }

        return [
            ['label' => $labelAntrian,      'url' => $approval, 'icon' => 'approval-inbox', 'tone' => 'primary'],
            ['label' => 'Lihat Semua Data', 'url' => $data,     'icon' => 'activity',       'tone' => 'ghost'],
            ['label' => 'Laporan Bulanan',  'url' => $report,   'icon' => 'report',         'tone' => 'ghost'],
        ];
    }

    public function send_text($user_src, $user_dst, $text_src, $text_dst, $phone_pemohon = '', $text_pemohon = '', $context = 'umum', $kegiatanID = null)
    {
        $row       = $this->db->table('tb_users')->where('UserID', $user_src)->get()->getResultArray();
        $phone_src = empty($row) ? '' : $row[0]['UserPhone'];

        $row       = $this->db->table('tb_users')->where('UserID', $user_dst)->get()->getResultArray();
        $phone_dst = empty($row) ? '' : $row[0]['UserPhone'];

        return (new Wa())->send_text($phone_src, $phone_dst, $text_src, $text_dst, $phone_pemohon, $text_pemohon, $context, $kegiatanID);
    }
}
