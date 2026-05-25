<?php
/**
 * login.php — Inicio de sesión.
 *
 * Seguridad aplicada:
 *  - CSRF token verificado en POST.
 *  - password_verify() para comparar contra el hash bcrypt.
 *  - session_regenerate_id() tras autenticación (previene session fixation).
 *  - Mensaje de error genérico: no indica si el email o contraseña es incorrecto (evita enumeración).
 *  - Límite de longitud en inputs para prevenir ataques de DoS contra bcrypt.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

if (isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$errors  = [];
$oldEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    try {
        $email    = validateEmail($_POST['email'] ?? '');
        // Limitar a 128 chars para no pasar strings enormes a password_verify
        $password = validateString($_POST['password'] ?? '', 'Contraseña', 1, 128);
        $oldEmail = $email;

        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT id, username, email, password_hash FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Comparar siempre (timing-safe) para evitar timing attacks
        $hashToVerify = $user ? $user['password_hash'] : '$2y$12$invalidhashpadding000000000000000000000000000000000000';
        $valid        = password_verify($password, $hashToVerify);

        if (!$user || !$valid) {
            // Mensaje genérico: no revelar si el email existe o no
            throw new InvalidArgumentException('Correo o contraseña incorrectos.');
        }

        // Autenticación exitosa
        session_regenerate_id(true);
        $_SESSION['user_id']  = (int) $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email']    = $user['email'];

        flashSuccess("¡Bienvenido de nuevo, {$user['username']}!");
        header('Location: /index.php');
        exit;

    } catch (InvalidArgumentException $e) {
        $errors[] = $e->getMessage();
    } catch (Throwable $e) {
        error_log('[LOGIN] ' . $e->getMessage());
        $errors[] = 'Ocurrió un error inesperado. Por favor intenta más tarde.';
    }
}

$pageTitle = 'Iniciar sesión';
require __DIR__ . '/templates/header.php';
?>

<div class="row">
    <div class="col s12 m8 offset-m2 l6 offset-l3">
        <div class="card z-depth-2">
            <div class="card-content">
                <span class="card-title center-align">
                    <i class="material-icons left blue-grey-text">login</i>Iniciar sesión
                </span>

                <?php foreach ($errors as $err): ?>
                    <div class="card-panel red lighten-4 red-text text-darken-4">
                        <i class="material-icons tiny">error</i> <?= h($err) ?>
                    </div>
                <?php endforeach; ?>

                <form method="POST" action="/login.php" novalidate>
                    <?= csrfField() ?>

                    <div class="input-field">
                        <i class="material-icons prefix">email</i>
                        <input id="email" name="email" type="email"
                               value="<?= h($oldEmail) ?>"
                               maxlength="255" required autocomplete="email">
                        <label for="email">Correo electrónico *</label>
                    </div>

                    <div class="input-field">
                        <i class="material-icons prefix">lock</i>
                        <input id="password" name="password" type="password"
                               maxlength="128" required autocomplete="current-password">
                        <label for="password">Contraseña *</label>
                    </div>

                    <div class="center-align" style="margin-top: 20px">
                        <button type="submit" class="btn blue-grey darken-3 waves-effect waves-light btn-large">
                            <i class="material-icons left">login</i>Ingresar
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-action center-align">
                ¿No tienes cuenta? <a href="/register.php">Regístrate gratis</a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
