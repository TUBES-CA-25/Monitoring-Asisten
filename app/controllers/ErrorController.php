<?php
class ErrorController extends Controller {
    
    // Menangani Error 401 (Unauthorized)
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
}