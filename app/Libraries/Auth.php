<?php

namespace App\Libraries;

use App\Models\M_Menu;

/**
 * Dulu application/libraries/Auth.php (CI3, dipakai lewat $this->ci = &get_instance()).
 * Di CI4 akses ke sesi/request lewat service() langsung, tidak perlu superobject global.
 */
class Auth
{
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
        $result = model(M_Menu::class)->CheckMenu(session('UserGroupID'), $menu_kode, $status);

        if ($result != 1) {
            $isAjax = (bool) legacy_incoming_request()?->isAJAX();

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
