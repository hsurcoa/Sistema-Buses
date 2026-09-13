# INFORME DE ANÁLISIS Y DISEÑO: Módulo de Caja y Finanzas

**Fecha:** 30 de Diciembre de 2025
**Proyecto:** Sistema de Venta de Pasajes y Encomiendas "Transporte Pando"
**Objetivo:** Diseñar la arquitectura lógica para el control de flujo de caja, cierre diario y reportes financieros unificados.

---

## 1. Análisis de la Situación Actual

Actualmente el sistema tiene dos fuentes de ingresos desconectadas financieramente:
1.  **Módulo de Ventas (Pasajes):** Genera ingresos por rutas/asientos.
2.  **Módulo de Encomiendas:** Genera ingresos por fletes de carga.

**Problema:** No existe una entidad central que agrupe este dinero en una "Sesión de Usuario" o "Turno". Si un vendedor hace 10 ventas y 5 encomiendas, no hay una forma rápida de decirle "¿Cuánto dinero debes tener en el cajón ahora mismo?".

---

## 2. Propuesta de Arquitectura de Base de Datos

Para implementar esto eficientemente sin romper lo que ya funciona, se sugiere un modelo de **"Libro Mayor Centralizado"**.

### Nuevas Tablas Sugeridas

#### A. `cajas_sesiones` (Control de Turnos)
Esta tabla controla cuándo un usuario abre y cierra su caja.
*   `id`: PK
*   `usuario_id`: FK (Quién abre la caja)
*   `fecha_apertura`: Datetime
*   `fecha_cierre`: Datetime (Null si está abierta)
*   `monto_inicial`: Decimal (Base/Cambio con el que inicia)
*   `monto_final_sistema`: Decimal (Lo que el sistema calcula que debe haber)
*   `monto_final_real`: Decimal (Lo que el usuario cuenta físicamente)
*   `diferencia`: Decimal (Sobrante o Faltante)
*   `estado`: Enum ('ABIERTA', 'CERRADA')

#### B. `movimientos_caja` (El Libro Diario)
Aquí se registra CADA centavo que entra o sale.
*   `id`: PK
*   `sesion_id`: FK (Vincula el dinero al turno actual)
*   `tipo_movimiento`: Enum ('INGRESO', 'EGRESO')
*   `origen_modulo`: Enum ('PASAJE', 'ENCOMIENDA', 'GASTO', 'APERTURA')
*   `referencia_id`: Int (ID de la venta o encomienda, para trazabilidad)
*   `monto`: Decimal
*   `descripcion`: Texto (Ej: "Boleto Ruta La Paz - Arica")
*   `fecha_creacion`: Datetime

---

## 3. Algoritmo y Lógica de Negocio

### Fase 1: Apertura de Caja
> **Regla de Oro:** Nadie puede vender un pasaje o registrar una encomienda si no tiene una caja "ABIERTA".

1.  Usuario intenta entrar a vender.
2.  Sistema verifica: `SELECT * FROM cajas_sesiones WHERE usuario_id = X AND estado = 'ABIERTA'`.
3.  **Si NO existe:** Redirigir obligatoriamente a pantalla "Apertura de Caja".
    *   Usuario ingresa monto inicial (ej: 200 Bs para cambio).
    *   Se crea registro en `cajas_sesiones` y un primer `movimientos_caja` (INGRESO - APERTURA).

### Fase 2: Registro de Ingresos (Automático)
Este es el punto clave de eficiencia. No obligamos al usuario a registrar la venta doble vez.

**Flujo en Venta de Pasajes:**
1.  Usuario confirma venta de boleto (Insert en tabla `ventas`).
2.  **TRIGGER o LÓGICA MODELO:** Inmediatamente el sistema inserta en `movimientos_caja`:
    *   `sesion_id`: ID de la caja abierta del usuario.
    *   `monto`: Precio del pasaje.
    *   `origen`: 'PASAJE'.
    *   `referencia_id`: ID del boleto vendido.

**Flujo en Encomiendas:**
1.  Idem anterior, pero con `origen`: 'ENCOMIENDA'.

### Fase 3: Registro de Egresos (Gastos)
Se debe crear una pequeña interfaz para registrar salidas de dinero manuales:
*   Combustible de emergencia.
*   Gastos de alimentación chofer.
*   Devoluciones de pasajes (Genera un movimiento de EGRESO).

### Fase 4: Cierre de Caja (Arqueo)
1.  Usuario selecciona "Cerrar Caja".
2.  **Algoritmo de Cálculo:**
    *   `Total Sistema` = (Monto Inicial + Suma de Ingresos) - (Suma de Egresos).
3.  Sistema muestra: "Deberías tener: **Bs. 1,540.50**".
4.  Usuario cuenta su dinero e ingresa: "Tengo: **Bs. 1,540.00**".
5.  Sistema guarda el cierre y calcula la diferencia (-0.50 Bs).
6.  Estado pasa a 'CERRADA'.

---

## 4. Estrategia de Reportes y Eficiencia

Para que los reportes sean rápidos (incluso con miles de datos), consultaremos principalmente la tabla `movimientos_caja`, evitando JOINs complejos innecesarios.

### Consultas Optimizadas

**1. Ingresos por Rango de Fechas:**
```sql
SELECT 
    DATE(fecha_creacion) as dia, 
    SUM(monto) as total 
FROM movimientos_caja 
WHERE tipo_movimiento = 'INGRESO' 
AND fecha_creacion BETWEEN :inicio AND :fin
GROUP BY dia;
```

**2. Ingresos vs Egresos (Balance):**
```sql
SELECT 
    tipo_movimiento, 
    SUM(monto) 
FROM movimientos_caja 
WHERE fecha_creacion BETWEEN :inicio AND :fin 
GROUP BY tipo_movimiento;
```

**3. Desglose por Origen (¿Qué es más rentable?):**
```sql
SELECT 
    origen_modulo, 
    SUM(monto) as total
FROM movimientos_caja 
WHERE tipo_movimiento = 'INGRESO' 
GROUP BY origen_modulo;
```

---

## 5. Hoja de Ruta de Implementación

Para implementar esto sin detener la operación, sugiero este orden:

1.  **Base de Datos:** Crear las tablas migraciones (`cajas_sesiones`, `movimientos_caja`).
2.  **Backend (Modelos):** 
    *   Crear `CajaModel`.
    *   Modificar `RutaModel` y `EncomiendaModel` para que, al guardar, llamen a una función `CajaModel::registrarMovimiento()`.
3.  **Middleware:** Crear un "CheckCajaAbierta" que impida acceder a ventas si no hay sesión.
4.  **Frontend:**
    *   Vista de Apertura/Cierre.
    *   Dashboard Financiero (Gráficos con Chart.js usando los datos de `movimientos_caja`).

### Conclusión
Esta estructura centralizada ("Libro Mayor") es la más profesional y escalable. Permite saber exactamente cuánto dinero entró por pasajes y cuánto por encomiendas, quién lo cobró y cuándo, facilitando auditorías perfectas.
