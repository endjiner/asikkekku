<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * M_DataTable — helper server-side processing untuk DataTables.
 *
 * dtTableGetList($sql) menerima SATU string SELECT apa pun (boleh mengandung
 * sub-query, window function, ekspresi seperti `f.FlowOrder+1`, dsb), lalu:
 *   - membungkusnya sebagai sub-query `(<sql>) dt_base`,
 *   - menerapkan pencarian global, pengurutan, dan LIMIT/OFFSET dari
 *     parameter POST DataTables,
 *   - mengembalikan { draw, recordsTotal, recordsFiltered, data }.
 *
 * Catatan penting: SQL dibungkus sebagai STRING mentah, BUKAN lewat
 * query-builder from()/get_compiled_select(), karena "identifier protection"
 * CI3 merusak ekspresi aritmetika di dalam sub-query. Nilai pencarian &
 * filter tetap lewat binding `?` sehingga aman dari injeksi.
 */
class M_DataTable extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function dtTableGetList($sql, $db = null, $addt_filter = array())
    {
        $DB = is_null($db) ? $this->db : $db;

        $dt_draw   = (int) $this->input->post('draw');
        $dt_start  = (int) $this->input->post('start');
        $dt_length = (int) $this->input->post('length');
        $dt_columns = $this->input->post('columns');
        $dt_order   = $this->input->post('order');
        $dt_search  = $this->input->post('search');
        $dt_search  = (is_array($dt_search) && isset($dt_search['value'])) ? trim((string) $dt_search['value']) : '';
        if (!is_array($dt_columns)) $dt_columns = array();
        if (!is_array($dt_order))   $dt_order   = array();

        $base = '(' . $sql . ') dt_base';

        // ---- WHERE: pencarian global (multi-kata) + filter tambahan --------
        $whereGroups = array();
        $bind = array();

        if ($dt_search !== '') {
            $terms = preg_split('/\s+/', $dt_search, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($terms as $term) {
                $like = '%' . $term . '%';
                $ors = array();
                foreach ($dt_columns as $col) {
                    if (!isset($col['data']) || $col['data'] === '' || $col['data'] === null) continue;
                    if (isset($col['searchable']) && $col['searchable'] === 'false') continue;
                    $ors[] = $DB->protect_identifiers((string) $col['data']) . ' LIKE ?';
                    $bind[] = $like;
                }
                if ($ors) $whereGroups[] = '(' . implode(' OR ', $ors) . ')';
            }
        }

        foreach ($addt_filter as $f) {
            if (empty($f) || !is_array($f)) continue;
            $ands = array();
            foreach ($f as $k => $v) {
                $ands[] = $DB->protect_identifiers((string) $k) . ' = ?';
                $bind[] = $v;
            }
            if ($ands) $whereGroups[] = '(' . implode(' AND ', $ands) . ')';
        }
        $whereSql = $whereGroups ? (' WHERE ' . implode(' AND ', $whereGroups)) : '';

        // ---- ORDER BY -----------------------------------------------------
        $orderSql = '';
        $ord = array();
        foreach ($dt_order as $o) {
            $ci = isset($o['column']) ? (int) $o['column'] : -1;
            if (!isset($dt_columns[$ci]['data']) || $dt_columns[$ci]['data'] === '') continue;
            if (isset($dt_columns[$ci]['orderable']) && $dt_columns[$ci]['orderable'] === 'false') continue;
            $dir = (isset($o['dir']) && strtolower($o['dir']) === 'desc') ? 'DESC' : 'ASC';
            $ord[] = $DB->protect_identifiers((string) $dt_columns[$ci]['data']) . ' ' . $dir;
        }
        if ($ord) $orderSql = ' ORDER BY ' . implode(', ', $ord);

        // ---- LIMIT ------------------------------------------------------
        $limitSql = ($dt_length > 0)
            ? (' LIMIT ' . $dt_length . ' OFFSET ' . max(0, $dt_start))
            : '';

        // ---- Eksekusi --------------------------------------------------
        $recordsTotal    = (int) $DB->query("SELECT COUNT(*) c FROM $base")->row('c');
        $recordsFiltered = (int) $DB->query("SELECT COUNT(*) c FROM $base" . $whereSql, $bind)->row('c');
        $data = $DB->query("SELECT * FROM $base" . $whereSql . $orderSql . $limitSql, $bind)->result_array();

        return array(
            'draw'            => $dt_draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        );
    }
}
