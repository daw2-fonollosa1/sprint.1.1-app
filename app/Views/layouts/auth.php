<!doctype html>
<!-- The language attribute helps browsers and assistive technologies read Catalan correctly. -->
<html lang="ca">
<head>
    <!-- UTF-8 supports Catalan accents; the viewport makes the layout fit phone screens. -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Accés a la gestió de projectes globalitzats">
    <title>Iniciar sessió · Gestió de projectes</title>

    <!-- Bootstrap provides the standard form, button, and alert styling used by the page. -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
        crossorigin="anonymous"
    >
    <!-- Load local styles after Bootstrap so the project's rules can customize its defaults. -->
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <!-- Bootstrap utilities center the form and add responsive space around it. -->
    <main class="container min-vh-100 d-flex align-items-center py-5">
        <!-- The authentication layout keeps the login page separate from the role navigation. -->
        <!-- Insert the PHP login view inside the shared authentication page frame. -->
        <?php require dirname(__DIR__) . '/pages/login.php'; ?>
    </main>

    <!-- defer waits until HTML parsing is complete before running form-validation code. -->
    <script src="/assets/js/app.js" defer></script>
</body>
</html>
