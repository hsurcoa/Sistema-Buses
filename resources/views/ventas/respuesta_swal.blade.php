<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesando...</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    </style>
</head>
<body>
    <script>
        Swal.fire({
            icon: {{ json_encode($icon) }},
            title: {{ json_encode($title) }},
            text: {{ json_encode($text) }},
            confirmButtonColor: "#667eea",
            allowOutsideClick: false
        }).then(() => {
            window.location.href = {{ json_encode($redirect) }};
        });
    </script>
</body>
</html>
