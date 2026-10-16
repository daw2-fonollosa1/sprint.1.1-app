<!doctype html>
<!-- Mark the page language so browsers and assistive technology use Catalan rules. -->
<html lang="ca">
<head>
    <!-- Declare text encoding and mobile viewport behavior for every application section. -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Aplicació de gestió de projectes globalitzats">
    <title><?= htmlspecialchars($page['title'], ENT_QUOTES, 'UTF-8') ?> · Projectes DAW2</title>

    <!-- Bootstrap supplies reusable grid, spacing, navigation, and component styles. -->
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

    <!-- This shared layout gives every role page the same responsive frame. -->
    <main class="container app-main py-4 py-lg-5">
        <?php if ($readAccessError !== ''): ?>
            <div class="alert alert-warning" role="alert">
                <h1 class="h5">Accés a les dades</h1>
                <p class="mb-0"><?= htmlspecialchars($readAccessError, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        <?php elseif ($readDatabaseError !== ''): ?>
            <div class="alert alert-danger" role="alert">
                <h1 class="h5">No s’han pogut carregar les dades</h1>
                <p class="mb-0"><?= htmlspecialchars($readDatabaseError, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        <?php else: ?>
            <!-- PHP includes the selected view after the controller has prepared its data. -->
            <?php require dirname(__DIR__) . $pageView; ?>
        <?php endif; ?>
    </main>

    <!-- Keep a consistent footer at the bottom of the shared application layout. -->
    <footer class="container app-footer py-4">
        <p class="mb-0">Projecte de gestió de projectes globalitzats · DAW2</p>
    </footer>

    <!-- Bootstrap's JavaScript bundle makes the responsive navigation toggle open and close. -->
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"
    ></script>
    <!-- This small shared script enhances the role selector after HTML parsing. -->
    <script src="/assets/js/app.js" defer></script>
    <?php if ($currentRoleKey === 'admin' && $pageKey === 'users'): ?>
        <!-- This script only filters server-rendered users and prepares normal PHP forms. -->
        <script src="/assets/js/admin-users.js" defer></script>
    <?php endif; ?>
</body>
</html>
