<section aria-labelledby="page-title">
    <!-- This reusable view receives the selected role, title, and description from the controller. -->
    <!-- The shared heading and card can later be replaced with real content for each section. -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
        <div>
            <p class="eyebrow mb-2">Vista de <?= htmlspecialchars($currentRole['label'], ENT_QUOTES, 'UTF-8') ?></p>
            <h1 class="display-6 fw-semibold mb-2" id="page-title">
                <?= htmlspecialchars($page['title'], ENT_QUOTES, 'UTF-8') ?>
            </h1>
            <p class="lead text-secondary mb-0">
                <?= htmlspecialchars($page['description'], ENT_QUOTES, 'UTF-8') ?>
            </p>
        </div>
        <span class="badge rounded-pill text-bg-light border">Estructura inicial</span>
    </div>

    <!-- Explain that this individual section has not been implemented yet. -->
    <div class="alert alert-info" role="status">
        La sessió i el rol s’han verificat. Aquesta secció encara està pendent d’implementar.
    </div>

    <!-- This card marks where future section-specific content will be placed. -->
    <div class="card app-content-card">
        <div class="card-body p-4 p-lg-5">
            <h2 class="h4">Contingut de la secció</h2>
            <p class="text-secondary mb-0">
                Aquest espai compartit es completarà amb la informació i les accions de cada secció.
            </p>
        </div>
    </div>
</section>
