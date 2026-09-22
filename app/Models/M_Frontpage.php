<?php

namespace App\Models;

class M_Frontpage extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
    }

    public function ListResult()
    {
        $search_text = trim((string) $this->request->getGet('search_text'));
        if ($search_text === '') {
            return [];
        }

        $type_search = trim((string) $this->request->getGet('type_search'));
        $like = '%' . $search_text . '%';

        $where = '';
        $bind = [];

        switch ($type_search) {
            case 'nama_petugas':
                $where = 'AND k.KegiatanNamaPelaksana LIKE ? ';
                $bind  = [$like];
                break;
            case 'judul_kegiatan':
                $where = 'AND k.KegiatanJudul LIKE ? ';
                $bind  = [$like];
                break;
            case 'no_sptb':
                $where = 'AND k.KegiatanNoSPTJB LIKE ? ';
                $bind  = [$like];
                break;
            case 'no_surat':
                $where = 'AND k.KegiatanNoSuratTugas LIKE ? ';
                $bind  = [$like];
                break;
            default:
                $where = 'AND (
                    k.KegiatanNamaPelaksana LIKE ?
                    OR k.KegiatanJudul LIKE ?
                    OR k.KegiatanNoSPTJB LIKE ?
                    OR k.KegiatanNoSuratTugas LIKE ?
                ) ';
                $bind  = [$like, $like, $like, $like];
                break;
        }

        $sql = "SELECT k.*,
                       k.KegiatanNamaPelaksana AS nama_pelaksana,
                       k.KegiatanJudul AS judul_kegiatan
                FROM tb_kegiatan k
                WHERE k.KegiatanDeletedAt IS NULL " . $where . "
                ORDER BY k.KegiatanTanggal DESC, k.KegiatanID DESC";

        $query  = $this->db->query($sql, $bind);
        $result = $query->getResultArray();

        return $result;
    }
}
