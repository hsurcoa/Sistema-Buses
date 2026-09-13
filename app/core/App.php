<?php
/*
 *  Clase App Core
 *  Crea URL's y carga el controlador "core"
 *  Formato URL: /controlador/metodo/parametros
 */
class App
{
    protected $currentController = 'Dashboard';
    protected $currentMethod = 'index';
    protected $params = [];

    public function __construct()
    {
        //print_r($this->getUrl());

        $url = $this->getUrl();

        // Buscar controlador si existe en controllers
        if (file_exists('../app/controllers/' . ucwords($url[0]) . '.php')) {
            // Si existe, setear como controlador por defecto
            $this->currentController = ucwords($url[0]);
            // Unset 0 Index
            unset($url[0]);
        }

        // Requerir el controlador
        require_once '../app/controllers/' . $this->currentController . '.php';

        // Instanciar la clase controlador
        $this->currentController = new $this->currentController;

        // Chequear metodo (segunda parte de la url)
        if (isset($url[1])) {
            if (method_exists($this->currentController, $url[1])) {
                $this->currentMethod = $url[1];
                unset($url[1]);
            }
        }

        // Obtener parametros
        $this->params = $url ? array_values($url) : [];

        // Llamar callback con array de parametros
        call_user_func_array([$this->currentController, $this->currentMethod], $this->params);
    }

    public function getUrl()
    {
        // 1. Intentar obtener de la variable $_GET (configuración estándar .htaccess)
        if (isset($_GET['url'])) {
            $url = rtrim($_GET['url'], '/');
            $url = filter_var($url, FILTER_SANITIZE_URL);
            $url = explode('/', $url);
            return $url;
        }

        // 2. Fallback: Intentar parsear REQUEST_URI si .htaccess no pasa el parámetro
        // Esto soluciona problemas comunes en XAMPP/Windows
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

        // Limpiar parámetros GET (?foo=bar)
        if (strpos($requestUri, '?') !== false) {
            $requestUri = substr($requestUri, 0, strpos($requestUri, '?'));
        }

        // Eliminar base folder del URI
        // Ejemplo: URI=/venta-pasajes/caja, Script=/venta-pasajes/index.php o /venta-pasajes/public/index.php
        $basePath = dirname(dirname($scriptName)); // subir niveles hasta la raiz del proyecto app
        // Ajuste simplificado: asumimos que URLROOT está bien definido o limpiamos la parte conocida

        // Método más directo: eliminar la parte de la URL que corresponde al folder del proyecto
        // Si URLROOT es http://localhost/venta-pasajes
        $projectFolder = '/venta-pasajes'; // Se podría detectar dinámicamente pero hardcodeamos por seguridad en este fix

        if (strpos($requestUri, $projectFolder) === 0) {
            $url = substr($requestUri, strlen($projectFolder));
            $url = ltrim($url, '/');

            if (!empty($url)) {
                $url = filter_var($url, FILTER_SANITIZE_URL);
                return explode('/', $url);
            }
        }

        return ['Dashboard']; // Controlador por defecto
    }
}
