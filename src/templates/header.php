<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle ?? APP_NAME) ?> — <?= h(APP_NAME) ?></title>

    <!-- Materialize CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <style>
        body        { display: flex; min-height: 100vh; flex-direction: column; background: #f5f5f5; }
        main        { flex: 1 0 auto; }
        nav .brand-logo { font-weight: 700; }
        .ad-card    { height: 100%; }
        .ad-card .card-image img { height: 200px; object-fit: cover; width: 100%; }
        .card-panel { border-radius: 4px; }
        footer      { background-color: #263238; }
    </style>
</head>
<body>

<!-- Barra de navegación -->
<nav class="blue-grey darken-3">
    <div class="nav-wrapper container">
        <a href="/index.php" class="brand-logo">
            <i class="material-icons left">campaign</i><?= h(APP_NAME) ?>
        </a>
        <a href="#" data-target="mobile-nav" class="sidenav-trigger"><i class="material-icons">menu</i></a>

        <ul class="right hide-on-med-and-down">
            <li><a href="/index.php"><i class="material-icons left">home</i>Inicio</a></li>
            <?php if (isLoggedIn()): ?>
                <li><a href="/create-ad.php"><i class="material-icons left">add_circle</i>Publicar</a></li>
                <li><a href="/logout.php"
                       onclick="return confirm('¿Cerrar sesión?')">
                    <i class="material-icons left">logout</i><?= h($_SESSION['username']) ?></a></li>
            <?php else: ?>
                <li><a href="/login.php"><i class="material-icons left">login</i>Ingresar</a></li>
                <li><a href="/register.php"><i class="material-icons left">person_add</i>Registrarse</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<!-- Menú lateral (móvil) -->
<ul class="sidenav" id="mobile-nav">
    <li><a href="/index.php"><i class="material-icons">home</i>Inicio</a></li>
    <?php if (isLoggedIn()): ?>
        <li><a href="/create-ad.php"><i class="material-icons">add_circle</i>Publicar anuncio</a></li>
        <li><a href="/logout.php"><i class="material-icons">logout</i>Cerrar sesión</a></li>
    <?php else: ?>
        <li><a href="/login.php"><i class="material-icons">login</i>Ingresar</a></li>
        <li><a href="/register.php"><i class="material-icons">person_add</i>Registrarse</a></li>
    <?php endif; ?>
</ul>

<main>
<div class="container" style="margin-top: 30px;">
<?= renderFlash() ?>
