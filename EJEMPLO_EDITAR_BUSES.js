// ==========================================
// 🚀 EJEMPLO DE USO: EDITAR BUS
// ==========================================

// Este archivo muestra ejemplos de las peticiones y respuestas
// que se generan durante el proceso de edición de un bus

// ==========================================
// 1. PETICIÓN AJAX PARA OBTENER DATOS
// ==========================================

// JavaScript (Frontend)
fetch('<?php echo URLROOT; ?>/vehiculos/obtener_bus', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'id=5'  // ID del bus a editar
})

// Respuesta JSON (Backend)
{
    "status": "success",
        "bus": {
        "id": "5",
            "propietario_nombres": "Juan Carlos",
                "propietario_apellidos": "Pérez García",
                    "tarjeta_circulacion": "TC-123456",
                        "placa": "ABC-1234",
                            "clase": "Bus",
                                "marca": "Volvo",
                                    "anio": "2020",
                                        "modelo": "B11R",
                                            "tipo_combustible": "Diesel",
                                                "carroceria": "Marcopolo",
                                                    "ejes": "2",
                                                        "color": "Blanco",
                                                            "nro_motor": "D13K500",
                                                                "cilindros": "6",
                                                                    "nro_serie": "YV3R8E40XLA123456",
                                                                        "ruedas": "6",
                                                                            "peso_seco": "12500",
                                                                                "peso_bruto": "18000",
                                                                                    "longitud": "12.50",
                                                                                        "altura": "3.80",
                                                                                            "ancho": "2.60",
                                                                                                "pasajeros": "45",
                                                                                                    "asientos": "45",
                                                                                                        "tipo_servicio": "Semicama",
                                                                                                            "estado": "1",
                                                                                                                "fecha_registro": "2024-12-20 10:30:00"
    }
}

// ==========================================
// 2. PETICIÓN AJAX PARA GUARDAR CAMBIOS
// ==========================================

// JavaScript (Frontend)
const formData = new FormData(document.getElementById('formEditarBus'));

fetch('<?php echo URLROOT; ?>/vehiculos/editar_bus', {
    method: 'POST',
    body: formData
})

// Datos enviados (FormData)
{
    "id": "5",
        "propietario_nombres": "Juan Carlos",
            "propietario_apellidos": "Pérez García",
                "tarjeta_circulacion": "TC-123456",
                    "placa": "ABC-1234",
                        "clase": "Bus",
                            "marca": "Volvo",
                                "anio": "2021",  // ← MODIFICADO
                                    "modelo": "B11R",
                                        "tipo_combustible": "Diesel",
                                            "carroceria": "Marcopolo",
                                                "ejes": "2",
                                                    "color": "Azul",  // ← MODIFICADO
                                                        "nro_motor": "D13K500",
                                                            "cilindros": "6",
                                                                "nro_serie": "YV3R8E40XLA123456",
                                                                    "ruedas": "6",
                                                                        "peso_seco": "12500",
                                                                            "peso_bruto": "18000",
                                                                                "longitud": "12.50",
                                                                                    "altura": "3.80",
                                                                                        "ancho": "2.60",
                                                                                            "pasajeros": "45",
                                                                                                "asientos": "45",
                                                                                                    "tipo_servicio": "Leito"  // ← MODIFICADO
}

// Respuesta JSON (Backend) - ÉXITO
{
    "status": "success",
        "message": "El bus ha sido actualizado correctamente.",
            "placa": "ABC-1234"
}

// Respuesta JSON (Backend) - ERROR
{
    "status": "error",
        "message": "La placa ingresada ya se encuentra registrada en el sistema."
}

// ==========================================
// 3. SWEETALERT2 MOSTRADO AL USUARIO
// ==========================================

// Éxito
Swal.fire({
    icon: 'success',
    title: '¡Actualizado!',
    text: 'El bus ha sido actualizado correctamente.',
    confirmButtonColor: '#3085d6',
    confirmButtonText: 'OK',
    allowOutsideClick: false
}).then((result) => {
    if (result.isConfirmed) {
        window.location.reload();  // Recarga la página
    }
});

// Error
Swal.fire({
    icon: 'error',
    title: 'Error al Actualizar',
    text: 'La placa ingresada ya se encuentra registrada en el sistema.',
    confirmButtonColor: '#d33',
    confirmButtonText: 'Entendido'
});

// ==========================================
// 4. EJEMPLO DE LLENADO DE CAMPOS
// ==========================================

// Campos de texto simples
document.getElementById('editPropietarioNombres').value = data.bus.propietario_nombres || '';
document.getElementById('editPlaca').value = data.bus.placa || '';

// Select (Dropdown)
document.getElementById('editClase').value = data.bus.clase || 'Bus';
document.getElementById('editTipoServicio').value = data.bus.tipo_servicio || 'Normal';

// Radio Buttons - Combustible
const combustible = data.bus.tipo_combustible || 'Diesel';
if (combustible === 'Diesel') {
    document.getElementById('editCombustibleDiesel').checked = true;
} else if (combustible === 'Gasolina') {
    document.getElementById('editCombustibleGasolina').checked = true;
} else if (combustible === 'GNV') {
    document.getElementById('editCombustibleGNV').checked = true;
}

// Radio Buttons - Color
const color = data.bus.color || 'Blanco';
if (color === 'Blanco') {
    document.getElementById('editColorBlanco').checked = true;
} else if (color === 'Rojo') {
    document.getElementById('editColorRojo').checked = true;
} else if (color === 'Azul') {
    document.getElementById('editColorAzul').checked = true;
} else if (color === 'Negro') {
    document.getElementById('editColorNegro').checked = true;
} else if (color === 'Plata') {
    document.getElementById('editColorPlata').checked = true;
}

// ==========================================
// 5. CONSULTAS SQL EJECUTADAS
// ==========================================

// Obtener bus por ID
SELECT * FROM vehiculos WHERE id = 5;

// Actualizar bus
UPDATE vehiculos SET
propietario_nombres = 'Juan Carlos',
    propietario_apellidos = 'Pérez García',
    tarjeta_circulacion = 'TC-123456',
    placa = 'ABC-1234',
    clase = 'Bus',
    marca = 'Volvo',
    anio = '2021',
    modelo = 'B11R',
    tipo_combustible = 'Diesel',
    carroceria = 'Marcopolo',
    ejes = '2',
    color = 'Azul',
    nro_motor = 'D13K500',
    cilindros = '6',
    nro_serie = 'YV3R8E40XLA123456',
    ruedas = '6',
    peso_seco = '12500',
    peso_bruto = '18000',
    longitud = '12.50',
    altura = '3.80',
    ancho = '2.60',
    pasajeros = '45',
    asientos = '45',
    tipo_servicio = 'Leito'
WHERE id = 5;
