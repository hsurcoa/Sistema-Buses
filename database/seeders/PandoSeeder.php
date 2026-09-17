<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Terminal;
use App\Models\Ruta;
use App\Models\Vehiculo;
use App\Models\TipoBus;
use App\Models\Personal;
use App\Models\Cliente;

class PandoSeeder extends Seeder
{
    public function run()
    {
        // 1. Terminales (Sucursales)
        $terminales = ['Cobija', 'Porvenir', 'Puerto Rico', 'El Sena', 'Chivé', 'Bolpebra'];
        foreach($terminales as $index => $t) {
            if(!Terminal::where('nombre_sede', $t)->exists()) {
                Terminal::insert(['nombre_sede' => $t, 'direccion' => 'Terminal ' . $t, 'telefono' => '12345678', 'numero_oficina' => 'OFI-' . ($index + 1), 'estado' => 1]);
            }
        }

        // 2. Rutas
        $destinos = ['Porvenir', 'Puerto Rico', 'El Sena', 'Chivé', 'Bolpebra'];
        foreach($destinos as $d) {
            if(!Ruta::where('origen', 'Cobija')->where('destino', $d)->exists()) {
                Ruta::insert(['origen' => 'Cobija', 'destino' => $d, 'estado' => 1]);
            }
        }

        // 3. Tipos de Bus
        $tipo1 = TipoBus::where('nombre', 'Minibus')->first();
        if(!$tipo1) {
            $tipo1Id = TipoBus::insertGetId(['nombre' => 'Minibus', 'pisos' => 1, 'capacidad' => 14, 'estado' => 1]);
        } else {
            $tipo1Id = $tipo1->id;
        }

        $tipo2 = TipoBus::where('nombre', 'Bus Dos Pisos')->first();
        if(!$tipo2) {
            $tipo2Id = TipoBus::insertGetId(['nombre' => 'Bus Dos Pisos', 'pisos' => 2, 'capacidad' => 60, 'estado' => 1]);
        } else {
            $tipo2Id = $tipo2->id;
        }

        // 4. Vehiculos
        $buses = [
            ['placa' => '1234-PND', 'tipo_bus_id' => $tipo1Id, 'clase' => 'Minibus', 'marca' => 'Toyota', 'modelo' => 'Hiace', 'anio' => '2019', 'asientos' => 14, 'estado' => 1, 'tarjeta_circulacion' => 'TC-1001'],
            ['placa' => '5678-PND', 'tipo_bus_id' => $tipo2Id, 'clase' => 'Bus Leito', 'marca' => 'Volvo', 'modelo' => 'Marcopolo', 'anio' => '2022', 'asientos' => 60, 'estado' => 1, 'tarjeta_circulacion' => 'TC-1002'],
            ['placa' => '9012-PND', 'tipo_bus_id' => $tipo1Id, 'clase' => 'Minibus', 'marca' => 'Nissan', 'modelo' => 'Caravan', 'anio' => '2020', 'asientos' => 14, 'estado' => 1, 'tarjeta_circulacion' => 'TC-1003'],
        ];
        foreach($buses as $b) {
            if(!Vehiculo::where('placa', $b['placa'])->exists()) {
                Vehiculo::insert($b);
            }
        }

        // 5. Personal (Choferes Bolivianos)
        $choferes = [
            ['nombres' => 'Juan', 'apellidos' => 'Mamani', 'tipo_documento' => 'CI', 'numero_documento' => '1234567', 'perfil' => 'Chofer', 'estado' => 1],
            ['nombres' => 'Carlos', 'apellidos' => 'Quispe', 'tipo_documento' => 'CI', 'numero_documento' => '7654321', 'perfil' => 'Chofer', 'estado' => 1],
            ['nombres' => 'Luis', 'apellidos' => 'Condori', 'tipo_documento' => 'CI', 'numero_documento' => '8888888', 'perfil' => 'Chofer', 'estado' => 1],
            ['nombres' => 'Mario', 'apellidos' => 'Apaza', 'tipo_documento' => 'CI', 'numero_documento' => '1111111', 'perfil' => 'Chofer', 'estado' => 1],
            ['nombres' => 'Hugo', 'apellidos' => 'Choque', 'tipo_documento' => 'CI', 'numero_documento' => '2222222', 'perfil' => 'Chofer', 'estado' => 1],
        ];
        foreach($choferes as $c) {
            if(!Personal::where('numero_documento', $c['numero_documento'])->exists()) {
                Personal::insert($c);
            }
        }

        // 6. Clientes (Pasajeros)
        $clientes = [
            ['nombres' => 'Ana', 'apellidos' => 'Flores', 'tipo_documento' => 'CI', 'numero_documento' => '9000001', 'celular' => '70000001'],
            ['nombres' => 'Maria', 'apellidos' => 'Choque', 'tipo_documento' => 'CI', 'numero_documento' => '9000002', 'celular' => '70000002'],
            ['nombres' => 'Jose', 'apellidos' => 'Vargas', 'tipo_documento' => 'CI', 'numero_documento' => '9000003', 'celular' => '70000003'],
            ['nombres' => 'Pedro', 'apellidos' => 'Gutierrez', 'tipo_documento' => 'CI', 'numero_documento' => '9000004', 'celular' => '70000004'],
            ['nombres' => 'Rosa', 'apellidos' => 'Rojas', 'tipo_documento' => 'CI', 'numero_documento' => '9000005', 'celular' => '70000005'],
            ['nombres' => 'Javier', 'apellidos' => 'Lopez', 'tipo_documento' => 'CI', 'numero_documento' => '9000006', 'celular' => '70000006'],
            ['nombres' => 'Sonia', 'apellidos' => 'Limachi', 'tipo_documento' => 'CI', 'numero_documento' => '9000007', 'celular' => '70000007'],
            ['nombres' => 'Victor', 'apellidos' => 'Huanca', 'tipo_documento' => 'CI', 'numero_documento' => '9000008', 'celular' => '70000008'],
            ['nombres' => 'Carmen', 'apellidos' => 'Ticona', 'tipo_documento' => 'CI', 'numero_documento' => '9000009', 'celular' => '70000009'],
            ['nombres' => 'Raul', 'apellidos' => 'Arias', 'tipo_documento' => 'CI', 'numero_documento' => '9000010', 'celular' => '70000010'],
        ];
        foreach($clientes as $cl) {
            if(!Cliente::where('numero_documento', $cl['numero_documento'])->exists()) {
                Cliente::insert($cl);
            }
        }
    }
}
