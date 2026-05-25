<?php
/**
 * db.php — Conexión PDO singleton.
 *
 * Seguridad aplicada:
 *  - PDO con modo ERRMODE_EXCEPTION para capturar errores sin exponerlos.
 *  - Charset utf8mb4 forzado en el DSN para evitar ataques de encoding.
 *  - Credenciales leídas desde variables de entorno.
 *  - EMULATE_PREPARES=false: prepared statements reales en el motor MySQL.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function getDB(): PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $host = env('DB_HOST', 'db');
    $port = env('DB_PORT', '3306');
    $name = env('DB_NAME', 'anuncios_db');
    $user = env('DB_USER', '');
    $pass = env('DB_PASS', '');

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // prepared statements reales
        ]);
    } catch (PDOException $e) {
        // Registrar el error real en el log, nunca al usuario
        error_log('[DB] Fallo de conexión: ' . $e->getMessage());
        http_response_code(500);
        // Mostrar mensaje genérico
        require __DIR__ . '/templates/header.php';
        echo '<div class="container" style="margin-top:80px"><div class="card-panel red lighten-4">
              <b>Error:</b> No se pudo conectar con la base de datos. Intenta más tarde.</div></div>';
        require __DIR__ . '/templates/footer.php';
        exit;
    }

    return $pdo;
}
