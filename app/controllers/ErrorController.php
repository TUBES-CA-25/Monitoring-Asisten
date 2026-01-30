<?php
class ErrorController extends Controller {
    
    // Menangani Error 401 (Unauthorized)
    public function unauthorized() {
        // Set Header HTTP agar browser tahu ini error
        http_response_code(401);
        
        $data['judul'] = '401 - Akses Ditolak';
        $this->view('errors/401', $data);
    }

    // Bisa ditambahkan method lain nanti (notFound, forbidden, dll)
    public function notFound() {
        http_response_code(404);
        $data['judul'] = '404 - Tidak Ditemukan';
        $this->view('errors/404', $data);
    }
}