# INFORME TÉCNICO: Diseño y Lógica del Nuevo Módulo de Encomiendas

**Fecha:** 30 de Diciembre de 2025
**Proyecto:** Sistema de Venta de Pasajes & Encomiendas "Valhalla"
**Solicitado por:** Administrador del Sistema

---

## 1. Resumen Ejecutivo
Este informe detalla la arquitectura lógica, algoritmos y flujos de trabajo necesarios para integrar un módulo de **Paquetería y Encomiendas** en el sistema actual de transportes. El objetivo es reutilizar la infraestructura de rutas existente, maximizando la rentabilidad de cada viaje mediante el transporte de carga, sin interferir con la venta de pasajes.

## 2. Análisis de Requerimientos e Integración

### 2.1. Integración con el Ecosistema Actual
El sistema actual gestiona `Rutas`, `Buses` y `Viajes`. El módulo de encomiendas no funcionará aislado, sino como una capa paralela al módulo de pasajes:
*   **Rutas:** Se utilizarán las mismas rutas (Origen -> Destino).
*   **Viajes:** Las encomiendas se asignarán a viajes específicos (coincidiendo con horarios de salida) para garantizar tiempos de entrega exactos.
*   **Cliente:** Se aprovechará la base de datos de clientes existente, diferenciando entre "Remitente" (quien envía) y "Destinatario" (quien recibe).

### 2.2. Nueva Estructura de Datos Propuesta
Se requiere la creación de entidades lógicas (tablas) para soportar esta operación sin tocar el código existente de pasajes:

1.  **`encomiendas`**: Tabla principal.
    *   ID Guía (Código único de rastreo, ej: `ENC-2025-001`).
    *   ID Viaje (Vinculación con el autobús que llevará la carga).
    *   Datos Remitente & Destinatario.
    *   Estado (Recibido, En Tránsito, En Destino, Entregado).
    *   Códigos de seguridad (QR o Código de retiro).
2.  **`detalles_encomienda`**:
    *   Descripción del contenido.
    *   Peso (kg).
    *   Tipo (Fragil, Documentos, Electrónica, General).
    *   Valor Declarado (para el seguro).
3.  **`tarifas_encomienda`**:
    *   Lógica de precios dinámica (se explica en la sección 3).

---

## 3. Lógica de Negocio y Algoritmos

### 3.1. Algoritmo de Cotización Dinámica (Pricing)
El precio no debe ser fijo. Se propone un algoritmo que considere tres variables:
1.  **Factor Distancia/Ruta ($F_ruta$):** Precio base por transportar algo de A a B.
2.  **Factor Peso ($F_peso$):** Precio por Kilogramo.
3.  **Factor Seguro ($F_seguro$):** Porcentaje del valor declarado (opcional, pero recomendado).

**Fórmula Propuesta:**
```
Total = (PrecioBaseRuta) + (Peso * PrecioPorKg) + (ValorDeclarado * %Seguro)
```

### 3.2. Lógica de Asignación de Capacidad (Bodega)
Al igual que los asientos, el bus tiene un límite de carga (volumen/peso).
*   **Algoritmo de Control:**
    1.  Obtener `Capacidad_Bodega` del bus asignado al viaje.
    2.  Sumar pesos de todas las encomiendas activas para ese `ID_Viaje`.
    3.  **SI** `(Peso_Actual + Nuevo_Peso) > Capacidad_Bodega` **ENTONCES**:
        *   Bloquear asignación a este viaje.
        *   Sugerir siguiente horario disponible automáticamente.

---

## 4. Flujo Operativo (Workflow)

### Paso 1: Recepción de la Encomienda (Counter de Origen)
1.  El operador selecciona la **Ruta de Destino**.
2.  El sistema muestra los **Próximos Viajes** disponibles (reutilizando la lógica de pasajes).
3.  El operador selecciona un viaje (ej: Bus de las 14:00 hrs).
4.  Se ingresan datos:
    *   **Remitente:** DNI/Nombre (Autocompletar si ya existe).
    *   **Destinatario:** DNI/Nombre/Teléfono (Vital para notificaciones).
    *   **Paquete:** Peso, Tipo y Valor.
5.  **Cálculo Automático:** El sistema ejecuta el algoritmo de precios y muestra el total.
6.  **Emisión:** Se genera una **Guía de Remisión** (Ticket con Código de Barras/QR).

### Paso 2: Manifiesto de Carga (Despacho)
Antes de que el bus salga, el sistema genera un "Manifiesto de Encomiendas" (lista de paquetes) para el chofer/ayudante, separado de la lista de pasajeros.

### Paso 3: Recepción en Destino
1.  Cuando el bus llega, el operador de destino cambia el estado del viaje a "Arribado".
2.  Automáticamente, todas las encomiendas de ese viaje pasan a estado **"Disponible para Recojo"**.
3.  (Opcional) El sistema envía un SMS/WhatsApp al destinatario: *"Su paquete ha llegado"*.

### Paso 4: Entrega Final
1.  El destinatario acude a la oficina.
2.  Presenta su DNI y el número de guía.
3.  El operador valida en el sistema.
4.  Cambio de estado a **"Entregado"** y cierre del ciclo.

---

## 5. Diseño del Algoritmo (Pseudocódigo)

Aquí se presenta la lógica técnica para la función principal: `registrarEncomienda()`

```pseudocode
FUNCION registrarEncomienda(datos_paquete, ruta_id, viaje_id):

    // 1. Validaciones
    viaje = OBTENER_VIAJE(viaje_id)
    SI viaje.estado != 'PROGRAMADO':
        RETORNAR Error("El bus ya salió, seleccione otro horario")

    // 2. Verificar Capacidad
    peso_actual_bodega = SUMAR_PESO_ENCOMIENDAS(viaje_id)
    capacidad_max = viaje.bus.capacidad_carga
    
    SI (peso_actual_bodega + datos_paquete.peso) > capacidad_max:
        RETORNAR Error("Bodega llena. Espacio disponible: " + (capacidad_max - peso_actual_bodega))

    // 3. Cotización Automaticas
    tarifa_base = OBTENER_TARIFA_BASE(ruta_id)
    costo_peso = datos_paquete.peso * PRECIO_POR_KG
    costo_seguro = datos_paquete.valor_declarado * 0.02 // 2% de seguro
    
    total_pagar = tarifa_base + costo_peso + costo_seguro

    // 4. Generar Código Único (GUIA)
    codigo_guia = GENERAR_UUID("ENC-") 

    // 5. Persistencia
    GUARDAR_EN_BD({
        codigo: codigo_guia,
        viaje_id: viaje_id,
        remitente: datos_paquete.remitente,
        destinatario: datos_paquete.destinatario,
        total: total_pagar,
        estado: 'REGISTRADO_EN_ORIGEN'
    })

    // 6. Output
    IMPRIMIR_TICKET(codigo_guia, codigo_qr, detalles)
    RETORNAR Exito
FIN FUNCION
```

## 6. Recomendaciones de Implementación

1.  **Impresión Térmica:** Implementar etiquetas adhesivas con QR para pegar en las cajas/sobres. Facilita el escaneo en la carga y descarga.
2.  **Módulo de Rastreo Público:** Crear una página simple (`/rastreo`) donde los clientes puedan poner su código de guía y ver dónde está su paquete sin llamar a la oficina.
3.  **Seguridad:** Implementar un campo de "Clave de Retiro" (4 dígitos) que el remitente comparte con el destinatario, para evitar entregas a personas equivocadas (opcional para envíos de alto valor).
