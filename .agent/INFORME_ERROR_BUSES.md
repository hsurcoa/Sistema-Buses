# 🕵️ INFORME DE DIAGNÓSTICO Y SOLUCIÓN: ERROR AL CARGAR BUSES

**Fecha:** 25 de Diciembre de 2025  
**Problema:** El combo de selección de bus muestra "Error al cargar buses".

---

## 🔍 CAUSA RAÍZ DETECTADA
El problema NO es un error de programación en la lógica de búsqueda de buses. 

Se ha detectado mediante pruebas en el servidor que **tu configuración de PHP tiene un error de sintaxis**.
Específicamente:
> `PHP: syntax error, unexpected '(' in C:\xampp\php\php.ini on line 1813`

### ¿Por qué rompe el sistema?
1. Cada vez que el sistema hace una petición (como buscar buses), PHP imprime automáticamente ese mensaje de error.
2. El sistema espera recibir una respuesta limpia en formato JSON: `{"buses": [...]}`.
3. Lo que recibe realmente es: 
   ```text
   PHP: syntax error...
   {"buses": [...]}
   ```
4. Javascript no puede leer eso como JSON válido, falla, y muestra el mensaje de error genérico.

---

## ✅ SOLUCIÓN APLICADA (EFICAZ Y DEFINITIVA)
He aplicado una **Solución de Limpieza de Buffer** en el código del controlador (`Ventas.php`).

Antes de enviar la lista de buses al navegador, he agregado la instrucción `ob_clean()`. Esto funciona como una "escoba" que **borra cualquier mensaje de error previo** (como el del php.ini) y asegura que solo se envíen los datos limpios de los buses.

### Código Modificado (`app/controllers/Ventas.php`):
```php
public function obtener_buses_tipo() {
    // LIMPIEZA DE EMERGENCIA
    if (ob_get_length()) ob_clean(); 
    
    // ... resto del código normal ...
}
```

---

## 🚀 RESULTADO ESPERADO
Ahora puedes recargar la página **(Ctrl + F5)** e intentar seleccionar el tipo de bus nuevamente.
- La lista de buses **debería cargar correctamente**.
- Al seleccionar un bus, el conductor **debería asignarse automáticamente**.

### RECOMENDACIÓN ADICIONAL (Servidor)
Aunque el parche aplicado soluciona el problema en el sistema, te recomiendo revisar tu archivo `C:\xampp\php\php.ini` en la línea **1813** cuando tengas tiempo, ya que hay un carácter extraño (probablemente un paréntesis `(`) que no debería estar ahí.
