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

    <!-- The controller chooses the title and text; this shared layout gives sections the same frame. -->
    <main
        class="container app-main py-4 py-lg-5"
        <?= $currentRoleKey === 'admin' ? 'data-admin-page="' . htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8') . '"' : '' ?>
        <?= $currentRoleKey === 'teacher' ? 'data-teacher-page="' . htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8') . '"' : '' ?>
    >
        <!-- The controller chooses a dedicated admin screen or the shared placeholder for other roles. -->
        <?php require dirname(__DIR__) . $pageView; ?>
    </main>

    <!-- Keep a consistent footer at the bottom of the shared application layout. -->
    <footer class="container app-footer py-4">
        <p class="mb-0">Projecte de gestió de projectes globalitzats · DAW2</p>
    </footer>

    <!-- Bootstrap's bundle powers interactive components such as the mobile navbar. -->
    <!-- Bootstrap's JavaScript bundle makes the responsive navigation toggle open and close. -->
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"
    ></script>
    <?php if ($adminDemoData !== null && in_array($pageKey, ['projects', 'evaluable-items'], true)): ?>
        <!-- Make PHP's sample records available to the browser demo as safe JSON data. -->
        <script type="application/json" id="admin-demo-data">
            <?= json_encode($adminDemoData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?>
        </script>
    <?php endif; ?>
    <?php if ($teacherDemoData !== null): ?>
        <!-- Give the teacher demonstration its own sample data without changing PHP page routing. -->
        <script type="application/json" id="teacher-demo-data">
            <?= json_encode($teacherDemoData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?>
        </script>
    <?php endif; ?>
    <!-- This small shared script enhances the role selector after HTML parsing. -->
    <script src="/assets/js/app.js" defer></script>
    <?php if ($currentRoleKey === 'admin' && in_array($pageKey, ['projects', 'evaluable-items'], true)): ?>
        <!-- Load shared sample-data helpers only for the sections that still use browser-only demonstrations. -->
        <script src="/assets/js/admin-common.js" defer></script>
        <?php if ($pageKey === 'projects'): ?>
            <script src="/assets/js/admin-projects.js" defer></script>
        <?php else: ?>
            <script src="/assets/js/admin-items.js" defer></script>
        <?php endif; ?>
    <?php endif; ?>
    <?php if ($currentRoleKey === 'admin' && $pageKey === 'users'): ?>
        <!-- User management already receives live rows from PHP/PDO, so it does not get demo JSON.
             This script only filters server-rendered users and prepares normal PHP forms. -->
        <script src="/assets/js/admin-users.js" defer></script>
    <?php endif; ?>
    <?php if ($currentRoleKey === 'teacher'): ?>
        <!-- Keep teacher project and grading interactions in a focused script file. -->
        <script src="/assets/js/teacher.js" defer></script>
    <?php endif; ?>
</body>
</html>
