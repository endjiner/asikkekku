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
        $order_by    = $this->request->getGet('order_by');
        $order_sort  = $this->request->getGet('order_sort');
        $search_text = trim((string) $this->request->getGet('search_text'));
        if ($search_text === '') {
            return [];
        }
        $type_result = $this->request->getGet('type_result');
        $type_search = $this->request->getGet('type_search');
        $text   = '';
        $text2  = '';
        $title  = '';
        $title2 = '';
        $filter = '';
        $sort   = '';
        $bind   = [];
        // Halaman publik (tanpa login) -> nilai pencarian WAJIB lewat binding (?),
        // jangan pernah ditempel langsung ke string SQL. Kolom & arah urut
        // dipilih dari daftar tetap (whitelist).
        $like = '%' . $search_text . '%';

        switch ($type_result) {
            case ($type_result == 'kuitansi' && $type_search == 'no_sptb'):
                $text   = 'KegiatanNoSPTJB as text';
                $text2  = 'KegiatanJudul as text2';
                $title2 = '"Judul Kegiatan : " as title2';
                $filter .= 'AND KegiatanNoSPTJB LIKE ? ';
                $bind[] = $like;
                break;
            case ($type_result == 'kuitansi' && $type_search == 'no_surat'):
                $text   = 'KegiatanNoSuratTugas as text';
                $text2  = 'KegiatanJudul as text2';
                $title2 = '"Judul Kegiatan : " as title2';
                $filter .= 'AND KegiatanNoSuratTugas LIKE ? ';
                $bind[] = $like;
                break;
            case ($type_result == 'kuitansi' && $type_search == 'judul_kegiatan'):
                $text   = 'KegiatanJudul as text';
                $text2  = 'KegiatanNamaPelaksana as text2';
                $title2 = '"Nama Petugas : " as title2';
                $filter .= 'AND KegiatanJudul LIKE ? ';
                $bind[] = $like;
                break;
            case ($type_result == 'kuitansi' && $type_search == 'nama_petugas'):
                $text   = 'KegiatanNamaPelaksana as text';
                $text2  = 'KegiatanJudul as text2';
                $title2 = '"Judul Kegiatan : " as title2';
                $filter .= 'AND KegiatanNamaPelaksana LIKE ? ';
                $bind[] = $like;
                break;
            default:
                $filter .= '';
                break;
        }

        $dir = (strtoupper((string) $order_sort) === 'DESC') ? 'DESC' : 'ASC';
        switch ($order_by) {
            case 'tanggal':
                $sort .= "ORDER BY KegiatanTanggal $dir";
                break;
            case 'nama':
                $sort .= "ORDER BY KegiatanNamaPelaksana $dir";
                break;
        }

        $sql = "SELECT ROW_NUMBER() OVER (PARTITION BY KegiatanID ) row_num, k.*, $text, $text2, $title2
                FROM tb_kegiatan k
                WHERE k.KegiatanDeletedAt IS NULL " . $filter . $sort;
        $query  = $this->db->query($sql, $bind);
        $result = $query->getResultArray();

        return $result;
    }
}
