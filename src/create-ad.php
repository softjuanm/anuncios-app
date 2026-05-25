<?php
/**
 * create-ad.php — Publicar un nuevo anuncio.
 *
 * Seguridad aplicada:
 *  - Requiere autenticación (requireLogin).
 *  - CSRF token verificado.
 *  - Validación server-side de todos los campos (tipo, longitud, valores nulos/vacíos).
 *  - Subida de imagen validada por MIME real (finfo), no por extensión del nombre.
 *  - Nombre de archivo aleatorio para evitar sobreescrituras y enumeración.
 *  - category_id validado como entero existente en BD (evita foreign-key injection).
 *  - Consulta parametrizada para INSERT.
 *  - Mensajes de error genéricos; detalles técnicos en log.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

requireLogin();

$errors  = [];
$oldData = ['title' => '', 'description' => '', 'body' => '', 'category_id' => ''];

$pdo  = getDB();
$cats = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    try {
        // Validar campos de texto
        $title       = validateString($_POST['title']       ?? '', 'Título',       5, 150);
        $description = validateString($_POST['description'] ?? '', 'Descripción',  10, 300);
        $body        = validateString($_POST['body']        ?? '', 'Texto del anuncio', 10, 5000);
        $categoryId  = validateInt($_POST['category_id']   ?? '', 'Categoría');

        $oldData = compact('title', 'description', 'body', 'category_id');

        // Verificar que la categoría existe en la BD (no confiar solo en el valor numérico)
        $catCheck = $pdo->prepare("SELECT id FROM categories WHERE id = ? LIMIT 1");
        $catCheck->execute([$categoryId]);
        if (!$catCheck->fetch()) {
            throw new InvalidArgumentException('La categoría seleccionada no existe.');
        }

        // Procesar imagen (opcional)
        $imageFile = $_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE];
        $imagePath = handleImageUpload($imageFile);

        // Insertar anuncio con consulta parametrizada
        $stmt = $pdo->prepare("
            INSERT INTO ads (user_id, category_id, title, description, body, image_path)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $categoryId,
            $title,
            $description,
            $body,
            $imagePath,
        ]);

        $newAdId = (int) $pdo->lastInsertId();

        flashSuccess('¡Tu anuncio fue publicado con éxito!');
        header("Location: /ad.php?id={$newAdId}");
        exit;

    } catch (InvalidArgumentException $e) {
        $errors[] = $e->getMessage();
    } catch (Throwable $e) {
        error_log('[CREATE-AD] ' . $e->getMessage());
        $errors[] = 'Ocurrió un error inesperado al publicar el anuncio. Intenta más tarde.';
    }
}

$pageTitle = 'Publicar anuncio';
require __DIR__ . '/templates/header.php';
?>

<div class="row">
    <div class="col s12 m10 offset-m1 l8 offset-l2">
        <div class="card z-depth-2">
            <div class="card-content">
                <span class="card-title">
                    <i class="material-icons left blue-grey-text">campaign</i>Publicar anuncio
                </span>

                <?php foreach ($errors as $err): ?>
                    <div class="card-panel red lighten-4 red-text text-darken-4">
                        <i class="material-icons tiny">error</i> <?= h($err) ?>
                    </div>
                <?php endforeach; ?>

                <form method="POST" action="/create-ad.php"
                      enctype="multipart/form-data" novalidate>
                    <?= csrfField() ?>

                    <!-- Título -->
                    <div class="input-field">
                        <i class="material-icons prefix">title</i>
                        <input id="title" name="title" type="text"
                               value="<?= h($oldData['title']) ?>"
                               minlength="5" maxlength="150" required>
                        <label for="title">Título del anuncio *</label>
                        <span class="helper-text">5 a 150 caracteres.</span>
                    </div>

                    <!-- Categoría -->
                    <div class="input-field">
                        <i class="material-icons prefix">category</i>
                        <select id="category_id" name="category_id" required>
                            <option value="" disabled
                                <?= empty($oldData['category_id']) ? 'selected' : '' ?>>
                                Selecciona una categoría
                            </option>
                            <?php foreach ($cats as $cat): ?>
                                <option value="<?= (int)$cat['id'] ?>"
                                    <?= (string)($oldData['category_id'] ?? '') === (string)$cat['id'] ? 'selected' : '' ?>>
                                    <?= h($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label>Categoría *</label>
                    </div>

                    <!-- Descripción corta -->
                    <div class="input-field">
                        <i class="material-icons prefix">short_text</i>
                        <textarea id="description" name="description"
                                  class="materialize-textarea"
                                  minlength="10" maxlength="300"
                                  required><?= h($oldData['description']) ?></textarea>
                        <label for="description">Descripción corta *</label>
                        <span class="helper-text">Resumen visible en el listado (10 a 300 caracteres).</span>
                    </div>

                    <!-- Texto completo -->
                    <div class="input-field">
                        <i class="material-icons prefix">article</i>
                        <textarea id="body" name="body"
                                  class="materialize-textarea"
                                  minlength="10" maxlength="5000"
                                  style="min-height: 140px"
                                  required><?= h($oldData['body']) ?></textarea>
                        <label for="body">Texto completo del anuncio *</label>
                        <span class="helper-text">Descripción detallada (10 a 5000 caracteres).</span>
                    </div>

                    <!-- Imagen (opcional) -->
                    <div class="file-field input-field">
                        <div class="btn blue-grey darken-2">
                            <span><i class="material-icons left">image</i>Imagen</span>
                            <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
                        </div>
                        <div class="file-path-wrapper">
                            <input class="file-path" type="text" placeholder="JPG, PNG o WEBP · máx. 2 MB (opcional)">
                        </div>
                    </div>

                    <div class="divider" style="margin: 20px 0"></div>

                    <div class="center-align">
                        <button type="submit" class="btn blue-grey darken-3 waves-effect waves-light btn-large">
                            <i class="material-icons left">publish</i>Publicar anuncio
                        </button>
                        <a href="/index.php" class="btn-flat waves-effect" style="margin-left:10px">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
