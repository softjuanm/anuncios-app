<?php
/**
 * logout.php — Cierre de sesión.
 *
 * Seguridad:
 *  - Destruye completamente la sesión (datos + cookie).
 *  - Solo acepta POST para evitar logout por CSRF vía GET (img src, etc.).
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isLoggedIn()) {
    // Mostrar página de confirmación
    $pageTitle = 'Cerrar sesión';
    require __DIR__ . '/templates/header.php';
    echo '
    <div class="row"><div class="col s12 m6 offset-m3">
    <div class="card z-depth-1 center-align" style="padding:30px">
        <i class="material-icons large blue-grey-text">logout</i>
        <h5>¿Deseas cerrar sesión?</h5>
        <form method="POST" action="/logout.php">
            ' . csrfField() . '
            <button type="submit" class="btn blue-grey darken-3 waves-effect waves-light">
                <i class="material-icons left">logout</i>Sí, cerrar sesión
            </button>
            &nbsp;
            <a href="/index.php" class="btn-flat waves-effect">Cancelar</a>
        </form>
    </div>
    </div></div>';
    require __DIR__ . '/templates/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    // Vaciar variables de sesión
    $_SESSION = [];

    // Borrar la cookie de sesión
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

header('Location: /index.php');
exit;
