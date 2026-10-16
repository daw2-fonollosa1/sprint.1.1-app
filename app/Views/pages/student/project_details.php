<?php

// Keep grade formatting readable without rounding the database's average value.
$escapeStudentValue = static fn (string|int $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$formatGrade = static function (string|int|float $value): string {
    $value = rtrim(rtrim((string) $value, '0'), '.');

    return str_replace('.', ',', $value);
};
?>
<section aria-labelledby="student-project-details-title">
    <?php if ($selectedProject === null): ?>
        <div class="alert alert-warning" role="status">
            <h1 class="h4">Projecte no disponible</h1>
            <p class="mb-0">El projecte no existeix o no està assignat al teu compte.</p>
        </div>
        <a class="btn btn-outline-primary" href="/?page=projects&amp;role=student">Torna als meus projectes</a>
    <?php else: ?>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 border-bottom pb-3 mb-4">
            <div>
                <p class="eyebrow mb-2">Els meus projectes / <?= $escapeStudentValue($selectedProject['code']) ?></p>
                <h1 class="h2 mb-1" id="student-project-details-title"><?= $escapeStudentValue($selectedProject['name']) ?></h1>
                <p class="text-secondary mb-0">Detalls del projecte assignat</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-secondary" href="/?page=projects&amp;role=student">Torna als projectes</a>
                <a class="btn btn-primary" href="/?page=grades&amp;role=student&amp;project_id=<?= (int) $selectedProject['id'] ?>">Consulta les qualificacions</a>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-lg-5">
                <article class="card border-0 shadow-sm h-100"><div class="card-body">
                    <h2 class="h4">Informació del projecte</h2>
                    <dl class="mt-3 mb-0">
                        <dt>Codi</dt><dd><?= $escapeStudentValue($selectedProject['code']) ?></dd>
                        <dt>Nom</dt><dd><?= $escapeStudentValue($selectedProject['name']) ?></dd>
                        <dt>Descripció</dt><dd class="mb-0"><?= nl2br($escapeStudentValue($selectedProject['description'])) ?></dd>
                    </dl>
                </div></article>
            </div>

            <div class="col-12 col-lg-7">
                <article class="card border-0 shadow-sm"><div class="card-body">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                        <h2 class="h4 mb-0">Ítems avaluables</h2>
                        <span class="badge text-bg-light border"><?= count($selectedProject['items']) ?> ítems</span>
                    </div>
                    <?php if ($selectedProject['items'] === []): ?>
                        <p class="text-secondary mb-0">Aquest projecte encara no té ítems avaluables.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($selectedProject['items'] as $item): ?>
                                <article class="list-group-item px-0">
                                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start gap-2">
                                        <div>
                                            <h3 class="h6 mb-1"><?= $escapeStudentValue($item['name']) ?></h3>
                                            <p class="small text-secondary mb-2"><?= $escapeStudentValue($item['code']) ?> · <?= $escapeStudentValue($item['description']) ?></p>
                                        </div>
                                        <?php if ($item['average'] === null): ?>
                                            <span class="badge text-bg-light border">Pendent</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-dark fs-6"><?= $escapeStudentValue($formatGrade($item['average'])) ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($item['teacherGrades'] !== []): ?>
                                        <p class="small mb-1">Qualificacions del professorat:</p>
                                        <ul class="small mb-1">
                                            <?php foreach ($item['teacherGrades'] as $teacherGrade): ?>
                                                <li><?= $escapeStudentValue($teacherGrade['name']) ?>: <?= $escapeStudentValue($formatGrade($teacherGrade['value'])) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <?php if ($item['gradeCount'] > 1): ?>
                                            <?php $values = array_map($formatGrade, array_column($item['teacherGrades'], 'value')); ?>
                                            <p class="small text-secondary mb-0">Mitjana aritmètica: (<?= $escapeStudentValue(implode(' + ', $values)) ?>) ÷ <?= (int) $item['gradeCount'] ?> = <?= $escapeStudentValue($formatGrade($item['average'])) ?>.</p>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <p class="small text-secondary mb-0">Encara no s’ha introduït cap qualificació per a aquest ítem.</p>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div></article>
            </div>
        </div>
    <?php endif; ?>
</section>
