<!-- This responsive sidebar stays beside the page on desktop and becomes an offcanvas on mobile. -->
<aside
    class="offcanvas-lg offcanvas-start app-sidebar bg-primary text-white"
    tabindex="-1"
    id="main-navigation"
    aria-label="Navegació principal"
>
    <!-- Bootstrap shows this heading and close button when the mobile panel is open. -->
    <div class="offcanvas-header app-sidebar-header">
        <h2 class="offcanvas-title h5 mb-0" id="main-navigation-title">Menú principal</h2>
        <button
            type="button"
            class="btn-close btn-close-white"
            data-bs-dismiss="offcanvas"
            aria-label="Tanca la navegació"
        ></button>
    </div>

    <div class="offcanvas-body app-sidebar-body">
        <?php
        // Show a Catalan role name from the role already checked by the server.
        $roleNames = [
            'admin' => 'Administrador',
            'teacher' => 'Professorat',
            'student' => 'Alumnat',
        ];
        $currentRoleName = $roleNames[$currentRoleKey] ?? 'Usuari';
        ?>

        <!-- The brand card gives the shared navigation a clear title and visual identity. -->
        <div class="app-sidebar-brand-card rounded-4 p-3">
            <a
                class="app-sidebar-brand d-flex align-items-center gap-3 text-decoration-none"
                href="/?page=dashboard&amp;role=<?= rawurlencode($currentRoleKey) ?>"
            >
                <span class="app-sidebar-logo d-inline-flex align-items-center justify-content-center rounded-3" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                        <path d="m4.5 7.8 7.5 4.3 7.5-4.3M12 12.2V20" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                    </svg>
                </span>
                <span class="d-flex flex-column">
                    <span class="app-sidebar-kicker">PROJECTE GLOBALITZAT</span>
                    <span class="app-sidebar-title">Gestió de projectes</span>
                </span>
            </a>

            <!-- Identify the active workspace without using a role value sent by the browser. -->
            <div class="app-sidebar-role d-flex align-items-center gap-2 mt-3 rounded-3 px-2 py-2">
                <span class="app-sidebar-role-dot" aria-hidden="true"></span>
                <span class="d-flex flex-column">
                    <span class="app-sidebar-role-label">Espai actual</span>
                    <span class="app-sidebar-role-name"><?= htmlspecialchars($currentRoleName, ENT_QUOTES, 'UTF-8') ?></span>
                </span>
                <span class="badge rounded-pill text-bg-light text-primary ms-auto">DAW2</span>
            </div>
        </div>

        <!-- These links still come from the controller after it verifies the user's session role. -->
        <nav class="app-sidebar-navigation mt-4" aria-label="Navegació principal">
            <p class="app-sidebar-section-label mb-2 px-2">Seccions</p>
            <ul class="navbar-nav flex-column gap-1">
                <?php foreach ($navigationItems as $item): ?>
                    <?php
                    // Keep Projects active while a teacher or student views project details.
                    $isProjectDetails = in_array($currentRoleKey, ['teacher', 'student'], true)
                        && $pageKey === 'project-details';
                    $isActive = $item['key'] === $pageKey || ($isProjectDetails && $item['key'] === 'projects');
                    ?>
                    <li class="nav-item">
                        <!-- Encode URL values and escape labels before placing them in the page. -->
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
        </nav>

        <!-- Sign-out remains a protected POST form and sits below the navigation links. -->
        <form class="app-sidebar-logout mt-auto pt-4" method="post" action="/?page=logout">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars((string) ($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
            >
            <button class="btn btn-light app-logout-button w-100 d-flex align-items-center gap-3 text-start" type="submit">
                <span class="app-logout-icon d-inline-flex align-items-center justify-content-center rounded-3" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 5H5.5A1.5 1.5 0 0 0 4 6.5v11A1.5 1.5 0 0 0 5.5 19H10M14 8l4 4-4 4M18 12H9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                <span class="d-flex flex-column flex-grow-1">
                    <span class="app-logout-title">Tanca la sessió</span>
                    <span class="app-logout-caption">Surt del teu compte</span>
                </span>
                <span class="app-logout-arrow" aria-hidden="true">→</span>
            </button>
        </form>
    </div>
</aside>
