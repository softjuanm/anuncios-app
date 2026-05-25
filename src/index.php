<?php
/**
 * index.php — Página principal: listado de anuncios activos.
 *
 * Seguridad aplicada:
 *  - Consulta parametrizada para filtro de categoría.
 *  - Toda salida escapada con h() para prevenir XSS.
 *  - Validación del parámetro GET category_id con filter_var.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$pageTitle = 'Anuncios';

// Filtro por categoría (validado como entero positivo)
$categoryFilter = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1]
]);

$pdo = getDB();

// Cargar categorías para el filtro
$cats = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();

// Construir consulta principal con prepared statement
if ($categoryFilter) {
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.description, a.image_path, a.created_at,
               c.name AS category_name,
               u.username, u.email, u.email_public
        FROM ads a
        JOIN categories c ON c.id = a.category_id
        JOIN users      u ON u.id = a.user_id
        WHERE a.status = 'active' AND a.category_id = ?
        ORDER BY a.created_at DESC
        LIMIT 60
    ");
    $stmt->execute([$categoryFilter]);
} else {
    $stmt = $pdo->query("
        SELECT a.id, a.title, a.description, a.image_path, a.created_at,
               c.name AS category_name,
               u.username, u.email, u.email_public
        FROM ads a
        JOIN categories c ON c.id = a.category_id
        JOIN users      u ON u.id = a.user_id
        WHERE a.status = 'active'
        ORDER BY a.created_at DESC
        LIMIT 60
    ");
}

$ads = $stmt->fetchAll();

require __DIR__ . '/templates/header.php';
?>

<div class="row">
    <!-- Sidebar: filtro de categorías -->
    <div class="col s12 m3">
        <div class="card">
            <div class="card-content">
                <span class="card-title grey-text text-darken-3">
                    <i class="material-icons left">filter_list</i>Categorías
                </span>
                <ul class="collection" style="margin-top:10px">
                    <li class="collection-item <?= !$categoryFilter ? 'active blue white-text' : '' ?>">
                        <a href="/index.php" class="<?= !$categoryFilter ? 'white-text' : '' ?>">
                            Todos los anuncios
                        </a>
                    </li>
                    <?php foreach ($cats as $cat): ?>
                    <li class="collection-item <?= $categoryFilter === (int)$cat['id'] ? 'active blue white-text' : '' ?>">
                        <a href="/index.php?category_id=<?= (int)$cat['id'] ?>"
                           class="<?= $categoryFilter === (int)$cat['id'] ? 'white-text' : '' ?>">
                            <?= h($cat['name']) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <?php if (isLoggedIn()): ?>
        <a href="/create-ad.php" class="btn blue-grey darken-3 waves-effect waves-light btn-block" style="width:100%">
            <i class="material-icons left">add</i>Publicar anuncio
        </a>
        <?php endif; ?>
    </div>

    <!-- Listado de anuncios -->
    <div class="col s12 m9">
        <h5 class="grey-text text-darken-2">
            <?= $categoryFilter ? h($cats[array_search($categoryFilter, array_column($cats, 'id'))]['name'] ?? 'Categoría') : 'Todos los anuncios' ?>
            <span class="badge new blue" data-badge-caption="anuncios"><?= count($ads) ?></span>
        </h5>

        <?php if (empty($ads)): ?>
            <div class="card-panel blue-grey lighten-5 center-align">
                <i class="material-icons large grey-text">search_off</i>
                <p class="grey-text">No hay anuncios publicados todavía.</p>
                <?php if (isLoggedIn()): ?>
                    <a href="/create-ad.php" class="btn blue-grey darken-2">¡Sé el primero!</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($ads as $ad): ?>
            <div class="col s12 m6 l4">
                <div class="card ad-card hoverable">
                    <?php if ($ad['image_path']): ?>
                    <div class="card-image">
                        <img src="<?= h(UPLOAD_URL_PATH . $ad['image_path']) ?>"
                             alt="Imagen del anuncio <?= h($ad['title']) ?>"
                             loading="lazy">
                    </div>
                    <?php endif; ?>
                    <div class="card-content">
                        <span class="card-title activator" style="font-size:1rem; font-weight:600">
                            <?= h($ad['title']) ?>
                            <i class="material-icons right">more_vert</i>
                        </span>
                        <span class="new badge blue-grey" data-badge-caption=""><?= h($ad['category_name']) ?></span>
                        <p class="grey-text" style="font-size:.85rem; margin-top:8px">
                            <?= h(mb_substr($ad['description'], 0, 100)) ?>…
                        </p>
                        <p style="font-size:.75rem" class="grey-text">
                            <i class="material-icons tiny">person</i> <?= h($ad['username']) ?>
                            &nbsp;·&nbsp;
                            <i class="material-icons tiny">schedule</i>
                            <?= h(date('d/m/Y', strtotime($ad['created_at']))) ?>
                        </p>
                    </div>
                    <div class="card-reveal">
                        <span class="card-title grey-text text-darken-4">
                            <?= h($ad['title']) ?>
                            <i class="material-icons right">close</i>
                        </span>
                        <p><?= h($ad['description']) ?></p>
                        <?php if ($ad['email_public']): ?>
                        <p>
                            <i class="material-icons tiny">email</i>
                            <a href="mailto:<?= h($ad['email']) ?>"><?= h($ad['email']) ?></a>
                        </p>
                        <?php endif; ?>
                    </div>
                    <div class="card-action">
                        <a href="/ad.php?id=<?= (int)$ad['id'] ?>" class="blue-grey-text text-darken-2">Ver detalle</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
