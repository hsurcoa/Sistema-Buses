# 🔄 DIAGRAMA DE FLUJO: EDITAR BUS

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         INICIO DEL PROCESO                              │
│                   Usuario hace clic en botón "Editar"                   │
└────────────────────────────────┬────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                          FRONTEND (JavaScript)                          │
│  ┌───────────────────────────────────────────────────────────────────┐  │
│  │ 1. Capturar data-id del botón clickeado                          │  │
│  │ 2. Validar que existe el ID                                      │  │
│  │ 3. Mostrar SweetAlert2 Loading                                   │  │
│  └───────────────────────────────────────────────────────────────────┘  │
└────────────────────────────────┬────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                        PETICIÓN AJAX (POST)                             │
│  ┌───────────────────────────────────────────────────────────────────┐  │
│  │ URL: /vehiculos/obtener_bus                                      │  │
│  │ Método: POST                                                     │  │
│  │ Body: id=5                                                       │  │
│  └───────────────────────────────────────────────────────────────────┘  │
└────────────────────────────────┬────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                   BACKEND (Vehiculos.php)                               │
│  ┌───────────────────────────────────────────────────────────────────┐  │
│  │ Método: obtener_bus()                                            │  │
│  │ 1. Validar que se recibió el ID                                  │  │
│  │ 2. Llamar al modelo: obtenerBusPorId($id)                        │  │
│  └───────────────────────────────────────────────────────────────────┘  │
└────────────────────────────────┬────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                   MODELO (VehiculoModel.php)                            │
│  ┌───────────────────────────────────────────────────────────────────┐  │
│  │ Método: obtenerBusPorId($id)                                     │  │
│  │ SQL: SELECT * FROM vehiculos WHERE id = :id                      │  │
│  │ Retorna: Objeto con los datos del bus                           │  │
│  └───────────────────────────────────────────────────────────────────┘  │
└────────────────────────────────┬────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                        RESPUESTA JSON                                   │
│  ┌───────────────────────────────────────────────────────────────────┐  │
│  │ {                                                                │  │
│  │   "status": "success",                                           │  │
│  │   "bus": {                                                       │  │
│  │     "id": "5",                                                   │  │
│  │     "placa": "ABC-1234",                                         │  │
│  │     "propietario_nombres": "Juan",                               │  │
│  │     ...                                                          │  │
│  │   }                                                              │  │
│  │ }                                                                │  │
│  └───────────────────────────────────────────────────────────────────┘  │
└────────────────────────────────┬────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                    FRONTEND (JavaScript)                                │
│  ┌───────────────────────────────────────────────────────────────────┐  │
│  │ 1. Cerrar SweetAlert2 Loading                                    │  │
│  │ 2. Llenar todos los campos del formulario:                       │  │
│  │    - Campos de texto: .value = data.bus.campo                    │  │
│  │    - Select: .value = data.bus.campo                             │  │
│  │    - Radio buttons: .checked = true (según valor)                │  │
│  │ 3. Abrir Modal de Edición (Bootstrap)                            │  │
│  └───────────────────────────────────────────────────────────────────┘  │
└────────────────────────────────┬────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                    USUARIO EDITA LOS DATOS                              │
│                 (Modifica campos en el modal)                           │
└────────────────────────────────┬────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────────┐
│              Usuario hace clic en "Guardar Cambios"                     │
└────────────────────────────────┬────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                    FRONTEND (JavaScript)                                │
│  ┌───────────────────────────────────────────────────────────────────┐  │
│  │ 1. Validar formulario (HTML5 validation)                         │  │
│  │ 2. Deshabilitar botón y mostrar spinner                          │  │
│  │ 3. Recopilar datos del formulario (FormData)                     │  │
│  └───────────────────────────────────────────────────────────────────┘  │
└────────────────────────────────┬────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                        PETICIÓN AJAX (POST)                             │
│  ┌───────────────────────────────────────────────────────────────────┐  │
│  │ URL: /vehiculos/editar_bus                                       │  │
│  │ Método: POST                                                     │  │
│  │ Body: FormData con todos los campos                              │  │
│  └───────────────────────────────────────────────────────────────────┘  │
└────────────────────────────────┬────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                   BACKEND (Vehiculos.php)                               │
│  ┌───────────────────────────────────────────────────────────────────┐  │
│  │ Método: editar_bus()                                             │  │
│  │ 1. Validar campos obligatorios                                   │  │
│  │ 2. Llamar al modelo: actualizarBus($datos)                       │  │
│  │ 3. Manejar excepciones (duplicados, errores)                     │  │
│  └───────────────────────────────────────────────────────────────────┘  │
└────────────────────────────────┬────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                   MODELO (VehiculoModel.php)                            │
│  ┌───────────────────────────────────────────────────────────────────┐  │
│  │ Método: actualizarBus($datos)                                    │  │
│  │ SQL: UPDATE vehiculos SET                                        │  │
│  │      campo1 = :valor1, campo2 = :valor2, ...                     │  │
│  │      WHERE id = :id                                              │  │
│  │ Retorna: true/false                                              │  │
│  └───────────────────────────────────────────────────────────────────┘  │
└────────────────────────────────┬────────────────────────────────────────┘
                                 │
                                 ▼
                    ┌────────────┴────────────┐
                    │                         │
                    ▼                         ▼
        ┌───────────────────┐     ┌───────────────────┐
        │      ÉXITO        │     │      ERROR        │
        └─────────┬─────────┘     └─────────┬─────────┘
                  │                         │
                  ▼                         ▼
    ┌─────────────────────────┐ ┌─────────────────────────┐
    │ Respuesta JSON          │ │ Respuesta JSON          │
    │ {                       │ │ {                       │
    │   "status": "success",  │ │   "status": "error",    │
    │   "message": "..."      │ │   "message": "..."      │
    │ }                       │ │ }                       │
    └─────────┬───────────────┘ └─────────┬───────────────┘
              │                           │
              ▼                           ▼
┌─────────────────────────┐   ┌─────────────────────────┐
│ FRONTEND (JavaScript)   │   │ FRONTEND (JavaScript)   │
│ 1. Cerrar modal         │   │ 1. Mantener modal       │
│ 2. SweetAlert2 Success  │   │ 2. SweetAlert2 Error    │
│ 3. Recargar página      │   │ 3. Restaurar botón      │
└─────────────────────────┘   └─────────────────────────┘
              │
              ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                    PÁGINA RECARGADA                                     │
│              Tabla muestra los datos actualizados                       │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 📊 COMPONENTES INVOLUCRADOS

### **1. Vista (registrar_buses.php)**
- Botón de editar con `data-id`
- Modal de edición HTML
- JavaScript para AJAX

### **2. Controlador (Vehiculos.php)**
- `obtener_bus()`: Endpoint para lectura
- `editar_bus()`: Endpoint para escritura

### **3. Modelo (VehiculoModel.php)**
- `obtenerBusPorId($id)`: Consulta SELECT
- `actualizarBus($datos)`: Consulta UPDATE

### **4. Base de Datos (MySQL)**
- Tabla: `vehiculos`
- Operaciones: SELECT, UPDATE

---

## 🎯 PUNTOS CLAVE

1. **Sin Recarga**: Todo el proceso se hace via AJAX
2. **Feedback Visual**: SweetAlert2 en cada paso
3. **Validación Doble**: Frontend (HTML5) + Backend (PHP)
4. **Manejo de Errores**: Try-catch para excepciones
5. **JSON Limpio**: `ob_clean()` antes de responder
6. **Seguridad**: Prepared statements con PDO

---

## 🔐 SEGURIDAD

- ✅ Validación de entrada en backend
- ✅ Prepared statements (previene SQL injection)
- ✅ Sanitización de datos con `trim()`
- ✅ Validación de campos obligatorios
- ✅ Manejo de errores sin exponer detalles técnicos

---

## ⚡ RENDIMIENTO

- ✅ Sin recarga de página (AJAX)
- ✅ Carga solo los datos necesarios
- ✅ Respuestas JSON compactas
- ✅ Consultas SQL optimizadas (WHERE id = :id)

---

## 🎨 EXPERIENCIA DE USUARIO

1. **Clic en Editar** → Loading inmediato
2. **Datos cargados** → Modal se abre suavemente
3. **Modificar campos** → Validación en tiempo real
4. **Guardar** → Spinner en el botón
5. **Éxito** → Alerta visual + Recarga automática

---

**Total de archivos modificados:** 3
- `app/views/admin/registrar_buses.php`
- `app/controllers/Vehiculos.php`
- `app/models/VehiculoModel.php`

**Total de líneas de código agregadas:** ~600 líneas
