<section aria-labelledby="page-title">
    <!-- Shared page heading; section-specific content will replace the card below. -->
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

    <div class="alert alert-info" role="status">
        Aquesta és una previsualització de la interfície. Encara no hi ha inici de sessió ni control real de permisos.
    </div>

    <div class="card app-content-card">
        <div class="card-body p-4 p-lg-5">
            <h2 class="h4">Contingut de la secció</h2>
            <p class="text-secondary mb-0">
                Aquest espai compartit es completarà amb la informació i les accions de cada secció.
            </p>
        </div>
    </div>
</section>
