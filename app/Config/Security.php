<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Security extends BaseConfig
{
    /** CI3 hanya pernah punya CSRF berbasis cookie. */
    public string $csrfProtection = 'cookie';

    /**
     * CI3 tidak randomize token per render -- token harus stabil selama
     * sesi supaya banyak tab/AJAX sekaligus tetap valid (lihat footer.php,
     * $.ajaxPrefilter). Mengaktifkan ini akan mengubah token tiap render
     * dan merusak asumsi itu.
     */
    public bool $tokenRandomize = false;

    /** Sama dengan CI3 csrf_token_name & csrf_cookie_name (keduanya 'csrf_asikkekku'). */
    public string $tokenName = 'csrf_asikkekku';

    public string $headerName = 'X-CSRF-TOKEN';

    public string $cookieName = 'csrf_asikkekku';

    /** Sama dengan CI3 csrf_expire. */
    public int $expires = 7200;

    /**
     * CI3 csrf_regenerate = FALSE ("token stabil selama sesi -> aman untuk
     * banyak AJAX/tab sekaligus"). Dipertahankan apa adanya -- ini keputusan
     * desain eksplisit tim, bukan default yang lupa diubah.
     */
    public bool $regenerate = false;

    /**
     * CI3 gagal CSRF = halaman error 403 langsung (show_error), bukan
     * redirect balik. Disamakan di sini.
     */
    public bool $redirect = false;
}
