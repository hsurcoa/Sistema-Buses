<?php

/**
 * SessionManager - Gestión Centralizada de Sesiones
 * Solución definitiva al problema de "Sesión Expirada"
 */
class SessionManager
{
    private static $instance = null;
    private static $sessionStarted = false;

    // Configuración de sesión
    private const SESSION_LIFETIME = 28800; // 8 horas en segundos
    private const SESSION_NAME = 'SISTEMA_TRANSPORTES_SESSION';
    private const ACTIVITY_TIMEOUT = 7200; // 2 horas de inactividad

    /**
     * Singleton - Una sola instancia del gestor de sesiones
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado - Inicializa la sesión de forma segura
     */
    private function __construct()
    {
        $this->initializeSession();
    }

    /**
     * Inicializar sesión con configuración óptima
     */
    private function initializeSession()
    {
        // Evitar múltiples inicios de sesión
        if (self::$sessionStarted || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // Configurar parámetros de sesión ANTES de session_start()
        ini_set('session.gc_maxlifetime', self::SESSION_LIFETIME);
        ini_set('session.cookie_lifetime', 0);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_samesite', 'Lax');

        // Configurar nombre de sesión personalizado
        session_name(self::SESSION_NAME);

        // Configurar parámetros de cookie
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => false, // Cambiar a true si usas HTTPS
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        // Iniciar sesión
        session_start();
        self::$sessionStarted = true;

        // Validar y renovar sesión
        $this->validateSession();
    }

    /**
     * Validar sesión activa y renovar si es necesario
     */
    private function validateSession()
    {
        // Si no hay marca de tiempo, es una sesión nueva
        if (!isset($_SESSION['CREATED_AT'])) {
            $_SESSION['CREATED_AT'] = time();
            $_SESSION['LAST_ACTIVITY'] = time();
            return;
        }

        // Verificar tiempo de inactividad
        if (isset($_SESSION['LAST_ACTIVITY'])) {
            $inactiveTime = time() - $_SESSION['LAST_ACTIVITY'];

            if ($inactiveTime > self::ACTIVITY_TIMEOUT) {
                // Sesión expirada por inactividad
                $this->destroy();
                return;
            }
        }

        // Actualizar última actividad
        $_SESSION['LAST_ACTIVITY'] = time();

        // Regenerar ID de sesión cada 30 minutos para seguridad
        if (isset($_SESSION['LAST_REGENERATION'])) {
            if (time() - $_SESSION['LAST_REGENERATION'] > 1800) {
                $this->regenerateId();
            }
        } else {
            $_SESSION['LAST_REGENERATION'] = time();
        }

        // --- SEGURIDAD: Generar Token CSRF si no existe ---
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    /**
     * Regenerar ID de sesión de forma segura
     */
    public function regenerateId()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
            $_SESSION['LAST_REGENERATION'] = time();
        }
    }

    /**
     * Verificar si el usuario está autenticado
     */
    public function isAuthenticated()
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Obtener ID del usuario autenticado
     */
    public function getUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Obtener datos del usuario
     */
    public function getUserData($key = null)
    {
        if ($key === null) {
            return [
                'user_id' => $_SESSION['user_id'] ?? null,
                'usuario' => $_SESSION['usuario'] ?? null,
                'email' => $_SESSION['email'] ?? null,
                'rol' => $_SESSION['rol'] ?? null
            ];
        }
        return $_SESSION[$key] ?? null;
    }

    /**
     * Establecer datos de sesión del usuario
     */
    public function setUserData($data)
    {
        foreach ($data as $key => $value) {
            $_SESSION[$key] = $value;
        }
        $_SESSION['LAST_ACTIVITY'] = time();
    }

    /**
     * Destruir sesión completamente
     */
    public function destroy()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];

            // Destruir cookie de sesión
            if (isset($_COOKIE[session_name()])) {
                setcookie(
                    session_name(),
                    '',
                    time() - 3600,
                    '/'
                );
            }

            session_destroy();
            self::$sessionStarted = false;
        }
    }

    /**
     * Verificar sesión para AJAX - Retorna JSON si no está autenticado
     */
    public function requireAuthAjax()
    {
        if (!$this->isAuthenticated()) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'msg' => 'Sesión expirada. Por favor, recargue la página e inicie sesión nuevamente.',
                'code' => 'SESSION_EXPIRED',
                'redirect' => URLROOT . '/login.php'
            ]);
            exit;
        }
    }

    /**
     * Verificar sesión para páginas normales - Redirige si no está autenticado
     */
    public function requireAuth($redirectUrl = null)
    {
        if (!$this->isAuthenticated()) {
            if ($redirectUrl === null) {
                $redirectUrl = URLROOT . '/login.php';
            }
            header('Location: ' . $redirectUrl);
            exit;
        }
    }

    /**
     * Obtener tiempo restante de sesión en segundos
     */
    public function getTimeRemaining()
    {
        if (!isset($_SESSION['LAST_ACTIVITY'])) {
            return 0;
        }

        $elapsed = time() - $_SESSION['LAST_ACTIVITY'];
        $remaining = self::ACTIVITY_TIMEOUT - $elapsed;

        return max(0, $remaining);
    }

    /**
     * Extender sesión (útil para operaciones largas)
     */
    public function extend()
    {
        $_SESSION['LAST_ACTIVITY'] = time();
    }

    /**
     * Obtener el Token CSRF actual
     */
    public function getCsrfToken()
    {
        return $_SESSION['csrf_token'] ?? null;
    }

    /**
     * Verificar si un token recibido es válido
     */
    public function verifyCsrfToken($token)
    {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}
