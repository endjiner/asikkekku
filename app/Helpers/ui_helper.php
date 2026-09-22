<?php

/**
 * ui_helper -- pembantu tampilan ASIKKEKKU
 *  - illus()        : render ikon ilustrasi flat (bundel lokal, bukan icon-font)
 *  - role_position(): posisi user aktif dari sesi
 *  - role_features(): daftar fitur yang boleh diakses posisi tsb
 *  - role_can()     : cek satu fitur
 *  - menu_feature() : peta kode menu -> fitur (untuk menyaring sidebar)
 *
 * Porting dari application/helpers/ui_helper.php (CI3). Satu-satunya
 * perubahan: get_instance()->session->userdata() -> session() (helper
 * global CI4).
 */

if (! function_exists('_ui_icon_name')) {
    function _ui_icon_name($name)
    {
        return preg_replace('/[^a-z0-9\-]/', '', strtolower((string) $name));
    }
}

if (! function_exists('_ui_svg_use')) {
    /**
     * Referensi satu simbol dari sprite (assets/images/icons/sprite.svg).
     * Ringan: hanya beberapa byte per pemakaian. currentColor tetap mewarisi
     * warna `color` elemen induk.
     */
    function _ui_svg_use($name)
    {
        $name = _ui_icon_name($name);
        // Referensi fragment saja (#si-nama), sprite-nya disisipkan langsung di
        // <body> (lihat ui_sprite_inline()) supaya ikon tetap tampil walau
        // server tidak mengizinkan <use> lintas-file (CSP/MIME/cache, dsb).
        return '<svg aria-hidden="true" focusable="false"><use href="#si-' . $name . '" xlink:href="#si-' . $name . '"></use></svg>';
    }
}

if (! function_exists('ui_sprite_inline')) {
    /**
     * Sisipkan konten mentah sprite.svg langsung ke halaman (sekali saja,
     * disembunyikan) supaya semua <use href="#si-..."> di halaman ini bisa
     * menemukan simbolnya tanpa perlu memuat file terpisah.
     */
    function ui_sprite_inline()
    {
        static $done = false;
        if ($done) {
            return '';
        }
        $done = true;
        $path = FCPATH . 'assets/images/icons/sprite.svg';
        if (! file_exists($path)) {
            return '';
        }
        $svg = file_get_contents($path);
        // Hilangkan xml declaration/comment kalau ada, lalu sembunyikan elemen.
        $svg = preg_replace('/<\?xml.*?\?>/s', '', $svg);
        $svg = preg_replace('/^<svg /', '<svg style="position:absolute;width:0;height:0;overflow:hidden" aria-hidden="true" ', $svg, 1);

        return $svg;
    }
}

if (! function_exists('svgico')) {
    /** Ikon SVG tanpa lingkaran -- untuk di dalam tombol/teks. */
    function svgico($name, $size = 18, $cls = '')
    {
        $s = (int) $size;

        return '<span class="svgico ' . $cls . '" style="width:' . $s . 'px;height:' . $s . 'px">' . _ui_svg_use($name) . '</span>';
    }
}

if (! function_exists('illus')) {
    /**
     * Ikon di dalam chip lingkaran (header halaman, menu sidebar, kartu).
     *
     * @param int $size 16|20|28|40|48|64 (diameter lingkaran)
     * @param string $extra kelas tambahan (mis. "on-navy", "tint-green")
     */
    function illus($name, $size = 40, $extra = '')
    {
        return '<span class="ico-illus ico-' . (int) $size . ($extra ? ' ' . $extra : '') . '">' . _ui_svg_use($name) . '</span>';
    }
}

if (! function_exists('role_position')) {
    function role_position()
    {
        return (string) session('UserPosition');
    }
}

if (! function_exists('role_is_admin')) {
    function role_is_admin()
    {
        // Grup 1 = Full Access, atau posisi SuperAdmin
        return session('UserGroupID') == 1 || role_position() === 'SuperAdmin';
    }
}

if (! function_exists('role_features')) {
    /**
     * Fitur per posisi. Kunci fitur:
     *   dashboard, manajemen_app, kegiatan_list, kegiatan_create, approval_inbox, report
     */
    function role_features()
    {
        if (role_is_admin()) {
            return ['dashboard', 'manajemen_app', 'kegiatan_list', 'kegiatan_create', 'approval_inbox', 'report'];
        }

        switch (role_position()) {
            case 'PJ-Kegiatan':
                // Pemohon/penyusun: hanya kelola datanya sendiri + kirim approval
                return ['dashboard', 'kegiatan_list', 'kegiatan_create'];

            case 'PPK-Staff':
            case 'Verifikator':
            case 'SPP':
            case 'SPM':
            case 'PPK':
            case 'PPSPM':
                // Petugas alur/pemeriksa: hanya antrian persetujuan masuk + laporan
                return ['dashboard', 'approval_inbox', 'report'];

            default:
                return ['dashboard'];
        }
    }
}

if (! function_exists('role_can')) {
    function role_can($feature)
    {
        return in_array($feature, role_features(), true);
    }
}

if (! function_exists('menu_feature')) {
    /** Peta kode menu (tb_menu.MenuKode) -> fitur role_can(). null = selalu tampil. */
    function menu_feature($menu_kode)
    {
        $map = [
            '1000' => 'dashboard',
            '2000' => 'manajemen_app',
            '2100' => 'manajemen_app',
            '2200' => 'manajemen_app',
            '2300' => 'manajemen_app',
            '3000' => null,            // induk "Manajemen Approval" -- anaknya yang menyaring
            '3100' => 'kegiatan_list',
            '3200' => 'approval_inbox',
            '3300' => 'report',
        ];

        return array_key_exists((string) $menu_kode, $map) ? $map[(string) $menu_kode] : null;
    }
}

if (! function_exists('mask_phone')) {
    /** 6281234567890 -> 62812xxxx7890 */
    function mask_phone($phone)
    {
        $p = preg_replace('/\D/', '', (string) $phone);
        if (strlen($p) < 8) {
            return $p;
        }

        return substr($p, 0, 5) . str_repeat('x', 4) . substr($p, -4);
    }
}
