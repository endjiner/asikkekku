<?php

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
     * diikuti `return;` persis sesudahnya. Jadi cukup menyetel response
     * yang sedang berjalan (service('response') itu instance yang sama
     * dengan $this->response controller) lalu balik normal -- `return;`
     * di kode pemanggil yang menghentikan eksekusinya, sama seperti CI3
     * (yang berhenti lewat exit() di dalam show_error()).
     */
    function show_error(string $message, int $statusCode = 500, string $heading = 'Error'): void
    {
        $body = view('errors/html/general', [
            'heading' => $heading,
            'message' => $message,
        ]);
        service('response')->setStatusCode($statusCode)->setBody($body);
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
