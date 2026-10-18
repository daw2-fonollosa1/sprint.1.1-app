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
    <!-- The shared shell places the role navigation beside each server-rendered page. -->
    <div class="app-shell">
        <?php require dirname(__DIR__) . '/partials/navigation.php'; ?>

        <div class="app-page">
            <!-- On mobile, open the same navigation panel as a Bootstrap offcanvas menu. -->
            <header class="app-mobile-header d-lg-none">
                <div class="container d-flex align-items-center justify-content-between py-2">
                    <a class="navbar-brand fw-semibold mb-0" href="/?page=dashboard&amp;role=<?= rawurlencode($currentRoleKey) ?>">
                        Projectes DAW2
                    </a>
                    <button
                        class="btn btn-outline-primary"
                        type="button"
                        data-bs-toggle="offcanvas"
                        data-bs-target="#main-navigation"
                        aria-controls="main-navigation"
                        aria-label="Obre la navegació"
                    >
                        Menú
                    </button>
                </div>
            </header>

            <!-- The content area expands to fill the space beside the desktop sidebar. -->
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

            <!-- Keep the footer aligned with the page content rather than the sidebar. -->
            <footer class="container app-footer py-4">
                <p class="mb-0">Projecte de gestió de projectes globalitzats · DAW2</p>
            </footer>
        </div>
    </div>

    <!-- Bootstrap's JavaScript bundle makes the responsive navigation toggle open and close. -->
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"
    ></script>
    <?php if ($currentRoleKey === 'admin' && $pageKey === 'users'): ?>
        <!-- This script only filters server-rendered users and prepares normal PHP forms. -->
        <script src="/assets/js/admin-users.js" defer></script>
    <?php endif; ?>
</body>
</html>
