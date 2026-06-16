<?php
class ErrorController extends Controller {
    
    public function unauthorized() {
        http_response_code(401);
        
        $data['judul'] = '401 - Akses Ditolak';
        $this->view('errors/401', $data);
    }

    public function serverError() {
        http_response_code(500);
        $data['judul'] = '500 - Server Error';
        $this->view('errors/500', $data);
    }

    public function notFound() {
        http_response_code(404);
        $data['judul'] = '404 - Tidak Ditemukan';
        $this->view('errors/404', $data);
    }

    public function badRequest() {
        http_response_code(400);
        $data['judul'] = '400 - Permintaan Tidak Valid';
        $this->view('errors/400', $data);
    }

    public function forbidden() {
        http_response_code(403);
        $data['judul'] = '403 - Akses Dilarang';
        $this->view('errors/403', $data);
    }

    public function methodNotAllowed() {
        http_response_code(405);
        $data['judul'] = '405 - Metode Salah';
        $this->view('errors/405', $data);
    }

    public function badGateway() {
        http_response_code(502);
        $data['judul'] = '502 - Bad Gateway';
        $this->view('errors/502', $data);
    }
}