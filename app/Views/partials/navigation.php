<header>
    <!-- Bootstrap navbar classes provide alignment and responsive collapse behavior. -->
    <nav class="navbar navbar-expand-lg navbar-dark app-navbar">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="/?page=dashboard&amp;role=<?= rawurlencode($currentRoleKey) ?>">
                Projectes DAW2
            </a>

            <!-- On small screens this button opens or closes the menu with Bootstrap JavaScript. -->
            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#main-navigation"
                aria-controls="main-navigation"
                aria-expanded="false"
                aria-label="Obre o tanca la navegació"
            >
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="main-navigation">
                <!-- The controller supplies links for the role verified in the PHP session. -->
                <!-- The loop creates one link per section; the current section is marked as active. -->
                <ul class="navbar-nav me-lg-auto my-3 my-lg-0">
                    <?php foreach ($navigationItems as $item): ?>
                        <?php
                        // Keep Projects active while a teacher or student views project details.
                        $isProjectDetails = in_array($currentRoleKey, ['teacher', 'student'], true)
                            && $pageKey === 'project-details';
                        $isActive = $item['key'] === $pageKey || ($isProjectDetails && $item['key'] === 'projects');
                        ?>
                        <li class="nav-item">
                            <!-- URL-encode values for safe links and HTML-escape the visible label. -->
                            <a
                                class="nav-link<?= $isActive ? ' active' : '' ?>"
                                href="/?page=<?= rawurlencode($item['key']) ?>&amp;role=<?= rawurlencode($currentRoleKey) ?>"
                                <?= $isActive ? 'aria-current="page"' : '' ?>
                            >
                                <?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <!-- Sign-out is a POST form and includes the session token checked by PHP. -->
                <form class="d-flex align-items-center" method="post" action="/?page=logout">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars((string) ($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    >
                    <button class="btn btn-sm btn-outline-light" type="submit">Tanca la sessió</button>
                </form>
            </div>
        </div>
    </nav>
</header>
