@extends('layouts.app')

@section('content')
<!-- Main Wrapper -->
<main class="app-main">
    <!-- Content Header (Page header) -->
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <!-- Título opcional si lo deseas -->
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="#">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="#">Administrador</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Registrar Personal</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <div class="app-content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-12">

                    <!-- CARD PRINCIPAL -->
                    <div class="card card-outline mb-4">
                        <!-- Cabecera del Card -->
                        <div class="card-header border-0">
                            <h3 class="card-title fw-bold">
                                <i class="fas fa-bus me-2"></i> REGISTRAR PERSONAL
                            </h3>
                        </div>

                        <!-- Cuerpo del Formulario -->
                        <div class="card-body">
                            <form action="<?php echo URLROOT; ?>/admin/guardar_personal" method="POST" enctype="multipart/form-data">
                                <!-- TOKEN DE SEGURIDAD CSRF -->
                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

                                <input type="hidden" name="id" value="">
                                <div class="row">

                                    <!-- COLUMNA IZQUIERDA: FOTO (3 columnas) -->
                                    <div class="col-md-3 text-center mb-4">
                                        <label class="form-label d-block text-start small text-muted text-uppercase fw-bold">Foto Personal</label>

                                        <!-- Contenedor de la foto placeholder -->
                                        <div class="border rounded bg-light d-flex align-items-center justify-content-center mb-3 overflow-hidden position-relative" style="min-height: 200px; width: 100%;">
                                            <!-- Imagen previa (vacía o cargada) -->
                                            <img id="preview-foto" src="#" alt="Vista Previa" class="d-none w-100">

                                            <!-- Placeholder Icono -->
                                            <div id="placeholder-foto" class="text-center text-muted">
                                                <i class="fas fa-camera fa-3x mb-2"></i>
                                                <p class="small m-0">Sin imagen</p>
                                            </div>
                                        </div>

                                        <!-- Botón de subida (Azul ancho) -->
                                        <label class="btn btn-primary w-100 shadow-sm">
                                            <i class="fas fa-camera me-2"></i> Subir Foto
                                            <input type="file" name="foto" class="d-none" onchange="previewImage(this)">
                                        </label>
                                    </div>

                                    <!-- COLUMNA DERECHA: CAMPOS (9 columnas) -->
                                    <div class="col-md-9">

                                        <!-- FILA 1: Nombres y Apellidos -->
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label small text-muted text-uppercase fw-bold">Nombres:</label>
                                                <input type="text" class="form-control" name="nombres" placeholder="Ej: Andrea Maria" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small text-muted text-uppercase fw-bold">Apellidos:</label>
                                                <input type="text" class="form-control" name="apellidos" placeholder="Ej: Evangelines Fernandez" required>
                                            </div>
                                        </div>

                                        <!-- FILA 2: Documento, Genero, Fecha -->
                                        <div class="row mb-3">
                                            <div class="col-md-3">
                                                <label class="form-label small text-muted text-uppercase fw-bold">Tipo Documento:</label>
                                                <select class="form-select" name="tipo_documento">
                                                    <option value="CI">CI</option>
                                                    <option value="PASAPORTE">PASAPORTE</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small text-muted text-uppercase fw-bold">N° Documento:</label>
                                                <input type="text" class="form-control" name="numero_documento" placeholder="Ej: 80808589">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small text-muted text-uppercase fw-bold">Género:</label>
                                                <select class="form-select" name="genero">
                                                    <option value="Femenino">Femenino</option>
                                                    <option value="Masculino">Masculino</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small text-muted text-uppercase fw-bold">Fecha Nacimiento:</label>
                                                <input type="date" class="form-control" name="fecha_nacimiento">
                                            </div>
                                        </div>

                                        <!-- FILA 3: Contacto -->
                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <label class="form-label small text-muted text-uppercase fw-bold">Celular:</label>
                                                <input type="tel" class="form-control" name="celular" placeholder="Ej: 999 999 999">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small text-muted text-uppercase fw-bold">Email:</label>
                                                <input type="email" class="form-control" name="email" placeholder="correo@ejemplo.com">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small text-muted text-uppercase fw-bold">Dirección Domicilio:</label>
                                                <input type="text" class="form-control" name="direccion" placeholder="Av. Principal 123">
                                            </div>
                                        </div>

                                        <!-- FILA 4: Ubicación y Perfil -->
                                        <div class="row mb-4">
                                            <div class="col-md-3">
                                                <label class="form-label small text-muted text-uppercase fw-bold">Departamento:</label>
                                                <select class="form-select" name="departamento" required>
                                                    <option value="" selected disabled>Seleccione</option>
                                                    <?php if (!empty($data['departamentos'])): ?>
                                                        <?php foreach ($data['departamentos'] as $dpto): ?>
                                                            <option value="<?php echo htmlspecialchars($dpto->nombre); ?>"><?php echo htmlspecialchars($dpto->nombre); ?></option>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small text-muted text-uppercase fw-bold">Provincia:</label>
                                                <input type="text" class="form-control" name="provincia" placeholder="Ingrese provincia">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small text-muted text-uppercase fw-bold">Distrito:</label>
                                                <input type="text" class="form-control" name="distrito" placeholder="Ingrese distrito">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small text-muted text-uppercase fw-bold">Perfil:</label>
                                                <select class="form-select" name="perfil" required>
                                                    <option value="" selected disabled>Seleccione</option>
                                                    <?php if (!empty($data['roles'])): ?>
                                                        <?php foreach ($data['roles'] as $rol): ?>
                                                            <option value="<?php echo htmlspecialchars($rol->nombre); ?>"><?php echo htmlspecialchars($rol->nombre); ?></option>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                        </div>

                                    </div> <!-- Fin Columna Derecha -->
                                </div> <!-- Fin Row Principal -->

                                <!-- FOOTER / BOTONES -->
                                <div class="card-footer bg-transparent border-0 ps-0 mt-2">
                                    <button type="submit" class="btn btn-secondary px-5" style="background-color: #6c757d; border-color: #6c757d;">
                                        Guardar
                                    </button>
                                </div>

                            </form>
                        </div> <!-- Fin Card Body -->
                    </div> <!-- Fin Card -->

                    <!-- CARD LISTADO (Tabla) -->
                    <div class="card card-outline">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered table-striped align-middle text-nowrap mb-0">
                                    <thead class="bg-light">
                                        <tr class="text-center text-secondary small text-uppercase">
                                            <th style="width: 100px;">Acciones</th>
                                            <th>Perfil</th>
                                            <th>Estado</th>
                                            <th>Nombres</th>
                                            <th>Apellidos</th>
                                            <th>Tipo</th>
                                            <th>Documento</th>
                                            <th>Genero</th>
                                            <th>Nacimiento</th>
                                            <th>Celular</th>
                                            <th>Email</th>
                                            <th>Direccion</th>
                                            <th>Departamento</th>
                                            <th>Provincia</th>
                                        </tr>
                                    </thead>
                                    <tbody class="small">
                                        <?php if (!empty($data['personal'])): ?>
                                            <?php foreach ($data['personal'] as $persona): ?>
                                                <tr>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-outline-primary border-0" title="Editar" onclick="editPersonal(<?php echo $persona->id; ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger border-0" title="Eliminar" onclick="confirmarEliminacion(<?php echo $persona->id; ?>, '<?php echo htmlspecialchars($persona->nombres . ' ' . $persona->apellidos); ?>')">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </td>
                                                    <td class="text-center"><?php echo $persona->perfil; ?></td>
                                                    <td class="text-center">
                                                        <a href="<?php echo URLROOT; ?>/admin/cambiar_estado/<?php echo $persona->id; ?>" class="text-decoration-none">
                                                            <?php if ($persona->estado): ?>
                                                                <span class="text-success"><i class="fas fa-toggle-on fa-lg"></i></span>
                                                            <?php else: ?>
                                                                <span class="text-secondary"><i class="fas fa-toggle-off fa-lg"></i></span>
                                                            <?php endif; ?>
                                                        </a>
                                                    </td>
                                                    <td><?php echo $persona->nombres; ?></td>
                                                    <td><?php echo $persona->apellidos; ?></td>
                                                    <td class="text-center"><?php echo $persona->tipo_documento; ?></td>
                                                    <td class="text-center"><?php echo $persona->numero_documento; ?></td>
                                                    <td class="text-center"><?php echo $persona->genero; ?></td>
                                                    <td class="text-center"><?php echo $persona->fecha_nacimiento; ?></td>
                                                    <td class="text-center"><?php echo $persona->celular; ?></td>
                                                    <td><?php echo $persona->email; ?></td>
                                                    <td><?php echo $persona->direccion_domicilio; ?></td>
                                                    <td><?php echo $persona->departamento; ?></td>
                                                    <td><?php echo $persona->provincia; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="14" class="text-center">No hay personal registrado.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div> <!-- Fin Card Listado -->

                </div>
            </div>
        </div>
    </div>
</main>

<!-- Script Simple para Previsualizar Foto -->
<script>
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview-foto').src = e.target.result;
                document.getElementById('preview-foto').classList.remove('d-none');
                document.getElementById('placeholder-foto').classList.add('d-none');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
<script>
    function editPersonal(id) {
        fetch('<?php echo URLROOT; ?>/admin/get_personal/' + id)
            .then(response => response.json())
            .then(data => {
                if (data) {
                    document.querySelector('[name="id"]').value = data.id;
                    document.querySelector('[name="nombres"]').value = data.nombres;
                    document.querySelector('[name="apellidos"]').value = data.apellidos;
                    document.querySelector('[name="tipo_documento"]').value = data.tipo_documento;
                    document.querySelector('[name="numero_documento"]').value = data.numero_documento;
                    document.querySelector('[name="genero"]').value = data.genero;
                    document.querySelector('[name="fecha_nacimiento"]').value = data.fecha_nacimiento;
                    document.querySelector('[name="celular"]').value = data.celular;
                    document.querySelector('[name="email"]').value = data.email;
                    document.querySelector('[name="direccion"]').value = data.direccion_domicilio;
                    document.querySelector('[name="departamento"]').value = data.departamento;

                    // Ahora son campos de texto, no selects
                    document.querySelector('[name="provincia"]').value = data.provincia || '';
                    document.querySelector('[name="distrito"]').value = data.distrito || '';

                    document.querySelector('[name="perfil"]').value = data.perfil;

                    if (data.foto) {
                        document.getElementById('preview-foto').src = '<?php echo URLROOT; ?>/public/img/personal/' + data.foto;
                        document.getElementById('preview-foto').classList.remove('d-none');
                        document.getElementById('placeholder-foto').classList.add('d-none');
                    } else {
                        document.getElementById('preview-foto').classList.add('d-none');
                        document.getElementById('placeholder-foto').classList.remove('d-none');
                    }

                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                }
            })
            .catch(error => console.error('Error:', error));
    }

    // Función para confirmar eliminación con SweetAlert2
    function confirmarEliminacion(id, nombre) {
        Swal.fire({
            title: '¿Eliminar Personal?',
            html: `<p>Estás a punto de eliminar definitivamente a:</p><p class="fw-bold text-danger">${nombre}</p><p class="text-muted">Esta acción no se puede deshacer.</p>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash-alt"></i> Sí, eliminar',
            cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
            reverseButtons: true,
            focusCancel: true
        }).then((result) => {
            if (result.isConfirmed) {
                // CREAR FORMULARIO POST DINÁMICO PARA SEGURIDAD CSRF
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?php echo URLROOT; ?>/admin/eliminar_personal/' + id;

                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = '<?php echo csrf_token(); ?>';

                form.appendChild(csrfInput);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>
@endsection
