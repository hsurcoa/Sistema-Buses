<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesando...</title>
    <style>
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .fallback { max-width: 420px; text-align: center; padding: 24px; }
        .fallback h1 { font-size: 1.1rem; margin: 0 0 8px; }
        .fallback p { color: #555; margin: 0 0 16px; }
        .fallback a { color: #667eea; font-weight: 600; }
    </style>
</head>
<body>
    <!--
        Antes esta pagina cargaba SweetAlert2 (CSS+JS) en el <head>, lo que
        bloquea el parser hasta que ese script externo termine de bajar, y
        el <body> no tenia ningun contenido propio: si el CDN fallaba o
        tardaba, quedaba en blanco (sin nada que pintar mientras tanto).
        Ahora el texto real va primero, en el <body>, y SweetAlert2 se pide
        recien despues: si carga bien se ve el popup de siempre, si no, el
        usuario ya vio el mensaje y el link/redirect funcionan igual.
    -->
    <noscript>
        <div class="fallback">
            <h1>{{ $title }}</h1>
            <p>{{ $text }}</p>
            <a href="{{ $redirect }}">Continuar</a>
        </div>
    </noscript>
    <div class="fallback" id="fallbackContent">
        <h1>{{ $title }}</h1>
        <p>{{ $text }}</p>
        <a href="{{ $redirect }}">Continuar</a>
    </div>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Si Swal cargó bien, oculta el fallback de texto plano y muestra el
        // popup normal; si algo falla (CDN caído), el catch deja el fallback
        // de arriba visible (ya estaba visible desde el primer render) y
        // redirige solo igual pasado un rato.
        try {
            document.getElementById('fallbackContent').style.display = 'none';
            Swal.fire({
                icon: {!! json_encode($icon, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!},
                title: {!! json_encode($title, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!},
                text: {!! json_encode($text, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!},
                confirmButtonColor: "#667eea",
                allowOutsideClick: false
            }).then(() => {
                window.location.href = {!! json_encode($redirect, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!};
            });
        } catch (e) {
            document.getElementById('fallbackContent').style.display = '';
            setTimeout(() => { window.location.href = {!! json_encode($redirect, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!}; }, 4000);
        }
    </script>
</body>
</html>
