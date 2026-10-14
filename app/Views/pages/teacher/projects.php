<section aria-labelledby="teacher-projects-title">
    <div class="mb-4"><p class="eyebrow mb-2">Mòdul docent</p><h1 class="h2 mb-1" id="teacher-projects-title">Els meus projectes</h1><p class="text-secondary mb-0">Projectes assignats al teu compte de professorat.</p></div>

    <!-- Each sample project links to its details and the grading screen for that project. -->
    <div class="row g-3">
        <?php foreach ($teacherProjects as $project): ?>
            <div class="col-12 col-lg-6"><article class="card border-0 shadow-sm h-100"><div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-2"><div><p class="small text-secondary mb-1"><?= htmlspecialchars($project['code'], ENT_QUOTES, 'UTF-8') ?></p><h2 class="h4 mb-0"><?= htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8') ?></h2></div><span class="badge text-bg-success"><?= htmlspecialchars($project['status'], ENT_QUOTES, 'UTF-8') ?></span></div>
                <p class="text-secondary"><?= htmlspecialchars($project['description'], ENT_QUOTES, 'UTF-8') ?></p>
                <dl class="row small mb-4"><dt class="col-6">Grup</dt><dd class="col-6"><?= htmlspecialchars($project['group'], ENT_QUOTES, 'UTF-8') ?></dd><dt class="col-6">Alumnes associats</dt><dd class="col-6"><?= count($project['studentIds']) ?></dd><dt class="col-6">Ítems avaluables</dt><dd class="col-6"><?= count($project['itemIds']) ?></dd></dl>
                <div class="d-flex flex-wrap gap-2 mt-auto"><a class="btn btn-outline-secondary" href="/?page=project-details&amp;role=teacher&amp;project_id=<?= rawurlencode($project['id']) ?>">Obri els detalls</a><a class="btn btn-primary" href="/?page=grades&amp;role=teacher&amp;project_id=<?= rawurlencode($project['id']) ?>">Qualifica l’alumnat</a></div>
            </div></article></div>
        <?php endforeach; ?>
    </div>

    <div class="alert alert-info mt-4 mb-0" role="status">La llista és una demostració dels projectes assignats; les dades provenen del controlador PHP.</div>
</section>
