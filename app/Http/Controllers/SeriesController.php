<?php

namespace App\Http\Controllers;

use App\Services\SerieBoletoService;
use Illuminate\Http\Request;

/**
 * Series de boletos (la vista `admin/registrar_serie_boletos` llama a
 * `/series/*`). Puerto de `legacy/app/controllers/Series.php` (Fase 5;
 * reescrito a Eloquent al cerrar la sesion).
 */
class SeriesController extends Controller
{
    public function __construct(private SerieBoletoService $series) {}

    public function index()
    {
        return view('admin.registrar_serie_boletos', ['data' => [
            'title' => 'Registrar Series de Boletos',
            'vendedores' => $this->series->obtenerVendedores(),
            'sedes' => $this->series->obtenerSedes(),
            'lista_series' => $this->series->listarSeries(),
        ]]);
    }

    public function guardar(Request $request)
    {
        $id = $request->input('id', '');
        $vendedorId = $request->input('vendedor_id', '');
        $sedeId = $request->input('sede_id', '');
        $nroSerie = trim($request->input('nro_serie', ''));

        if ($vendedorId === '' || $sedeId === '' || $nroSerie === '') {
            return response("<script>alert('Todos los campos son obligatorios'); window.location.href='".URLROOT."/series';</script>");
        }

        $datos = ['id' => $id, 'usuario_id' => $vendedorId, 'sede_id' => $sedeId, 'numero_serie' => $nroSerie];
        $mensaje = $this->series->registrarSerie($datos) ? 'Operación exitosa' : 'Error al guardar';

        return response("<script>alert(".json_encode($mensaje).") ; window.location.href='".URLROOT."/series';</script>");
    }

    public function cambiarEstado(int $id)
    {
        $serie = $this->series->obtenerSerie($id);
        if ($serie) {
            $this->series->cambiarEstado($id, $serie->estado ? 0 : 1);
        }

        return redirect(URLROOT.'/series');
    }
}
