<?php
/**
 * functions.php — Funciones de seguridad y utilidades.
 */

declare(strict_types=1);

// ── Salida segura (previene XSS) ──────────────────────────────────────────────
function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ── CSRF — generación y validación de token ───────────────────────────────────
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . h(csrfToken()) . '">';
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        die('Token de seguridad inválido. Por favor recarga la página.');
    }
}

// ── Autenticación ─────────────────────────────────────────────────────────────
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = 'Debes iniciar sesión para acceder a esa página.';
        header('Location: /login.php');
        exit;
    }
}

function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email'    => $_SESSION['email'],
    ];
}

// ── Flash messages ────────────────────────────────────────────────────────────
function flashSuccess(string $msg): void {
    $_SESSION['flash_success'] = $msg;
}

function flashError(string $msg): void {
    $_SESSION['flash_error'] = $msg;
}

function renderFlash(): string {
    $html = '';
    if (!empty($_SESSION['flash_success'])) {
        $html .= '<div class="card-panel green lighten-4 green-text text-darken-4">'
               . h($_SESSION['flash_success']) . '</div>';
        unset($_SESSION['flash_success']);
    }
    if (!empty($_SESSION['flash_error'])) {
        $html .= '<div class="card-panel red lighten-4 red-text text-darken-4">'
               . h($_SESSION['flash_error']) . '</div>';
        unset($_SESSION['flash_error']);
    }
    return $html;
}

// ── Validación de entrada ─────────────────────────────────────────────────────

/**
 * Valida que un string no esté vacío y cumpla longitud mínima/máxima.
 * Retorna el valor trimmeado o lanza error de validación.
 */
function validateString(string $value, string $label, int $min = 1, int $max = 255): string {
    $value = trim($value);
    if (strlen($value) < $min) {
        throw new InvalidArgumentException("{$label} es obligatorio y debe tener al menos {$min} carácter(es).");
    }
    if (strlen($value) > $max) {
        throw new InvalidArgumentException("{$label} no puede superar {$max} caracteres.");
    }
    return $value;
}

function validateEmail(string $value): string {
    $value = trim($value);
    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('El correo electrónico no es válido.');
    }
    if (strlen($value) > 255) {
        throw new InvalidArgumentException('El correo electrónico no puede superar 255 caracteres.');
    }
    return strtolower($value);
}

function validateInt(mixed $value, string $label, int $min = 1): int {
    $int = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min]]);
    if ($int === false) {
        throw new InvalidArgumentException("{$label} debe ser un número entero válido (mínimo {$min}).");
    }
    return $int;
}

// ── Subida de imágenes ────────────────────────────────────────────────────────

/**
 * Procesa y valida un archivo de imagen subido.
 * Retorna la ruta relativa guardada, o null si no se subió ninguna.
 *
 * Seguridad:
 *  - Verifica MIME real con finfo (no confía en extensión ni en $_FILES['type']).
 *  - Whitelist de extensiones y MIME types.
 *  - Nombre de archivo aleatorio (sin datos del usuario).
 *  - Límite de tamaño máximo.
 */
function handleImageUpload(array $file): ?string {
    // Si no se envió ninguna imagen, retornar null
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Error al subir la imagen. Código: ' . $file['error']);
    }

    // Verificar tamaño
    if ($file['size'] > MAX_FILE_SIZE) {
        throw new InvalidArgumentException('La imagen supera el tamaño máximo permitido (2 MB).');
    }

    // Verificar MIME real con finfo
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeReal = $finfo->file($file['tmp_name']);

    if (!in_array($mimeReal, ALLOWED_MIME_TYPES, true)) {
        throw new InvalidArgumentException('Tipo de archivo no permitido. Solo se aceptan JPG, PNG y WEBP.');
    }

    // Extraer extensión desde el MIME real (no del nombre original)
    $mimeToExt = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];
    $ext = $mimeToExt[$mimeReal];

    // Nombre de archivo aleatorio para evitar sobreescrituras y enumeración
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $destPath = UPLOAD_DIR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        error_log('[UPLOAD] Fallo al mover archivo a: ' . $destPath);
        throw new RuntimeException('No se pudo guardar la imagen. Intenta más tarde.');
    }

    return $filename;
}
