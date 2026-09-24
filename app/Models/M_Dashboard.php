<?php

namespace App\Models;

use App\Libraries\Wa;

class M_Dashboard extends BaseModel
{
    private $UserID;
    private $UserGroupID;
    private $UserName;
    private $UserPosition;

    public function __construct()
    {
        parent::__construct();
        $this->UserID       = $this->session->get('UserID');
        $this->UserGroupID  = $this->session->get('UserGroupID');
        $this->UserName     = $this->session->get('UserName');
        $this->UserPosition = $this->session->get('UserPosition');
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

    public function GetPjSummary()
    {
        $uid = (int) $this->UserID;
        $draft = (int) $this->db->query(
            "SELECT COUNT(*) c FROM tb_kegiatan WHERE KegiatanUserID = ? AND KegiatanStatus = 'editable' AND KegiatanDeletedAt IS NULL", [$uid])->getRow()->c;
        $revisi = (int) $this->db->query(
            "SELECT COUNT(*) c FROM tb_kegiatan WHERE KegiatanUserID = ? AND KegiatanStatus = 'Perlu Revisi' AND KegiatanDeletedAt IS NULL", [$uid])->getRow()->c;
        $onProgress = (int) $this->db->query(
            "SELECT COUNT(*) c FROM tb_kegiatan WHERE KegiatanUserID = ? AND KegiatanStatus = 'Approval OnProgress' AND KegiatanDeletedAt IS NULL", [$uid])->getRow()->c;
        $complete = (int) $this->db->query(
            "SELECT COUNT(*) c FROM tb_kegiatan WHERE KegiatanUserID = ? AND KegiatanStatus = 'Approval Selesai' AND KegiatanDeletedAt IS NULL", [$uid])->getRow()->c;
        $total = (int) $this->db->query(
            "SELECT COUNT(*) c FROM tb_kegiatan WHERE KegiatanUserID = ? AND KegiatanDeletedAt IS NULL", [$uid])->getRow()->c;

        return [
            'draft'          => $draft,
            'revisi'         => $revisi,
            'perlu_tindakan' => $draft + $revisi,
            'on_progress'    => $onProgress,
            'complete'       => $complete,
            'total'          => $total,
        ];
    }

    /**
     * Khusus PJ-Kegiatan: Daftar seluruh pengajuan milik PJ yang aktif/terbaru,
     * dilengkapi status terakhir dan posisi meja verifikasi secara real-time.
     */
    public function GetPjSubmissions($limit = 15, &$total = null)
    {
        $uid = (int) $this->UserID;

        $rows = $this->db->query("
            SELECT g.KegiatanID, g.KegiatanJudul, g.KegiatanNoSuratTugas, g.KegiatanStatus, 
                   g.KegiatanStatusTerakhir, g.KegiatanKeteranganTerakhir, g.KegiatanNamaPelaksana,
                   g.KegiatanTanggal, COALESCE(g.KegiatanJenisID, 1) AS KegiatanJenisID
            FROM tb_kegiatan g
            WHERE g.KegiatanUserID = ? AND g.KegiatanDeletedAt IS NULL
            ORDER BY 
                CASE 
                    WHEN g.KegiatanStatus = 'Perlu Revisi' THEN 1
                    WHEN g.KegiatanStatus = 'editable' THEN 2
                    WHEN g.KegiatanStatus = 'Approval OnProgress' THEN 3
                    WHEN g.KegiatanStatus = 'Approval Selesai' THEN 4
                    ELSE 5
                END ASC,
                g.KegiatanID DESC
        ", [$uid])->getResultArray();

        $total = count($rows);
        $slice = ($limit > 0) ? array_slice($rows, 0, $limit) : $rows;
        if (empty($slice)) {
            return [];
        }

        $onProgIds = [];
        foreach ($slice as $r) {
            if ($r['KegiatanStatus'] === 'Approval OnProgress') {
                $onProgIds[] = (int) $r['KegiatanID'];
            }
        }
        $slaMap = ! empty($onProgIds) ? $this->manajemenApproval()->SlaEvalBatch($onProgIds) : [];

        $flowRows = $this->db->query("
            SELECT FlowOrder, FlowCode, FlowPosition, FlowNote, JenisID
            FROM tb_approval_flow
            WHERE FlowOrder > 0
            ORDER BY FlowOrder ASC
        ")->getResultArray();

        $flowByJenis = [];
        foreach ($flowRows as $f) {
            $j = (int) $f['JenisID'];
            $flowByJenis[$j][] = $f;
        }

        $shortPos = function ($pos) {
            $m = [
                'PJ-Kegiatan' => 'Pengusulan (PJ)',
                'PPK-Staff'   => 'Staf PPK',
                'SPM'         => 'SPM',
                'Verifikator' => 'Verifikator',
                'PPK'         => 'PPK',
                'PPSPM'       => 'PPSPM',
            ];
            return isset($m[$pos]) ? $m[$pos] : $pos;
        };

        $items = [];
        foreach ($slice as $r) {
            $kid        = (int) $r['KegiatanID'];
            $st         = $r['KegiatanStatus'];
            $jenis      = (int) $r['KegiatanJenisID'];
            $stages     = isset($flowByJenis[$jenis]) ? $flowByJenis[$jenis] : (isset($flowByJenis[1]) ? $flowByJenis[1] : []);
            $maxStages  = count($stages);

            $currentStageOrder = 1;
            $currentDeskName   = 'Pengusulan (PJ)';
            $badgeType         = 'draft';
            $statusText        = 'Draf (Belum Dikirim)';
            $cta               = 'Lengkapi & Kirim';
            $url               = base_url('manajemen_approval/list_data');
            $slaLevel          = null;
            $elapsedHk         = 0;

            if ($st === 'editable') {
                $currentStageOrder = 1;
                $currentDeskName   = 'Pengusulan (PJ)';
                $badgeType         = 'draft';
                $statusText        = 'Draf Belum Diajukan';
                $cta               = 'Lengkapi & Kirim';
            } elseif ($st === 'Perlu Revisi') {
                $currentStageOrder = 1;
                $currentDeskName   = 'Perlu Revisi oleh PJ';
                $badgeType         = 'revisi';
                $statusText        = 'Perlu Revisi';
                $cta               = 'Perbaiki Data';
            } elseif ($st === 'Approval OnProgress') {
                $badgeType = 'progress';
                if (isset($slaMap[$kid])) {
                    $eval            = $slaMap[$kid];
                    $currentDeskName = $eval['stage_position'] ? $shortPos($eval['stage_position']) : 'Petugas Verifikasi';
                    $slaLevel        = $eval['level'];
                    $elapsedHk       = (int) $eval['stage_elapsed_hk'];
                    $statusText      = 'Sedang di Meja ' . $currentDeskName;
                } else {
                    $currentDeskName = 'Verifikasi';
                    $statusText      = 'Sedang Diproses';
                }

                // Tentukan step order
                foreach ($stages as $stg) {
                    if ($stg['FlowPosition'] === $currentDeskName || $shortPos($stg['FlowPosition']) === $currentDeskName || $stg['FlowCode'] === $r['KegiatanStatusTerakhir']) {
                        $currentStageOrder = (int) $stg['FlowOrder'];
                        break;
                    }
                }
                $cta = 'Pantau Progres';
            } elseif ($st === 'Approval Selesai') {
                $currentStageOrder = $maxStages > 0 ? $maxStages : 7;
                $currentDeskName   = 'Penerbitan SPP / SP2D Selesai';
                $badgeType         = 'success';
                $statusText        = 'Selesai Dicairkan';
                $cta               = 'Lihat Berkas';
            } elseif ($st === 'Dibatalkan') {
                $badgeType         = 'danger';
                $currentDeskName   = 'Pengajuan Dibatalkan';
                $statusText        = 'Dibatalkan';
                $cta               = 'Lihat Info';
            }

            $items[] = [
                'KegiatanID'          => $kid,
                'judul'               => $r['KegiatanJudul'],
                'no_surat'            => $r['KegiatanNoSuratTugas'],
                'pelaksana'           => $r['KegiatanNamaPelaksana'],
                'status'              => $st,
                'status_text'         => $statusText,
                'badge_type'          => $badgeType,
                'current_desk'        => $currentDeskName,
                'current_stage_order' => $currentStageOrder,
                'max_stages'          => $maxStages > 0 ? $maxStages : 7,
                'catatan_terakhir'    => $r['KegiatanKeteranganTerakhir'],
                'updated_at'          => $r['KegiatanTanggal'],
                'sla_level'           => $slaLevel,
                'elapsed_hk'          => $elapsedHk,
                'cta'                 => $cta,
                'url'                 => $url,
                'stages'              => $stages,
            ];
        }

        return $items;
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
            'can_nudge' => ($isAdmin || in_array($pos, $petugasAlur, true) || $pos === 'PJ-Kegiatan'),
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
