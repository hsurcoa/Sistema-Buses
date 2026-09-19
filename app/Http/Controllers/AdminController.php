<?php

namespace App\Http\Controllers;

use App\Mail\CredencialesGeneradas;
use App\Models\Terminal;
use App\Services\AsientoService;
use App\Services\AsignacionService;
use App\Services\ConfiguracionService;
use App\Services\PersonalService;
use App\Services\RolPermisoService;
use App\Services\RutaService;
use App\Services\TerminalService;
use App\Services\TipoBusService;
use App\Services\TramoService;
use App\Services\UsuarioAdminService;
use App\Services\VehiculoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Personal, usuarios del sistema, roles/permisos, choferes (asignar_buses),
 * sucursales, rutas/paradas/tarifas, tipos de bus y el mapa de asientos por
 * tipo de bus. Puerto de `legacy/app/controllers/Admin.php` (Fase 5;
 * reescrito a Eloquent al cerrar la sesion — ver informe de fin de sesion).
 *
 * `Admin::registrar_buses()` (solo la vista) se porta aqui; el CRUD de
 * buses (guardar/editar/eliminar/obtener/PDF) vive en `VehiculosController`
 * porque asi lo llaman las vistas hoy (`/vehiculos/*`, no `/admin/*`).
 * `Admin::registrar_serie_boletos()/guardar_serie_boletos()/cambiar_estado_serie()`
 * NO se portan: son codigo muerto (la vista real usa `/series/*`, servido
 * por `SeriesController`).
 */
class AdminController extends Controller
{
    public function __construct(
        private PersonalService $personal,
        private RolPermisoService $rolPermiso,
        private UsuarioAdminService $usuariosAdmin,
        private AsignacionService $asignaciones,
        private TerminalService $terminales,
        private RutaService $rutas,
        private TramoService $tramos,
        private TipoBusService $tiposBus,
        private AsientoService $asientos,
        private VehiculoService $vehiculos,
        private ConfiguracionService $config,
    ) {}

    private function esAdministrador(): bool
    {
        return auth()->user()?->rol?->nombre === 'Administrador';
    }

    // =====================================================================
    // PERSONAL
    // =====================================================================

    public function registrarPersonal()
    {
        return view('admin.personal_form', ['data' => [
            'title' => 'Registrar Personal',
            'personal' => $this->personal->obtenerPersonal(),
            'departamentos' => $this->personal->obtenerDepartamentos(),
            'roles' => $this->rolPermiso->obtenerRoles(),
        ]]);
    }

    public function guardarPersonal(Request $request)
    {
        $foto = '';
        $oldEmail = '';
        if ($request->filled('id')) {
            $usuarioActual = $this->personal->obtenerPersonalPorId((int) $request->input('id'));
            $foto = $usuarioActual->foto;
            $oldEmail = $usuarioActual->email;
        }

        if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
            $archivo = $request->file('foto');
            $extension = strtolower($archivo->getClientOriginalExtension());
            if (! in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                return response('<script>alert("Error de Seguridad: Tipo de archivo no permitido. Solo se aceptan imágenes (JPG, PNG, WEBP)."); window.history.back();</script>');
            }
            $mime = $archivo->getMimeType();
            if (! in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
                return response('<script>alert("Error de Seguridad: El archivo no es una imagen válida o está corrupto."); window.history.back();</script>');
            }
            if ($archivo->getSize() > 5 * 1024 * 1024) {
                return response('<script>alert("Error: La imagen es demasiado grande. Máximo 5MB."); window.history.back();</script>');
            }

            $uploadDir = public_path('img/personal');
            if (! is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $newFileName = time().'_'.bin2hex(random_bytes(8)).'.'.$extension;
            if (! $archivo->move($uploadDir, $newFileName)) {
                return response('<script>alert("Error del Servidor: No se pudo guardar la imagen."); window.history.back();</script>');
            }
            $foto = $newFileName;
        }

        $perfilSeleccionado = $request->input('perfil', '');
        $rolValido = false;
        foreach ($this->rolPermiso->obtenerRoles() as $rol) {
            if ($rol->nombre === $perfilSeleccionado) {
                $rolValido = true;
                break;
            }
        }
        if (! $rolValido) {
            return response('<script>alert("Error: El perfil seleccionado no es válido."); window.history.back();</script>');
        }

        $data = [
            'id' => $request->input('id', ''),
            'nombres' => trim($request->input('nombres')),
            'apellidos' => trim($request->input('apellidos')),
            'tipo_documento' => $request->input('tipo_documento'),
            'numero_documento' => trim($request->input('numero_documento')),
            'genero' => $request->input('genero'),
            'fecha_nacimiento' => $request->input('fecha_nacimiento'),
            'celular' => trim($request->input('celular')),
            'email' => trim($request->input('email')),
            'direccion_domicilio' => trim($request->input('direccion')),
            'departamento' => $request->input('departamento', ''),
            'provincia' => $request->input('provincia', ''),
            'distrito' => $request->input('distrito', ''),
            'perfil' => $perfilSeleccionado,
            'foto' => $foto,
        ];

        $idParaVerificar = $data['id'] !== '' ? (int) $data['id'] : null;
        if ($this->usuariosAdmin->existeEmail($data['email'], $idParaVerificar)) {
            return response('<script>alert("Error: El correo electrónico ya está registrado por otro usuario."); window.location.href="'.URLROOT.'/admin/registrar_personal";</script>');
        }

        try {
            if ($data['id'] !== '') {
                $this->personal->actualizarPersonal($data);

                if ($oldEmail !== '' && $oldEmail !== $data['email']) {
                    $usuario = $data['numero_documento'];
                    $passwordPlana = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 8);
                    $passwordHash = password_hash($passwordPlana, PASSWORD_BCRYPT);
                    $this->usuariosAdmin->actualizarCredenciales($oldEmail, $data['email'], $passwordHash);

                    if (filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                        $this->enviarCredenciales($data['email'], $data['nombres'].' '.$data['apellidos'], $usuario, $passwordPlana);
                    }
                }
            } elseif ($this->personal->agregarPersonal($data)) {
                $usuario = $data['numero_documento'];
                $passwordPlana = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 8);
                $passwordHash = password_hash($passwordPlana, PASSWORD_BCRYPT);

                if ($this->usuariosAdmin->registrarUsuario([
                    'username' => $usuario,
                    'nombres' => $data['nombres'],
                    'apellidos' => $data['apellidos'],
                    'email' => $data['email'],
                    'password_hash' => $passwordHash,
                    'perfil' => $data['perfil'],
                ]) && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $this->enviarCredenciales($data['email'], $data['nombres'].' '.$data['apellidos'], $usuario, $passwordPlana);
                }
            }

            return redirect(URLROOT.'/admin/registrar_personal');
        } catch (\Exception $e) {
            Log::error('AdminController::guardarPersonal: '.$e->getMessage());

            return response('<script>alert("Error del Sistema: No se pudo procesar la solicitud. Por favor intente nuevamente."); window.history.back();</script>');
        }
    }

    private function enviarCredenciales(string $email, string $nombreCompleto, string $usuario, string $passwordPlana): void
    {
        try {
            Mail::to($email)->send(new CredencialesGeneradas($nombreCompleto, $usuario, $passwordPlana));
        } catch (\Throwable $e) {
            Log::error('AdminController::enviarCredenciales: '.$e->getMessage());
        }
    }

    public function eliminarPersonal(int $id)
    {
        $this->personal->eliminarPersonal($id);

        return redirect(URLROOT.'/admin/registrar_personal');
    }

    public function cambiarEstado(int $id)
    {
        $personal = $this->personal->obtenerPersonalPorId($id);
        if ($personal) {
            $this->personal->cambiarEstado($id, $personal->estado ? 0 : 1);
        }

        return redirect(URLROOT.'/admin/registrar_personal');
    }

    public function getPersonal(int $id)
    {
        return response()->json($this->personal->obtenerPersonalPorId($id));
    }

    // =====================================================================
    // USUARIOS DEL SISTEMA
    // =====================================================================

    public function usuarios()
    {
        if (! $this->esAdministrador()) {
            return redirect(URLROOT.'/dashboard');
        }

        return view('admin.usuarios', ['data' => [
            'title' => 'Usuarios del sistema',
            'usuarios' => $this->usuariosAdmin->listarUsuarios(),
            'roles' => $this->usuariosAdmin->listarRolesActivos(),
            'sucursales' => Terminal::orderBy('nombre_sede')->get(),
            'usuario_actual_id' => (int) auth()->id(),
        ]]);
    }

    public function guardarUsuario(Request $request)
    {
        $this->prepararRespuestaUsuarios();

        $id = (int) $request->input('id', 0);
        $d = [
            'id' => $id,
            'nombres' => trim($request->input('nombres', '')),
            'apellidos' => trim($request->input('apellidos', '')),
            'email' => strtolower(trim($request->input('email', ''))),
            'username' => trim($request->input('username', '')),
            'nro_documento' => trim($request->input('nro_documento', '')),
            'celular' => trim($request->input('celular', '')),
            'rol_id' => (int) $request->input('rol_id', 0),
            'sucursal_id' => (int) $request->input('sucursal_id', 0),
            'activo' => $request->boolean('activo'),
        ];
        $password = (string) $request->input('password', '');
        $generar = $request->boolean('generar_password');
        $actual = $id ? $this->usuariosAdmin->obtenerUsuario($id) : null;
        $miId = (int) auth()->id();

        $errores = [];
        if ($id && ! $actual) {
            $this->responderUsuarios(false, 'El usuario no existe.');
        }
        if ($d['nombres'] === '' || $d['apellidos'] === '') {
            $errores[] = 'Nombres y apellidos son obligatorios.';
        }
        if (! filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'Ingrese un correo electrónico válido (se usa para iniciar sesión).';
        } elseif ($this->usuariosAdmin->campoEnUso('email', $d['email'], $id)) {
            $errores[] = 'El correo '.$d['email'].' ya pertenece a otro usuario.';
        }
        if ($d['username'] !== '' && $this->usuariosAdmin->campoEnUso('username', $d['username'], $id)) {
            $errores[] = 'El nombre de usuario '.$d['username'].' ya está en uso.';
        }
        $rol = $this->usuariosAdmin->rolActivo($d['rol_id']);
        if (! $rol) {
            $errores[] = 'Seleccione un rol activo.';
        }
        $sucursal = $d['sucursal_id'] ? Terminal::find($d['sucursal_id']) : null;
        if ($d['sucursal_id'] && (! $sucursal || ! $sucursal->estado)) {
            $errores[] = 'La sucursal seleccionada no existe o está inactiva.';
        }
        if (! $d['sucursal_id'] && $rol && ! in_array($rol->nombre, ['Administrador', 'Supervisor'], true)) {
            $errores[] = 'Asigne la sucursal donde trabaja este usuario (obligatoria para el rol '.$rol->nombre.').';
        }

        if ($generar) {
            $password = $this->generarPassword();
        } elseif ($password !== '' || ! $id) {
            if (strlen($password) < 8) {
                $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
            } elseif ($password !== (string) $request->input('password_confirmacion', '')) {
                $errores[] = 'La confirmación de la contraseña no coincide.';
            }
        }

        if ($id && $id === $miId) {
            if (! $d['activo']) {
                $errores[] = 'No puede desactivar su propia cuenta.';
            }
            if ($rol && $rol->nombre !== 'Administrador') {
                $errores[] = 'No puede quitarse a sí mismo el rol Administrador.';
            }
        }
        if ($id && $actual && $actual->rol === 'Administrador' && $actual->estado === 'activo'
            && (! $d['activo'] || ($rol && $rol->nombre !== 'Administrador'))
            && $this->usuariosAdmin->contarAdministradoresActivos($id) === 0) {
            $errores[] = 'Debe quedar al menos un Administrador activo.';
        }

        if ($errores) {
            $this->responderUsuarios(false, implode(' ', $errores));
        }

        $d['password_hash'] = $password !== '' ? password_hash($password, PASSWORD_BCRYPT) : null;

        try {
            if ($id) {
                $this->usuariosAdmin->actualizarUsuario($d);
            } else {
                $id = $this->usuariosAdmin->crearUsuario($d);
            }
        } catch (\Exception $e) {
            Log::error('AdminController::guardarUsuario: '.$e->getMessage());
            $this->responderUsuarios(false, 'No se pudo guardar el usuario.');
        }

        $this->responderUsuarios(true, $id ? 'Usuario actualizado.' : 'Usuario creado.', [
            'password_generada' => $generar ? $password : null,
            'email' => $d['email'],
        ]);
    }

    public function estadoUsuario(Request $request)
    {
        $this->prepararRespuestaUsuarios();

        $id = (int) $request->input('id', 0);
        $activar = $request->boolean('activo');
        $usuario = $this->usuariosAdmin->obtenerUsuario($id);

        if (! $usuario) {
            $this->responderUsuarios(false, 'El usuario no existe.');
        }
        if (! $activar && $id === (int) auth()->id()) {
            $this->responderUsuarios(false, 'No puede desactivar su propia cuenta.');
        }
        if (! $activar && $usuario->rol === 'Administrador' && $this->usuariosAdmin->contarAdministradoresActivos($id) === 0) {
            $this->responderUsuarios(false, 'Debe quedar al menos un Administrador activo.');
        }

        $this->usuariosAdmin->cambiarEstadoUsuario($id, $activar);
        $this->responderUsuarios(true, $activar ? 'Usuario activado.' : 'Usuario desactivado: ya no puede iniciar sesión.');
    }

    public function eliminarUsuario(Request $request)
    {
        $this->prepararRespuestaUsuarios();

        $id = (int) $request->input('id', 0);
        $usuario = $this->usuariosAdmin->obtenerUsuario($id);

        if (! $usuario) {
            $this->responderUsuarios(false, 'El usuario no existe.');
        }
        if ($id === (int) auth()->id()) {
            $this->responderUsuarios(false, 'No puede eliminar su propia cuenta.');
        }
        if ($usuario->rol === 'Administrador' && $usuario->estado === 'activo' && $this->usuariosAdmin->contarAdministradoresActivos($id) === 0) {
            $this->responderUsuarios(false, 'Debe quedar al menos un Administrador activo.');
        }

        if (! $this->usuariosAdmin->eliminarUsuarioSinHistorial($id)) {
            $this->responderUsuarios(false, 'Este usuario tiene ventas, cajas o encomiendas registradas: no se puede eliminar sin perder el historial. Desactívelo en su lugar.');
        }

        DB::table('spatie_model_has_roles')->where('model_id', $id)->where('model_type', 'App\\Models\\Usuario')->delete();

        $this->responderUsuarios(true, 'Usuario eliminado.');
    }

    private function prepararRespuestaUsuarios(): void
    {
        if (! $this->esAdministrador()) {
            $this->responderUsuarios(false, 'Solo un Administrador puede gestionar usuarios.');
        }
    }

    private function responderUsuarios(bool $ok, string $mensaje, array $extra = []): never
    {
        echo json_encode(array_merge(['status' => $ok ? 'success' : 'error', 'message' => $mensaje], $extra), JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function generarPassword(): string
    {
        $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $password = '';
        for ($i = 0; $i < 10; $i++) {
            $password .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        }

        return $password;
    }

    // =====================================================================
    // ROLES Y PERMISOS
    // =====================================================================

    public function rolesPermisos()
    {
        return view('admin.roles_permisos', ['data' => [
            'title' => 'Roles y Permisos',
            'roles' => $this->rolPermiso->obtenerRoles(),
            'permisosAgrupados' => $this->rolPermiso->obtenerPermisosAgrupados(),
        ]]);
    }

    public function getPermisosRol(int $rol_id)
    {
        return response()->json([
            'success' => true,
            'permisos' => $this->rolPermiso->obtenerPermisosPorRol($rol_id),
        ]);
    }

    public function guardarPermisos(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $rolId = $data['rol_id'] ?? null;
        $permisos = $data['permisos'] ?? [];

        if (! $rolId) {
            return response()->json(['success' => false, 'message' => 'Rol no especificado']);
        }

        $resultado = $this->rolPermiso->actualizarPermisosRol((int) $rolId, $permisos);

        return response()->json(['success' => $resultado, 'message' => $resultado ? 'Permisos actualizados correctamente' : 'Error al actualizar permisos']);
    }

    public function crearRol(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $nombre = trim($data['nombre'] ?? '');
        $descripcion = trim($data['descripcion'] ?? '');

        if ($nombre === '') {
            return response()->json(['success' => false, 'message' => 'El nombre del rol es obligatorio']);
        }

        $rolId = $this->rolPermiso->crearRol(['nombre' => $nombre, 'descripcion' => $descripcion, 'activo' => 1]);

        return $rolId
            ? response()->json(['success' => true, 'message' => 'Rol creado correctamente', 'rol_id' => $rolId])
            : response()->json(['success' => false, 'message' => 'Error al crear el rol']);
    }

    // =====================================================================
    // BUSES (solo la vista; CRUD en VehiculosController) Y CHOFERES
    // =====================================================================

    public function registrarBuses()
    {
        $alertasFlotaActivo = ! empty($this->config->obtenerConfiguracion()['alertas_flota_documentos_activo']);

        return view('admin.registrar_buses', ['data' => [
            'title' => 'Registrar Buses',
            'vehiculos' => $this->vehiculos->listarVehiculos(),
            'tipos_buses' => $this->tiposBus->listarTiposParaFlota(),
            'alertas_flota_activo' => $alertasFlotaActivo,
            'alertas_documentos' => $alertasFlotaActivo ? $this->vehiculos->alertasDocumentos() : ['criticos' => [], 'avisos' => []],
        ]]);
    }

    public function asignarBuses()
    {
        $asignacionesActivas = [];
        foreach ($this->asignaciones->listarAsignaciones(false) as $a) {
            $asignacionesActivas[(int) $a->bus_id] = $a;
        }

        $buses = $this->asignaciones->obtenerBuses();
        foreach ($buses as $bus) {
            $a = $asignacionesActivas[(int) $bus->id] ?? null;
            $bus->asignacion_id = $a->id ?? null;
            $bus->chofer_id = $a->chofer_id ?? null;
            $bus->copiloto_id = $a->copiloto_id ?? null;
            $bus->nombre_copiloto = $a->nombre_copiloto ?? null;
        }

        return view('admin.asignar_buses', ['data' => [
            'title' => 'Choferes',
            'choferes' => $this->asignaciones->obtenerChoferes(),
            'copilotos' => $this->asignaciones->obtenerCopilotos(),
            'buses' => $buses,
            'es_admin' => $this->esAdministrador(),
        ]]);
    }

    public function guardarChoferRapido(Request $request)
    {
        if (! $this->esAdministrador()) {
            return response()->json(['status' => 'error', 'message' => 'Solo un Administrador puede registrar personal.']);
        }

        $entrada = json_decode($request->getContent(), true) ?: [];

        $perfil = ($entrada['perfil'] ?? '') === 'Copiloto' ? 'Copiloto' : 'Chofer';
        $nombres = trim((string) ($entrada['nombres'] ?? ''));
        $apellidos = trim((string) ($entrada['apellidos'] ?? ''));
        $documento = trim((string) ($entrada['numero_documento'] ?? ''));
        $celular = trim((string) ($entrada['celular'] ?? ''));

        if ($nombres === '' || $apellidos === '') {
            return response()->json(['status' => 'error', 'message' => 'Nombre y apellido son obligatorios.']);
        }

        try {
            $nuevoId = $this->personal->agregarPersonal([
                'nombres' => mb_substr($nombres, 0, 100),
                'apellidos' => mb_substr($apellidos, 0, 100),
                'tipo_documento' => $documento !== '' ? 'CI' : '',
                'numero_documento' => mb_substr($documento, 0, 20),
                'genero' => '',
                'fecha_nacimiento' => null,
                'celular' => mb_substr($celular, 0, 20),
                'email' => '',
                'direccion_domicilio' => '',
                'departamento' => '',
                'provincia' => '',
                'distrito' => '',
                'perfil' => $perfil,
                'foto' => '',
            ]);
            if (! $nuevoId) {
                throw new \Exception('No se pudo guardar.');
            }

            return response()->json([
                'status' => 'success',
                'message' => ($perfil === 'Chofer' ? 'Chofer' : 'Chofer de relevo').' registrado.',
                'persona' => [
                    'id' => $nuevoId,
                    'nombres' => $nombres,
                    'apellidos' => $apellidos,
                    'numero_documento' => $documento,
                    'perfil' => $perfil,
                ],
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            Log::error('AdminController::guardarChoferRapido: '.$e->getMessage());

            return response()->json(['status' => 'error', 'message' => 'No se pudo registrar. Intente nuevamente.']);
        }
    }

    public function guardarAsignacion(Request $request)
    {
        $datos = [
            'id' => trim($request->input('id', '')),
            'chofer_id' => trim($request->input('chofer_id', '')),
            'bus_id' => trim($request->input('bus_id', '')),
            'copiloto_id' => trim($request->input('copiloto_id', '')),
        ];
        $reemplazar = $request->boolean('reemplazar');

        if ($datos['chofer_id'] === '' || $datos['bus_id'] === '') {
            return response()->json(['status' => 'error', 'message' => 'El chofer y el bus son obligatorios.']);
        }

        try {
            $validacion = $this->asignaciones->validar($datos);
            if ($validacion['errores']) {
                return response()->json(['status' => 'error', 'message' => implode(' ', $validacion['errores'])]);
            }

            $choques = array_filter($validacion['conflictos'], fn ($c) => ! str_starts_with($c, 'Aviso:'));
            if ($choques && ! $reemplazar) {
                return response()->json([
                    'status' => 'conflict',
                    'message' => 'Hay asignaciones activas que chocan con esta.',
                    'conflictos' => $validacion['conflictos'],
                ], 200, [], JSON_UNESCAPED_UNICODE);
            }

            $this->asignaciones->guardar($datos, $reemplazar);

            $avisos = array_values(array_filter($validacion['conflictos'], fn ($c) => str_starts_with($c, 'Aviso:')));

            return response()->json([
                'status' => 'success',
                'message' => $datos['id'] ? 'Asignación actualizada.' : 'Asignación registrada.',
                'avisos' => $avisos,
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            Log::error('AdminController::guardarAsignacion: '.$e->getMessage());

            return response()->json(['status' => 'error', 'message' => 'Ocurrió un error interno al guardar la asignación.']);
        }
    }

    public function eliminarAsignacion(int $id)
    {
        $this->asignaciones->finalizarAsignacion($id);

        return redirect(URLROOT.'/admin/asignar_buses');
    }

    public function obtenerAsignacion(Request $request)
    {
        $asignacion = $this->asignaciones->obtenerAsignacionPorId(trim($request->input('id', '')));

        if (! $asignacion) {
            return response()->json(['status' => 'error', 'message' => 'No se encontró la asignación.']);
        }

        return response()->json([
            'status' => 'success',
            'id_chofer' => $asignacion->chofer_id,
            'id_bus' => $asignacion->bus_id,
            'id_copiloto' => $asignacion->copiloto_id,
        ]);
    }

    public function editarAsignacion(Request $request)
    {
        return $this->guardarAsignacion($request);
    }

    // =====================================================================
    // SUCURSALES (Terminales)
    // =====================================================================

    public function registrarTerminal()
    {
        return view('admin.registrar_terminal', ['data' => [
            'title' => 'Sucursales',
            'terminales' => $this->terminales->listarSucursales(),
            'es_admin' => $this->esAdministrador(),
        ]]);
    }

    public function guardarTerminal(Request $request)
    {
        $volver = function (string $msg, string $detalle = '') {
            return redirect(URLROOT.'/admin/registrar_terminal?msg='.$msg.($detalle !== '' ? '&detalle='.urlencode($detalle) : ''));
        };

        if (! $this->esAdministrador()) {
            return $volver('error', 'Solo un Administrador puede crear o modificar sucursales.');
        }

        $d = [
            'id' => (int) $request->input('id', 0),
            'nombre_sede' => trim($request->input('nombre_sede', '')),
            'direccion' => trim($request->input('direccion', '')),
            'numero_oficina' => trim($request->input('numero_oficina', '')),
            'telefono' => trim($request->input('telefono', '')),
            'prefijo_boleto' => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $request->input('prefijo_boleto', ''))),
            'pago_qr_titular' => trim($request->input('pago_qr_titular', '')),
            'pago_qr_entidad' => trim($request->input('pago_qr_entidad', '')),
        ];

        if ($d['nombre_sede'] === '' || $d['direccion'] === '') {
            return $volver('error', 'El nombre y la dirección de la sucursal son obligatorios.');
        }
        if (! preg_match('/^[A-Z0-9]{2,5}$/', $d['prefijo_boleto'])) {
            return $volver('error', 'El prefijo de boleto debe tener de 2 a 5 letras o números (p. ej. EAL).');
        }
        if ($this->terminales->prefijoEnUso($d['prefijo_boleto'], $d['id'])) {
            return $volver('error', 'El prefijo '.$d['prefijo_boleto'].' ya lo usa otra sucursal.');
        }

        if ($request->boolean('quitar_qr')) {
            $d['pago_qr_imagen'] = null;
        }
        if ($request->hasFile('pago_qr_imagen')) {
            $archivo = $request->file('pago_qr_imagen');
            $info = $archivo->isValid() ? @getimagesize($archivo->getRealPath()) : false;
            $tipos = [IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_WEBP => 'webp'];
            if (! $archivo->isValid() || ! $info || ! isset($tipos[$info[2]])) {
                return $volver('error', 'El QR debe ser una imagen PNG, JPG o WEBP.');
            }
            if ($archivo->getSize() > 2 * 1024 * 1024) {
                return $volver('error', 'La imagen del QR debe pesar como máximo 2 MB.');
            }
            $destinoDir = public_path('uploads/pagos');
            if (! is_dir($destinoDir)) {
                mkdir($destinoDir, 0755, true);
            }
            $nombreArchivo = 'qr_sucursal_'.date('Ymd_His').'_'.bin2hex(random_bytes(4)).'.'.$tipos[$info[2]];
            if (! $archivo->move($destinoDir, $nombreArchivo)) {
                return $volver('error', 'No se pudo guardar la imagen del QR.');
            }
            $d['pago_qr_imagen'] = 'uploads/pagos/'.$nombreArchivo;
        }

        try {
            $this->terminales->guardarSucursal($d);
        } catch (\Exception $e) {
            Log::error('AdminController::guardarTerminal: '.$e->getMessage());

            return $volver('error', 'No se pudo guardar la sucursal.');
        }

        return $volver($d['id'] ? 'actualizada' : 'creada');
    }

    public function cambiarEstadoTerminal(int $id)
    {
        if ($this->esAdministrador()) {
            $terminal = $this->terminales->obtenerTerminal($id);
            if ($terminal) {
                $this->terminales->cambiarEstado($id, $terminal->estado ? 0 : 1);
            }
        }

        return redirect(URLROOT.'/admin/registrar_terminal');
    }

    public function eliminarTerminal(int $id)
    {
        if (! $this->esAdministrador()) {
            return redirect(URLROOT.'/admin/registrar_terminal?msg=error&detalle='.urlencode('Solo un Administrador puede eliminar sucursales.'));
        }

        return $this->terminales->eliminarSucursalSinHistorial($id)
            ? redirect(URLROOT.'/admin/registrar_terminal?msg=eliminada')
            : redirect(URLROOT.'/admin/registrar_terminal?msg=error&detalle='.urlencode('La sucursal tiene usuarios, viajes, ventas o encomiendas: desactívela en lugar de eliminarla.'));
    }

    // =====================================================================
    // RUTAS, PARADAS Y TARIFAS
    // =====================================================================

    public function rutasParadas(Request $request)
    {
        $rutas = DB::table('rutas as r')
            ->select('r.*',
                DB::raw('(SELECT COUNT(*) FROM rutas_paradas p WHERE p.ruta_id = r.id AND p.estado = 1) AS paradas'),
                DB::raw('(SELECT COUNT(*) FROM viajes v WHERE v.ruta_id = r.id) AS viajes'),
                DB::raw('(SELECT COUNT(*) FROM tarifas_tramo t WHERE t.ruta_id = r.id AND t.precio_sugerido = 1) AS tarifas_sugeridas'))
            ->orderByDesc('r.estado')->orderBy('r.origen')->orderBy('r.destino')
            ->get();

        $rutaId = (int) $request->query('ruta', $rutas[0]->id ?? 0);
        $ruta = null;
        foreach ($rutas as $r) {
            if ((int) $r->id === $rutaId) {
                $ruta = $r;
            }
        }

        $paradasEncomienda = [];
        if ($ruta) {
            foreach (DB::table('rutas_paradas')->where('ruta_id', $ruta->id)->where('estado', 1)->select('id', 'precio_base_encomienda')->get() as $p) {
                $paradasEncomienda[(int) $p->id] = (float) $p->precio_base_encomienda;
            }
        }

        return view('admin.registrar_rutas_paradas', ['data' => [
            'title' => 'Rutas y tarifas',
            'rutas' => $rutas,
            'ruta' => $ruta,
            'puntos' => $ruta ? $this->tramos->puntos($ruta->id) : [],
            'tarifas' => $ruta ? $this->tramos->matriz($ruta->id) : [],
            'precio_encomienda' => $paradasEncomienda,
            'es_admin' => $this->esAdministrador(),
        ]]);
    }

    private function entradaJsonRutas(Request $request): array
    {
        $entrada = json_decode($request->getContent(), true) ?: [];
        if (! $this->esAdministrador()) {
            $this->responderRutas(false, 'Solo un Administrador puede modificar rutas y tarifas.');
        }

        return $entrada;
    }

    private function responderRutas(bool $ok, string $mensaje): never
    {
        echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $mensaje], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function guardarParadas(Request $request)
    {
        $entrada = $this->entradaJsonRutas($request);
        $rutaId = (int) ($entrada['ruta_id'] ?? 0);

        $paradas = [];
        $nombres = [];
        foreach ($entrada['paradas'] ?? [] as $p) {
            $nombre = trim(preg_replace('/\s+/', ' ', (string) ($p['nombre'] ?? '')));
            if ($nombre === '') {
                $this->responderRutas(false, 'Todas las paradas deben tener nombre.');
            }
            $clave = mb_strtolower($nombre);
            if (isset($nombres[$clave])) {
                $this->responderRutas(false, 'La parada "'.$nombre.'" está repetida.');
            }
            $nombres[$clave] = true;
            $paradas[] = [
                'id' => (int) ($p['id'] ?? 0),
                'nombre' => mb_substr($nombre, 0, 100),
                'precio_encomienda' => max(0, round((float) ($p['precio_encomienda'] ?? 0), 2)),
            ];
        }

        try {
            $this->tramos->guardarParadas($rutaId, $paradas);
        } catch (\Exception $e) {
            Log::error('AdminController::guardarParadas: '.$e->getMessage());
            $this->responderRutas(false, 'No se pudieron guardar las paradas.');
        }
        $this->responderRutas(true, 'Paradas guardadas. Revise las tarifas de los tramos nuevos.');
    }

    public function guardarTarifas(Request $request)
    {
        $entrada = $this->entradaJsonRutas($request);
        $rutaId = (int) ($entrada['ruta_id'] ?? 0);

        $puntos = $this->tramos->puntos($rutaId);
        if (! $puntos) {
            $this->responderRutas(false, 'La ruta no existe.');
        }

        $validos = [];
        foreach ($puntos as $i => $desde) {
            if ($desde['tipo'] === 'destino') {
                continue;
            }
            foreach (array_slice($puntos, $i + 1) as $hasta) {
                $validos[$desde['id'].'-'.($hasta['tipo'] === 'destino' ? 0 : $hasta['id'])] = true;
            }
        }

        $tarifas = [];
        foreach ($entrada['tarifas'] ?? [] as $t) {
            $clave = (int) ($t['desde'] ?? -1).'-'.(int) ($t['hasta'] ?? -1);
            $precio = round((float) ($t['precio'] ?? 0), 2);
            if (! isset($validos[$clave])) {
                continue;
            }
            if ($precio <= 0) {
                $this->responderRutas(false, 'Todas las tarifas deben ser mayores a 0.');
            }
            $tarifas[] = ['desde' => (int) $t['desde'], 'hasta' => (int) $t['hasta'], 'precio' => $precio];
        }
        if (count($tarifas) !== count($validos)) {
            $this->responderRutas(false, 'Complete la tarifa de todos los tramos ('.count($validos).').');
        }

        try {
            $this->tramos->guardarTarifas($rutaId, $tarifas);
        } catch (\Exception $e) {
            Log::error('AdminController::guardarTarifas: '.$e->getMessage());
            $this->responderRutas(false, 'No se pudieron guardar las tarifas.');
        }
        $this->responderRutas(true, 'Tarifas guardadas.');
    }

    public function guardarRuta(Request $request)
    {
        if (! $this->esAdministrador()) {
            return redirect(URLROOT.'/admin/rutas_paradas');
        }

        $id = $request->input('id', '');
        $origen = strtoupper(trim($request->input('origen', '')));
        $destino = strtoupper(trim($request->input('destino', '')));

        if ($origen === '' || $destino === '') {
            return response("<script>alert('Complete todos los campos obligatorios'); window.location.href='".URLROOT."/admin/rutas_paradas';</script>");
        }

        $resultado = $id !== ''
            ? $this->rutas->actualizarRuta((int) $id, $origen, $destino, 1)
            : $this->rutas->registrarRuta($origen, $destino);

        $mensaje = $resultado === true
            ? ($id !== '' ? 'Ruta actualizada correctamente' : 'Ruta registrada correctamente')
            : $resultado;

        return response("<script>alert(".json_encode($mensaje).") ; window.location.href='".URLROOT."/admin/rutas_paradas';</script>");
    }

    public function cambiarEstadoRuta(int $id)
    {
        $ruta = $this->rutas->obtenerRutaPorId($id);
        if ($ruta) {
            $this->rutas->cambiarEstado($id, $ruta->estado ? 0 : 1);
        }

        return redirect(URLROOT.'/admin/rutas_paradas');
    }

    public function eliminarRuta(int $id)
    {
        $resultado = $this->rutas->eliminarRuta($id);
        $mensaje = $resultado === true ? 'Ruta eliminada correctamente' : $resultado;

        return response("<script>alert(".json_encode($mensaje).") ; window.location.href='".URLROOT."/admin/rutas_paradas';</script>");
    }

    // =====================================================================
    // TIPOS DE BUSES
    // =====================================================================

    public function tiposBuses()
    {
        return view('admin.tipos_buses', ['data' => [
            'title' => 'Tipos de Buses',
            'tiposBuses' => $this->tiposBus->listarTiposBuses(),
        ]]);
    }

    public function guardarTipoBus(Request $request)
    {
        $data = [
            'id' => $request->input('id', ''),
            'nombre' => trim($request->input('nombre', '')),
            'capacidad' => (int) $request->input('capacidad', 0),
            'pisos' => (int) $request->input('pisos', 1),
            'configuracion_asientos' => $request->input('configuracion_asientos', ''),
        ];

        if ($data['nombre'] === '' || ! $data['capacidad']) {
            return response("<script>alert('Complete todos los campos obligatorios'); window.location.href='".URLROOT."/admin/tipos_buses';</script>");
        }

        $ok = $data['id'] !== '' ? $this->tiposBus->actualizarTipoBus($data) : $this->tiposBus->agregarTipoBus($data);
        $msg = $ok
            ? ($data['id'] !== '' ? 'Tipo de bus actualizado' : 'Tipo de bus registrado')
            : ($data['id'] !== '' ? 'Error al actualizar' : 'Error al registrar');

        return response("<script>alert(".json_encode($msg).") ; window.location.href='".URLROOT."/admin/tipos_buses';</script>");
    }

    public function cambiarEstadoTipoBus(int $id)
    {
        $tipoBus = $this->tiposBus->obtenerTipoBus($id);
        if ($tipoBus) {
            $this->tiposBus->cambiarEstado($id, $tipoBus->estado ? 0 : 1);
        }

        return redirect(URLROOT.'/admin/tipos_buses');
    }

    public function eliminarTipoBus(Request $request)
    {
        $id = trim($request->input('id', ''));
        if ($id === '') {
            return response()->json(['status' => 'error', 'message' => 'No se proporcionó el ID del tipo de bus.']);
        }

        try {
            return $this->tiposBus->eliminarTipoBus((int) $id)
                ? response()->json(['status' => 'success', 'message' => 'El tipo de bus ha sido eliminado correctamente del sistema.'])
                : response()->json(['status' => 'error', 'message' => 'No se pudo eliminar el tipo de bus. Por favor, intente nuevamente.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Error al eliminar el tipo de bus: '.$e->getMessage()]);
        }
    }

    // =====================================================================
    // MAPA DE ASIENTOS POR TIPO DE BUS
    // =====================================================================

    public function mapaAsientos(?int $tipoBusId = null)
    {
        if (! $tipoBusId) {
            return redirect(URLROOT.'/admin/tipos_buses');
        }

        $tipoBus = $this->tiposBus->obtenerTipoBus($tipoBusId);
        if (! $tipoBus) {
            return response("<script>alert('Tipo de bus no encontrado'); window.location.href='".URLROOT."/admin/tipos_buses';</script>");
        }

        return view('admin.mapa_asientos', ['data' => [
            'title' => 'Mapa de Asientos - '.$tipoBus->nombre,
            'busData' => [
                'id' => $tipoBus->id,
                'nombre' => $tipoBus->nombre,
                'capacidad' => $tipoBus->capacidad,
                'pisos' => $tipoBus->pisos,
                'configuracion_asientos' => $tipoBus->configuracion_asientos,
            ],
            'asientos' => $this->asientos->obtenerAsientosPorBus($tipoBusId),
        ]]);
    }

    public function obtenerAsientosDisponibles(Request $request)
    {
        $rutaId = (int) $request->query('ruta_id', 0);
        $fecha = $request->query('fecha', '');
        $busId = (int) $request->query('bus_id', 0);

        if (! $rutaId || ! $fecha || ! $busId) {
            return response()->json(['success' => false, 'message' => 'Parámetros faltantes']);
        }

        try {
            return response()->json(['success' => true, 'asientos' => $this->asientos->obtenerAsientosDisponibles($rutaId, $fecha, $busId)]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al obtener asientos: '.$e->getMessage()]);
        }
    }

    public function reservarAsientos(Request $request)
    {
        $input = json_decode($request->getContent(), true);
        $asientos = $input['asientos'] ?? [];
        $rutaId = (int) ($input['ruta_id'] ?? 0);
        $fecha = $input['fecha'] ?? '';
        $usuarioId = auth()->id() ?? 0;

        if (! $asientos || ! $rutaId || ! $fecha || ! $usuarioId) {
            return response()->json(['success' => false, 'message' => 'Datos incompletos']);
        }

        $asientoIds = array_map(fn ($a) => $a['numero'] ?? $a['id'] ?? 0, $asientos);

        try {
            $resultado = $this->asientos->reservarAsientos($asientoIds, $usuarioId, $rutaId, $fecha);

            return $resultado
                ? response()->json(['success' => true, 'message' => 'Asientos reservados exitosamente', 'expira_en' => 900])
                : response()->json(['success' => false, 'message' => 'Error al reservar asientos. Algunos pueden estar ocupados.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: '.$e->getMessage()]);
        }
    }

    public function generarAsientos(?int $tipoBusId = null)
    {
        if (! $tipoBusId) {
            return response()->json(['success' => false, 'message' => 'ID de tipo de bus no especificado']);
        }

        $tipoBus = $this->tiposBus->obtenerTipoBus($tipoBusId);
        if (! $tipoBus) {
            return response()->json(['success' => false, 'message' => 'Tipo de bus no encontrado']);
        }

        $configuracion = $this->asientos->generarConfiguracionDefecto($tipoBus->capacidad, $tipoBus->pisos);
        $resultado = $this->asientos->crearAsientosPorTipo($tipoBusId, $configuracion);

        return $resultado
            ? response()->json(['success' => true, 'message' => 'Asientos generados correctamente', 'total' => count($configuracion)])
            : response()->json(['success' => false, 'message' => 'Error al generar asientos']);
    }

    public function limpiarReservasExpiradas()
    {
        $resultado = $this->asientos->limpiarReservasExpiradas();

        return response()->json(['success' => $resultado, 'message' => $resultado ? 'Reservas limpiadas' : 'Error al limpiar reservas']);
    }

    public function estadisticasAsientos(int $tipoBusId)
    {
        return response()->json(['success' => true, 'estadisticas' => $this->asientos->obtenerEstadisticasAsientos($tipoBusId)]);
    }

    // =====================================================================
    // PARADAS Y ENCOMIENDAS (endpoints JSON compartidos con Ventas/Encomiendas)
    // =====================================================================

    public function obtenerInfoRutaJson(int $rutaId)
    {
        if (! $rutaId) {
            return response()->json(['status' => 'error', 'message' => 'ID de ruta no especificado']);
        }

        $paradas = $this->rutas->obtenerParadasPorRuta($rutaId);

        return response()->json([
            'status' => 'success',
            'ruta_id' => $rutaId,
            'paradas' => array_map(fn ($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre_parada,
                'precio_pasaje' => $p->precio_pasaje,
                'precio_encomienda' => $p->precio_base_encomienda,
                'label_pasajero' => $p->nombre_parada.' - Bs '.number_format($p->precio_pasaje, 2),
                'label_encomienda' => $p->nombre_parada.' - Base Bs '.number_format($p->precio_base_encomienda, 2),
            ], is_array($paradas) ? $paradas : $paradas->all()),
            'catalogos' => ['paquetes' => $this->rutas->obtenerTiposEncomienda()],
        ]);
    }

    /** Arancel de encomiendas: catálogo de tipos de paquete y su precio (base + cargo por kg excedente). */
    public function tiposEncomienda()
    {
        return view('admin.tipos_encomienda', ['data' => [
            'title' => 'Arancel de Encomiendas',
            'tipos' => $this->rutas->obtenerTodosTiposEncomienda(),
            'es_admin' => $this->esAdministrador(),
        ]]);
    }

    public function guardarTipoEncomienda(Request $request)
    {
        $volver = fn (string $msg, string $detalle = '') => redirect(URLROOT.'/admin/tipos_encomienda?msg='.$msg.($detalle !== '' ? '&detalle='.urlencode($detalle) : ''));

        if (! $this->esAdministrador()) {
            return $volver('error', 'Solo un Administrador puede cambiar el arancel de encomiendas.');
        }

        $resultado = $this->rutas->guardarTipoEncomienda([
            'id' => (int) $request->input('id', 0),
            'nombre' => trim($request->input('nombre', '')),
            'descripcion' => trim($request->input('descripcion', '')) ?: null,
            'precio_extra' => (float) $request->input('precio_extra', 0),
            'peso_incluido_kg' => $request->filled('peso_incluido_kg') ? (float) $request->input('peso_incluido_kg') : null,
            'precio_por_kg_excedente' => (float) $request->input('precio_por_kg_excedente', 0),
        ]);

        return $resultado['status'] ? $volver('guardado') : $volver('error', $resultado['message']);
    }

    public function cambiarEstadoTipoEncomienda(int $id)
    {
        if ($this->esAdministrador()) {
            $this->rutas->cambiarEstadoTipoEncomienda($id);
        }

        return redirect(URLROOT.'/admin/tipos_encomienda');
    }

    public function eliminarTipoEncomienda(int $id)
    {
        if (! $this->esAdministrador()) {
            return redirect(URLROOT.'/admin/tipos_encomienda?msg=error&detalle='.urlencode('Solo un Administrador puede eliminar tipos del arancel.'));
        }

        $resultado = $this->rutas->eliminarTipoEncomienda($id);

        return $resultado['status']
            ? redirect(URLROOT.'/admin/tipos_encomienda?msg=eliminado')
            : redirect(URLROOT.'/admin/tipos_encomienda?msg=error&detalle='.urlencode($resultado['message']));
    }

    /** Para el aviso en "Editar Viaje" (pestaña Servicios y Precio): si ya hay tramo completo cargado, el Precio Base del viaje no se usa. */
    public function rutaTieneTarifaCompleta(int $rutaId)
    {
        $precio = $this->tramos->tieneTarifaCompleta($rutaId);

        return response()->json(['tiene' => $precio !== null, 'precio' => $precio]);
    }

    public function cotizarEnvio(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $paradaId = $data['parada_id'] ?? null;
        $tipo = $data['tipo'] ?? 'pasajero';
        $paqueteId = $data['paquete_id'] ?? null;
        $peso = (float) ($data['peso'] ?? 0);

        if (! $paradaId) {
            return response()->json(['status' => false, 'message' => 'Faltan datos']);
        }

        return response()->json($this->rutas->calcularPrecioDinamico((int) $paradaId, $tipo, $paqueteId ? (int) $paqueteId : null, $peso));
    }
}
