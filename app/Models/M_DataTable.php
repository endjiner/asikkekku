<?php

namespace App\Models;

/**
 * M_DataTable -- helper server-side processing untuk DataTables.
 *
 * dtTableGetList($sql) menerima SATU string SELECT apa pun (boleh mengandung
 * sub-query, window function, ekspresi seperti `f.FlowOrder+1`, dsb), lalu:
 *   - membungkusnya sebagai sub-query `(<sql>) dt_base`,
 *   - menerapkan pencarian global, pengurutan, dan LIMIT/OFFSET dari
 *     parameter POST DataTables,
 *   - mengembalikan { draw, recordsTotal, recordsFiltered, data }.
 *
 * Catatan penting: SQL dibungkus sebagai STRING mentah, BUKAN lewat
 * query-builder from()/getCompiledSelect(), karena "identifier protection"
 * CI merusak ekspresi aritmetika di dalam sub-query. Nilai pencarian &
 * filter tetap lewat binding `?` sehingga aman dari injeksi.
 */
class M_DataTable extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Kolom/alias SQL yang sah hanya huruf/angka/underscore, opsional
     * diawali alias tabel + titik (mis. "k.KegiatanID"). protectIdentifiers()
     * cuma membungkus nilai dengan backtick TANPA meng-escape backtick
     * yang sudah ada di dalamnya, jadi nama kolom harus divalidasi lebih
     * dulu di sini -- kolom `columns[i][data]`/`order[i][column]` datang
     * langsung dari POST DataTables (bisa direkayasa lewat request mentah).
     */
    private function _safeIdentifier($name)
    {
        return (bool) preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)?$/', (string) $name);
    }

    public function dtTableGetList($sql, $db = null, $addt_filter = [])
    {
        $DB = is_null($db) ? $this->db : $db;

        $dt_draw    = (int) $this->request->getPost('draw');
        $dt_start   = (int) $this->request->getPost('start');
        $dt_length  = (int) $this->request->getPost('length');
        $dt_columns = $this->request->getPost('columns');
        $dt_order   = $this->request->getPost('order');
        $dt_search  = $this->request->getPost('search');
        $dt_search  = (is_array($dt_search) && isset($dt_search['value'])) ? trim((string) $dt_search['value']) : '';
        if (! is_array($dt_columns)) {
            $dt_columns = [];
        }
        if (! is_array($dt_order)) {
            $dt_order = [];
        }

        $base = '(' . $sql . ') dt_base';

        // ---- WHERE: pencarian global (multi-kata) + filter tambahan --------
        $whereGroups = [];
        $bind        = [];

        if ($dt_search !== '') {
            $terms = preg_split('/\s+/', $dt_search, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($terms as $term) {
                $like = '%' . $term . '%';
                $ors  = [];
                foreach ($dt_columns as $col) {
                    if (! isset($col['data']) || $col['data'] === '' || $col['data'] === null) {
                        continue;
                    }
                    if (isset($col['searchable']) && $col['searchable'] === 'false') {
                        continue;
                    }
                    if (! $this->_safeIdentifier($col['data'])) {
                        continue;
                    }
                    $ors[]  = $DB->protectIdentifiers((string) $col['data']) . ' LIKE ?';
                    $bind[] = $like;
                }
                if ($ors) {
                    $whereGroups[] = '(' . implode(' OR ', $ors) . ')';
                }
            }
        }

        foreach ($addt_filter as $f) {
            if (empty($f) || ! is_array($f)) {
                continue;
            }
            $ands = [];
            foreach ($f as $k => $v) {
                if (! $this->_safeIdentifier($k)) {
                    continue;
                }
                $ands[] = $DB->protectIdentifiers((string) $k) . ' = ?';
                $bind[] = $v;
            }
            if ($ands) {
                $whereGroups[] = '(' . implode(' AND ', $ands) . ')';
            }
        }
        $whereSql = $whereGroups ? (' WHERE ' . implode(' AND ', $whereGroups)) : '';

        // ---- ORDER BY -----------------------------------------------------
        $orderSql = '';
        $ord      = [];
        foreach ($dt_order as $o) {
            $ci = isset($o['column']) ? (int) $o['column'] : -1;
            if (! isset($dt_columns[$ci]['data']) || $dt_columns[$ci]['data'] === '') {
                continue;
            }
            if (isset($dt_columns[$ci]['orderable']) && $dt_columns[$ci]['orderable'] === 'false') {
                continue;
            }
            if (! $this->_safeIdentifier($dt_columns[$ci]['data'])) {
                continue;
            }
            $dir   = (isset($o['dir']) && strtolower($o['dir']) === 'desc') ? 'DESC' : 'ASC';
            $ord[] = $DB->protectIdentifiers((string) $dt_columns[$ci]['data']) . ' ' . $dir;
        }
        if ($ord) {
            $orderSql = ' ORDER BY ' . implode(', ', $ord);
        }

        // ---- LIMIT ------------------------------------------------------
        $limitSql = ($dt_length > 0)
            ? (' LIMIT ' . $dt_length . ' OFFSET ' . max(0, $dt_start))
            : '';

        // ---- Eksekusi --------------------------------------------------
        // Tanpa filter aktif, "difilter" == "total" -- jangan jalankan COUNT
        // yang sama dua kali (ini kondisi paling umum: buka halaman list
        // tanpa pencarian).
        $recordsTotal    = (int) $DB->query("SELECT COUNT(*) c FROM $base")->getRow()->c;
        $recordsFiltered = ($whereSql === '')
            ? $recordsTotal
            : (int) $DB->query('SELECT COUNT(*) c FROM ' . $base . $whereSql, $bind)->getRow()->c;
        $data = $DB->query('SELECT * FROM ' . $base . $whereSql . $orderSql . $limitSql, $bind)->getResultArray();

        return [
            'draw'            => $dt_draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ];
    }
}
