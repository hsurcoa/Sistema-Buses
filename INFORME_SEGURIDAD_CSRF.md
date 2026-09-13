# 🛡️ INFORME DE SEGURIDAD: IMPLEMENTACIÓN ANTI-CSRF MANUAL
**Sistema:** MVC PHP Personalizado (No-CodeIgniter 4 Nativo)
**Fecha:** 04 Enero 2026
**Prioridad:** ALTA 🔴

---

## 1. EL PROBLEMA: ¿QUÉ ES CSRF?
**Cross-Site Request Forgery (Falsificación de Petición en Sitios Cruzados)**

Tu sistema actual confía ciegamente en cualquier petición que venga de un navegador con sesión iniciada.
*   **Escenario de Ataque:** Un administrador con sesión activa visita una página web externa trampa. Esa página envía una orden oculta a `tudominio.com/admin/eliminar_usuario/1`.
*   **Resultado:** Tu sistema ve la cookie de sesión válida y ejecuta la orden, eliminando al usuario sin que el admin lo sepa.

---

## 2. LA SOLUCIÓN TÉCNICA
Implementar un sistema de **"Token Secretos por Sesión"**.

### Mecanismo Propuesto:
1.  Al iniciar sesión, se genera un código aleatorio largo (Token) y se guarda en la `$_SESSION` del servidor.
2.  Cada formulario HTML legítimo debe incluir este Token en un campo `input type="hidden"`.
3.  Al recibir un `POST`, el servidor compara: `¿El Token del formulario == El Token de la Sesión?`.

---

## 3. GUÍA DE IMPLEMENTACIÓN (Developer Guide)

### FASE A: Backend (Generación de Token)
En tu archivo núcleo de sesión o controlador base, asegura la existencia del token.

```php
// En el inicio de sesión o constructor base
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Genera token seguro
}
```

### FASE B: Frontend (Inyección en Vistas)
Debes editar **TODOS** los archivos de vista (`app/views/...`) que contengan `<form method="POST">`.

```html
<form action="..." method="POST">
    <!-- AGREGAR ESTA LÍNEA DENTRO DEL FORM -->
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    ...
</form>
```

### FASE C: Backend (Validación)
En cada método de tus Controladores (`app/Controllers/...`) que reciba datos POST:

```php
public function guardar_algo() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Bloque de Seguridad
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            // Opcional: Registrar intento de ataque en logs
            die("Error 403: Petición inválida o expirada.");
        }
        
        // ... Resto del código ...
    }
}
```

---

## 4. CONSIDERACIONES ESPECIALES

### AJAX Requests
Para las peticiones JavaScript (jQuery/Fetch), el token debe enviarse como dato o cabecera.

**Opción A: En el cuerpo del POST**
```javascript
data: {
    ...datos,
    csrf_token: '<?php echo $_SESSION['csrf_token']; ?>' // Inyectar desde PHP al cargar la página
}
```

**Opción B: Meta Tag (Más limpio)**
1. Poner en el `<head>`: `<meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">`
2. Leer con JS: `let token = document.querySelector('meta[name="csrf-token"]').content;`

---

## 5. CONCLUSIÓN
Implementar esto requiere modificar múltiples archivos, pero elevará el nivel de seguridad de "Básico" a "Profesional", protegiendo la integridad de los datos ante ataques externos.

**Tiempo estimado de implementación:** 2-3 horas (dependiendo de la cantidad de formularios).
