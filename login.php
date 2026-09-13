<?php
// Configuración de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ✅ Cargar configuración y SessionManager
require_once 'app/config/config.php';
require_once 'app/core/SessionManager.php';

$session = SessionManager::getInstance();

// Si ya está logueado, redirigir al Dashboard
if ($session->isAuthenticated()) {
    header("Location: " . URLROOT . "/dashboard");
    exit;
}

$mensaje_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email_input = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    try {
        // Conexión manual rápida para login
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Buscar usuario por email
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :val LIMIT 1");
        $stmt->execute(['val' => $email_input]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // ✅ Usar SessionManager para establecer datos de sesión
            $session->regenerateId(); // Seguridad: nuevo ID de sesión
            $session->setUserData([
                'user_id' => $user['id'],
                'usuario' => $user['nombres'] . ' ' . $user['apellidos'],
                'email' => $user['email'],
                'rol' => $user['rol'] ?? 'usuario'
            ]);

            // Redirigir al Dashboard
            header("Location: " . URLROOT . "/dashboard");
            exit;
        } else {
            $mensaje_error = "Credenciales inválidas.";
        }
    } catch (PDOException $e) {
        $mensaje_error = "Error sistema: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Sistema de Transportes</title>
    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #4e54c8;
            --secondary-color: #8f94fb;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        @keyframes gradient {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            backdrop-filter: blur(8.5px);
            -webkit-backdrop-filter: blur(8.5px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            padding: 3rem;
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 10;
            color: white;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            border-radius: 50px;
            padding: 12px 20px;
            color: white;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.3);
            box-shadow: none;
            color: white;
            outline: 2px solid rgba(255, 255, 255, 0.5);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .btn-login {
            background: white;
            color: #333;
            border-radius: 50px;
            padding: 12px;
            font-weight: 700;
            width: 100%;
            border: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            color: var(--primary-color);
        }

        .brand-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            background: white;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .floating-shapes div {
            position: absolute;
            width: 60px;
            height: 60px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            z-index: 1;
        }
    </style>
</head>

<body>

    <!-- Formas Flotantes Decorativas -->
    <div class="floating-shapes">
        <div style="top: 10%; left: 10%; width: 80px; height: 80px;"></div>
        <div style="bottom: 20%; right: 10%; width: 120px; height: 120px;"></div>
        <div style="top: 40%; right: 30%; width: 50px; height: 50px;"></div>
    </div>

    <div class="glass-card animate__animated animate__zoomIn">
        <div class="text-center mb-4">
            <div class="mb-3">
                <i class="fas fa-bus-alt fa-3x text-white"></i>
            </div>
            <h2 class="fw-bold">Bienvenido</h2>
            <p class="text-white-50">Sistema de Gestión de Pasajes</p>
        </div>

        <?php if (!empty($mensaje_error)): ?>
            <div class="alert alert-danger px-3 py-2 rounded-pill text-center fs-7" role="alert">
                <i class="fas fa-exclamation-circle me-1"></i> <?php echo $mensaje_error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label ms-2 small fw-bold text-uppercase text-white-50">Email</label>
                <input type="email" name="email" class="form-control" placeholder="admin@empresa.com" required>
            </div>

            <div class="mb-4">
                <label class="form-label ms-2 small fw-bold text-uppercase text-white-50">Contraseña</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-login mb-3">
                INGRESAR <i class="fas fa-arrow-right ms-2"></i>
            </button>

            <div class="text-center">
                <a href="#" class="text-white-50 text-decoration-none small">¿Olvidaste tu contraseña?</a>
            </div>
        </form>
    </div>

</body>

</html>