<?php

use CodeIgniter\HTTP\IncomingRequest;

/**
 * Kompatibilitas dengan pola CI3 lama yang dipakai di seluruh aplikasi ini
 * (controller/model/library diporting apa adanya dari CodeIgniter 3).
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */

if (! function_exists('legacy_redirect')) {
    /**
     * Pengganti redirect() CI3: mengirim header Location lalu exit() SAAT
     * ITU JUGA. redirect() bawaan CI4 cuma mengembalikan objek
     * RedirectResponse -- baru benar-benar mengalihkan kalau di-`return`
     * dari method controller. Banyak pemanggil lama di aplikasi ini
     * (mis. Auth::cekLogin()/cekMenu(), dipanggil dari constructor
     * controller) mengandalkan redirect yang langsung berhenti dari mana
     * saja, persis seperti redirect() CI3. Dipakai seragam di semua
     * pemanggilan supaya perilakunya konsisten & tidak perlu menganalisis
     * tiap titik panggil satu-satu.
     */
    function legacy_redirect(string $uri): void
    {
        redirect()->to($uri)->send();
        exit;
    }
}

if (! function_exists('show_error')) {
    /**
     * Pengganti show_error() CI3. CI4 tidak lagi menyediakan fungsi ini
     * (dokumen/uploadan langsung pakai exception untuk error framework),
     * tapi setiap pemanggilan lama di controller aplikasi ini SELALU
     * diikuti `return;` polos (bukan `return $this->response;`) persis
     * sesudahnya. CodeIgniter::gatherOutput() hanya memakai body dari NILAI
     * BALIK controller -- kalau baliknya bukan ResponseInterface/string
     * (termasuk `return;` kosong), body yang di-setBody() di sini akan
     * DITIMPA balik jadi kosong, terlepas dari `service('response')` adalah
     * instance yang sama dengan $this->response controller. Makanya pakai
     * echo (ditangkap ob_start() yang membungkus eksekusi controller),
     * bukan setBody() -- sama seperti pola echo json_encode(...) yang
     * dipakai controller lain, dan cocok dengan `return;` polos yang sudah
     * ada di setiap titik panggil. setStatusCode() aman lewat method chain
     * biasa karena gatherOutput() cuma menimpa body, bukan status code.
     */
    function show_error(string $message, int $statusCode = 500, string $heading = 'Error'): void
    {
        $body = view('errors/html/general', [
            'heading' => $heading,
            'message' => $message,
        ]);
        service('response')->setStatusCode($statusCode);
        echo $body;
    }
}

if (! function_exists('show_404')) {
    /** Pengganti show_404() CI3 -- lihat show_error(). */
    function show_404(string $message = 'Halaman yang Anda cari tidak ditemukan.'): void
    {
        show_error($message, 404, 'Halaman Tidak Ditemukan');
    }
}

if (! function_exists('legacy_get_post')) {
    /**
     * Pengganti $this->input->get_post() CI3: nilai POST kalau ada,
     * kalau tidak fallback ke GET.
     *
     * @return array|string|null
     */
    function legacy_get_post(string $key)
    {
        $request = service('request');

        return $request->getPost($key) ?? $request->getGet($key);
    }
}

if (! function_exists('legacy_incoming_request')) {
    /**
     * service('request') CI3 dulu selalu IncomingRequest ($this->input) --
     * CI4 juga bisa CLIRequest (spark/cron dari command line). Null kalau
     * bukan IncomingRequest, dipakai bareng Model/Auth yang cuma perlu
     * fitur web-request (isAJAX() dkk.).
     */
    function legacy_incoming_request(): ?IncomingRequest
    {
        $req = service('request');

        return ($req instanceof IncomingRequest) ? $req : null;
    }
}

if (! function_exists('html_escape')) {
    /**
     * Pengganti html_escape() CI3 untuk kompatibilitas tampilan.
     *
     * @param mixed $var
     * @param bool  $double_encode
     * @return mixed
     */
    function html_escape($var, bool $double_encode = true)
    {
        if (empty($var)) {
            return $var;
        }

        if (is_array($var)) {
            foreach (array_keys($var) as $key) {
                $var[$key] = html_escape($var[$key], $double_encode);
            }

            return $var;
        }

        return htmlspecialchars((string) $var, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $double_encode);
    }
}

