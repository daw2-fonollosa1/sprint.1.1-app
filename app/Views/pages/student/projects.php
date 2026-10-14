<section aria-labelledby="student-projects-title">
    <div class="mb-4">
        <p class="eyebrow mb-2">Mòdul de l’alumnat</p>
        <h1 class="h2 mb-1" id="student-projects-title">Els meus projectes</h1>
        <p class="text-secondary mb-0">Projectes assignats a <?= htmlspecialchars($studentDemoData['student']['name'], ENT_QUOTES, 'UTF-8') ?>.</p>
    </div>

    <!-- Each card describes one assigned project and opens its server-rendered detail page. -->
    <div class="row g-3">
        <?php foreach ($studentDemoData['projects'] as $project): ?>
            <?php
            // Count the evaluated and pending items for this project from its own rows.
            $projectRows = array_values(array_filter(
                $studentGradeRows,
                static fn (array $row): bool => $row['projectId'] === $project['id'],
            ));
            $projectGradedCount = count(array_filter(
                $projectRows,
                static fn (array $row): bool => $row['average'] !== null,
            ));
            ?>
            <div class="col-12 col-lg-6">
                <article class="card border-0 shadow-sm h-100"><div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                        <div><p class="small text-secondary mb-1"><?= htmlspecialchars($project['code'], ENT_QUOTES, 'UTF-8') ?></p><h2 class="h4 mb-0"><?= htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8') ?></h2></div>
                        <span class="badge text-bg-success"><?= htmlspecialchars($project['status'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <p class="text-secondary"><?= htmlspecialchars($project['description'], ENT_QUOTES, 'UTF-8') ?></p>
                    <dl class="row small mb-4">
                        <dt class="col-7">Ítems avaluables</dt><dd class="col-5"><?= count($project['itemIds']) ?></dd>
                        <dt class="col-7">Ítems qualificats</dt><dd class="col-5"><?= $projectGradedCount ?> / <?= count($projectRows) ?></dd>
                    </dl>
                    <a class="btn btn-primary mt-auto align-self-start" href="/?page=project-details&amp;role=student&amp;project_id=<?= rawurlencode($project['id']) ?>">Obri els detalls</a>
                </div></article>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="alert alert-info mt-4 mb-0" role="status">La llista mostra només els projectes assignats a l’alumne de demostració seleccionat.</div>
</section>
