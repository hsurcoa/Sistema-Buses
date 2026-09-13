<?php
// fix_phantom_users.php
// Script para crear usuarios choferes/copilotos reales y arreglar la asignación corrupta

require_once 'app/config/config.php';
require_once 'app/core/Database.php';

class ChoferFixer
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function run()
    {
        echo "<h1>🛠️ Reparación de Usuarios Fantasma y Asignación de Tripulación</h1>";
        echo "<pre>";

        // 1. Crear 5 Personales Reales (Si no existen) en tabla 'personal'
        $nuevosPersonales = [
            ['nombres' => 'Carlos', 'apellidos' => 'Mamani', 'rol' => 'Chofer', 'ci' => '1001'],
            ['nombres' => 'Juan', 'apellidos' => 'Perez', 'rol' => 'Chofer', 'ci' => '1002'],
            ['nombres' => 'Luis', 'apellidos' => 'Quispe', 'rol' => 'Chofer', 'ci' => '1003'],
            ['nombres' => 'Mario', 'apellidos' => 'Condori', 'rol' => 'Copiloto', 'ci' => '2001'],
            ['nombres' => 'Pedro', 'apellidos' => 'Vargas', 'rol' => 'Copiloto', 'ci' => '2002']
        ];

        $idsGenerados = [];

        foreach ($nuevosPersonales as $c) {
            // Verificar si existe por numero_documento
            $this->db->query("SELECT id FROM personal WHERE numero_documento = :ci");
            $this->db->bind(':ci', $c['ci']);
            $existe = $this->db->single();

            if ($existe) {
                echo "ℹ️ Personal {$c['nombres']} {$c['apellidos']} ya existe (ID: {$existe->id}).\n";
                if ($c['rol'] == 'Chofer') $idsGenerados['chofer'][] = $existe->id;
                else $idsGenerados['copiloto'][] = $existe->id;
            } else {
                // Crear personal
                $sql = "INSERT INTO personal (nombres, apellidos, numero_documento, perfil, estado, created_at) 
                        VALUES (:nom, :ape, :ci, :rol, 1, NOW())";

                $this->db->query($sql);
                $this->db->bind(':nom', $c['nombres']);
                $this->db->bind(':ape', $c['apellidos']);
                $this->db->bind(':ci', $c['ci']);
                $this->db->bind(':rol', $c['rol']);

                if ($this->db->execute()) {
                    $newId = $this->db->lastInsertId();
                    echo "✅ Creado PERSONAL: {$c['nombres']} {$c['apellidos']} (ID: $newId)\n";
                    if ($c['rol'] == 'Chofer') $idsGenerados['chofer'][] = $newId;
                    else $idsGenerados['copiloto'][] = $newId;
                } else {
                    echo "❌ Error creando {$c['nombres']}\n";
                }
            }
        }

        // 2. Arreglar la Asignación del Bus SAE-9895 (ID 10)
        // Tomaremos el primer chofer y primer copiloto generados
        $choferRealId = $idsGenerados['chofer'][0] ?? null;
        $copilotoRealId = $idsGenerados['copiloto'][0] ?? null;

        if ($choferRealId && $copilotoRealId) {
            $busId = 10; // ID del Bus SAE-9895 segun diagnostico anterior

            // Verificar si ya tiene asignacion (aunque sea corrupta)
            $this->db->query("SELECT id FROM asignaciones_buses WHERE bus_id = :bid AND estado = 1");
            $this->db->bind(':bid', $busId);
            $asig = $this->db->single();

            if ($asig) {
                // ACTUALIZAR asignacion existente con IDs REALES
                echo "\n🔧 Actualizando asignación ID {$asig->id} con usuarios reales...\n";
                $this->db->query("UPDATE asignaciones_buses SET chofer_id = :cid, copiloto_id = :copid, fecha_asignacion = NOW() WHERE id = :aid");
                $this->db->bind(':cid', $choferRealId);
                $this->db->bind(':copid', $copilotoRealId);
                $this->db->bind(':aid', $asig->id);

                if ($this->db->execute()) {
                    echo "✅ ¡CORREGIDO! Asignación actualizada.\n";
                    echo "   -> Nuevo Chofer ID: $choferRealId\n";
                    echo "   -> Nuevo Copiloto ID: $copilotoRealId\n";
                } else {
                    echo "❌ Error al actualizar asignación.\n";
                }
            } else {
                // CREAR nueva asignacion
                echo "\n✨ Creando nueva asignación limpia...\n";
                $this->db->query("INSERT INTO asignaciones_buses (bus_id, chofer_id, copiloto_id, estado, fecha_asignacion) VALUES (:bid, :cid, :copid, 1, NOW())");
                $this->db->bind(':bid', $busId);
                $this->db->bind(':cid', $choferRealId);
                $this->db->bind(':copid', $copilotoRealId);

                if ($this->db->execute()) {
                    echo "✅ Asignación creada exitosamente.\n";
                } else {
                    echo "❌ Error creando asignación.\n";
                }
            }

            // También actualizamos el viaje, por si acaso tiene IDs corruptos directos
            // Viaje ID 12
            $this->db->query("UPDATE viajes SET chofer_id = :cid WHERE id = 12"); // Opcional, pero ayuda
            $this->db->bind(':cid', $choferRealId);
            $this->db->execute();
            echo "✅ Viaje #12 actualizado directamente con Chofer ID $choferRealId (backup).\n";
        } else {
            echo "❌ No se pudieron generar IDs de choferes válidos para realizar el arreglo.\n";
        }

        echo "</pre>";
    }
}

$fixer = new ChoferFixer();
$fixer->run();
