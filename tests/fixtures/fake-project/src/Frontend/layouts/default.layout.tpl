<header>
    <h1>{if isset($page_title) && $page_title}{$page_title}{else}Noctalys{/if}</h1>
</header>
<main class="container">
    {$_view}
</main>
<footer>
    <p>&copy; {$smarty.now|date_format:"%Y"} Noctalys - Tous droits réservés</p>
</footer>

<style>
    .durationdebug {
        position: fixed;
        bottom: 0;
        left: 0;
        background-color: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 10px;
        font-size: 12px;
        z-index: 1000;
    }
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background-color: #f5f5f5;
        color: #333;
        line-height: 1.6;
    }

    .container {
        width: 80%;
        margin: 0 auto;
        padding: 20px;
    }

    header {
        background-color: #2c3e50;
        color: #ecf0f1;
        padding: 20px 0;
        text-align: center;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    main {
        background-color: white;
        border-radius: 8px;
        padding: 30px;
        margin: 20px 0;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
    }

    h1 {
        color: rgb(255, 255, 255);
        margin-bottom: 20px;
        font-size: 2.5rem;
    }

    h2 {
        color: #2c3e50;
        margin-bottom: 20px;
        font-size: 2.5rem;
    }

    p.message {
        font-size: 1.2rem;
        margin-bottom: 20px;
        line-height: 1.8;
    }

    footer {
        text-align: center;
        padding: 20px 0;
        color: #7f8c8d;
        font-size: 0.9rem;
    }

    .primary-btn {
        margin: auto auto;
        width: fit-content;
        display: inline-block;
        padding: 10px 20px;
        background-color: #2c3e50;
        border-radius: 5px;
        margin-top: 20px;
    }
    .primary-btn a {
        color: #ecf0f1;
        text-decoration: none;
    }

    @media (max-width: 768px) {
        .container {
            width: 95%;
        }

        h1 {
            font-size: 2rem;
        }
    }
</style>
