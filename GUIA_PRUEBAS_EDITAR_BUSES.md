# 🧪 GUÍA DE PRUEBAS: EDITAR BUS

## ✅ CHECKLIST DE PRUEBAS

### **Prueba 1: Abrir Modal de Edición**
- [ ] Navegar a `/admin/registrar_buses`
- [ ] Verificar que aparece la lista de buses
- [ ] Hacer clic en el botón de editar (lápiz) de cualquier bus
- [ ] **Resultado esperado**: 
  - Aparece un loading de SweetAlert2
  - El loading se cierra automáticamente
  - Se abre el modal de edición
  - Todos los campos están rellenados con los datos del bus

---

### **Prueba 2: Verificar Datos Cargados**
- [ ] Con el modal abierto, verificar que los siguientes campos están correctos:
  - [ ] Nombres del Propietario
  - [ ] Apellidos del Propietario
  - [ ] Placa (con formato BOL + placa)
  - [ ] Tarjeta de Circulación
  - [ ] Marca y Modelo
  - [ ] Año de Fabricación
  - [ ] Clase (Bus/Minivan/Vagoneta)
  - [ ] Tipo de Combustible (radio button seleccionado)
  - [ ] Color (radio button seleccionado)
  - [ ] Total de Asientos
  - [ ] Tipo de Servicio (Normal/Semicama/Leito/Suite)

---

### **Prueba 3: Editar y Guardar (Caso Exitoso)**
- [ ] Modificar el campo "Año de Fabricación" (ejemplo: cambiar de 2020 a 2021)
- [ ] Modificar el campo "Color" (seleccionar otro color)
- [ ] Hacer clic en "Guardar Cambios"
- [ ] **Resultado esperado**:
  - El botón se deshabilita y muestra un spinner
  - El modal se cierra
  - Aparece un SweetAlert2 con:
    - ✅ Icono de éxito (verde)
    - Título: "¡Actualizado!"
    - Mensaje: "El bus ha sido actualizado correctamente."
    - Botón azul "OK"
  - Al hacer clic en "OK", la página se recarga
  - En la tabla, el bus muestra los datos actualizados

---

### **Prueba 4: Validación de Campos Obligatorios**
- [ ] Abrir el modal de edición
- [ ] Borrar el contenido del campo "Placa"
- [ ] Hacer clic en "Guardar Cambios"
- [ ] **Resultado esperado**:
  - Aparece un mensaje de validación HTML5
  - El formulario NO se envía
  - El modal permanece abierto

---

### **Prueba 5: Cancelar Edición**
- [ ] Abrir el modal de edición
- [ ] Modificar algunos campos
- [ ] Hacer clic en "Cancelar"
- [ ] **Resultado esperado**:
  - El modal se cierra
  - Los cambios NO se guardan
  - La tabla muestra los datos originales

---

### **Prueba 6: Cerrar Modal con X**
- [ ] Abrir el modal de edición
- [ ] Modificar algunos campos
- [ ] Hacer clic en la X (botón de cerrar)
- [ ] **Resultado esperado**:
  - El modal se cierra
  - Los cambios NO se guardan

---

### **Prueba 7: Editar Múltiples Buses**
- [ ] Editar el primer bus de la lista
- [ ] Guardar cambios
- [ ] Esperar a que la página recargue
- [ ] Editar el segundo bus de la lista
- [ ] Guardar cambios
- [ ] **Resultado esperado**:
  - Ambos buses se actualizan correctamente
  - No hay conflictos entre ediciones

---

### **Prueba 8: Campos Opcionales Vacíos**
- [ ] Abrir el modal de edición
- [ ] Expandir el accordion "Motor, Dimensiones y Pesos"
- [ ] Dejar todos los campos opcionales vacíos
- [ ] Guardar cambios
- [ ] **Resultado esperado**:
  - La actualización se realiza correctamente
  - Los campos opcionales se guardan como vacíos (NULL o '')

---

### **Prueba 9: Selección de Radio Buttons**
- [ ] Abrir el modal de edición
- [ ] Cambiar el tipo de combustible (ejemplo: de Diesel a GNV)
- [ ] Cambiar el color (ejemplo: de Blanco a Negro)
- [ ] Guardar cambios
- [ ] Volver a abrir el modal del mismo bus
- [ ] **Resultado esperado**:
  - Los radio buttons muestran las selecciones correctas
  - GNV está seleccionado
  - Negro está seleccionado

---

### **Prueba 10: Selección de Dropdowns**
- [ ] Abrir el modal de edición
- [ ] Cambiar la "Clase" (ejemplo: de Bus a Minivan)
- [ ] Cambiar el "Tipo de Servicio" (ejemplo: de Normal a Leito)
- [ ] Guardar cambios
- [ ] Volver a abrir el modal del mismo bus
- [ ] **Resultado esperado**:
  - Los dropdowns muestran las selecciones correctas
  - "Minivan" está seleccionado en Clase
  - "Leito" está seleccionado en Tipo de Servicio

---

## 🐛 PRUEBAS DE ERROR

### **Error 1: Placa Duplicada**
- [ ] Abrir el modal de edición del Bus #1
- [ ] Cambiar la placa a una que ya existe (ejemplo: la placa del Bus #2)
- [ ] Guardar cambios
- [ ] **Resultado esperado**:
  - Aparece un SweetAlert2 de error
  - Mensaje: "La placa ingresada ya se encuentra registrada en el sistema."
  - El modal permanece abierto
  - El botón se restaura

---

### **Error 2: ID Inválido**
- [ ] Abrir la consola del navegador (F12)
- [ ] Ejecutar: `abrirModalEditar({getAttribute: () => '99999'})`
- [ ] **Resultado esperado**:
  - Aparece un SweetAlert2 de error
  - Mensaje: "No se encontró el bus con el ID proporcionado."

---

### **Error 3: Sin Conexión**
- [ ] Desconectar internet o detener el servidor
- [ ] Intentar abrir el modal de edición
- [ ] **Resultado esperado**:
  - Aparece un SweetAlert2 de error
  - Mensaje: "No se pudo conectar con el servidor. Verifique su conexión a internet."

---

## 🔍 VERIFICACIÓN EN BASE DE DATOS

### **Verificar Actualización**
```sql
-- Antes de editar, ejecutar:
SELECT * FROM vehiculos WHERE id = 1;

-- Anotar los valores actuales

-- Después de editar y guardar, ejecutar nuevamente:
SELECT * FROM vehiculos WHERE id = 1;

-- Verificar que los campos modificados se actualizaron correctamente
```

---

## 📊 VERIFICACIÓN EN CONSOLA DEL NAVEGADOR

### **Ver Petición AJAX (Obtener Bus)**
1. Abrir DevTools (F12)
2. Ir a la pestaña "Network"
3. Hacer clic en "Editar" en un bus
4. Buscar la petición `obtener_bus`
5. Verificar:
   - **Request Method**: POST
   - **Request Payload**: `id=X`
   - **Response**: JSON con status "success" y datos del bus

### **Ver Petición AJAX (Guardar Cambios)**
1. Modificar campos y guardar
2. Buscar la petición `editar_bus`
3. Verificar:
   - **Request Method**: POST
   - **Request Payload**: FormData con todos los campos
   - **Response**: JSON con status "success" o "error"

---

## ⚡ PRUEBAS DE RENDIMIENTO

### **Tiempo de Carga del Modal**
- [ ] Hacer clic en "Editar"
- [ ] Medir el tiempo desde el clic hasta que el modal se abre
- [ ] **Resultado esperado**: < 1 segundo

### **Tiempo de Guardado**
- [ ] Modificar campos y hacer clic en "Guardar Cambios"
- [ ] Medir el tiempo desde el clic hasta que aparece el SweetAlert2
- [ ] **Resultado esperado**: < 2 segundos

---

## 🎨 PRUEBAS DE UI/UX

### **Responsive Design**
- [ ] Abrir el modal en pantalla completa (1920x1080)
- [ ] Abrir el modal en tablet (768px)
- [ ] Abrir el modal en móvil (375px)
- [ ] **Resultado esperado**: El modal se adapta correctamente a todos los tamaños

### **Accesibilidad**
- [ ] Navegar por el formulario usando solo el teclado (Tab)
- [ ] Verificar que todos los campos son accesibles
- [ ] Verificar que se puede cerrar el modal con Escape
- [ ] **Resultado esperado**: Navegación fluida con teclado

---

## 📝 REGISTRO DE PRUEBAS

| Prueba | Estado | Fecha | Observaciones |
|--------|--------|-------|---------------|
| Prueba 1 | ⬜ | | |
| Prueba 2 | ⬜ | | |
| Prueba 3 | ⬜ | | |
| Prueba 4 | ⬜ | | |
| Prueba 5 | ⬜ | | |
| Prueba 6 | ⬜ | | |
| Prueba 7 | ⬜ | | |
| Prueba 8 | ⬜ | | |
| Prueba 9 | ⬜ | | |
| Prueba 10 | ⬜ | | |
| Error 1 | ⬜ | | |
| Error 2 | ⬜ | | |
| Error 3 | ⬜ | | |

**Leyenda:**
- ⬜ Pendiente
- ✅ Pasó
- ❌ Falló

---

## 🚀 PRUEBA RÁPIDA (5 MINUTOS)

1. Ir a `/admin/registrar_buses`
2. Hacer clic en editar del primer bus
3. Cambiar el año a 2024
4. Cambiar el color a Azul
5. Guardar cambios
6. Verificar que aparece el SweetAlert2 de éxito
7. Verificar que la página recarga
8. Verificar que el bus muestra "2024" en la tabla

**Si todo funciona correctamente, la implementación está lista para producción! 🎉**
