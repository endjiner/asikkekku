<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use DateTimeInterface;

class Cookie extends BaseConfig
{
    /** Sama dengan CI3 cookie_prefix. */
    public string $prefix = '';

    /** @var DateTimeInterface|int|string */
    public $expires = 0;

    /** Sama dengan CI3 cookie_path. */
    public string $path = '/';

    /** Sama dengan CI3 cookie_domain. */
    public string $domain = '';

    /** Sama dengan CI3 cookie_secure. */
    public bool $secure = false;

    /**
     * CI3 cookie_httponly = FALSE (perilaku asli dipertahankan apa adanya
     * saat migrasi). Pertimbangkan diubah ke TRUE sebagai hardening lanjutan
     * -- tapi itu perubahan perilaku, di luar cakupan migrasi framework ini.
     */
    public bool $httponly = false;

    /**
     * CI3 tidak pernah menyetel SameSite secara eksplisit (browser modern
     * default ke Lax). Disetel eksplisit di sini supaya perilakunya jelas
     * tertulis dan sama seperti yang sudah terjadi selama ini.
     *
     * @var ''|'Lax'|'None'|'Strict'
     */
    public string $samesite = 'Lax';

    public bool $raw = false;
}
