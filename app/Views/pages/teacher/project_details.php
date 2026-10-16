<?php

// The controller selects this project only from the current teacher's assigned projects.
$escapeTeacherValue = static fn (string|int $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$firstItemId = $selectedProject['items'][0]['id'] ?? '';
?>
<section aria-labelledby="project-details-title">
    <?php if ($selectedProject === null): ?>
        <div class="alert alert-warning" role="status">
            <h1 class="h4">Projecte no disponible</h1>
            <p class="mb-0">El projecte no existeix o no està assignat al teu compte.</p>
        </div>
        <a class="btn btn-outline-primary" href="/?page=projects&amp;role=teacher">Torna als projectes</a>
    <?php else: ?>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 border-bottom pb-3 mb-4">
            <div>
                <p class="eyebrow mb-2">Projectes / <?= $escapeTeacherValue($selectedProject['code']) ?></p>
                <h1 class="h2 mb-1" id="project-details-title"><?= $escapeTeacherValue($selectedProject['name']) ?></h1>
                <p class="text-secondary mb-0">Detalls del projecte assignat</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-secondary" href="/?page=projects&amp;role=teacher">Torna als projectes</a>
                <a class="btn btn-primary" href="/?page=grades&amp;role=teacher&amp;project_id=<?= (int) $selectedProject['id'] ?>">Obri qualificacions</a>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-lg-5">
                <article class="card border-0 shadow-sm h-100"><div class="card-body">
                    <h2 class="h4">Informació general</h2>
                    <dl class="mt-3 mb-0">
                        <dt>Codi del projecte</dt><dd><?= $escapeTeacherValue($selectedProject['code']) ?></dd>
                        <dt>Nom del projecte</dt><dd><?= $escapeTeacherValue($selectedProject['name']) ?></dd>
                        <dt>Descripció</dt><dd class="mb-0"><?= nl2br($escapeTeacherValue($selectedProject['description'])) ?></dd>
                    </dl>
                </div></article>
            </div>

            <div class="col-12 col-lg-7">
                <article class="card border-0 shadow-sm mb-4"><div class="card-body">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                        <h2 class="h4 mb-0">Alumnat assignat</h2>
                        <span class="badge text-bg-light border"><?= count($selectedProject['students']) ?> alumnes</span>
                    </div>
                    <?php if ($selectedProject['students'] === []): ?>
                        <p class="text-secondary mb-0">Encara no hi ha alumnat assignat.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($selectedProject['students'] as $student): ?>
                                <li class="list-group-item px-0 d-flex justify-content-between align-items-center gap-2">
                                    <span><?= $escapeTeacherValue($student['full_name']) ?></span>
                                    <?php if ($firstItemId !== ''): ?>
                                        <a class="btn btn-sm btn-outline-primary" href="/?page=grades&amp;role=teacher&amp;project_id=<?= (int) $selectedProject['id'] ?>&amp;student_id=<?= (int) $student['id'] ?>&amp;item_id=<?= (int) $firstItemId ?>">Qualifica</a>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div></article>

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
                                <a class="list-group-item list-group-item-action px-0" href="/?page=grades&amp;role=teacher&amp;project_id=<?= (int) $selectedProject['id'] ?>&amp;item_id=<?= (int) $item['id'] ?>">
                                    <span class="fw-semibold"><?= $escapeTeacherValue($item['name']) ?></span>
                                    <span class="small text-secondary d-block"><?= $escapeTeacherValue($item['code']) ?> · <?= $escapeTeacherValue($item['description']) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div></article>
            </div>
        </div>
    <?php endif; ?>
</section>
