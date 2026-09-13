# 📊 AUDITORÍA DE SISTEMA V2: PRE-LANZAMIENTO COMERCIAL
**Producto:** Sistema de Venta de Pasajes
**Versión Auditada:** 2.1 (Post-Parches Admin)
**Fecha:** 04 de Enero de 2026
**Estatus Global:** 🟡 EN PROCESO DE FORTALECIMIENTO

---

## 1. RESUMEN DE SEGURIDAD 🛡️
Se ha realizado un trabajo excelente asegurando el núcleo administrativo (`Admin.php`), pero esta seguridad **NO SE HA REPLICADO** en los módulos operativos (`Ventas`, `Caja`), lo que deja puertas abiertas.

| Módulo | CSRF (Anti-Hack) | Exposición Errores | Validación Inputs | Estatus |
| :--- | :---: | :---: | :---: | :---: |
| **ADMIN** (Gestión) | 🟢 BLINDADO | 🟢 OCULTOS | 🟢 ESTRICTA | **LISTO** ✅ |
| **VENTAS** (Pasajes) | 🔴 VULNERABLE | 🔴 EXPUESTOS | 🟡 MEDIA | **RIESGO** ⚠️ |
| **CAJA** (Dinero) | 🔴 VULNERABLE | 🔴 EXPUESTOS | 🟡 MEDIA | **RIESGO** ⚠️ |

> **Alerta Crítica:** Actualmente, un atacante podría forzar la venta de un boleto o el cierre de caja mediante un enlace trampa, ya que estos módulos no verifican el Token de Seguridad que implementamos hoy.

---

## 2. ANÁLISIS DE CÓDIGO Y ARQUITECTURA 🏗️

### A. Exposición de Datos
En `Ventas.php` (Línea 471) se observó:
`'msg' => 'Error Crítico: ' . $e->getMessage()`
**Riesgo:** Si falla la base de datos, el usuario (o hacker) verá la estructura interna de tus tablas.
**Solución:** Replicar el parche de `error_log` + "Error Genérico" que hicimos en Admin.

### B. Inconsistencia en Sesiones
*   `Admin.php` usa ahora `SessionManager::getInstance()`.
*   `Caja.php` accede manualmente a `$_SESSION['user_id']`.
**Mejora:** Estandarizar todo el sistema para usar `SessionManager`. Esto evitará bugs donde la sesión se "pierde" en un módulo pero sigue viva en otro.

---

## 3. HOJA DE RUTA PARA COMERCIALIZACIÓN (SaaS) 🚀

Para vender esto a múltiples empresas, necesitas 3 pilares que aún faltan:

### Pilar 1: Seguridad Uniforme (Prioridad 1)
Debes aplicar el patrón "Anti-CSRF" en **TODOS** los formularios:
1.  Venta de Pasajes (`procesar_venta`).
2.  Apertura y Cierre de Caja.
3.  Creación de Rutas.

### Pilar 2: Multi-Tenancy (Multi-Empresa)
Actualmente, el sistema asume que solo hay UNA empresa de buses.
*   **Problema:** Si vendes el sistema a la "Empresa A" y a la "Empresa B", ¿cómo separas sus datos?
*   **Solución Rápida:** Instalar un código y base de datos diferente por cliente.
*   **Solución Escalable (SaaS):** Agregar campo `empresa_id` a todas las tablas y filtrar por defecto.

### Pilar 3: Instalación y Licenciamiento
*   **Instalador:** Crear un script `/install` que pida las credenciales de BD y cree las tablas automáticamente.
*   **Licencias:** Implementar un sistema que verifique si la licencia del cliente está activa (ej. consultando tu servidor API cada 24h).

---

## 4. CONCLUSIÓN Y VEREDICTO
El sistema tiene una base sólida y el módulo administrativo es ahora de nivel profesional. Sin embargo, **no está listo para venta masiva** hasta que "blindes" el área de dinero (Caja y Ventas).

**Recomendación Inmediata:**
Dedicar la próxima fase de desarrollo exclusivamente a replicar los parches de seguridad en `Ventas.php` y `Caja.php`. Una vez hecho eso, el sistema será seguro para operar en internet.

¡Buen trabajo hasta ahora! La base es excelente. 🌟
