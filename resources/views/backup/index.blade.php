@extends('layouts.app')

@section('content')

<!--begin::App Main-->
<main class="app-main">
    <!--begin::App Content Header-->
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Copias de Seguridad</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="<?php echo URLROOT; ?>/dashboard">Gestión</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Copias de Seguridad</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!--end::App Content Header-->

    <!--begin::App Content-->
    <div class="app-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <!-- Backup Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Copias de Seguridad</h3>
                            <p class="text-muted mb-0">Generación y Gestión de Respaldos del Sistema</p>
                        </div>
                        <div class="card-body">
                            <!-- Generate Backup Section -->
                            <div class="mb-4">
                                <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                                    <div>
                                        <h5 class="mb-1">
                                            <i class="bi bi-database me-2"></i>GENERAR NUEVO BACKUP
                                        </h5>
                                    </div>
                                </div>

                                <!-- Info Box -->
                                <div class="alert alert-info" role="alert">
                                    <h6 class="alert-heading mb-2">
                                        <i class="bi bi-info-circle me-2"></i>¿Qué incluye el backup?
                                    </h6>
                                    <ul class="mb-0 ps-3">
                                        <li><strong>Base de datos:</strong> Exportación completa (estructura y datos) en formato .sql</li>
                                        <li><strong>Código fuente:</strong> Archivos del sistema comprimidos en .zip</li>
                                        <li><strong>Exclusiones:</strong> Se excluyen carpetas temporales y backups previos.</li>
                                    </ul>
                                </div>

                                <!-- Generate Button -->
                                <div class="text-end">
                                    <button type="button" class="btn btn-success btn-lg" id="generateBackupBtn">
                                        <i class="bi bi-download me-2"></i>Generar Backup Completo
                                    </button>
                                </div>
                            </div>

                            <!-- Existing Backups Section -->
                            <div>
                                <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                                    <div>
                                        <h5 class="mb-1">
                                            <i class="bi bi-archive me-2"></i>BACKUPS EXISTENTES
                                        </h5>
                                    </div>
                                </div>

                                <!-- Export Buttons -->
                                <div class="mb-3">
                                    <button class="btn btn-outline-secondary btn-sm me-2">Copy</button>
                                    <button class="btn btn-outline-secondary btn-sm me-2">Excel</button>
                                    <button class="btn btn-outline-secondary btn-sm me-2">CSV</button>
                                    <button class="btn btn-outline-secondary btn-sm">PDF</button>
                                    <div class="float-end">
                                        <input type="text" class="form-control form-control-sm" placeholder="Buscar:" style="width: 200px;">
                                    </div>
                                </div>

                                <!-- Backups Table -->
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover" id="backupsTable">
                                        <thead>
                                            <tr>
                                                <th>TIPO</th>
                                                <th>NOMBRE DEL ARCHIVO</th>
                                                <th>TAMAÑO</th>
                                                <th>FECHA DE CREACIÓN</th>
                                                <th>ACCIONES</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (isset($data['backups']) && !empty($data['backups'])): ?>
                                                <?php foreach ($data['backups'] as $backup): ?>
                                                    <tr>
                                                        <td>
                                                            <?php if ($backup['type'] == 'sql'): ?>
                                                                <span class="badge bg-success">SQL</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-primary">ZIP</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($backup['name']); ?></td>
                                                        <td><?php echo $backup['size']; ?></td>
                                                        <td><?php echo $backup['date']; ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-primary" onclick="downloadBackup('<?php echo $backup['name']; ?>')">
                                                                <i class="bi bi-download"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger" onclick="deleteBackup('<?php echo $backup['name']; ?>')">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">No hay backups disponibles</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--end::App Content-->
</main>
<!--end::App Main-->

<script>
    // Variable global para almacenar el nombre del archivo a eliminar
    let fileToDelete = null;

    // Generar Backup con SweetAlert2
    document.getElementById('generateBackupBtn').addEventListener('click', function() {
        const btn = this;
        const originalHTML = btn.innerHTML;

        // Deshabilitar botón y mostrar spinner
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generando...';

        // Realizar petición AJAX
        fetch('<?php echo URLROOT; ?>/backup/generate', {
                method: 'GET'
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Backup Exitoso!',
                        html: `
                        <div class="text-start">
                            <p class="mb-2"><strong>Base de Datos:</strong> ${data.details.database}</p>
                            <p class="mb-2"><strong>Archivos:</strong> ${data.details.files}</p>
                            <p class="mb-0"><strong>Ubicación:</strong> ${data.details.location}</p>
                        </div>
                    `,
                        confirmButtonColor: '#28a745',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al Generar Backup',
                        text: data.message || 'Ocurrió un error inesperado',
                        confirmButtonColor: '#d33'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Conexión',
                    text: 'No se pudo conectar con el servidor. Por favor, intente nuevamente.',
                    confirmButtonColor: '#d33'
                });
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            });
    });

    async function downloadBackup(filename) {
        try {
            // Mostrar indicador de carga
            Swal.fire({
                title: 'Descargando...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // 1. Obtener datos del servidor
            const response = await fetch('<?php echo URLROOT; ?>/backup/download/' + encodeURIComponent(filename));
            if (!response.ok) throw new Error('Error de red al descargar');

            // 2. Obtener Blob (binario puro)
            const blob = await response.blob();

            // 3. Determinar MIME Type correcto
            const extension = filename.split('.').pop().toLowerCase();
            let mimeType = 'application/octet-stream';

            if (extension === 'sql') mimeType = 'application/sql';
            if (extension === 'csv') mimeType = 'text/csv';

            // 4. Usar la función robusta
            downloadFile(blob, filename, mimeType);

            // Cerrar el loading y mostrar éxito
            Swal.fire({
                icon: 'success',
                title: 'Descarga Iniciada',
                text: 'El archivo se está descargando',
                timer: 2000,
                showConfirmButton: false
            });

        } catch (error) {
            console.error('Download error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error al Descargar',
                text: error.message,
                confirmButtonColor: '#d33'
            });
        }
    }

    /**
     * Función Universal para forzar descargas en Chrome/Edge/Firefox
     * Soluciona el error de "Error de red" o nombres tipo Hash.
     * @param {Blob|string} data - El contenido del archivo o el objeto Blob
     * @param {string} filename - El nombre exacto (ej: backup_2025.sql)
     * @param {string} mimeType - (Opcional) ej: 'application/pdf' o 'application/sql'
     */
    function downloadFile(data, filename, mimeType = 'application/octet-stream') {
        // 1. Convertir a Blob si es texto (SQL/CSV) y agregar BOM si es necesario
        let blob;
        if (data instanceof Blob) {
            // Si ya es Blob, usamos tal cual (el fetch response.blob() ya maneja el contenido binario)
            blob = data;
        } else {
            // El \ufeff es vital para que Excel/Windows reconozca tildes en SQL/CSV
            const isText = mimeType.includes('text') || filename.endsWith('.sql') || filename.endsWith('.csv');
            blob = new Blob([isText ? '\ufeff' + data : data], {
                type: mimeType
            });
        }

        // 2. Crear la URL del objeto en memoria
        const url = window.URL.createObjectURL(blob);

        // 3. Crear el elemento invisible (El truco para Chrome)
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', filename);
        link.style.display = 'none';

        // 4. IMPORTANTE: Añadirlo al DOM (Chrome requiere esto)
        document.body.appendChild(link);

        // 5. Disparar la descarga
        link.click();

        // 6. EL SECRETO: Esperar antes de limpiar (Soluciona el error de 0 bytes / nombre corrupto)
        // Chrome necesita unos milisegundos para "agarrar" el archivo.
        setTimeout(() => {
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);
        }, 200);
    }

    function deleteBackup(filename) {
        Swal.fire({
            title: '¿Está seguro?',
            html: `
                <div class="text-start">
                    <p class="mb-2"><strong>Archivo:</strong></p>
                    <p class="text-muted mb-3" style="word-break: break-all;">${filename}</p>
                    <div class="alert alert-danger mb-0">
                        <i class="bi bi-exclamation-octagon me-2"></i>
                        <strong>⚠️ Esta acción es IRREVERSIBLE y no se puede deshacer.</strong>
                    </div>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bi bi-trash me-2"></i>Sí, eliminar',
            cancelButtonText: '<i class="bi bi-x-circle me-2"></i>Cancelar',
            customClass: {
                confirmButton: 'btn btn-danger',
                cancelButton: 'btn btn-secondary'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                Swal.fire({
                    title: 'Eliminando...',
                    text: 'Por favor espere',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Realizar petición AJAX
                fetch('<?php echo URLROOT; ?>/backup/delete/' + encodeURIComponent(filename), {
                        method: 'GET'
                    })
                    .then(response => {
                        // Verificar si la respuesta es exitosa
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }

                        // Intentar parsear como JSON
                        const contentType = response.headers.get("content-type");
                        if (contentType && contentType.indexOf("application/json") !== -1) {
                            return response.json();
                        } else {
                            // Si no es JSON, leer como texto para debugging
                            return response.text().then(text => {
                                console.error('Respuesta no-JSON recibida:', text);
                                throw new Error('El servidor no devolvió una respuesta JSON válida');
                            });
                        }
                    })
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Eliminado!',
                                text: data.message,
                                confirmButtonColor: '#28a745'
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'No se pudo eliminar el archivo',
                                confirmButtonColor: '#d33'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error al eliminar backup:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de Conexión',
                            text: error.message || 'No se pudo conectar con el servidor',
                            confirmButtonColor: '#d33'
                        });
                    });
            }
        });
    }
</script>

@endsection
