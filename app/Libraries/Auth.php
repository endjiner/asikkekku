<?php

namespace App\Libraries;

use App\Models\M_Menu;
use CodeIgniter\HTTP\IncomingRequest;

/**
 * Dulu application/libraries/Auth.php (CI3, dipakai lewat $this->ci = &get_instance()).
 * Di CI4 akses ke sesi/request lewat service() langsung, tidak perlu superobject global.
 */
class Auth
{
    private ?M_Menu $menu = null;

    private function menu(): M_Menu
    {
        return $this->menu ??= model(M_Menu::class);
    }

    /** Cek sesi login. */
    public function cekLogin(): void
    {
        if (empty(session('UserID'))) {
            legacy_redirect(base_url('MainPage/login'));
        }
    }

    /** Cek akses menu (kode menu tb_menu.MenuKode + status 'r'|'c'|'u'|'d'). */
    public function cekMenu(string $menu_kode, string $status): void
    {
        $result = $this->menu()->CheckMenu(session('UserGroupID'), $menu_kode, $status);

        if ($result != 1) {
            $request = service('request');
            $isAjax  = ($request instanceof IncomingRequest) && $request->isAJAX();

            if (! $isAjax) {
                $this->alert_error_akses();
                legacy_redirect(base_url('dashboard'));
            } else {
                $this->alert_error_response('anda tidak punya akses tersebut !');
            }
        }
    }

    public function alert_error_akses(?string $msg = null): void
    {
        session()->set('error_info', $msg ?? 'anda tidak punya akses tersebut !');
    }

    public function alert_error_response(string $msg): void
    {
        echo json_encode(['error' => $msg]);
        exit();
    }
}
