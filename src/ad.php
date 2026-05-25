<?php
/**
 * ad.php — Vista de detalle de un anuncio.
 *
 * Seguridad aplicada:
 *  - id validado como entero positivo (FILTER_VALIDATE_INT).
 *  - Consulta parametrizada para obtener el anuncio.
 *  - Toda salida escapada con h() para prevenir XSS.
 *  - Email del autor solo visible si el usuario lo autorizó (email_public = 1).
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Validar el parámetro id
$adId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1]
]);

if (!$adId) {
    http_response_code(400);
    flashError('ID de anuncio inválido.');
    header('Location: /index.php');
    exit;
}

$pdo  = getDB();
$stmt = $pdo->prepare("
    SELECT a.id, a.title, a.description, a.body, a.image_path,
           a.created_at, a.status,
           c.name  AS category_name,
           u.username, u.email, u.email_public
    FROM ads a
    JOIN categories c ON c.id = a.category_id
    JOIN users      u ON u.id = a.user_id
    WHERE a.id = ? AND a.status = 'active'
    LIMIT 1
");
$stmt->execute([$adId]);
$ad = $stmt->fetch();

if (!$ad) {
    http_response_code(404);
    $pageTitle = 'Anuncio no encontrado';
    require __DIR__ . '/templates/header.php';
    echo '
    <div class="card-panel orange lighten-4 center-align">
        <i class="material-icons large orange-text">search_off</i>
        <p>El anuncio que buscas no existe o fue eliminado.</p>
        <a href="/index.php" class="btn blue-grey darken-2">Ver todos los anuncios</a>
    </div>';
    require __DIR__ . '/templates/footer.php';
    exit;
}

$pageTitle = $ad['title'];
require __DIR__ . '/templates/header.php';
?>

<!-- Breadcrumb -->
<nav class="breadcrumb-nav" style="background:none; box-shadow:none; margin-bottom:10px">
    <div class="nav-wrapper">
        <a href="/index.php" class="breadcrumb grey-text">Inicio</a>
        <a href="/index.php" class="breadcrumb grey-text"><?= h($ad['category_name']) ?></a>
        <span class="breadcrumb grey-text text-darken-2"><?= h(mb_substr($ad['title'], 0, 40)) ?></span>
    </div>
</nav>

<div class="row">
    <!-- Columna principal -->
    <div class="col s12 m8">
        <div class="card z-depth-1">

            <?php if ($ad['image_path']): ?>
            <div class="card-image">
                <img src="<?= h(UPLOAD_URL_PATH . $ad['image_path']) ?>"
                     alt="Imagen: <?= h($ad['title']) ?>"
                     style="max-height: 420px; object-fit:cover; width:100%">
            </div>
            <?php endif; ?>

            <div class="card-content">
                <!-- Badge de categoría -->
                <span class="new badge blue-grey" data-badge-caption=""><?= h($ad['category_name']) ?></span>

                <h4 style="margin-top: 10px"><?= h($ad['title']) ?></h4>

                <p class="grey-text"><em><?= h($ad['description']) ?></em></p>

                <div class="divider" style="margin: 16px 0"></div>

                <!-- Texto completo del anuncio -->
                <div style="line-height: 1.7; white-space: pre-wrap">
                    <?= h($ad['body']) ?>
                </div>
            </div>

            <div class="card-action grey lighten-4">
                <a href="/index.php" class="blue-grey-text">
                    <i class="material-icons tiny">arrow_back</i> Volver al listado
                </a>
            </div>
        </div>
    </div>

    <!-- Panel del anunciante -->
    <div class="col s12 m4">
        <div class="card z-depth-1">
            <div class="card-content">
                <span class="card-title" style="font-size:1.1rem">
                    <i class="material-icons left blue-grey-text">person</i>Anunciante
                </span>

                <p>
                    <strong><?= h($ad['username']) ?></strong>
                </p>

                <?php if ($ad['email_public']): ?>
                <p>
                    <i class="material-icons tiny">email</i>
                    <a href="mailto:<?= h($ad['email']) ?>"><?= h($ad['email']) ?></a>
                </p>
                <?php else: ?>
                <p class="grey-text" style="font-size:.85rem">
                    <i class="material-icons tiny">lock</i>
                    El anunciante prefiere no mostrar su correo.
                </p>
                <?php endif; ?>

                <div class="divider" style="margin: 12px 0"></div>

                <p class="grey-text" style="font-size:.82rem">
                    <i class="material-icons tiny">schedule</i>
                    Publicado el <?= h(date('d \d\e F \d\e Y', strtotime($ad['created_at']))) ?>
                </p>
            </div>
        </div>

        <?php if ($ad['email_public']): ?>
        <div class="card z-depth-1">
            <div class="card-content">
                <span class="card-title" style="font-size:1rem">
                    <i class="material-icons left blue-grey-text">mail</i>Contactar
                </span>
                <a href="mailto:<?= h($ad['email']) ?>?subject=<?= rawurlencode('Consulta: ' . $ad['title']) ?>"
                   class="btn blue-grey darken-3 waves-effect waves-light" style="width:100%">
                    <i class="material-icons left">send</i>Enviar correo
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
