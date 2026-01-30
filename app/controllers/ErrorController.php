<?php
class ErrorController extends Controller {
    
    // Error 401 (Unauthorized)
    public function unauthorized() {
        // Set Header HTTP agar browser tahu ini error
        http_response_code(401);
        
        $data['judul'] = '401 - Akses Ditolak';
        $this->view('errors/401', $data);
    }

   // Error 500 (Internal Server Error)
    public function serverError() {
        http_response_code(500);
        $data['judul'] = '500 - Server Error';
        $this->view('errors/500', $data);
    }

    // Error 404 (Not Found)
    public function notFound() {
        http_response_code(404);
        $data['judul'] = '404 - Tidak Ditemukan';
        $this->view('errors/404', $data);
    }

    // Error 400 (Bad Request)
    public function badRequest() {
        http_response_code(400);
        $data['judul'] = '400 - Permintaan Tidak Valid';
        $this->view('errors/400', $data);
    }
}