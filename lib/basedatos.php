<?php
// Librería de acceso a base de datos (PDO wrapper)
// CRÍTICO: Todas las queries DEBEN incluir filtro id_institucion (RN-01)

class BaseDatos {
    private static $instancia = null;
    private $pdo;
    private $ultima_query = '';

    /**
     * Singleton: obtener instancia de conexión
     */
    public static function obtener() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado (singleton)
     */
    private function __construct() {
        $db_config = config('db');

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $db_config['host'],
            $db_config['port'],
            $db_config['name'],
            $db_config['charset']
        );

        try {
            $this->pdo = new PDO($dsn, $db_config['user'], $db_config['pass']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            // Establecer charset UTF-8 explícitamente
            $this->pdo->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
        } catch (PDOException $e) {
            $this->registrar_error('Database connection failed: ' . $e->getMessage());

            // En consola (cron, scripts de mantenimiento) no sirve una página
            // HTML: lo que se lee es la salida del proceso.
            if (PHP_SAPI === 'cli') {
                fwrite(STDERR, "Error de conexión a la base de datos: " . $e->getMessage() . "\n");
                exit(1);
            }

            require_once LIB_PATH . '/errores.php';
            responder_error(
                503,
                'El sistema no está disponible',
                'No pudimos conectar con la base de datos. Suele ser temporal: espera un par de minutos y vuelve a intentarlo.',
                'PDO: ' . $e->getMessage()
            );
        }
    }

    /**
     * Preparar statement
     */
    public function preparar($sql) {
        $this->ultima_query = $sql;
        return $this->pdo->prepare($sql);
    }

    /**
     * Ejecutar query con parámetros (prepared statement)
     * Ejemplo:
     *   $resultado = $bd->ejecutar(
     *       'SELECT * FROM reporte WHERE id_institucion = :inst AND id_reporte = :id',
     *       [':inst' => $_SESSION['id_institucion'], ':id' => 123]
     *   );
     */
    public function ejecutar($sql, $parametros = []) {
        try {
            $stmt = $this->preparar($sql);
            $stmt->execute($parametros);
            return $stmt;
        } catch (PDOException $e) {
            $this->registrar_error("Query error: $sql\n" . $e->getMessage());
            throw new Exception('Error ejecutando query. Contacte al administrador.');
        }
    }

    /**
     * Obtener un registro como array asociativo
     */
    public function obtener_uno($sql, $parametros = []) {
        $stmt = $this->ejecutar($sql, $parametros);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener todos los registros
     */
    public function obtener_todos($sql, $parametros = []) {
        $stmt = $this->ejecutar($sql, $parametros);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener valor de una columna
     */
    public function obtener_valor($sql, $parametros = []) {
        $resultado = $this->obtener_uno($sql, $parametros);
        if ($resultado) {
            return array_values($resultado)[0];
        }
        return null;
    }

    /**
     * Insertar registro
     * Retorna id insertado
     */
    public function insertar($tabla, $datos) {
        $this->validar_identificador($tabla);
        $columnas = array_keys($datos);
        array_walk($columnas, [$this, 'validar_identificador']);
        $placeholders = array_map(fn($c) => ":$c", $columnas);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $tabla,
            implode(', ', $columnas),
            implode(', ', $placeholders)
        );

        try {
            $stmt = $this->preparar($sql);
            $stmt->execute($datos);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            $this->registrar_error("Insert error en tabla $tabla: " . $e->getMessage());
            throw new Exception('Error insertando registro.');
        }
    }

    /**
     * Actualizar registro
     * Retorna número de filas afectadas
     */
    public function actualizar($tabla, $datos, $where, $parametros_where = []) {
        $this->validar_identificador($tabla);
        $columnas = array_keys($datos);
        array_walk($columnas, [$this, 'validar_identificador']);
        $sets = array_map(fn($c) => "$c = :$c", $columnas);
        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $tabla,
            implode(', ', $sets),
            $where
        );

        $parametros = array_merge($datos, $parametros_where);

        try {
            $stmt = $this->preparar($sql);
            $stmt->execute($parametros);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->registrar_error("Update error en tabla $tabla: " . $e->getMessage());
            throw new Exception('Error actualizando registro.');
        }
    }

    /**
     * Eliminar registro
     * Retorna número de filas afectadas
     */
    public function eliminar($tabla, $where, $parametros = []) {
        $this->validar_identificador($tabla);
        $sql = "DELETE FROM $tabla WHERE $where";

        try {
            $stmt = $this->preparar($sql);
            $stmt->execute($parametros);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->registrar_error("Delete error en tabla $tabla: " . $e->getMessage());
            throw new Exception('Error eliminando registro.');
        }
    }

    /**
     * Iniciar/revertir transacción manualmente (uso: pruebas de integración que
     * envuelven cada prueba en una transacción y la revierten en tearDown, sin
     * dejar datos de prueba en la base de datos real).
     */
    public function iniciar_transaccion() {
        $this->pdo->beginTransaction();
    }

    public function revertir_transaccion() {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    /**
     * Ejecutar transacción
     * $callback recibe $this como parámetro
     */
    public function transaccion(callable $callback) {
        try {
            $this->pdo->beginTransaction();
            $resultado = $callback($this);
            $this->pdo->commit();
            return $resultado;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->registrar_error('Transaction rolled back: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Contar registros
     */
    public function contar($tabla, $where = '1=1', $parametros = []) {
        $this->validar_identificador($tabla);
        $sql = "SELECT COUNT(*) as total FROM $tabla WHERE $where";
        return intval($this->obtener_valor($sql, $parametros));
    }

    /**
     * Verificar si existe un registro
     */
    public function existe($tabla, $where, $parametros = []) {
        return $this->contar($tabla, $where, $parametros) > 0;
    }

    /**
     * Obtener esquema de una tabla (for future migrations)
     */
    public function obtener_columnas($tabla) {
        $sql = "DESCRIBE $tabla";
        return $this->obtener_todos($sql);
    }

    /**
     * Validar que un nombre de tabla/columna sea un identificador SQL seguro
     * antes de interpolarlo en una query. Defensa en profundidad: hoy ningún
     * llamador pasa claves de $_POST/$_GET sin filtrar como $tabla o columnas de
     * $datos, pero esta librería es el único punto donde esa interpolación ocurre,
     * así que se valida aquí para que un futuro llamador descuidado no reintroduzca
     * una inyección SQL vía nombre de columna/tabla.
     */
    private function validar_identificador($nombre) {
        if (!is_string($nombre) || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $nombre)) {
            throw new Exception('Nombre de tabla o columna no válido: ' . var_export($nombre, true));
        }
    }

    /**
     * Registrar errores en archivo de log
     */
    private function registrar_error($mensaje) {
        $log_file = LOG_DIR . '/database_errors.log';
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[$timestamp] $mensaje\n";

        if (!is_dir(LOG_DIR)) {
            @mkdir(LOG_DIR, 0755, true);
        }

        @file_put_contents($log_file, $log_message, FILE_APPEND);
    }

    /**
     * Obtener última query ejecutada (para debugging)
     */
    public function obtener_ultima_query() {
        return $this->ultima_query;
    }

    /**
     * Cerrar conexión
     */
    public function cerrar() {
        $this->pdo = null;
    }
}
