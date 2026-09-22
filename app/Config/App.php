<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class App extends BaseConfig
{
    /**
     * base_url dihitung dinamis di resolveBaseURL() (diporting apa adanya dari
     * CI3 application/config/config.php), jadi nilai di sini cuma placeholder.
     */
    public string $baseURL = 'http://localhost/';

    /**
     * @var list<string>
     */
    public array $allowedHostnames = [];

    /**
     * Tanpa index.php di URL (mengandalkan mod_rewrite, sama seperti sebelumnya).
     */
    public string $indexPage = '';

    public string $uriProtocol = 'REQUEST_URI';

    public string $permittedURIChars = 'a-z 0-9~%.:_\-';

    public string $defaultLocale = 'en';

    public bool $negotiateLocale = false;

    /**
     * @var list<string>
     */
    public array $supportedLocales = ['en'];

    public string $appTimezone = 'Asia/Jakarta';

    public string $charset = 'UTF-8';

    public bool $forceGlobalSecureRequests = false;

    /**
     * @var array<string, string>
     */
    public array $proxyIPs = [];

    public bool $CSPEnabled = false;

    public function __construct()
    {
        parent::__construct();

        $this->baseURL = $this->resolveBaseURL();
    }

    /**
     * base_url: dinamis tapi HTTP_HOST divalidasi (cegah Host header injection
     * -> link phishing di notifikasi WA/email). Untuk produksi, isi env var
     * APP_BASE_URL dengan URL tetap. Port 1:1 dari CI3 config.php.
     */
    private function resolveBaseURL(): string
    {
        $fixed = getenv('APP_BASE_URL') ?: '';
        if ($fixed !== '') {
            return rtrim($fixed, '/') . '/';
        }

        $host = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : 'localhost';
        if (! preg_match('/^[a-zA-Z0-9.\-]+(:[0-9]{1,5})?$/', $host)) {
            $host = 'localhost';
        }
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $path   = isset($_SERVER['SCRIPT_NAME']) ? str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']) : '/';

        return $scheme . '://' . $host . $path;
    }
}
