<?php
/**
 * config.php — Configuración global de la aplicación.
 *
 * Seguridad aplicada:
 *  - Errores ocultos al usuario, registrados en log interno.
 *  - Headers de seguridad HTTP enviados en cada respuesta.
 *  - Sesión con cookies HttpOnly + SameSite (Lax para compatibilidad).
 *  - Secretos leídos desde variables de entorno, nunca hardcodeados.
 */

declare(strict_types=1);

// ── Error handling: ocultar al usuario, registrar internamente ────────────────
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/logs/app.log');
error_reporting(E_ALL);

// ── Directorio de logs ─────────────────────────────────────────────────────────
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0750, true);
}

// ── Charset de la respuesta HTTP ─────────────────────────────────────────────
header('Content-Type: text/html; charset=UTF-8');

// ── Headers de seguridad HTTP ─────────────────────────────────────────────────
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// ── Sesión segura ─────────────────────────────────────────────────────────────
ini_set('session.cookie_httponly', '1');   // inaccesible desde JavaScript
ini_set('session.cookie_samesite', 'Lax'); // previene CSRF básico
ini_set('session.use_strict_mode', '1');   // rechaza IDs de sesión no iniciados por el servidor
// En producción con HTTPS activar: ini_set('session.cookie_secure', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Constantes de la aplicación ───────────────────────────────────────────────
define('APP_NAME', 'AnunciosFácil');

// Subida de imágenes
define('UPLOAD_DIR',        __DIR__ . '/uploads/');
define('UPLOAD_URL_PATH',   '/uploads/');
define('MAX_FILE_SIZE',     2 * 1024 * 1024);                         // 2 MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// ── Función auxiliar para leer variables de entorno ───────────────────────────
function env(string $key, string $default = ''): string {
    $value = $_ENV[$key] ?? getenv($key);
    return ($value !== false && $value !== '') ? (string) $value : $default;
}
