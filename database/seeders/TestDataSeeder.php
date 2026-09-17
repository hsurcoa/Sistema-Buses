<?php

namespace Database\Seeders;

use App\Models\AsignacionBus;
use App\Models\Cliente;
use App\Models\EncomiendaTipo;
use App\Models\Personal;
use App\Models\Ruta;
use App\Models\SerieBoleto;
use App\Models\Terminal;
use App\Models\TipoBus;
use App\Models\Vehiculo;
use App\Models\Viaje;
use App\Services\EncomiendaService;
use App\Services\RutaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

/**
 * Datos de prueba end-to-end (5 registros por modulo): completa lo que
 * PandoSeeder deja en 0 -viajes, boletos vendidos, encomiendas- reusando
 * los servicios reales (RutaService::registrarVentaTransaccion,
 * EncomiendaService::registrarEncomienda) para que las ventas respeten
 * las mismas validaciones de negocio que una venta real desde la UI
 * (asiento dentro de la capacidad del bus, caja abierta, etc.).
 *
 * Requiere haber corrido PandoSeeder antes (rutas, vehiculos, personal,
 * clientes, terminales). Idempotente: se puede correr varias veces.
 */
class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $vehiculos = $this->asegurarVehiculos();
        $choferes = Personal::where('estado', 1)->orderBy('id')->limit(5)->get();
        $rutas = Ruta::where('estado', 1)->orderBy('id')->limit(5)->get();
        $terminales = Terminal::where('estado', 1)->get();
        $clientes = Cliente::orderBy('id')->limit(5)->get();

        if ($choferes->isEmpty() || $rutas->isEmpty() || $clientes->isEmpty()) {
            $this->command?->error('Faltan datos base (choferes/rutas/clientes). Corré primero: php artisan db:seed-pando');

            return;
        }

        $this->asignarBuses($vehiculos, $choferes);
        $viajes = $this->crearViajes($rutas, $vehiculos, $choferes, $terminales);
        $this->venderBoletos($viajes, $clientes);
        $this->asegurarSerieBoletos();
        $this->asegurarTiposEncomienda();
        $this->crearEncomiendas($viajes);
    }

    /** Completa la flota a 5 vehiculos (PandoSeeder ya deja 3). */
    private function asegurarVehiculos()
    {
        $tipoMinibus = TipoBus::where('nombre', 'Minibus')->first();
        $tipoDoble = TipoBus::where('nombre', 'Bus Dos Pisos')->first();

        $extra = [
            ['placa' => '3456-PND', 'tipo_bus_id' => $tipoDoble?->id, 'clase' => 'Bus Leito', 'marca' => 'Mercedes-Benz', 'modelo' => 'O500', 'anio' => '2021', 'asientos' => 60, 'estado' => 1, 'tarjeta_circulacion' => 'TC-1004'],
            ['placa' => '7890-PND', 'tipo_bus_id' => $tipoMinibus?->id, 'clase' => 'Minibus', 'marca' => 'Toyota', 'modelo' => 'Coaster', 'anio' => '2018', 'asientos' => 14, 'estado' => 1, 'tarjeta_circulacion' => 'TC-1005'],
        ];
        foreach ($extra as $v) {
            if (! Vehiculo::where('placa', $v['placa'])->exists()) {
                Vehiculo::create($v);
            }
        }

        return Vehiculo::where('estado', 1)->orderBy('id')->limit(5)->get();
    }

    /** Asigna un chofer a cada bus (Choferes, modulo Registros). */
    private function asignarBuses($vehiculos, $choferes): void
    {
        foreach ($vehiculos as $i => $bus) {
            if (AsignacionBus::where('bus_id', $bus->id)->where('estado', 1)->exists()) {
                continue;
            }
            AsignacionBus::create([
                'bus_id' => $bus->id,
                'chofer_id' => $choferes[$i % $choferes->count()]->id,
                'estado' => 1,
                'fecha_asignacion' => now(),
            ]);
        }
    }

    /** Un viaje programado por ruta, saliendo los proximos dias (visibles en Dashboard y Crear Rutas). */
    private function crearViajes($rutas, $vehiculos, $choferes, $terminales): array
    {
        $viajes = [];
        foreach ($rutas as $i => $ruta) {
            $fechaSalida = now()->addDays($i + 1)->setTime(8 + $i, 0);

            $viaje = Viaje::where('ruta_id', $ruta->id)->whereDate('fecha_salida', $fechaSalida->toDateString())->first();
            if (! $viaje) {
                $bus = $vehiculos[$i % $vehiculos->count()];
                $viaje = Viaje::create([
                    'ruta_id' => $ruta->id,
                    'tipo_bus_id' => $bus->tipo_bus_id,
                    'bus_id' => $bus->id,
                    'chofer_id' => $choferes[$i % $choferes->count()]->id,
                    'terminal_origen_id' => $terminales->firstWhere('nombre_sede', $ruta->origen)?->id,
                    'terminal_destino_id' => $terminales->firstWhere('nombre_sede', $ruta->destino)?->id,
                    'fecha_salida' => $fechaSalida,
                    'hora_salida' => $fechaSalida->format('H:i:s'),
                    'precio_base' => 50 + $i * 10,
                    'tipo_servicio' => 'Ejecutivo',
                    'estado' => 'Programado',
                ]);
            }
            $viajes[] = $viaje;
        }

        return $viajes;
    }

    /** Vende un boleto por viaje via el flujo real de venta (mismas validaciones que la UI). */
    private function venderBoletos(array $viajes, $clientes): void
    {
        $rutaService = app(RutaService::class);
        foreach ($viajes as $i => $viaje) {
            if (\App\Models\Boleto::where('viaje_id', $viaje->id)->where('estado', 'vendido')->exists()) {
                continue;
            }
            $cliente = $clientes[$i % $clientes->count()];
            try {
                $rutaService->registrarVentaTransaccion([
                    'viaje_id' => $viaje->id,
                    'asiento' => $i + 1,
                    'documento' => $cliente->numero_documento,
                    'nombres' => $cliente->nombres,
                    'apellidos' => $cliente->apellidos,
                    'celular' => $cliente->celular,
                    'usuario_id' => 1,
                    'estado' => 'vendido',
                    'metodo_pago' => $i % 2 === 0 ? 'EFECTIVO' : 'QR',
                    'parada_subida_id' => 0,
                    'parada_id' => 0,
                ]);
            } catch (\Exception $e) {
                Log::warning('TestDataSeeder: no se pudo vender boleto de prueba: '.$e->getMessage());
            }
        }
    }

    private function asegurarSerieBoletos(): void
    {
        if (! SerieBoleto::where('usuario_id', 1)->exists()) {
            SerieBoleto::create(['usuario_id' => 1, 'sede_id' => 1, 'numero_serie' => 'A-00001', 'estado' => true]);
        }
    }

    private function asegurarTiposEncomienda(): void
    {
        $tipos = [
            ['nombre' => 'Documentos', 'precio_extra' => 0, 'descripcion' => 'Sobres y documentos', 'estado' => 1],
            ['nombre' => 'Caja pequeña', 'precio_extra' => 5, 'descripcion' => 'Hasta 5kg', 'estado' => 1],
            ['nombre' => 'Paquete grande', 'precio_extra' => 15, 'descripcion' => 'Más de 5kg', 'estado' => 1],
        ];
        foreach ($tipos as $t) {
            EncomiendaTipo::firstOrCreate(['nombre' => $t['nombre']], $t);
        }
    }

    /** Una encomienda por viaje via el flujo real (exige caja abierta del usuario 1, ya existe en esta instancia). */
    private function crearEncomiendas(array $viajes): void
    {
        $encomiendaService = app(EncomiendaService::class);
        $tiposCarga = ['GENERAL', 'DOCUMENTOS', 'FRAGIL', 'ELECTRONICA', 'PERECIBLE'];

        foreach ($viajes as $i => $viaje) {
            if (\App\Models\Encomienda::where('viaje_id', $viaje->id)->exists()) {
                continue;
            }
            $resultado = $encomiendaService->registrarEncomienda([
                'viaje_id' => $viaje->id,
                'remitente_nombre' => 'Remitente Prueba '.($i + 1),
                'remitente_dni' => '8000000'.$i,
                'destinatario_nombre' => 'Destinatario Prueba '.($i + 1),
                'destinatario_dni' => '8100000'.$i,
                'destinatario_telefono' => '7000000'.$i,
                'descripcion' => 'Encomienda de prueba #'.($i + 1),
                'peso' => 2 + $i,
                'tipo_carga' => $tiposCarga[$i % count($tiposCarga)],
                'valor_declarado' => 100 + $i * 20,
                'clave_retiro' => str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
                'usuario_id' => 1,
                'precio_verificado' => 30 + $i * 5,
            ]);
            if (! $resultado['status']) {
                Log::warning('TestDataSeeder: no se pudo crear encomienda de prueba: '.$resultado['message']);
            }
        }
    }
}
