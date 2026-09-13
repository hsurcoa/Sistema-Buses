<?php
/*
 *  Clase Database PDO
 *  Conecta a la base de datos
 *  Crea sentencias preparadas
 *  Vincula valores
 *  Retorna filas y resultados
 */
class Database
{
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;

    private $dbh;
    private $stmt;
    private $error;

    public function __construct()
    {
        // Configurar DSN
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname;
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );

        // Crear instancia PDO
        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            // Detener la ejecución si hay un error crítico de conexión
            die('<div style="background-color: #f8d7da; color: #721c24; padding: 20px; text-align: center; font-family: sans-serif; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px;">
                    <h2>Error de Conexión</h2>
                    <p>No se pudo conectar a la base de datos <strong>' . $this->dbname . '</strong>.</p>
                    <p><small>Detalles: ' . $this->error . '</small></p>
                 </div>');
        }
    }

    // Preparar sentencia con query
    public function query($sql)
    {
        $this->stmt = $this->dbh->prepare($sql);
    }

    // Vincular valores
    public function bind($param, $value, $type = null)
    {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    // Ejecutar la sentencia preparada
    public function execute()
    {
        return $this->stmt->execute();
    }

    // Obtener resultado set como array de objetos
    public function resultSet()
    {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Obtener un solo registro
    public function single()
    {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_OBJ);
    }

    // Obtener row count
    public function rowCount()
    {
        return $this->stmt->rowCount();
    }

    // Iniciar transacción
    public function beginTransaction()
    {
        return $this->dbh->beginTransaction();
    }

    // Confirmar transacción
    public function commit()
    {
        return $this->dbh->commit();
    }

    // Revertir transacción
    public function rollBack()
    {
        return $this->dbh->rollBack();
    }

    // Obtener último ID insertado
    public function lastInsertId()
    {
        return $this->dbh->lastInsertId();
    }
}
