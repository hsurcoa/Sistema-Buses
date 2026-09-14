<?php
/**
 * Contexto de sucursal del usuario en sesion.
 *
 * Reglas (decididas por el dueño):
 * - Cada terminal es una sucursal con su propia caja.
 * - El vendedor opera y ve solo su sucursal.
 * - Administrador y Supervisor pueden operar en cualquiera y ver todas (o filtrar).
 *
 * legacy/public/index.php refresca en cada peticion los datos de sesion
 * (sucursal_id, sucursal_nombre, rol) desde la BD.
 */
class Sucursal
{
    private const ROLES_GLOBALES = ['Administrador', 'Supervisor'];

    /** Sucursal asignada al usuario (null si no tiene). */
    public static function delUsuario()
    {
        return !empty($_SESSION['sucursal_id']) ? (int) $_SESSION['sucursal_id'] : null;
    }

    public static function nombreDelUsuario()
    {
        return $_SESSION['sucursal_nombre'] ?? null;
    }

    /** Administrador y Supervisor ven y operan todas las sucursales. */
    public static function puedeVerTodas()
    {
        return in_array($_SESSION['rol'] ?? '', self::ROLES_GLOBALES, true);
    }

    /**
     * Sucursal por la que se filtran tableros y reportes:
     * - rol global: la elegida en ?sucursal= (o null = todas)
     * - resto: siempre la propia (0 si no tiene, para no mostrar datos ajenos)
     */
    public static function filtro()
    {
        if (self::puedeVerTodas()) {
            $elegida = (int) ($_GET['sucursal'] ?? $_POST['sucursal'] ?? 0);
            return $elegida > 0 ? $elegida : null;
        }
        return self::delUsuario() ?? 0;
    }

    public static function listar($soloActivas = true)
    {
        $db = new Database();
        $db->query('SELECT id, nombre_sede, direccion, telefono, prefijo_boleto, estado,
                           pago_qr_imagen, pago_qr_titular, pago_qr_entidad
                    FROM terminales' . ($soloActivas ? ' WHERE estado = 1' : '') . ' ORDER BY nombre_sede');
        return $db->resultSet();
    }

    public static function obtener($id)
    {
        if (!$id) {
            return null;
        }
        $db = new Database();
        $db->query('SELECT * FROM terminales WHERE id = :id');
        $db->bind(':id', $id);
        return $db->single() ?: null;
    }

    /**
     * Agrega a una consulta el filtro de sucursal sobre $columna.
     * Devuelve [fragmentoSql, parametros]. Sin filtro devuelve ['', []].
     */
    public static function condicion($columna, $filtro = false)
    {
        $filtro = $filtro === false ? self::filtro() : $filtro;
        if ($filtro === null) {
            return ['', []];
        }
        return [" AND {$columna} = :sucursal_filtro", [':sucursal_filtro' => (int) $filtro]];
    }

    /**
     * Siguiente correlativo de la sucursal (boletos o guias), seguro ante ventas
     * simultaneas: el UPDATE bloquea la fila y LAST_INSERT_ID devuelve el valor
     * de esta conexion. Debe llamarse dentro de la transaccion de la venta.
     */
    public static function siguienteCodigo(Database $db, $sucursalId, $tipo = 'boleto')
    {
        $columna = $tipo === 'guia' ? 'correlativo_guia' : 'correlativo_boleto';
        $db->query("UPDATE terminales SET {$columna} = LAST_INSERT_ID({$columna} + 1) WHERE id = :id");
        $db->bind(':id', $sucursalId);
        $db->execute();
        if ($db->rowCount() === 0) {
            return null;
        }
        $numero = (int) $db->lastInsertId();

        $db->query('SELECT prefijo_boleto FROM terminales WHERE id = :id');
        $db->bind(':id', $sucursalId);
        $prefijo = $db->single()->prefijo_boleto ?: 'SUC' . $sucursalId;

        return $tipo === 'guia'
            ? sprintf('ENC-%s-%06d', $prefijo, $numero)
            : sprintf('%s-%06d', $prefijo, $numero);
    }
}
