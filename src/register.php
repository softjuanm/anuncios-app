<?php
/**
 * register.php — Registro de nuevos usuarios.
 *
 * Seguridad aplicada:
 *  - CSRF token verificado antes de procesar el POST.
 *  - Validación server-side de todos los campos (tipo, longitud, formato).
 *  - Contraseña hasheada con password_hash (bcrypt, costo 12).
 *  - Consulta parametrizada para INSERT.
 *  - Mensajes de error genéricos al usuario; detalles en log.
 *  - session_regenerate_id tras login automático post-registro.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Redirigir si ya está autenticado
if (isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$errors  = [];
$oldData = ['username' => '', 'email' => '', 'email_public' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Verificar token CSRF
    verifyCsrf();

    // 2. Validar campos
    try {
        $username     = validateString($_POST['username'] ?? '', 'Nombre de usuario', 3, 50);
        $email        = validateEmail($_POST['email'] ?? '');
        $password     = validateString($_POST['password'] ?? '', 'Contraseña', 8, 128);
        $passConfirm  = $_POST['password_confirm'] ?? '';
        $emailPublic  = isset($_POST['email_public']) ? 1 : 0;

        // Guardar para repoblar el formulario en caso de error
        $oldData = ['username' => $username, 'email' => $email, 'email_public' => (bool)$emailPublic];

        // Solo letras, números y guiones bajos en el username
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            throw new InvalidArgumentException('El nombre de usuario solo puede contener letras, números y guiones bajos (_).');
        }

        if ($password !== $passConfirm) {
            throw new InvalidArgumentException('Las contraseñas no coinciden.');
        }

        // Requisito mínimo de contraseña
        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            throw new InvalidArgumentException('La contraseña debe tener al menos una letra mayúscula y un número.');
        }

        $pdo = getDB();

        // Verificar si el username o email ya existen
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $check->execute([$username, $email]);
        if ($check->fetch()) {
            // Mensaje genérico: no revelar cuál campo ya existe (evitar enumeración de usuarios)
            throw new InvalidArgumentException('Los datos ingresados ya están registrados. Intenta con otros o inicia sesión.');
        }

        // Hashear contraseña con bcrypt (costo 12)
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        // Insertar usuario con consulta parametrizada
        $insert = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, email_public)
            VALUES (?, ?, ?, ?)
        ");
        $insert->execute([$username, $email, $passwordHash, $emailPublic]);
        $newUserId = (int) $pdo->lastInsertId();

        // Login automático: regenerar ID de sesión para prevenir session fixation
        session_regenerate_id(true);
        $_SESSION['user_id']  = $newUserId;
        $_SESSION['username'] = $username;
        $_SESSION['email']    = $email;

        flashSuccess("¡Bienvenido, {$username}! Tu cuenta fue creada con éxito.");
        header('Location: /index.php');
        exit;

    } catch (InvalidArgumentException $e) {
        $errors[] = $e->getMessage();
    } catch (Throwable $e) {
        // Registrar error real, mostrar mensaje genérico
        error_log('[REGISTER] ' . $e->getMessage());
        $errors[] = 'Ocurrió un error inesperado. Por favor intenta más tarde.';
    }
}

$pageTitle = 'Crear cuenta';
require __DIR__ . '/templates/header.php';
?>

<div class="row">
    <div class="col s12 m8 offset-m2 l6 offset-l3">
        <div class="card z-depth-2">
            <div class="card-content">
                <span class="card-title center-align">
                    <i class="material-icons left blue-grey-text">person_add</i>Crear cuenta
                </span>

                <?php foreach ($errors as $err): ?>
                    <div class="card-panel red lighten-4 red-text text-darken-4">
                        <i class="material-icons tiny">error</i> <?= h($err) ?>
                    </div>
                <?php endforeach; ?>

                <form method="POST" action="/register.php" novalidate>
                    <?= csrfField() ?>

                    <!-- Nombre de usuario -->
                    <div class="input-field">
                        <i class="material-icons prefix">person</i>
                        <input id="username" name="username" type="text"
                               value="<?= h($oldData['username']) ?>"
                               minlength="3" maxlength="50" required
                               pattern="[a-zA-Z0-9_]+"
                               title="Solo letras, números y guiones bajos">
                        <label for="username">Nombre de usuario *</label>
                        <span class="helper-text">3-50 caracteres. Solo letras, números y _</span>
                    </div>

                    <!-- Email -->
                    <div class="input-field">
                        <i class="material-icons prefix">email</i>
                        <input id="email" name="email" type="email"
                               value="<?= h($oldData['email']) ?>"
                               maxlength="255" required>
                        <label for="email">Correo electrónico *</label>
                    </div>

                    <!-- Contraseña -->
                    <div class="input-field">
                        <i class="material-icons prefix">lock</i>
                        <input id="password" name="password" type="password"
                               minlength="8" maxlength="128" required
                               autocomplete="new-password">
                        <label for="password">Contraseña *</label>
                        <span class="helper-text">Mínimo 8 caracteres, una mayúscula y un número.</span>
                    </div>

                    <!-- Confirmar contraseña -->
                    <div class="input-field">
                        <i class="material-icons prefix">lock_outline</i>
                        <input id="password_confirm" name="password_confirm" type="password"
                               minlength="8" maxlength="128" required
                               autocomplete="new-password">
                        <label for="password_confirm">Confirmar contraseña *</label>
                    </div>

                    <!-- Visibilidad del email -->
                    <div style="margin: 20px 0 10px 0;">
                        <label>
                            <input type="checkbox" name="email_public" value="1"
                                   <?= $oldData['email_public'] ? 'checked' : '' ?>>
                            <span>
                                Permitir que mi correo sea visible en mis anuncios
                                <small class="grey-text">(para que los interesados te contacten directamente)</small>
                            </span>
                        </label>
                    </div>

                    <div class="center-align" style="margin-top: 20px">
                        <button type="submit" class="btn blue-grey darken-3 waves-effect waves-light btn-large">
                            <i class="material-icons left">how_to_reg</i>Registrarme
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-action center-align">
                ¿Ya tienes cuenta? <a href="/login.php">Inicia sesión</a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
