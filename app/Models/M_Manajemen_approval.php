<?php

namespace App\Models;

use App\Libraries\Wa;

class M_Manajemen_approval extends BaseModel
{
    private $UserID;
    private $UserName;
    private $UserGroupID;
    private $UserFullName;
    private $UserPosition;

    /** @var M_Dashboard */
    private $M_Dashboard;

    public function __construct()
    {
        parent::__construct();
        $this->M_Dashboard = model(M_Dashboard::class);

        $this->UserID       = $this->session->get('UserID');
        $this->UserName     = $this->session->get('UserName');
        $this->UserGroupID  = $this->session->get('UserGroupID');
        $this->UserFullName = $this->session->get('UserFullName');
        $this->UserPosition = $this->session->get('UserPosition');
    }

    /* ================= JENIS PENGAJUAN (alur per jenis) ================= */

    /** JenisID sebuah kegiatan (fallback 1 = perjadin, satu-satunya alur lama). */
    private function _jenisOf($KegiatanID)
    {
        $r = $this->db->table('tb_kegiatan')->select('KegiatanJenisID')
            ->getWhere(['KegiatanID' => (int) $KegiatanID])->getRowArray();

        return (! empty($r) && (int) $r['KegiatanJenisID'] > 0) ? (int) $r['KegiatanJenisID'] : 1;
    }

    /** Daftar jenis pengajuan aktif untuk dropdown form pembuatan pengajuan. */
    public function JenisPengajuanAktif()
    {
        if (! $this->db->tableExists('tb_jenis_pengajuan')) {
            return [['JenisID' => 1, 'JenisNama' => 'Perjalanan Dinas (LS)']];
        }

        return $this->db->table('tb_jenis_pengajuan')->select('JenisID, JenisNama')
            ->orderBy('JenisUrutan', 'ASC')->orderBy('JenisNama', 'ASC')
            ->getWhere(['JenisAktif' => 1])->getResultArray();
    }

    /** Validasi JenisID dari input (whitelist ke tb_jenis_pengajuan aktif). */
    public function JenisIsValid($JenisID)
    {
        $JenisID = (int) $JenisID;
        if ($JenisID <= 0) {
            return false;
        }
        if (! $this->db->tableExists('tb_jenis_pengajuan')) {
            return $JenisID === 1;
        }

        return $this->db->table('tb_jenis_pengajuan')->getWhere(
            ['JenisID' => $JenisID, 'JenisAktif' => 1])->getNumRows() > 0;
    }

    /** Daftar Kode Output (dari tb_vrbl 'dok_output_list', JSON). */
    public function OutputList()
    {
        $r = $this->db->table('tb_vrbl')->getWhere(['VrblName' => 'dok_output_list'])->getRowArray();
        $j = ! empty($r['VrblValue']) ? json_decode($r['VrblValue'], true) : null;

        return is_array($j) ? array_values(array_filter(array_map('trim', $j))) : [];
    }

    /** Pilihan pegawai (dari tb_users) untuk pemilih pelaksana di form pengajuan. */
    public function PegawaiOptions()
    {
        $has  = function ($c) { return $this->db->fieldExists($c, 'tb_users'); };
        $cols = 'UserID, UserName AS NIP, UserFullName AS Nama';
        foreach (['UserGol' => 'Gol', 'UserJabatan' => 'Jabatan', 'UserRekening' => 'Rekening',
            'UserBank' => 'Bank', 'UserNPWP' => 'NPWP', 'UserPhone' => 'Phone'] as $c => $as) {
            $cols .= $has($c) ? ", $c AS $as" : ", '' AS $as";
        }

        return $this->db->table('tb_users')->select($cols, false)
            ->where('UserAktif', 1)->orderBy('UserFullName', 'ASC')
            ->get()->getResultArray();
    }

    /** Simpan ulang baris pelaksana sebuah kegiatan (hapus + isi ulang). */
    private function _syncPelaksana($KegiatanID, array $rows)
    {
        if (! $this->db->tableExists('tb_kegiatan_pelaksana')) {
            return [];
        }
        $KegiatanID = (int) $KegiatanID;
        $this->db->table('tb_kegiatan_pelaksana')->where('KegiatanID', $KegiatanID)->delete();
        $nama = [];
        $urut = 0;
        foreach ($rows as $r) {
            $n = trim((string) (isset($r['nama']) ? $r['nama'] : ''));
            if ($n === '') {
                continue;
            }
            $nama[] = $n;
            $this->db->table('tb_kegiatan_pelaksana')->insert([
                'KegiatanID' => $KegiatanID,
                'UserID'     => ! empty($r['userid']) ? (int) $r['userid'] : null,
                'Nama'       => $n,
                'NIP'        => isset($r['nip']) ? trim((string) $r['nip']) : null,
                'Gol'        => isset($r['gol']) ? trim((string) $r['gol']) : null,
                'Jabatan'    => isset($r['jabatan']) ? trim((string) $r['jabatan']) : null,
                'Rekening'   => isset($r['rekening']) ? trim((string) $r['rekening']) : null,
                'Bank'       => isset($r['bank']) ? trim((string) $r['bank']) : null,
                'NPWP'       => isset($r['npwp']) ? trim((string) $r['npwp']) : null,
                'Urut'       => $urut++,
            ]);
        }

        return $nama;
    }

    /**
     * SELECT dasar daftar kegiatan (tanpa hiasan HTML). Dipakai sebagai
     * sub-query oleh M_DataTable::dtTableGetList() untuk server-side paging.
     */
    public function KegiatanGetListSql()
    {
        // Untuk PJ-Kegiatan: hanya tampilkan pengajuan yang diajukan oleh user yang sedang login
        $userFilter = '';
        if ($this->UserPosition === 'PJ-Kegiatan') {
            $userFilter = ' AND g.KegiatanUserID = ' . (int) $this->UserID;
        }

        // Sertakan info pengembalian terakhir (siapa yang harus merevisi &
        // jenisnya) supaya tombol Revisi/Hentikan Proses bisa ditampilkan.
        return 'SELECT g.KegiatanID AS row_num, g.*,
                    lh.FlowDestUser  AS RevisiUserID,
                    lh.FlowRejectType AS RevisiJenis
                FROM tb_kegiatan g
                LEFT JOIN tb_approval_history lh
                  ON lh.HistoryID = (
                        SELECT MAX(ah2.HistoryID)
                        FROM tb_approval_history ah2
                        WHERE ah2.KegiatanID = g.KegiatanID
                  )
                WHERE g.KegiatanDeletedAt IS NULL' . $userFilter;
    }

    /**
     * Tambahkan kolom hiasan (Action, KegiatanStatusBadges + badge SLA) ke
     * kumpulan baris kegiatan. Dipanggil HANYA untuk baris di halaman aktif
     * (server-side), jadi tetap ringan walau data ratusan ribu.
     */
    public function KegiatanDecorateRows($rows)
    {
        if (empty($rows)) {
            return [];
        }

        $ids = [];
        foreach ($rows as $r) {
            $ids[] = $r['KegiatanID'];
        }
        $slaMap = $this->SlaEvalBatch($ids);

        $result = [];
        foreach ($rows as $value) {
            $value['DT_RowId'] = 'row-kegiatan-' . $value['KegiatanID'];
            $value['DT_RowAttr'] = ['data-id' => $value['KegiatanID']];
            $action = '';
            $action .= ('<button type="button" class="btn btn-info btn-sm mr-1 KegiatanInfo" title="Info Detail" data-tooltip="true"
                        data-toggle="modal" data-target="#modal-xl" data="' . $value['KegiatanID'] . '" aria-label="Lihat info detail">
                            ' . svgico('info', 15) . '
                        </button>');
            $isOwnerPJ = ($this->UserPosition == 'PJ-Kegiatan' && $value['KegiatanUserID'] == $this->UserID);
            if ($value['KegiatanStatus'] == 'editable' && ($isOwnerPJ || $this->UserPosition == 'SuperAdmin')) {
                $action .= ('<button type="button" class="btn btn-success btn-sm mr-1 KegiatanSend" title="Kirim Approval" data-tooltip="true"
                            data="' . $value['KegiatanID'] . '" aria-label="Kirim approval">
                                ' . svgico('send', 15) . '
                            </button>');
                $action .= ('<button type="button" class="btn btn-primary btn-sm mr-1 KegiatanEdit" title="Edit" data-tooltip="true"
                            data-toggle="modal" data-target="#modal-l-Kegiatan" data="' . $value['KegiatanID'] . '" aria-label="Edit pengajuan">
                                ' . svgico('edit', 15) . '
                            </button>');
                $action .= ('<button type="button" class="btn btn-danger btn-sm mr-1 KegiatanDelete" title="Hapus" data-tooltip="true"
                            data="' . $value['KegiatanID'] . '" aria-label="Hapus pengajuan">
                                ' . svgico('trash', 15) . '
                            </button>');
            }

            // --- Pengajuan "Perlu Revisi" yang ditujukan ke user ini -----------
            $isRevisiPetugas = ($value['KegiatanStatus'] == 'Perlu Revisi'
                && isset($value['RevisiUserID']) && (int) $value['RevisiUserID'] === (int) $this->UserID)
                || ($value['KegiatanStatus'] == 'Perlu Revisi' && $this->UserPosition == 'SuperAdmin');
            if ($isRevisiPetugas) {
                $action .= ('<button type="button" class="btn btn-primary btn-sm mr-1 KegiatanEdit" title="Perbaiki Data" data-tooltip="true"
                            data-toggle="modal" data-target="#modal-l-Kegiatan" data="' . $value['KegiatanID'] . '" aria-label="Perbaiki data pengajuan">
                                ' . svgico('edit', 15) . '
                            </button>');
                if (($this->UserPosition == 'PJ-Kegiatan' && $value['KegiatanUserID'] == $this->UserID)
                    || $this->UserPosition == 'SuperAdmin') {
                    $action .= ('<button type="button" class="btn btn-danger btn-sm mr-1 KegiatanTerminate" title="Hentikan Proses (batalkan)" data-tooltip="true"
                                data="' . $value['KegiatanID'] . '" aria-label="Hentikan proses pengajuan">
                                    ' . svgico('reject', 15) . '
                                </button>');
                }
                $action .= ('<button type="button" class="btn btn-success btn-sm mr-1 KegiatanRevisiKirim" title="Kirim Ulang setelah revisi" data-tooltip="true"
                            data="' . $value['KegiatanID'] . '" aria-label="Kirim ulang pengajuan">
                                ' . svgico('send', 15) . '
                            </button>');
            }

            if (! in_array($value['KegiatanStatus'], ['editable', 'Perlu Revisi'], true) && $this->UserPosition == 'SuperAdmin') {
                $action .= ('<button type="button" class="btn btn-danger btn-sm mr-1 KegiatanDelete" title="Hapus" data-tooltip="true"
                            data="' . $value['KegiatanID'] . '" aria-label="Hapus pengajuan">
                                ' . svgico('trash', 15) . '
                            </button>');
            }
            $value['Action'] = $action;

            // Format Status to Soft Badge Pills
            $value['KegiatanStatusBadges'] = kegiatan_status_badge(
                $value['KegiatanStatus'],
                $value['RevisiJenis'] ?? null
            );

            // Peringatan Dini 4HK: sisipkan badge SLA untuk pengajuan yang berjalan.
            if ($value['KegiatanStatus'] == 'Approval OnProgress' && isset($slaMap[$value['KegiatanID']])) {
                $ewBadge = sla_badge($slaMap[$value['KegiatanID']]);
                if ($ewBadge !== '') {
                    $value['KegiatanStatusBadges'] .= ' ' . $ewBadge;
                }
            }

            $result[] = $value;
        }

        return $result;
    }

    /**
     * Evaluasi SLA (Peringatan Dini 4HK) untuk banyak kegiatan sekaligus.
     * Tanpa argumen: semua kegiatan berstatus 'Approval OnProgress'.
     * Mengembalikan map [KegiatanID => hasil sla_evaluasi()], plus kunci
     * '_row' berisi baris mentah (dipakai dashboard peringatan dini).
     */
    public function SlaEvalBatch($ids = null)
    {
        $filter = "k.KegiatanStatus = 'Approval OnProgress'";
        if (is_array($ids) && ! empty($ids)) {
            $clean  = array_map('intval', $ids);
            $filter = 'k.KegiatanID IN (' . implode(',', $clean) . ')';
        }
        $filter .= ' AND k.KegiatanDeletedAt IS NULL';

        $sql = "
            SELECT t2.KegiatanID, t2.KegiatanStatus, t2.KegiatanUserID, t2.KegiatanJudul,
                   t2.KegiatanNoSuratTugas, t2.KegiatanStatusTerakhir,
                   t2.FlowResult, t2.FlowDestUser, t2.mulai, t2.stage_since,
                   f2.FlowPosition AS PendingPosition, f2.FlowCode AS PendingFlowCode
            FROM (
                SELECT t1.*, IF(t1.FlowResult = 1, f.FlowOrder + 1, f.FlowOrder - 1) AS NextFlowOrder
                FROM (
                    SELECT k.KegiatanID, k.KegiatanStatus, k.KegiatanUserID, k.KegiatanJudul,
                           k.KegiatanNoSuratTugas, k.KegiatanStatusTerakhir,
                           COALESCE(k.KegiatanJenisID, 1) AS KegiatanJenisID,
                           h.FlowResult, h.FlowDestUser, h.FlowDate AS stage_since, hm.mulai
                    FROM tb_kegiatan k
                    LEFT JOIN (
                        SELECT * FROM tb_approval_history
                        WHERE HistoryID IN (SELECT MAX(HistoryID) FROM tb_approval_history GROUP BY KegiatanID)
                    ) h ON k.KegiatanID = h.KegiatanID
                    LEFT JOIN (
                        SELECT KegiatanID, MIN(FlowDate) AS mulai FROM tb_approval_history GROUP BY KegiatanID
                    ) hm ON k.KegiatanID = hm.KegiatanID
                    WHERE $filter
                ) t1
                LEFT JOIN tb_approval_flow f
                    ON t1.KegiatanStatusTerakhir = f.FlowCode AND f.JenisID = t1.KegiatanJenisID
            ) t2
            LEFT JOIN tb_approval_flow f2
                ON t2.NextFlowOrder = f2.FlowOrder AND f2.JenisID = t2.KegiatanJenisID
        ";

        $map = [];
        foreach ($this->db->query($sql)->getResultArray() as $r) {
            if (isset($map[$r['KegiatanID']])) {
                continue; // tahap nonaktif bisa memunculkan baris ganda
            }
            $eval = sla_evaluasi([
                'status'           => $r['KegiatanStatus'],
                'mulai'            => $r['mulai'],
                'stage_since'      => $r['stage_since'],
                'last_result'      => $r['FlowResult'],
                'pending_position' => $r['PendingPosition'],
            ]);
            $eval['_row']          = $r;
            $map[$r['KegiatanID']] = $eval;
        }

        return $map;
    }

    /** Evaluasi SLA satu kegiatan (dipakai modal Info & cek status publik). */
    public function KegiatanSlaEval($KegiatanID)
    {
        $KegiatanID = (int) $KegiatanID;
        $k          = $this->db->table('tb_kegiatan')->getWhere(['KegiatanID' => $KegiatanID])->getRowArray();
        if (empty($k)) {
            return sla_evaluasi([]);
        }

        $hist = $this->db->table('tb_approval_history')->orderBy('HistoryID', 'ASC')
            ->getWhere(['KegiatanID' => $KegiatanID])->getResultArray();
        if (empty($hist)) {
            return sla_evaluasi(['status' => $k['KegiatanStatus']]);
        }

        $jenis = ((int) $k['KegiatanJenisID']) > 0 ? (int) $k['KegiatanJenisID'] : 1;
        $flow  = $this->db->table('tb_approval_flow')->getWhere(['JenisID' => $jenis])->getResultArray();
        $first = $hist[0];
        $last  = $hist[count($hist) - 1];

        $byCode  = [];
        $byOrder = [];
        foreach ($flow as $f) {
            $byCode[$f['FlowCode']]              = $f;
            $byOrder[(int) $f['FlowOrder']][]    = $f;
        }
        $pendingPos = '';
        if (isset($byCode[$last['FlowCode']])) {
            $ord     = (int) $byCode[$last['FlowCode']]['FlowOrder'];
            $nextOrd = ((int) $last['FlowResult'] === 1) ? $ord + 1 : $ord - 1;
            if (! empty($byOrder[$nextOrd])) {
                $pendingPos = $byOrder[$nextOrd][0]['FlowPosition'];
            }
        }

        return sla_evaluasi([
            'status'           => $k['KegiatanStatus'],
            'mulai'            => $first['FlowDate'],
            'selesai_pada'     => $last['FlowDate'],
            'stage_since'      => $last['FlowDate'],
            'last_result'      => $last['FlowResult'],
            'pending_position' => $pendingPos,
        ]);
    }

    /**
     * Kirim pengingat WhatsApp untuk pengajuan yang terhambat (Peringatan Dini 4HK).
     *
     * @param string   $mode           'cron' (batch, anti-spam 1x/tahap/hari) atau 'manual' (tombol dashboard)
     * @param int|null $onlyKegiatanID batasi ke satu kegiatan (untuk mode manual)
     * @return array   ['terkirim' => int, 'dilewati' => int, 'detail' => array]
     */
    public function EarlyWarningKirim($mode = 'cron', $onlyKegiatanID = null)
    {
        $ids = $onlyKegiatanID ? [(int) $onlyKegiatanID] : null;
        $map = $this->SlaEvalBatch($ids);

        $allowed = ($mode === 'manual')
            ? ['mepet', 'terlambat', 'dikembalikan']
            : ['mepet', 'terlambat'];

        $hasLog   = $this->db->tableExists('tb_ew_notifikasi');
        $today    = date('Y-m-d');
        $terkirim = 0;
        $gagal    = 0;
        $dilewati = 0;
        $detail   = [];

        $wa = new Wa();

        foreach ($map as $kid => $eval) {
            if (! in_array($eval['level'], $allowed, true)) {
                $dilewati++;

                continue;
            }

            $row      = $eval['_row'];
            $flowCode = $row['PendingFlowCode'] ? $row['PendingFlowCode'] : ($row['KegiatanStatusTerakhir'] ?: '');

            if ($mode === 'cron' && $hasLog) {
                $dup = $this->db->query(
                    'SELECT id FROM tb_ew_notifikasi WHERE KegiatanID = ? AND FlowCode = ? AND DATE(TanggalKirim) = ? LIMIT 1',
                    [$kid, $flowCode, $today]
                )->getNumRows();
                if ($dup > 0) {
                    $dilewati++;

                    continue;
                }
            }

            // Nomor tujuan: petugas tahap berjalan + Penanggung Jawab Kegiatan
            $phone_officer = '';
            $phone_pj      = '';
            if (! empty($row['FlowDestUser'])) {
                $u = $this->db->table('tb_users')->getWhere(['UserID' => $row['FlowDestUser']])->getRowArray();
                if (! empty($u['UserPhone'])) {
                    $phone_officer = $u['UserPhone'];
                }
            }
            if (! empty($row['KegiatanUserID'])) {
                $u = $this->db->table('tb_users')->getWhere(['UserID' => $row['KegiatanUserID']])->getRowArray();
                if (! empty($u['UserPhone'])) {
                    $phone_pj = $u['UserPhone'];
                }
            }
            if ($phone_officer === '' && $phone_pj === '') {
                $dilewati++;

                continue;
            }

            $judul    = $row['KegiatanJudul'];
            $stage    = $row['PendingPosition'] ? $row['PendingPosition'] : 'petugas berikutnya';
            $deadline = $eval['deadline'] ? date('d/m/Y', strtotime($eval['deadline'])) : '-';

            $txt_officer = 'Pengingat: pengajuan "' . $judul . '" sudah berjalan ' . $eval['elapsed_hk'] . ' hari kerja'
                . ($eval['stage_elapsed_hk'] > 0 ? ' dan tertahan ' . $eval['stage_elapsed_hk'] . ' HK di tahap ' . $stage : '')
                . '. Target cair sebelum ' . $deadline . '. Mohon segera ditindaklanjuti.';
            $txt_pj = 'Update pengajuan "' . $judul . '": masih diproses (hari kerja ke-' . $eval['elapsed_hk'] . ', tahap ' . $stage . '). '
                . ($eval['level'] === 'terlambat'
                    ? 'Sudah melewati target 4 hari kerja, sedang dikawal petugas.'
                    : 'Mendekati batas target 4 hari kerja.');

            $items = [];
            if ($phone_officer !== '') {
                $items[] = ['phone' => $phone_officer, 'text' => $txt_officer];
            }
            if ($phone_pj !== '') {
                $items[] = ['phone' => $phone_pj, 'text' => $txt_pj];
            }

            $r         = $wa->send_bulk($items, 'peringatan_dini', $kid);
            $terkirim += $r['sent'];
            $gagal    += $r['failed'];

            if ($hasLog) {
                $this->db->table('tb_ew_notifikasi')->insert([
                    'KegiatanID'   => $kid,
                    'FlowCode'     => $flowCode,
                    'Level'        => $eval['level'],
                    'TanggalKirim' => date('Y-m-d H:i:s'),
                ]);
            }
            $detail[] = ['KegiatanID' => $kid, 'level' => $eval['level'], 'nomor' => count($items)];
        }

        return ['terkirim' => $terkirim, 'gagal' => $gagal, 'dilewati' => $dilewati, 'detail' => $detail];
    }

    public function KegiatanGetData($KegiatanID = null)
    {
        $filter = '';
        $bind   = [];
        if (! is_null($KegiatanID)) {
            $filter .= 'AND KegiatanID = ? ';
            $bind[]  = $KegiatanID;
        }

        $sql    = 'SELECT ROW_NUMBER() OVER (PARTITION BY KegiatanID ) row_num, k.* FROM tb_kegiatan k WHERE 1=1  ' . $filter;
        $query  = $this->db->query($sql, $bind);
        $result['Kegiatan'] = $query->getResultArray();

        $result['Pelaksana'] = [];
        if (! is_null($KegiatanID) && $this->db->tableExists('tb_kegiatan_pelaksana')) {
            $result['Pelaksana'] = $this->db->table('tb_kegiatan_pelaksana')->orderBy('Urut', 'ASC')->orderBy('id', 'ASC')
                ->getWhere(['KegiatanID' => (int) $KegiatanID])->getResultArray();
        }

        return $result;
    }

    public function KegiatanModify()
    {
        $this->db->transStart();

        $KegiatanID           = $this->request->getPost('KegiatanID');
        $KegiatanJudul        = $this->request->getPost('KegiatanJudul');
        $KegiatanKeterangan   = $this->request->getPost('KegiatanKeterangan');
        $KegiatanNoSuratTugas = $this->request->getPost('KegiatanNoSuratTugas');
        $KegiatanTanggal      = $this->request->getPost('KegiatanTanggal');
        $KegiatanDestUser     = $this->request->getPost('KegiatanDestUser');
        $KegiatanLampiran     = $this->request->getPost('KegiatanLampiran');
        $KegiatanLampiranPrev = $this->request->getPost('KegiatanLampiranPrev');
        $KegiatanPemohonTipe  = $this->request->getPost('KegiatanPemohonTipe');
        $KegiatanPemohonPhone = preg_replace('/\D/', '', (string) $this->request->getPost('KegiatanPemohonPhone'));

        // --- Pelaksana (repeater) + intake dokumen ---
        $pelIn   = (array) $this->request->getPost('pel'); // pel[i][userid|nama|nip|gol|jabatan|rekening|bank|npwp]
        $pelRows = [];
        foreach ($pelIn as $row) {
            if (! is_array($row)) {
                continue;
            }
            if (trim((string) (isset($row['nama']) ? $row['nama'] : '')) === '') {
                continue;
            }
            $pelRows[] = $row;
        }
        $namaPelaksana = implode('; ', array_map(function ($r) { return trim((string) $r['nama']); }, $pelRows));
        // Fallback: form lama masih kirim KegiatanNamaPelaksana teks
        if ($namaPelaksana === '') {
            $namaPelaksana = (string) $this->request->getPost('KegiatanNamaPelaksana');
        }

        $kodeOutput = trim((string) $this->request->getPost('KegiatanKodeOutput'));
        if ($kodeOutput !== '' && ! in_array($kodeOutput, $this->OutputList(), true)) {
            // terima apa adanya tapi tidak divalidasi ketat (daftar bisa berubah); simpan saja
        }

        $data = [
            'KegiatanNoSuratTugas' => $KegiatanNoSuratTugas,
            'KegiatanJudul'        => $KegiatanJudul,
            'KegiatanTanggal'      => $KegiatanTanggal,
            'KegiatanNamaPelaksana' => $namaPelaksana,
            'KegiatanKodeOutput'   => ($kodeOutput === '') ? null : $kodeOutput,
            'KegiatanAsalTujuan'   => trim((string) $this->request->getPost('KegiatanAsalTujuan')) ?: null,
            'KegiatanJmlHari'      => ((int) $this->request->getPost('KegiatanJmlHari')) ?: null,
            'KegiatanPemohonTipe'  => in_array($KegiatanPemohonTipe, ['internal', 'eksternal'], true) ? $KegiatanPemohonTipe : 'internal',
            'KegiatanPemohonPhone' => ($KegiatanPemohonPhone === '') ? null : $KegiatanPemohonPhone,
            'KegiatanKeterangan'   => $KegiatanKeterangan,
            'KegiatanLampiran'     => $KegiatanLampiranPrev,
            'KegiatanDestUser'     => $KegiatanDestUser,
            // Catatan: KegiatanUserID (pemilik/PJ) HANYA diisi saat data baru
            // dibuat -- jangan ditimpa saat diedit (mis. revisi oleh SPM/Staff
            // PPK), supaya kepemilikan tetap di PJ pembuat.
        ];

        $filename = isset($_FILES['KegiatanLampiran']['name']) ? $_FILES['KegiatanLampiran']['name'] : '';
        if ($filename) {
            $cleanName             = preg_replace('/[^a-zA-Z0-9_.-]/', '_', basename($filename));
            $data['KegiatanLampiran'] = $cleanName;
        }
        if ($KegiatanID == '') {
            // Jenis pengajuan hanya ditetapkan saat pembuatan (alur sudah berjalan
            // setelah itu). Whitelist ke tb_jenis_pengajuan aktif.
            $KegiatanJenisID = (int) $this->request->getPost('KegiatanJenisID');
            if (! $this->JenisIsValid($KegiatanJenisID)) {
                $this->db->transComplete();

                return ['code' => 1, 'message' => 'Jenis pengajuan tidak valid.'];
            }
            $data['KegiatanJenisID'] = $KegiatanJenisID;
            $data['KegiatanStatus']  = 'editable';
            $data['KegiatanUserID']  = $this->UserID;
            $this->db->table('tb_kegiatan')->insert($data);
            $KegiatanID = $this->db->insertID();
        } else {
            $this->Auth->cekMenu('3100', 'u');
            $this->db->table('tb_kegiatan')->where('KegiatanID', $KegiatanID)->update($data);
        }

        // Sinkron baris pelaksana (kalau form mengirim repeater 'pel').
        if (! empty($pelRows)) {
            $this->_syncPelaksana($KegiatanID, $pelRows);
        }

        if ($this->db->error()['code'] != 0) {
            return $this->db->error();
        }
        $this->db->transComplete();
    }

    /**
     * Hapus pengajuan. Sekarang:
     *  - HANYA SuperAdmin yang boleh (dulu semua user grup normal bisa -> risiko
     *    kehilangan data + riwayat pengajuan orang lain).
     *  - SOFT DELETE: set KegiatanDeletedAt, baris & riwayat TIDAK dibuang.
     *    Semua query daftar sudah menyaring "KegiatanDeletedAt IS NULL".
     */
    public function KegiatanDelete()
    {
        if ($this->UserPosition !== 'SuperAdmin') {
            return ['code' => 1, 'message' => 'Hanya SuperAdmin yang dapat menghapus pengajuan.'];
        }

        $this->db->transStart();
        $KegiatanID = (int) $this->request->getPost('KegiatanID');
        $this->db->table('tb_kegiatan')->where('KegiatanID', $KegiatanID)
            ->update(['KegiatanDeletedAt' => date('Y-m-d H:i:s')]);
        $this->db->transComplete();

        if ($this->db->error()['code'] != 0) {
            return $this->db->error();
        }

        return null;
    }

    public function KegiatanSendApproval()
    {
        $this->db->transStart();

        $KegiatanID = $this->request->getPost('KegiatanID');

        $kegiatan = $this->db->table('tb_kegiatan')->where('KegiatanID', $KegiatanID)->get(1)->getResultArray()[0];

        $jenis = ((int) $kegiatan['KegiatanJenisID']) > 0 ? (int) $kegiatan['KegiatanJenisID'] : 1;
        $flow  = $this->db->table('tb_approval_flow')->where('FlowOrder', 1)->where('JenisID', $jenis)->get(1)->getResultArray()[0];

        $this->db->table('tb_kegiatan')
            ->set('KegiatanStatus', 'Approval OnProgress')
            ->set('KegiatanStatusTerakhir', $flow['FlowCode'])
            ->set('KegiatanKeteranganTerakhir', 'Sudah Diajukan Approval oleh PJ-Kegiatan')
            ->where('KegiatanID', $KegiatanID)
            ->update();

        $data = [
            'KegiatanID'       => $KegiatanID,
            'FlowCode'         => $flow['FlowCode'],
            'FlowKeterangan'   => 'Sudah Diajukan Approval oleh PJ-Kegiatan',
            'FlowResult'       => 1,
            'FlowDate'         => date('Y-m-d H:i:s'),
            'FlowUserID'       => $this->UserID,
            'FlowUserName'     => $this->UserFullName,
            'FlowUserPosition' => $this->UserPosition,
            'FlowDestUser'     => $kegiatan['KegiatanDestUser'],
        ];
        $this->db->table('tb_approval_history')->insert($data);

        $text_src     = 'Pengajuanmu sudah diajukan dan akan segera diproses ... ';
        $text_dst     = 'Halo, ada pengajuan baru yang harus segera kamu tinjau ya ... ';
        $text_pemohon = 'Pengajuan pencairan dana untuk kegiatan "' . $kegiatan['KegiatanJudul'] . '" telah DIAJUKAN dan sedang dalam proses persetujuan.';
        $this->M_Dashboard->send_text(
            $this->UserID, $kegiatan['KegiatanDestUser'], $text_src, $text_dst,
            isset($kegiatan['KegiatanPemohonPhone']) ? $kegiatan['KegiatanPemohonPhone'] : '', $text_pemohon,
            'persetujuan', $KegiatanID
        );

        $this->db->transComplete();

        // Hook: auto-update Kartu Kendali TU saat pengajuan pertama dikirim.
        model(M_Dokumen::class)->kartuKendaliAutoFill((int) $KegiatanID);
    }

    public function GetFormInfoKegiatan($KegiatanID = null)
    {
        $filter = '';
        $bind   = [];
        if (! is_null($KegiatanID)) {
            $filter .= 'AND h.KegiatanID = ? ';
            $bind[]  = $KegiatanID;
        }

        $sql   = 'SELECT h.*, f.FlowNote, u.UserFullName FlowDestUserName
                FROM tb_approval_history h
                LEFT JOIN tb_kegiatan k ON k.KegiatanID = h.KegiatanID
                LEFT JOIN tb_approval_flow f
                    ON h.FlowCode = f.FlowCode AND f.JenisID = COALESCE(k.KegiatanJenisID, 1)
                LEFT JOIN tb_users u ON h.FlowDestUser = u.UserID
                WHERE 1=1 ' . $filter . ' ORDER BY HistoryID';
        $query = $this->db->query($sql, $bind);
        $result['history'] = $query->getResultArray();

        return $result;
    }

    public function GetLastStatus($KegiatanID = null)
    {
        $KegiatanID = (int) $KegiatanID;

        // Pengajuan berstatus "Perlu Revisi": petugas tujuan bertindak
        // SEBAGAI tahap tujuan itu sendiri (perbaiki data lalu kirim ulang
        // maju), bukan mundur satu tahap.
        $k     = $this->db->table('tb_kegiatan')->getWhere(['KegiatanID' => $KegiatanID])->getRowArray();
        $jenis = (! empty($k) && (int) $k['KegiatanJenisID'] > 0) ? (int) $k['KegiatanJenisID'] : 1;
        if (! empty($k) && $k['KegiatanStatus'] === 'Perlu Revisi') {
            return $this->db->table('tb_approval_flow')->getWhere(
                ['FlowCode' => $k['KegiatanStatusTerakhir'], 'JenisID' => $jenis])->getResultArray();
        }

        $sql    = "SELECT f.FlowOrder, h.FlowResult
                FROM tb_approval_flow f
                LEFT JOIN tb_kegiatan k ON k.KegiatanStatusTerakhir = f.FlowCode AND f.JenisID = COALESCE(k.KegiatanJenisID, 1)
                LEFT JOIN (SELECT FlowResult, KegiatanID FROM tb_approval_history WHERE KegiatanID = $KegiatanID ORDER BY HistoryID DESC LIMIT 1 ) h
                ON k.KegiatanID = h.KegiatanID
                WHERE k.KegiatanID = $KegiatanID";
        $result = $this->db->query($sql)->getResultArray();

        if (! empty($result)) {
            $result = $result[0];
            $filter = ($result['FlowResult'] == 1) ? 'FlowOrder > ' . $result['FlowOrder'] . ' ORDER BY FlowOrder ASC' : 'FlowOrder < ' . $result['FlowOrder'] . ' ORDER BY FlowOrder DESC';
            $sql    = 'SELECT * FROM tb_approval_flow
                    WHERE JenisID = ' . (int) $jenis . " AND $filter LIMIT 1";
            $query  = $this->db->query($sql);
            $result = $query->getResultArray();

            return $result;
        }

        return [];
    }

    // -------------------------------------------------
    /**
     * SELECT dasar antrian persetujuan (tanpa hiasan HTML). Filter per posisi
     * user dibakukan di dalam SQL memakai escape/cast yang aman -- supaya
     * hitungan total server-side juga ikut terfilter. Dipakai sebagai
     * sub-query oleh M_DataTable::dtTableGetList().
     */
    public function KegiatanApprovalGetListSql()
    {
        $where = '';
        if ($this->UserPosition != 'SuperAdmin') {
            $where = 'WHERE f2.FlowPosition = ' . $this->db->escape((string) $this->UserPosition)
                . ' AND t2.FlowDestUser = ' . (int) $this->UserID . ' ';
        }

        return "SELECT ROW_NUMBER() OVER (ORDER BY KegiatanID) row_num, t2.*, f2.FlowPosition
                FROM (
                    SELECT t1.*, IF(t1.FlowResult=1,f.FlowOrder+1,FlowOrder-1) FlowOrder
                    FROM (
                        SELECT k.*, h.FlowResult, h.FlowDestUser
                        FROM tb_kegiatan k
                        LEFT JOIN (
                            SELECT * FROM tb_approval_history
                            WHERE HistoryID IN (
                                SELECT MAX(HistoryID) HistoryID
                                FROM tb_approval_history
                                GROUP BY KegiatanID
                            )
                        ) h ON k.KegiatanID = h.KegiatanID
                        WHERE k.KegiatanStatus = 'Approval OnProgress' AND k.KegiatanDeletedAt IS NULL
                    ) t1
                    LEFT JOIN tb_approval_flow f
                        ON t1.KegiatanStatusTerakhir = f.FlowCode AND f.JenisID = COALESCE(t1.KegiatanJenisID, 1)
                ) t2
                LEFT JOIN tb_approval_flow f2
                    ON t2.FlowOrder = f2.FlowOrder AND f2.JenisID = COALESCE(t2.KegiatanJenisID, 1)
                $where";
    }

    /** Tambahkan tombol Action + badge SLA (untuk baris di halaman aktif). */
    public function KegiatanApprovalDecorateRows($rows)
    {
        if (empty($rows)) {
            return [];
        }
        $ids = [];
        foreach ($rows as $r) {
            $ids[] = $r['KegiatanID'];
        }
        $slaMap = $this->SlaEvalBatch($ids);

        $result = [];
        foreach ($rows as $value) {
            $value['Action'] =
                '<button type="button" class="btn btn-success btn-xs mr-1 KegiatanInfo"
                    data-toggle="modal" data-target="#modal-xl" data="' . $value['KegiatanID'] . '">
                    ' . svgico('check-double', 14) . '
                </button>';

            // Peringatan Dini 4HK: badge SLA di kolom status antrian.
            if (isset($slaMap[$value['KegiatanID']])) {
                $ewBadge = sla_badge($slaMap[$value['KegiatanID']]);
                if ($ewBadge !== '') {
                    $value['KegiatanStatus'] .= ' ' . $ewBadge;
                }
            }

            $result[] = $value;
        }

        return $result;
    }

    /** Daftar lengkap tanpa paging (dipakai dashboard untuk hitung antrian). */
    /** Cache per-instance: Dashboard memanggil ini 2x per load (hitungan &
     *  daftar action item) untuk data yang sama dalam satu request. */
    private $_kegiatanApprovalListCache = null;

    public function KegiatanApprovalGetList()
    {
        if ($this->_kegiatanApprovalListCache !== null) {
            return $this->_kegiatanApprovalListCache;
        }
        $rows = $this->db->query($this->KegiatanApprovalGetListSql())->getResultArray();

        return $this->_kegiatanApprovalListCache = $this->KegiatanApprovalDecorateRows($rows);
    }

    public function ApprovalFormInfoKegiatanSubmit()
    {
        $KegiatanID = (int) $this->request->getPost('KegiatanID');
        $FlowResult = (int) $this->request->getPost('FlowResult');

        return ($FlowResult === 1)
            ? $this->_approvalTeruskan($KegiatanID)
            : $this->_approvalKembalikan($KegiatanID);
    }

    /**
     * Tentukan OTOMATIS petugas tahap berikutnya (tidak perlu dipilih manual).
     * Prioritas: (1) user yang pernah menangani posisi itu untuk kegiatan ini,
     * (2) tujuan pilihan PJ saat membuat (khusus lompatan pertama PJK), (3) user
     * aktif pertama pada posisi tersebut.
     */
    private function _nextStageUser($FlowCode, $KegiatanID)
    {
        $jenis = $this->_jenisOf($KegiatanID);
        $cur   = $this->db->table('tb_approval_flow')->getWhere(
            ['FlowCode' => $FlowCode, 'JenisID' => $jenis])->getRowArray();
        if (empty($cur)) {
            return 0;
        }
        $next = $this->db->table('tb_approval_flow')->where('FlowOrder >', (int) $cur['FlowOrder'])
            ->where('JenisID', $jenis)
            ->orderBy('FlowOrder', 'ASC')->get(1)->getRowArray();
        if (empty($next)) {
            return 0; // sudah tahap terakhir
        }
        $pos = $next['FlowPosition'];

        $prev = $this->db->query(
            'SELECT h.FlowUserID FROM tb_approval_history h
             WHERE h.KegiatanID = ? AND h.FlowUserPosition = ? AND h.FlowResult = 1
             ORDER BY h.HistoryID DESC LIMIT 1',
            [(int) $KegiatanID, $pos])->getRowArray();
        if (! empty($prev['FlowUserID'])) {
            return (int) $prev['FlowUserID'];
        }

        // Lompatan pertama (tahap #1 -> #2): pakai tujuan pilihan PJ saat membuat.
        if ((int) $cur['FlowOrder'] === 1) {
            $keg = $this->db->table('tb_kegiatan')->getWhere(['KegiatanID' => (int) $KegiatanID])->getRowArray();
            if (! empty($keg['KegiatanDestUser'])) {
                return (int) $keg['KegiatanDestUser'];
            }
        }

        $u = $this->db->table('tb_users')->orderBy('UserID', 'ASC')
            ->getWhere(['UserPosition' => $pos, 'UserAktif' => 1], 1)->getRowArray();

        return ! empty($u) ? (int) $u['UserID'] : 0;
    }

    /* ---- SETUJUI / KIRIM ULANG (maju ke tahap berikutnya, OTOMATIS) ----- */
    private function _approvalTeruskan($KegiatanID)
    {
        $KegiatanID = (int) $KegiatanID;
        $cek        = $this->db->table('tb_kegiatan')->getWhere(['KegiatanID' => $KegiatanID], 1)->getRowArray();
        if (empty($cek)) {
            return ['code' => 1, 'message' => 'Pengajuan tidak ditemukan.'];
        }
        if (! empty($cek['KegiatanDeletedAt'])) {
            return ['code' => 1, 'message' => 'Pengajuan sudah dihapus, tidak bisa diproses.'];
        }
        // Non-SuperAdmin hanya boleh memproses jika memang petugas tujuan tahap ini.
        if ($this->UserPosition !== 'SuperAdmin') {
            $last = $this->db->query(
                'SELECT FlowDestUser FROM tb_approval_history WHERE KegiatanID = ? ORDER BY HistoryID DESC LIMIT 1',
                [$KegiatanID])->getRowArray();
            if (! $last || (int) $last['FlowDestUser'] !== (int) $this->UserID) {
                return ['code' => 1, 'message' => 'Pengajuan ini bukan pada antrian Anda.'];
            }
        }

        $this->db->transStart();

        $jenis              = ((int) $cek['KegiatanJenisID']) > 0 ? (int) $cek['KegiatanJenisID'] : 1;
        $FlowCode           = (string) $this->request->getPost('FlowCode');
        $FlowKeterangan     = (string) $this->request->getPost('FlowKeterangan');
        $KegiatanNoKwitansi = $this->request->getPost('KegiatanNoKwitansi');
        $KegiatanNoSPTJB    = $this->request->getPost('KegiatanNoSPTJB');

        // Gate tanda tangan PPK (opsional, tb_vrbl 'dok_gate_ppk'=1): PPK tidak
        // bisa meneruskan sebelum semua dokumen slot 'ppk' ditandatangani.
        if ($this->UserPosition === 'PPK') {
            $mDokumen = model(M_Dokumen::class);
            if ($mDokumen->dokGatePpkOn()) {
                $kurang = $mDokumen->ttdKurangUntukPosisi($KegiatanID, 'PPK');
                if (! empty($kurang)) {
                    $this->db->transComplete();

                    return ['code' => 1, 'message' => 'Belum bisa disetujui: ' . count($kurang)
                        . ' dokumen belum Anda tanda tangani (buka Info Pengajuan -> Dokumen Pencairan).'];
                }
            }
        }

        $FlowDestUser = $this->_nextStageUser($FlowCode, $KegiatanID);

        $isSelesai = $this->db->query(
            'SELECT 1 FROM tb_approval_flow
             WHERE JenisID = ? AND FlowOrder > (SELECT FlowOrder FROM tb_approval_flow WHERE FlowCode = ? AND JenisID = ?)',
            [$jenis, $FlowCode, $jenis])->getResultArray();

        $this->db->table('tb_approval_history')->insert([
            'KegiatanID'       => $KegiatanID,
            'FlowCode'         => $FlowCode,
            'FlowKeterangan'   => $FlowKeterangan,
            'FlowResult'       => 1,
            'FlowDate'         => date('Y-m-d H:i:s'),
            'FlowUserID'       => $this->UserID,
            'FlowUserName'     => $this->UserFullName,
            'FlowUserPosition' => $this->UserPosition,
            'FlowDestUser'     => $FlowDestUser,
        ]);

        $upd = $this->db->table('tb_kegiatan');
        $upd->set('KegiatanStatusTerakhir', $FlowCode);
        $upd->set('KegiatanKeteranganTerakhir', $FlowKeterangan);
        $upd->set('KegiatanStatus', empty($isSelesai) ? 'Approval Selesai' : 'Approval OnProgress');
        if ($KegiatanNoKwitansi) {
            $upd->set('KegiatanNoKwitansi', $KegiatanNoKwitansi);
        }
        if ($KegiatanNoSPTJB) {
            $upd->set('KegiatanNoSPTJB', $KegiatanNoSPTJB);
        }
        $upd->where('KegiatanID', $KegiatanID);
        $upd->update();

        $keg           = $this->db->table('tb_kegiatan')->getWhere(['KegiatanID' => $KegiatanID])->getRowArray();
        $judul         = $keg['KegiatanJudul'];
        $pemohon_phone = isset($keg['KegiatanPemohonPhone']) ? $keg['KegiatanPemohonPhone'] : '';

        if (empty($isSelesai)) {
            $text_src = 'Pengajuan "' . $judul . '" telah SELESAI diproses seluruh tahap. Dana siap dicairkan.';
            $this->M_Dashboard->send_text($keg['KegiatanUserID'], '', $text_src, '', $pemohon_phone,
                'Kabar baik! Pengajuan pencairan dana untuk kegiatan "' . $judul . '" telah SELESAI diproses.',
                'persetujuan', $KegiatanID);
        } else {
            $posisi   = $this->_flowPosisi($FlowCode, $jenis);
            $text_src = 'Pengajuan "' . $judul . '" telah diteruskan oleh ' . $posisi . ' ke tahap berikutnya.';
            $text_dst = 'Halo, ada pengajuan "' . $judul . '" yang perlu Anda tinjau.';
            $this->M_Dashboard->send_text($keg['KegiatanUserID'], $FlowDestUser, $text_src, $text_dst, $pemohon_phone,
                'Pengajuan "' . $judul . '" telah disetujui oleh ' . $posisi . ' dan lanjut ke tahap berikutnya.',
                'persetujuan', $KegiatanID);
        }

        if ($this->db->error()['code'] != 0) {
            return $this->db->error();
        }
        $this->db->transComplete();

        // Hook: auto-update Kartu Kendali TU saat approval maju.
        model(M_Dokumen::class)->kartuKendaliAutoFill($KegiatanID);

        return null;
    }

    /* ---- KEMBALIKAN (revisi / hentikan proses) ------------------------ */
    private function _approvalKembalikan($KegiatanID)
    {
        $this->db->transStart();

        $FlowKeterangan = trim((string) $this->request->getPost('FlowKeterangan'));
        $jenis          = ($this->request->getPost('FlowRejectType') === 'terminate') ? 'terminate' : 'revisi';
        $destCode       = (string) $this->request->getPost('FlowDestCode');
        $destUser       = (int) $this->request->getPost('FlowDestUser');

        $keg = $this->db->table('tb_kegiatan')->getWhere(['KegiatanID' => $KegiatanID])->getRowArray();
        if (empty($keg)) {
            $this->db->transComplete();

            return ['code' => 1, 'message' => 'Data kegiatan tidak ditemukan.'];
        }
        $jenisId = ((int) $keg['KegiatanJenisID']) > 0 ? (int) $keg['KegiatanJenisID'] : 1;

        // Hentikan Proses: keputusan hanya di PJ -> tujuan otomatis ke PJ pembuat,
        // tidak perlu memilih petugas.
        if ($jenis === 'terminate') {
            $destCode = $this->_stageSatuCode($jenisId);
            $destUser = (int) $keg['KegiatanUserID'];
        }
        $targetValid = $this->db->table('tb_approval_flow')->getWhere([
            'JenisID' => $jenisId, 'FlowCode' => $destCode, 'FlowIsRevisiTarget' => 1,
        ])->getNumRows() > 0;
        if (! $targetValid) {
            $this->db->transComplete();

            return ['code' => 1, 'message' => 'Tahap tujuan pengembalian tidak valid.'];
        }
        if ($destUser <= 0) {
            $this->db->transComplete();

            return ['code' => 1, 'message' => 'Petugas tujuan pengembalian wajib dipilih.'];
        }

        $this->db->table('tb_approval_history')->insert([
            'KegiatanID'       => $KegiatanID,
            'FlowCode'         => $destCode,               // tahap tujuan revisi
            'FlowKeterangan'   => $FlowKeterangan,
            'FlowResult'       => 0,
            'FlowRejectType'   => $jenis,
            'FlowDate'         => date('Y-m-d H:i:s'),
            'FlowUserID'       => $this->UserID,           // yang mengembalikan
            'FlowUserName'     => $this->UserFullName,
            'FlowUserPosition' => $this->UserPosition,
            'FlowDestUser'     => $destUser,               // petugas yang harus merevisi
        ]);

        $this->db->table('tb_kegiatan')
            ->set('KegiatanStatusTerakhir', $destCode)
            ->set('KegiatanKeteranganTerakhir', $FlowKeterangan)
            ->set('KegiatanStatus', 'Perlu Revisi')
            ->where('KegiatanID', $KegiatanID)
            ->update();

        // --- Notifikasi WA ke SEMUA peran yang sudah dilewati + petugas tujuan ---
        $judul   = $keg['KegiatanJudul'];
        $posisi  = $this->UserPosition;
        $tujuan  = $this->_flowPosisi($destCode, $jenisId);
        $kalimat = ($jenis === 'terminate')
            ? 'Pengajuan "' . $judul . '" DIKEMBALIKAN oleh ' . $posisi . ' dengan permintaan HENTIKAN PROSES (dokumen dobel). Menunggu keputusan PJ-Kegiatan.'
            : 'Pengajuan "' . $judul . '" DIKEMBALIKAN oleh ' . $posisi . ' untuk REVISI di tahap ' . $tujuan . '.';
        if ($FlowKeterangan !== '') {
            $kalimat .= ' Catatan: ' . $FlowKeterangan;
        }

        $konteks = ($jenis === 'terminate') ? 'hentikan_proses' : 'revisi';
        $this->_kirimWaBanyak($this->_teleponPihakTerkait($KegiatanID, $destUser), $kalimat, $konteks, $KegiatanID);
        $dst = $this->db->table('tb_users')->getWhere(['UserID' => $destUser])->getRowArray();
        if (! empty($dst['UserPhone'])) {
            $this->_kirimWaBanyak([$dst['UserPhone']],
                ($jenis === 'terminate')
                    ? 'Ada pengajuan "' . $judul . '" dengan permintaan HENTIKAN PROSES. Buka menu Daftar Pengajuan untuk memutuskan Hentikan Proses atau Revisi.'
                    : 'Ada pengajuan "' . $judul . '" yang perlu Anda REVISI. Buka menu Daftar Pengajuan, perbaiki datanya, lalu Kirim Ulang.',
                $konteks, $KegiatanID);
        }

        if ($this->db->error()['code'] != 0) {
            return $this->db->error();
        }
        $this->db->transComplete();

        // Hook: auto-update Kartu Kendali TU saat approval dikembalikan.
        model(M_Dokumen::class)->kartuKendaliAutoFill($KegiatanID);

        return null;
    }

    /* ---- HENTIKAN PROSES / BATALKAN (hanya PJ) ---------------------- */
    public function KegiatanTerminate()
    {
        $this->db->transStart();
        $KegiatanID = (int) $this->request->getPost('KegiatanID');
        $catatan    = trim((string) $this->request->getPost('FlowKeterangan'));
        if ($catatan === '') {
            $catatan = 'Proses dihentikan (dokumen dobel).';
        }

        $keg = $this->db->table('tb_kegiatan')->getWhere(['KegiatanID' => $KegiatanID])->getRowArray();
        if (empty($keg)) {
            $this->db->transComplete();

            return ['code' => 1, 'message' => 'Data tidak ditemukan.'];
        }
        if ($this->UserPosition !== 'PJ-Kegiatan' && $this->UserPosition !== 'SuperAdmin') {
            $this->db->transComplete();

            return ['code' => 1, 'message' => 'Hanya PJ-Kegiatan yang dapat menghentikan proses.'];
        }

        // Hapus berkas lampiran dari disk (hemat penyimpanan) lalu kosongkan.
        if (! empty($keg['KegiatanLampiran'])) {
            foreach (preg_split('/\s*[,;]\s*/', (string) $keg['KegiatanLampiran'], -1, PREG_SPLIT_NO_EMPTY) as $f) {
                $path = FCPATH . 'assets/lampiran/' . basename($f);
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        }

        $this->db->table('tb_kegiatan')->where('KegiatanID', $KegiatanID)->update([
            'KegiatanStatus'             => 'Dibatalkan',
            'KegiatanLampiran'           => '',
            'KegiatanKeteranganTerakhir' => $catatan,
        ]);

        $jenisId = ((int) $keg['KegiatanJenisID']) > 0 ? (int) $keg['KegiatanJenisID'] : 1;
        $this->db->table('tb_approval_history')->insert([
            'KegiatanID'       => $KegiatanID,
            'FlowCode'         => $keg['KegiatanStatusTerakhir'] ?: $this->_stageSatuCode($jenisId),
            'FlowKeterangan'   => $catatan,
            'FlowResult'       => 0,
            'FlowRejectType'   => 'terminate',
            'FlowDate'         => date('Y-m-d H:i:s'),
            'FlowUserID'       => $this->UserID,
            'FlowUserName'     => $this->UserFullName,
            'FlowUserPosition' => $this->UserPosition,
            'FlowDestUser'     => (int) $keg['KegiatanUserID'],
        ]);

        $this->_kirimWaBanyak(
            $this->_teleponPihakTerkait($KegiatanID, 0),
            'Pengajuan "' . $keg['KegiatanJudul'] . '" telah DIHENTIKAN / dibatalkan oleh PJ-Kegiatan. Alasan: ' . $catatan,
            'hentikan_proses', $KegiatanID
        );

        if ($this->db->error()['code'] != 0) {
            return $this->db->error();
        }
        $this->db->transComplete();

        return null;
    }

    /* ---- Data pendukung form pengembalian --------------------------- */
    /** Tahap-tahap tujuan revisi (FlowIsRevisiTarget=1) + daftar petugasnya. */
    public function RevisiTargets($KegiatanID)
    {
        $KegiatanID = (int) $KegiatanID;
        $keg        = $this->db->table('tb_kegiatan')->getWhere(['KegiatanID' => $KegiatanID])->getRowArray();
        $jenisId    = (! empty($keg) && (int) $keg['KegiatanJenisID'] > 0) ? (int) $keg['KegiatanJenisID'] : 1;
        $out        = [];
        $targets    = $this->db->table('tb_approval_flow')->orderBy('FlowOrder', 'ASC')
            ->getWhere(['JenisID' => $jenisId, 'FlowIsRevisiTarget' => 1])
            ->getResultArray();
        foreach ($targets as $flow) {
            $code = $flow['FlowCode'];
            if ((int) $flow['FlowOrder'] === 1) {
                // Tahap pertama = PJ pembuat kegiatan
                $u = ! empty($keg['KegiatanUserID'])
                    ? $this->db->table('tb_users')->getWhere(['UserID' => $keg['KegiatanUserID']])->getResultArray()
                    : [];
            } else {
                $u = $this->db->table('tb_users')->orderBy('UserFullName', 'ASC')
                    ->getWhere(['UserPosition' => $flow['FlowPosition'], 'UserAktif' => 1])
                    ->getResultArray();
            }
            $out[] = [
                'code'     => $code,
                'position' => $flow['FlowPosition'],
                'label'    => ucwords(strtolower($flow['FlowNote'])),
                'users'    => array_map(function ($r) {
                    return ['id' => $r['UserID'], 'name' => $r['UserFullName']];
                }, $u),
            ];
        }

        return $out;
    }

    /** FlowCode tahap pertama (FlowOrder = 1) sebuah jenis; fallback 'PJK'. */
    private function _stageSatuCode($jenisID)
    {
        $r = $this->db->table('tb_approval_flow')->select('FlowCode')->getWhere(
            ['JenisID' => (int) $jenisID, 'FlowOrder' => 1])->getRowArray();

        return ! empty($r['FlowCode']) ? $r['FlowCode'] : 'PJK';
    }

    /* ---- KIRIM ULANG setelah revisi (petugas tujuan -> maju) --------- */
    public function KegiatanRevisiKirimUlang()
    {
        // Sama seperti "teruskan", tetapi hanya boleh dari status Perlu Revisi
        // oleh petugas tujuan. Pengecekan hak dilakukan di controller.
        return $this->_approvalTeruskan((int) $this->request->getPost('KegiatanID'));
    }

    /* ---- Helper WA -------------------------------------------------- */
    /** Nomor telepon semua user yang pernah bertindak (disetujui) pada
     *  kegiatan ini, kecuali $exceptUserID. */
    private function _teleponPihakTerkait($KegiatanID, $exceptUserID = 0)
    {
        $rows = $this->db->query(
            'SELECT DISTINCT u.UserPhone
             FROM tb_approval_history h
             JOIN tb_users u ON u.UserID = h.FlowUserID
             WHERE h.KegiatanID = ? AND u.UserID <> ? AND u.UserPhone IS NOT NULL AND u.UserPhone <> \'\'',
            [(int) $KegiatanID, (int) $exceptUserID])->getResultArray();

        return array_column($rows, 'UserPhone');
    }

    private function _kirimWaBanyak($phones, $text, $context = 'persetujuan', $kegiatanID = null)
    {
        $phones = array_values(array_unique(array_filter((array) $phones)));
        if (empty($phones) || $text === '') {
            return ['sent' => 0, 'failed' => 0, 'total' => 0];
        }

        return (new Wa())->send_bulk(array_map(function ($p) use ($text) {
            return ['phone' => $p, 'text' => $text];
        }, $phones), $context, $kegiatanID);
    }

    private function _flowPosisi($FlowCode, $jenisID = 1)
    {
        $r = $this->db->table('tb_approval_flow')->getWhere(
            ['FlowCode' => $FlowCode, 'JenisID' => (int) $jenisID])->getRowArray();

        return ! empty($r) ? $r['FlowPosition'] : $FlowCode;
    }

    // -------------------------------------------------
    public function ReportGetList()
    {
        // Rentang bulan: "date_start" & "date_end" berformat "YYYY-MM".
        // Kalau kosong / tidak valid -> pakai bulan berjalan. Nilai dinormalkan
        // jadi tanggal PHP (bukan tempel string) -> aman dari injeksi.
        $parseMonth = function ($v) {
            $p = explode('-', (string) $v);
            $y = isset($p[0]) ? (int) $p[0] : 0;
            $m = isset($p[1]) ? (int) $p[1] : 0;
            if ($y < 2000 || $y > 2100 || $m < 1 || $m > 12) {
                return null;
            }

            return sprintf('%04d-%02d', $y, $m);
        };
        $start = $parseMonth($this->request->getGet('date_start'));
        $end   = $parseMonth($this->request->getGet('date_end'));
        if ($start === null) {
            $start = date('Y-m');
        }
        if ($end === null) {
            $end = $start;
        }
        if ($end < $start) {
            $end = $start;
        }

        $from = $start . '-01 00:00:00';
        $to   = date('Y-m-t 23:59:59', strtotime($end . '-01'));

        // Saringan status laporan: proses | selesai | kembali (boleh gabungan).
        $allowed = ['proses', 'selesai', 'kembali'];
        $status  = (array) $this->request->getGet('status');
        $status  = array_values(array_intersect($allowed, $status));
        if (empty($status)) {
            $status = $allowed;
        }

        $bind         = [$from, $to];
        $statusFilter = '';
        if (count($status) < count($allowed)) {
            $statusFilter = ' WHERE report_status IN (' . implode(',', array_fill(0, count($status), '?')) . ')';
            $bind         = array_merge($bind, $status);
        }

        $sql = 'SELECT * FROM (
                    SELECT k.*, f.FlowPosition, h.FlowDate, h.FlowResult, h.FlowUserName,
                        CASE
                            WHEN k.KegiatanStatus = \'Approval Selesai\' THEN \'selesai\'
                            WHEN h.FlowResult = 0 THEN \'kembali\'
                            ELSE \'proses\'
                        END AS report_status
                    FROM tb_approval_history h
                    LEFT JOIN tb_kegiatan k ON k.KegiatanID = h.KegiatanID
                    LEFT JOIN tb_approval_flow f ON h.FlowCode = f.FlowCode AND f.JenisID = COALESCE(k.KegiatanJenisID, 1)
                    WHERE k.KegiatanDeletedAt IS NULL AND h.HistoryID IN (
                        SELECT MAX(HistoryID) HistoryID FROM tb_approval_history
                        WHERE FlowDate BETWEEN ? AND ?
                        GROUP BY KegiatanID
                    )
                ) rpt' . $statusFilter . '
                ORDER BY KegiatanTanggal ASC';
        $query = $this->db->query($sql, $bind);

        return $query->getResultArray();
    }

    // -------------------------------------------------
    public function getUserDestination($FlowOrder, $JenisID = 1)
    {
        $next  = (int) $FlowOrder + 1;
        $sql   = 'SELECT f.*, u.UserID, u.UserFullName
                FROM tb_approval_flow f
                LEFT JOIN tb_users u ON f.FlowPosition = u.UserPosition
                WHERE f.FlowOrder = ? AND f.JenisID = ? AND u.UserAktif = 1';
        $query = $this->db->query($sql, [$next, (int) $JenisID]);

        return $query->getResultArray();
    }

    /**
     * DASBOR -- sebaran berkas yang sedang berjalan di tiap tahap alur
     * (untuk visual "Denyut Pencairan"). Read-only, di-scope per jenis.
     * Balikan: array of ['order','code','position','label','count'].
     */
    public function PipelineByStage($jenisID = 1)
    {
        $jenisID = (int) $jenisID ?: 1;

        $flow = $this->db->query(
            'SELECT FlowOrder, FlowCode, FlowPosition, FlowNote
             FROM tb_approval_flow WHERE JenisID = ? AND FlowOrder > 0
             ORDER BY FlowOrder', [$jenisID])->getResultArray();
        if (empty($flow)) {
            return [];
        }

        $orderOf = [];
        $out     = [];
        foreach ($flow as $f) {
            $ord           = (int) $f['FlowOrder'];
            $orderOf[$f['FlowCode']] = $ord;
            $out[$ord] = [
                'order'    => $ord,
                'code'     => $f['FlowCode'],
                'position' => $f['FlowPosition'],
                'label'    => $f['FlowNote'] ?: $f['FlowPosition'],
                'count'    => 0,
            ];
        }
        $maxOrder = max(array_keys($out));

        $rows = $this->db->query(
            'SELECT KegiatanStatus, KegiatanStatusTerakhir
             FROM tb_kegiatan
             WHERE KegiatanDeletedAt IS NULL AND COALESCE(KegiatanJenisID, 1) = ?
               AND KegiatanStatus IN (\'editable\',\'Approval OnProgress\',\'Perlu Revisi\')',
            [$jenisID])->getResultArray();

        foreach ($rows as $r) {
            $st   = $r['KegiatanStatus'];
            $last = $r['KegiatanStatusTerakhir'];
            if ($st === 'editable' || $last === null || $last === '' || ! isset($orderOf[$last])) {
                $stage = 1;
            } elseif ($st === 'Perlu Revisi') {
                $stage = $orderOf[$last];
            } else {
                $stage = min($orderOf[$last] + 1, $maxOrder);
            }
            if (isset($out[$stage])) {
                $out[$stage]['count']++;
            }
        }

        ksort($out);

        return array_values($out);
    }

    /**
     * DASBOR -- KPI bulan berjalan: jumlah pengajuan yang SELESAI bulan ini
     * dan rata-rata hari kerja dari tanggal pengajuan sampai selesai.
     */
    public function MonthlyKpi($jenisID = 1)
    {
        $jenisID = (int) $jenisID ?: 1;
        helper('sla_helper');

        $lastCode = $this->db->query(
            'SELECT FlowCode FROM tb_approval_flow
             WHERE JenisID = ? AND FlowOrder > 0 ORDER BY FlowOrder DESC LIMIT 1',
            [$jenisID])->getRow();
        $lastCode = $lastCode ? $lastCode->FlowCode : 'SLS';

        $rows = $this->db->query(
            'SELECT k.KegiatanTanggal, h.FlowDate
             FROM tb_kegiatan k
             JOIN tb_approval_history h
               ON h.KegiatanID = k.KegiatanID AND h.FlowCode = ? AND h.FlowResult = 1
             WHERE k.KegiatanDeletedAt IS NULL AND COALESCE(k.KegiatanJenisID, 1) = ?
               AND k.KegiatanStatus = \'Approval Selesai\'
               AND YEAR(h.FlowDate) = YEAR(CURDATE()) AND MONTH(h.FlowDate) = MONTH(CURDATE())',
            [$lastCode, $jenisID])->getResultArray();

        $sum = 0;
        $cnt = 0;
        foreach ($rows as $r) {
            if (! empty($r['KegiatanTanggal']) && ! empty($r['FlowDate'])) {
                $sum += sla_hari_kerja($r['KegiatanTanggal'], $r['FlowDate']);
                $cnt++;
            }
        }

        return [
            'selesai_bulan_ini' => count($rows),
            'avg_hk'            => $cnt ? round($sum / $cnt, 1) : null,
        ];
    }
}
