<!doctype html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Aplicació de gestió de projectes globalitzats">
    <title><?= htmlspecialchars($page['title'], ENT_QUOTES, 'UTF-8') ?> · Projectes DAW2</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
        crossorigin="anonymous"
    >
    <!-- Load project styles after Bootstrap so local rules can customize the framework. -->
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <!-- Shared navigation is included by every page using this layout. -->
    <?php require dirname(__DIR__) . '/partials/navigation.php'; ?>

    <main class="container app-main py-4 py-lg-5">
        <!-- Replace this placeholder with the selected section's real view later. -->
        <?php require dirname(__DIR__) . '/pages/placeholder.php'; ?>
    </main>

    <footer class="container app-footer py-4">
        <p class="mb-0">Projecte de gestió de projectes globalitzats · DAW2</p>
    </footer>

    <!-- Bootstrap's bundle powers interactive components such as the mobile navbar. -->
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"
    ></script>
    <!-- Defer the app script until the document has been parsed. -->
    <script src="/assets/js/app.js" defer></script>
</body>
</html>
