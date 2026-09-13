# Informe de Errores y Soluciones - Sistema de Venta de Pasajes

## 1. Problema Principal: "Error del Servidor" en Venta/Reserva

### Detección:
Se detectó que el servidor PHP tiene un error de configuración en el archivo `php.ini` que genera una alerta de sintaxis antes de ejecutar cualquier script:
`PHP: syntax error, unexpected '(' in C:\xampp\php\php.ini on line 1813`

Esto causaba que todas las respuestas AJAX, que se esperaban como JSON puro (ej: `{"status":"success"...}`), llegaran corruptas al navegador, conteniendo primero el error de PHP y luego el JSON válido. Librerías como jQuery fallaban al intentar parsear esta respuesta mixta, resultando en un error genérico o vacío en la interfaz.

### Solución Implementada:
Dado que no se puede editar el archivo `php.ini` del servidor directamente desde este entorno, se implementó una **solución de resiliencia en el Frontend (JavaScript)**.

Se modificó el archivo `venta_pasajes.php` para:
1.  Recibir las respuestas del servidor como **Texto Plano** (`dataType: text`) en lugar de forzar JSON.
2.  Implementar una función `parsearRespuestaServidor(text)` que:
    -   Busca la primera llave `{` y la última llave `}` en la respuesta.
    -   Extrae únicamente ese fragmento, descartando cualquier basura o advertencia previa generada por PHP.
    -   Parsea el JSON limpio.
3.  Si el servidor devuelve un error fatal real (que capturamos también en el Backend), este se mostrará correctamente en la alerta.

## 2. Verificación de Base de Datos y Modelo

Se ejecutó un diagnóstico completo del sistema (`diagnostico_sistema.php`) confirmando:
-   **Conexión DB:** Exitosa.
-   **Tablas:** Todas las tablas críticas (`boletos`, `clientes`, `viajes`, `sesiones_caja`) existen.
-   **Integridad:** La tabla `boletos` tiene las columnas necesarias, incluyendo `sesion_caja_id`.
-   **Prueba Lógica:** Se realizó una inserción de prueba exitosa directamente en la base de datos, confirmando que no hay bloqueos ni errores de constraints.

## 3. Estado del Ticket

La funcionalidad de ticket (`ticket_termico.php`) está lista para usarse. El flujo es:
1.  Venta Exitosa -> Backend devuelve ID Boleto.
2.  Frontend recibe ID -> Llama a `imprimirTicket(id)`.
3.  Se abre ventana `controladortransacciones/ticket_termico/{id}` que muestra el diseño HTML listo para imprimir.

## 4. Algoritmo Óptimo de Venta

Se refactorizó el controlador `ControladorTransacciones.php` para manejar la venta de forma atómica:
1.  **Limpieza de Buffer:** `ob_end_clean()` elimina cualquier ruido previo.
2.  **Transacción SQL:** Se usa `beginTransaction()` y `commit()` para asegurar que si falla la inserción del boleto, no se cree el cliente a medias.
3.  **Captura de Errores:** Se usa `try-catch (Throwable)` para capturar errores fatales y devolverlos como JSON legible.

## Recomendación Final para el Usuario

Para solucionar la causa raíz del problema de "ruido" en las respuestas, se recomienda contactar al administrador del servidor o editar manualmente el archivo **`C:\xampp\php\php.ini`** en la línea **1813** y corregir el error de sintaxis (probablemente un paréntesis extra o faltante). Sin embargo, con los parches aplicados, el sistema **ya es funcional**.
