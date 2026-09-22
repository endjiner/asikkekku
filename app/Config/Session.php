<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Session\Handlers\BaseHandler;
use CodeIgniter\Session\Handlers\FileHandler;

class Session extends BaseConfig
{
    /**
     * @var class-string<BaseHandler>
     */
    public string $driver = FileHandler::class;

    /** Sama dengan CI3 sess_cookie_name. */
    public string $cookieName = 'jtp_session';

    /** Sama dengan CI3 sess_expiration (detik). */
    public int $expiration = 7200;

    /**
     * CI3 sess_save_path = NULL (ikut default PHP). Di CI4 wajib path
     * absolut yang writable -> pakai writable/session bawaan (lebih portabel).
     */
    public string $savePath = WRITEPATH . 'session';

    /** Sama dengan CI3 sess_match_ip. */
    public bool $matchIP = false;

    /** Sama dengan CI3 sess_time_to_update. */
    public int $timeToUpdate = 300;

    /** Sama dengan CI3 sess_regenerate_destroy. */
    public bool $regenerateDestroy = false;

    public ?string $DBGroup = null;

    public int $lockRetryInterval = 100_000;

    public int $lockMaxRetries = 300;
}
