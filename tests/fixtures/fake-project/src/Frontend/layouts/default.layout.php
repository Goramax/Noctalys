<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= css_path('main') ?>" />
    <script type="module" src="<?= js_path('main') ?>" defer></script>
</head>

<body>
    <header>
        <h1> <?= isset($page_title) && $page_title ? $page_title : "Noctalys" ?></h1>
    </header>
    <main class="container">
        <?= $_view ?>
    </main>
    <footer>
        <p>&copy; <?= date('Y') ?> Noctalys - Tous droits réservés</p>
    </footer>

</body>

</html>