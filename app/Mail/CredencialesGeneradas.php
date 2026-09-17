<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Credenciales de acceso para personal nuevo/con email actualizado.
 * Reescritura Laravel de `legacy/app/core/EmailHelper.php` (usaba PHPMailer
 * manual con credenciales SMTP en texto plano dentro del codigo — ya
 * movidas a `.env`, ver informe de fin de sesion).
 */
class CredencialesGeneradas extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nombreUsuario,
        public string $usuario,
        public string $password,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Bienvenido a BusDriver - Credenciales de Acceso');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.credenciales',
            with: [
                'nombreUsuario' => $this->nombreUsuario,
                'usuario' => $this->usuario,
                'password' => $this->password,
                'loginUrl' => URLROOT.'/login.php',
                'anio' => date('Y'),
            ],
        );
    }
}
