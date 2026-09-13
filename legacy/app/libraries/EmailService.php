<?php
// app/libraries/EmailService.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Asegúrate de requerir el autoload de composer en tu index.php o bootstrap:
// require 'vendor/autoload.php';

class EmailService
{
    private $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);

        // Configuración del Servidor SMTP (Ejemplo con Gmail)
        // REEMPLAZAR CON TUS CREDENCIALES REALES O VARIABLES DE ENTORNO
        $this->mailer->isSMTP();
        $this->mailer->Host       = 'smtp.gmail.com';
        $this->mailer->SMTPAuth   = true;
        $this->mailer->Username   = 'tu_correo@gmail.com'; // TU EMAIL
        $this->mailer->Password   = 'tu_contraseña_aplicacion'; // TU PASSWORD DE APLICACIÓN
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port       = 587;

        // Configuración General
        $this->mailer->setFrom('no-reply@busdriver.com', 'Sistema BusDriver');
        $this->mailer->CharSet = 'UTF-8';
        $this->mailer->isHTML(true);
    }

    public function enviarCredenciales($destinatarioEmail, $nombre, $usuario, $passwordPlana)
    {
        try {
            $this->mailer->addAddress($destinatarioEmail, $nombre);
            $this->mailer->Subject = 'Bienvenido a BusDriver - Credenciales de Acceso';

            // Cargar Template
            $body = $this->getTemplate($nombre, $usuario, $passwordPlana);
            $this->mailer->Body = $body;
            $this->mailer->AltBody = "Bienvenido $nombre. Tu usuario es: $usuario y tu contraseña es: $passwordPlana. Ingresa en: " . URLROOT;

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            // Loguear error si es necesario: error_log($this->mailer->ErrorInfo);
            return false;
        }
    }

    private function getTemplate($nombre, $usuario, $password)
    {
        $loginUrl = URLROOT . '/login.php'; // o tu ruta de login
        $year = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; margin: 0; padding: 0; }
        .email-container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background-color: #ffffff; padding: 40px 20px; text-align: center; border-bottom: 4px solid #007bff; }
        .content { padding: 40px 30px; color: #333333; }
        .h1-title { color: #2c3e50; font-size: 28px; font-weight: 800; margin: 0 0 20px 0; text-transform: uppercase; letter-spacing: 1px; }
        .logo-img { width: 120px; margin-bottom: 20px; }
        .welcome-text { font-size: 16px; line-height: 1.6; color: #555555; margin-bottom: 30px; }
        .credentials-box { background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; padding: 0; margin-bottom: 30px; overflow: hidden; }
        .cred-row { display: flex; border-bottom: 1px solid #e9ecef; }
        .cred-row:last-child { border-bottom: none; }
        .cred-label { background-color: #edf2f7; padding: 15px; width: 35%; font-weight: 600; color: #4a5568; font-size: 14px; display: flex; align-items: center; justify-content: flex-end; }
        .cred-value { padding: 15px; width: 65%; font-family: 'Courier New', monospace; font-weight: bold; color: #2d3748; font-size: 16px; display: flex; align-items: center; }
        .btn-container { text-align: center; margin-top: 40px; }
        .btn { background-color: #007bff; color: #ffffff !important; text-decoration: none; padding: 15px 30px; border-radius: 5px; font-weight: bold; font-size: 16px; display: inline-block; transition: background-color 0.3s; box-shadow: 0 2px 4px rgba(0,123,255,0.3); }
        .btn:hover { background-color: #0056b3; }
        .footer { background-color: #2c3e50; color: #8fa1b3; padding: 20px; text-align: center; font-size: 12px; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <!-- Título Principal -->
            <h1 class="h1-title">Bienvenido a<br><span style="color: #007bff;">BusDriver</span></h1>
            
            <!-- Icono Bus (Usando imagen remota segura o base64 si fuera pequeño, usaremos un icono placeholder bonito) -->
            <div style="margin: 20px 0;">
               <img src="https://cdn-icons-png.flaticon.com/512/3066/3066259.png" alt="Bus Icon" width="100" style="opacity: 0.9;">
            </div>
            
            <div style="color: #007bff; font-weight: bold; font-size: 18px; letter-spacing: 2px;">BUSDRIVER TRANSPORTE</div>
        </div>

        <div class="content">
            <p class="welcome-text">
                Hola <strong>$nombre</strong>,<br><br>
                Bienvenido al sistema. Se ha creado tu cuenta administrativa exitosamente. Recuerda que estas credenciales son <strong>estrictamente personales</strong> e intransferibles, de acuerdo a los protocolos de seguridad de la empresa.
            </p>

            <div class="credentials-box">
                <div class="cred-row">
                    <div class="cred-label">Usuario:</div>
                    <div class="cred-value">$usuario</div>
                </div>
                <div class="cred-row">
                    <div class="cred-label">Contraseña:</div>
                    <div class="cred-value">$password</div>
                </div>
            </div>

            <div class="btn-container">
                <a href="$loginUrl" class="btn">Ingresar a BusDriver</a>
            </div>
        </div>

        <div class="footer">
            &copy; $year Sistema de Transportes BusDriver. Todos los derechos reservados.<br>
            Este es un mensaje automático, por favor no responder.
        </div>
    </div>
</body>
</html>
HTML;
    }
}
