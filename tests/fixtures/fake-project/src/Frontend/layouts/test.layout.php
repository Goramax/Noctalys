<header>
    <h1> <?=  isset($page_title) && $page_title ? $page_title : "Noctalys" ?></h1>
</header>
<main class="container">
    <?= $_view ?>
</main>
<footer>
    <p>&copy; <?= date('Y') ?> Noctalys - Tous droits réservés</p>
</footer>


<style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f4f4f4;
    }

    header {
        background-color: #333;
        color: white;
        padding: 10px 0;
        text-align: center;
    }

    .container {
        max-width: 800px;
        margin: 20px auto;
        padding: 20px;
        background-color: white;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .file-content{
        background-color: #f9f9f9;
        padding: 10px;
        border-radius: 5px;
        border: 1px solid #ddd;
        margin-bottom: 20px;
        font-family: monospace;
        white-space: pre-wrap;
        overflow-x: auto;
    }

    footer {
        text-align: center;
        padding: 10px 0;
        background-color: #333;
        color: white;
    }
    h1 {
        font-size: 2em;
        margin-bottom: 20px;
    }
    h2 {
        font-size: 1.5em;
        margin-bottom: 10px;
    }
    p {
        font-size: 1em;
        line-height: 1.5;
        margin-bottom: 10px;
    }
</style>