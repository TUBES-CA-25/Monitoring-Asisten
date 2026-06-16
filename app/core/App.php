<?php
date_default_timezone_set('Asia/Makassar');
class App {
    protected $controller = 'AuthController';
    protected $method = 'index';
    protected $params = [];

    public function __construct() {
        $url = $this->parseURL();
        
        // FIX: Ensure $url is array, not null
        if ($url === null || empty($url)) {
            $url = [];
        }

        if (isset($url[0]) && !empty($url[0])) {
            $u_ctrl = ucfirst($url[0]) . 'Controller';
            if (file_exists('../app/controllers/' . $u_ctrl . '.php')) {
                $this->controller = $u_ctrl;
                unset($url[0]);
            } else {
                // [BARU - Patch 4 V3] Sebelumnya: controller tidak dikenal
                // diam-diam jatuh ke AuthController::index() (halaman login)
                // -> URL salah/typo terasa "berhasil" tapi menampilkan
                // halaman yang tidak relevan, tanpa status HTTP 404 yang
                // benar. Sekarang: tampilkan halaman error 404 resmi.
                $this->show404();
                return;
            }
        }

        require_once '../app/controllers/' . $this->controller . '.php';
        $this->controller = new $this->controller;

        if (isset($url[1])) {
            if ($this->isRoutableAction($this->controller, $url[1])) {
                $this->method = $url[1];
                unset($url[1]);
            } else {
                // [BARU - Patch 4 V3] Sebelumnya: method tidak dikenal pada
                // controller yang valid diam-diam jatuh ke method 'index()'
                // controller tersebut (mis. /admin/typo -> admin/dashboard),
                // dan method internal Controller (view/model/checkAccess)
                // bisa terpanggil langsung lewat URL (mis. /admin/checkAccess)
                // karena method_exists() tidak peduli asal/visibility method.
                // Sekarang: hanya method PUBLIC yang dideklarasikan LANGSUNG
                // di controller turunan yang bisa diakses; selain itu 404.
                $this->show404();
                return;
            }
        }

        if (!empty($url)) {
            $this->params = array_values($url);
        }

        try {
            call_user_func_array([$this->controller, $this->method], $this->params);
        } catch (\ArgumentCountError $e) {
            // [BARU - Patch 4 V3] URL valid secara routing tapi parameter
            // wajib (mis. /kepalalab/assistantDetail tanpa {id}) tidak
            // terpenuhi -> sebelumnya fatal error PHP (atau halaman kosong
            // di produksi, stack trace di mode debug). Sekarang: 404.
            $this->show404();
        }
    }

    /**
     * [BARU - Patch 4 V3] Sebuah method hanya boleh diakses lewat URL jika:
     * 1. Memang ada di controller (method_exists), DAN
     * 2. Bersifat PUBLIC (bukan protected/private seperti checkAccess()), DAN
     * 3. Dideklarasikan LANGSUNG di kelas controller turunan - BUKAN
     *    diwarisi dari Controller (base class), agar helper internal
     *    seperti view()/model() tidak bisa dipanggil langsung dari URL.
     */
    private function isRoutableAction($controllerInstance, $method) {
        if (!method_exists($controllerInstance, $method)) return false;

        try {
            $ref = new ReflectionMethod($controllerInstance, $method);
        } catch (\ReflectionException $e) {
            return false;
        }

        return $ref->isPublic() && $ref->getDeclaringClass()->getName() !== 'Controller';
    }

    private function show404() {
        require_once '../app/controllers/ErrorController.php';
        $error = new ErrorController();
        $error->notFound();
        exit;
    }

    public function parseURL() {
        if (isset($_GET['url']) && !empty($_GET['url'])) {
            return explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
        }
        return null;
    }
}
?>