// ============================================
// 🎨 PLANTILLA REUTILIZABLE SWEETALERT2
// ============================================
// Copia y adapta este código para otros formularios

// ============================================
// 1️⃣ BACKEND PHP - Controlador
// ============================================

<? php
class TuControlador extends Controller {
    private $tuModel;

    public function __construct() {
        $this -> tuModel = $this -> model('TuModel');
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            // ✅ PASO 1: Limpiar buffer y establecer headers JSON
            ob_clean();
            header('Content-Type: application/json; charset=utf-8');

            // ✅ PASO 2: Recoger datos del formulario
            $datos = [
                'campo1' => trim($_POST['campo1'] ?? ''),
                'campo2' => trim($_POST['campo2'] ?? ''),
                // ... más campos
            ];

            // ✅ PASO 3: Validación básica
            if (empty($datos['campo1'])) {
                echo json_encode([
                'status' => 'error',
                'message' => 'El campo1 es obligatorio.'
            ]);
                exit;
            }

            // ✅ PASO 4: Intentar guardar
            try {
                if ($this -> tuModel -> guardar($datos)) {
                    // ÉXITO
                    echo json_encode([
                    'status' => 'success',
                    'message' => 'Registro guardado exitosamente.',
                    'id' => $this -> tuModel -> lastInsertId() // Opcional
                ]);
                } else {
                    // ERROR GENÉRICO
                    echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al guardar. Intente nuevamente.'
                ]);
                }
            } catch (Exception $e) {
                // ERROR ESPECÍFICO
                echo json_encode([
                'status' => 'error',
                'message' => 'Error: '.$e -> getMessage()
            ]);
            }
            exit;
        } else {
            header('Location: '.URLROOT. '/tu-ruta');
        }
    }
}
?>

    // ============================================
    // 2️⃣ FRONTEND JAVASCRIPT - Vista
    // ============================================

    <script>
        const miFormulario = document.getElementById('miFormulario');

        miFormulario.addEventListener('submit', function(e) {
            // PASO 1: Prevenir recarga
            e.preventDefault();

        // PASO 2: Deshabilitar botón y mostrar loading
        const btnSubmit = document.getElementById('btnSubmit');
        const textoOriginal = btnSubmit.innerHTML;
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';

        // PASO 3: Recopilar datos
        const formData = new FormData(this);

        // PASO 4: Enviar AJAX
        fetch('<?php echo URLROOT; ?>/tu-controlador/guardar', {
            method: 'POST',
        body: formData
        })
        .then(response => {
            // Validar que sea JSON
            const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Respuesta inválida del servidor');
            }
        return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
            // ✅ ÉXITO
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: data.message,
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            }).then(() => {
                // Redirigir o limpiar formulario
                window.location.href = '<?php echo URLROOT; ?>/tu-ruta';
                // O: miFormulario.reset();
            });
            } else {
            // ❌ ERROR
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message,
                confirmButtonColor: '#d33'
            });

        // Restaurar botón
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = textoOriginal;
            }
        })
        .catch(error => {
            // ⚠️ ERROR DE RED
            console.error('Error:', error);
        Swal.fire({
            icon: 'error',
        title: 'Error de Conexión',
        text: 'No se pudo conectar con el servidor.',
        confirmButtonColor: '#d33'
            });

        btnSubmit.disabled = false;
        btnSubmit.innerHTML = textoOriginal;
        });
    });
    </script>

// ============================================
// 3️⃣ VARIACIONES DE SWEETALERT2
// ============================================

// ✅ CONFIRMACIÓN ANTES DE ELIMINAR
function confirmarEliminacion(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "Esta acción no se puede deshacer",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Proceder con eliminación
            eliminarRegistro(id);
        }
    });
}

// ✅ ÉXITO CON TIMER AUTOMÁTICO
Swal.fire({
    icon: 'success',
    title: '¡Guardado!',
    text: 'Redirigiendo...',
    timer: 2000,
    timerProgressBar: true,
    showConfirmButton: false
}).then(() => {
    window.location.href = '/ruta';
});

// ✅ ÉXITO CON HTML PERSONALIZADO
Swal.fire({
    icon: 'success',
    title: '¡Registro Exitoso!',
    html: `
        <div class="text-start">
            <p><strong>ID:</strong> ${data.id}</p>
            <p><strong>Nombre:</strong> ${data.nombre}</p>
            <p><strong>Fecha:</strong> ${data.fecha}</p>
        </div>
    `,
    confirmButtonColor: '#3085d6'
});

// ✅ ADVERTENCIA CON OPCIONES
Swal.fire({
    icon: 'warning',
    title: 'Atención',
    text: '¿Desea continuar con esta acción?',
    showCancelButton: true,
    showDenyButton: true,
    confirmButtonText: 'Sí, continuar',
    denyButtonText: 'No, cancelar',
    cancelButtonText: 'Volver',
    confirmButtonColor: '#3085d6',
    denyButtonColor: '#d33',
    cancelButtonColor: '#6c757d'
}).then((result) => {
    if (result.isConfirmed) {
        // Usuario confirmó
    } else if (result.isDenied) {
        // Usuario negó
    } else {
        // Usuario canceló
    }
});

// ✅ INPUT EN SWEETALERT2
Swal.fire({
    title: 'Ingrese el motivo',
    input: 'textarea',
    inputPlaceholder: 'Escriba aquí...',
    inputAttributes: {
        'aria-label': 'Motivo'
    },
    showCancelButton: true,
    confirmButtonText: 'Enviar',
    cancelButtonText: 'Cancelar',
    inputValidator: (value) => {
        if (!value) {
            return 'Debe ingresar un motivo';
        }
    }
}).then((result) => {
    if (result.isConfirmed) {
        const motivo = result.value;
        // Procesar motivo
    }
});

// ✅ LOADING MIENTRAS SE PROCESA
Swal.fire({
    title: 'Procesando...',
    html: 'Por favor espere',
    allowOutsideClick: false,
    allowEscapeKey: false,
    didOpen: () => {
        Swal.showLoading();
    }
});

// Cerrar cuando termine
Swal.close();

// ✅ TOAST (NOTIFICACIÓN PEQUEÑA)
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer);
        toast.addEventListener('mouseleave', Swal.resumeTimer);
    }
});

Toast.fire({
    icon: 'success',
    title: 'Guardado exitosamente'
});

// ============================================
// 4️⃣ FUNCIÓN REUTILIZABLE AJAX
// ============================================

/**
 * Envía un formulario por AJAX y muestra SweetAlert2
 * @param {HTMLFormElement} form - El formulario a enviar
 * @param {string} url - URL del endpoint
 * @param {string} successMessage - Mensaje de éxito
 * @param {string} redirectUrl - URL de redirección (opcional)
 */
function enviarFormularioAjax(form, url, successMessage, redirectUrl = null) {
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const btnSubmit = form.querySelector('[type="submit"]');
        const textoOriginal = btnSubmit.innerHTML;
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';

        const formData = new FormData(form);

        fetch(url, {
            method: 'POST',
            body: formData
        })
            .then(response => {
                if (!response.headers.get('content-type')?.includes('application/json')) {
                    throw new Error('Respuesta inválida');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: data.message || successMessage,
                        confirmButtonColor: '#3085d6'
                    }).then(() => {
                        if (redirectUrl) {
                            window.location.href = redirectUrl;
                        } else {
                            form.reset();
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message,
                        confirmButtonColor: '#d33'
                    });
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = textoOriginal;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Conexión',
                    text: 'No se pudo conectar con el servidor.',
                    confirmButtonColor: '#d33'
                });
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = textoOriginal;
            });
    });
}

// USO:
const miForm = document.getElementById('miFormulario');
enviarFormularioAjax(
    miForm,
    '/controlador/guardar',
    'Registro guardado exitosamente',
    '/lista'
);

// ============================================
// 5️⃣ CLASE REUTILIZABLE (POO)
// ============================================

class FormularioAjax {
    constructor(formId, endpoint, redirectUrl = null) {
        this.form = document.getElementById(formId);
        this.endpoint = endpoint;
        this.redirectUrl = redirectUrl;
        this.btnSubmit = this.form.querySelector('[type="submit"]');
        this.textoOriginal = this.btnSubmit.innerHTML;

        this.init();
    }

    init() {
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    handleSubmit(e) {
        e.preventDefault();
        this.setLoading(true);

        const formData = new FormData(this.form);

        fetch(this.endpoint, {
            method: 'POST',
            body: formData
        })
            .then(response => this.validateResponse(response))
            .then(data => this.handleSuccess(data))
            .catch(error => this.handleError(error));
    }

    validateResponse(response) {
        if (!response.headers.get('content-type')?.includes('application/json')) {
            throw new Error('Respuesta inválida del servidor');
        }
        return response.json();
    }

    handleSuccess(data) {
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: data.message,
                confirmButtonColor: '#3085d6'
            }).then(() => {
                if (this.redirectUrl) {
                    window.location.href = this.redirectUrl;
                } else {
                    this.form.reset();
                    this.setLoading(false);
                }
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message,
                confirmButtonColor: '#d33'
            });
            this.setLoading(false);
        }
    }

    handleError(error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error de Conexión',
            text: 'No se pudo conectar con el servidor.',
            confirmButtonColor: '#d33'
        });
        this.setLoading(false);
    }

    setLoading(isLoading) {
        if (isLoading) {
            this.btnSubmit.disabled = true;
            this.btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';
        } else {
            this.btnSubmit.disabled = false;
            this.btnSubmit.innerHTML = this.textoOriginal;
        }
    }
}

// USO:
new FormularioAjax('formBuses', '/vehiculos/guardar', '/admin/registrar_buses');
new FormularioAjax('formRutas', '/rutas/guardar', '/admin/rutas');
new FormularioAjax('formClientes', '/clientes/guardar'); // Sin redirección

// ============================================
// 6️⃣ INTEGRACIÓN CON JQUERY (SI LO USAS)
// ============================================

$('#miFormulario').on('submit', function (e) {
    e.preventDefault();

    const $btn = $(this).find('[type="submit"]');
    const textoOriginal = $btn.html();
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Procesando...');

    $.ajax({
        url: '/controlador/guardar',
        method: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function (data) {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: data.message,
                    confirmButtonColor: '#3085d6'
                }).then(() => {
                    window.location.href = '/lista';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message,
                    confirmButtonColor: '#d33'
                });
                $btn.prop('disabled', false).html(textoOriginal);
            }
        },
        error: function () {
            Swal.fire({
                icon: 'error',
                title: 'Error de Conexión',
                text: 'No se pudo conectar con el servidor.',
                confirmButtonColor: '#d33'
            });
            $btn.prop('disabled', false).html(textoOriginal);
        }
    });
});

// ============================================
// 7️⃣ TEMAS PERSONALIZADOS
// ============================================

// Tema Oscuro
Swal.fire({
    icon: 'success',
    title: '¡Éxito!',
    text: 'Operación completada',
    background: '#1e1e1e',
    color: '#fff',
    confirmButtonColor: '#3085d6'
});

// Tema Personalizado con CSS
const MySwal = Swal.mixin({
    customClass: {
        confirmButton: 'btn btn-primary',
        cancelButton: 'btn btn-secondary'
    },
    buttonsStyling: false
});

MySwal.fire({
    icon: 'success',
    title: '¡Éxito!',
    text: 'Con estilos Bootstrap'
});
