<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 0; }
        .email-wrapper { width: 100%; background-color: #f4f6f9; padding: 20px 0; }
        .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .header { background-color: #ffffff; padding: 40px 20px 20px 20px; text-align: center; }
        .h1-title { color: #000000; font-size: 32px; font-weight: 800; margin: 0; line-height: 1.2; }
        .logo-container { margin: 20px 0; }
        .logo-text-blue { color: #1e88e5; font-size: 24px; font-weight: 900; letter-spacing: 1px; display: block; }
        .logo-text-sub { color: #1e88e5; font-size: 14px; font-weight: 700; letter-spacing: 3px; text-transform: uppercase; margin-top: 5px; display: block; }
        .content { padding: 20px 40px 40px 40px; color: #555555; text-align: center; }
        .welcome-text { font-size: 16px; line-height: 1.6; color: #666666; margin-bottom: 30px; text-align: left; }
        .credentials-table { width: 100%; border-collapse: collapse; margin: 30px 0; border: 1px solid #1e88e5; }
        .credentials-table td { padding: 15px; border: 1px solid #1e88e5; text-align: center; font-size: 16px; }
        .cred-label { font-weight: bold; color: #000000; width: 40%; background-color: #ffffff; }
        .cred-value { font-family: 'Courier New', monospace; color: #000000; background-color: #ffffff; font-weight: 500; }
        .btn-container { text-align: center; margin-top: 30px; }
        .btn { background-color: #1e90ff; color: #ffffff !important; text-decoration: none; padding: 15px 40px; border-radius: 4px; font-weight: 600; font-size: 16px; display: inline-block; box-shadow: 0 4px 6px rgba(30, 144, 255, 0.3); }
        .footer { background-color: #ffffff; color: #999999; padding: 20px; text-align: center; font-size: 12px; border-top: 1px solid #eeeeee; }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="header">
                <h1 class="h1-title">Bienvenido a<br>BusDriver</h1>
                <div class="logo-container">
                    <img src="https://cdn-icons-png.flaticon.com/512/3066/3066259.png" alt="BusDriver" width="80" style="display: block; margin: 0 auto;">
                    <span class="logo-text-blue" style="margin-top: 10px;">BUSDRIVER</span>
                    <span class="logo-text-sub">TRANSPORTE</span>
                </div>
            </div>

            <div class="content">
                <p class="welcome-text">
                    Bienvenido al sistema, recuerda que estas credenciales son individuales y no podrás compartirlo de acuerdo a los protocolos de la empresa
                </p>

                <table class="credentials-table">
                    <tr>
                        <td class="cred-label">Usuario:</td>
                        <td class="cred-value">{{ $usuario }}</td>
                    </tr>
                    <tr>
                        <td class="cred-label">Contraseña:</td>
                        <td class="cred-value">{{ $password }}</td>
                    </tr>
                </table>

                <div class="btn-container">
                    <a href="{{ $loginUrl }}" class="btn">Ingresas a BusDriver</a>
                </div>
            </div>

            <div class="footer">
                &copy; {{ $anio }} Sistema de Transportes BusDriver.
            </div>
        </div>
    </div>
</body>
</html>
