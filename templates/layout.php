<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/pages.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <title><?= $title ?? 'EcoRide' ?></title>
</head>
<body>

<header class="container-header">
    <h1>
        <a href="/" class="site-brand" style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:10px;">
            <span class="material-icons">eco</span>
            EcoRide
        </a>
    </h1>

    <!-- Placeholder que ton JS va remplir -->
    <nav id="navbar"></nav>
</header>

<main>
    <?= $content ?>
</main>

<footer>
    <p>&copy; <?= date('Y') ?> EcoRide</p>
</footer>

<!-- On charge ton ancien navbar.js -->
<script src="/assets/js/navbar.js"></script>

</body>
</html>
