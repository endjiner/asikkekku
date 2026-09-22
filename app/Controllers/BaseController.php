<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Session\Session;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Helper CI3 lama yang dulu diautoload lewat application/config/autoload.php.
     *
     * @var list<string>
     */
    protected $helpers = ['url', 'file', 'form', 'ui', 'sla'];

    protected ?Session $session = null;

    /** @var \CodeIgniter\Database\BaseConnection */
    protected $db;

    /**
     * Dulu $this->data di controller CI3 -- dideklarasikan di sini supaya
     * tidak kena deprecation "creation of dynamic property" PHP 8.2+.
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->session = service('session');
        $this->db      = db_connect();
    }

    /**
     * Respons JSON seragam. HARUS di-`return` oleh pemanggil (bukan cuma
     * dipanggil lalu `return;` kosong) -- CodeIgniter::gatherOutput() cuma
     * memakai body dari NILAI BALIK method controller, lihat catatan
     * panjang di show_error() (app/Common.php).
     */
    protected function jsonResponse($data, int $statusCode = 200): ResponseInterface
    {
        return $this->response
            ->setStatusCode($statusCode)
            ->setContentType('application/json')
            ->setBody(json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}
