<header>
    <nav class="navbar navbar-expand-lg navbar-dark app-navbar">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="/?page=dashboard&amp;role=<?= rawurlencode($currentRoleKey) ?>">
                Projectes DAW2
            </a>

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
                <!-- The controller supplies only the links for the selected preview role. -->
                <ul class="navbar-nav me-lg-auto my-3 my-lg-0">
                    <?php foreach ($navigationItems as $item): ?>
                        <?php $isActive = $item['key'] === $pageKey; ?>
                        <li class="nav-item">
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

                <!-- This GET form previews role-specific navigation; it is not login. -->
                <form class="role-preview d-flex align-items-center gap-2" method="get" action="/">
                    <input type="hidden" name="page" value="dashboard">
                    <label class="form-label mb-0" for="role-preview">Previsualitza el rol</label>
                    <select class="form-select form-select-sm" id="role-preview" name="role" data-role-preview>
                        <?php foreach ($roleOptions as $roleKey => $roleLabel): ?>
                            <option value="<?= htmlspecialchars($roleKey, ENT_QUOTES, 'UTF-8') ?>"<?= $roleKey === $currentRoleKey ? ' selected' : '' ?>>
                                <?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-sm btn-outline-light" type="submit">Aplica</button>
                </form>
            </div>
        </div>
    </nav>
</header>
