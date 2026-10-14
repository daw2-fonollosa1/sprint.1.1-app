<section aria-labelledby="student-project-details-title">
    <?php if ($selectedProject === null): ?>
        <!-- Invalid or unassigned project IDs never reveal another student's project details. -->
        <div class="alert alert-warning" role="status">
            <h1 class="h4">Projecte no disponible</h1>
            <p class="mb-0">Aquest projecte no està assignat a l’alumne de demostració.</p>
        </div>
        <a class="btn btn-outline-primary" href="/?page=projects&amp;role=student">Torna als meus projectes</a>
    <?php else: ?>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 border-bottom pb-3 mb-4">
            <div>
                <p class="eyebrow mb-2">Els meus projectes / <?= htmlspecialchars($selectedProject['code'], ENT_QUOTES, 'UTF-8') ?></p>
                <h1 class="h2 mb-1" id="student-project-details-title"><?= htmlspecialchars($selectedProject['name'], ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="text-secondary mb-0">Detalls del projecte assignat</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-secondary" href="/?page=projects&amp;role=student">Torna als projectes</a>
                <a class="btn btn-primary" href="/?page=grades&amp;role=student&amp;project_id=<?= rawurlencode($selectedProject['id']) ?>">Consulta les qualificacions</a>
            </div>
        </div>

        <div class="row g-4">
            <!-- Project fields come from the same sample records used by the other role previews. -->
            <div class="col-12 col-lg-5">
                <article class="card border-0 shadow-sm h-100"><div class="card-body">
                    <div class="d-flex justify-content-between align-items-center gap-2"><h2 class="h4">Informació del projecte</h2><span class="badge text-bg-success"><?= htmlspecialchars($selectedProject['status'], ENT_QUOTES, 'UTF-8') ?></span></div>
                    <dl class="mt-3 mb-0">
                        <dt>Codi</dt><dd><?= htmlspecialchars($selectedProject['code'], ENT_QUOTES, 'UTF-8') ?></dd>
                        <dt>Nom</dt><dd><?= htmlspecialchars($selectedProject['name'], ENT_QUOTES, 'UTF-8') ?></dd>
                        <dt>Grup</dt><dd><?= htmlspecialchars($selectedProject['group'], ENT_QUOTES, 'UTF-8') ?></dd>
                        <dt>Descripció</dt><dd class="mb-0"><?= nl2br(htmlspecialchars($selectedProject['description'], ENT_QUOTES, 'UTF-8')) ?></dd>
                    </dl>
                </div></article>
            </div>

            <!-- List only the evaluable items attached to this project, with this student's own results. -->
            <div class="col-12 col-lg-7">
                <article class="card border-0 shadow-sm"><div class="card-body">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3"><h2 class="h4 mb-0">Ítems avaluables</h2><span class="badge text-bg-light border"><?= count($selectedProject['itemIds']) ?> ítems</span></div>
                    <div class="list-group list-group-flush">
                        <?php foreach ($studentGradeRows as $gradeRow): ?>
                            <?php if ($gradeRow['projectId'] === $selectedProject['id']): ?>
                                <article class="list-group-item px-0">
                                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start gap-2">
                                        <div><h3 class="h6 mb-1"><?= htmlspecialchars($gradeRow['itemName'], ENT_QUOTES, 'UTF-8') ?></h3><p class="small text-secondary mb-2"><?= htmlspecialchars($gradeRow['itemCode'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($gradeRow['itemDescription'], ENT_QUOTES, 'UTF-8') ?></p></div>
                                        <?php if ($gradeRow['average'] === null): ?>
                                            <span class="badge text-bg-light border">Pendent</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-dark fs-6"><?= number_format($gradeRow['average'], 2, ',', '') ?> / 10</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($gradeRow['average'] !== null): ?>
                                        <p class="small mb-1">Qualificacions del professorat:</p>
                                        <ul class="small mb-1">
                                            <?php foreach ($gradeRow['teacherGrades'] as $teacherGrade): ?>
                                                <li><?= htmlspecialchars($teacherGrade['name'], ENT_QUOTES, 'UTF-8') ?>: <?= number_format($teacherGrade['value'], 1, ',', '') ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <?php if ($gradeRow['gradeCount'] > 1): ?>
                                            <p class="small text-secondary mb-0">Mitjana aritmètica: (<?= implode(' + ', array_map(static fn (array $grade): string => number_format($grade['value'], 1, ',', ''), $gradeRow['teacherGrades'])) ?>) ÷ <?= $gradeRow['gradeCount'] ?> = <?= number_format($gradeRow['average'], 2, ',', '') ?>.</p>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <p class="small text-secondary mb-0">Encara no s’ha introduït cap qualificació per a aquest ítem.</p>
                                    <?php endif; ?>
                                </article>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div></article>
            </div>
        </div>

        <div class="alert alert-info mt-4 mb-0" role="status">Les notes i els detalls són dades de demostració temporals; no es desen permanentment.</div>
    <?php endif; ?>
</section>
