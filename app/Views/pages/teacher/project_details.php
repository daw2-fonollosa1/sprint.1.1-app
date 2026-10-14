<?php
// Translate the project ID relationships into names for the detail lists.
$projectStudents = $selectedProject === null
    ? []
    : array_filter($teacherDemoData['users'], static fn (array $user): bool => in_array($user['id'], $selectedProject['studentIds'], true));
$projectItems = $selectedProject === null
    ? []
    : array_filter($teacherDemoData['items'], static fn (array $item): bool => in_array($item['id'], $selectedProject['itemIds'], true));
$firstStudentId = $projectStudents === [] ? '' : array_values($projectStudents)[0]['id'];
$firstItemId = $projectItems === [] ? '' : array_values($projectItems)[0]['id'];
?>
<section aria-labelledby="project-details-title">
    <?php if ($selectedProject === null): ?>
        <div class="alert alert-warning" role="status"><h1 class="h4">No hi ha projectes assignats</h1><p class="mb-0">Quan tingues un projecte assignat, els seus detalls apareixeran ací.</p></div>
    <?php else: ?>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 border-bottom pb-3 mb-4">
            <div><p class="eyebrow mb-2">Projectes / <?= htmlspecialchars($selectedProject['code'], ENT_QUOTES, 'UTF-8') ?></p><h1 class="h2 mb-1" id="project-details-title">Detalls del projecte</h1><p class="text-secondary mb-0"><?= htmlspecialchars($selectedProject['name'], ENT_QUOTES, 'UTF-8') ?></p></div>
            <div class="d-flex flex-wrap gap-2"><a class="btn btn-outline-secondary" href="/?page=projects&amp;role=teacher">Torna als projectes</a><a class="btn btn-primary" href="/?page=grades&amp;role=teacher&amp;project_id=<?= rawurlencode($selectedProject['id']) ?>">Obri qualificacions</a></div>
        </div>

        <div class="row g-4">
            <!-- The left card shows the project fields from the project requirements. -->
            <div class="col-12 col-lg-5"><article class="card border-0 shadow-sm h-100"><div class="card-body">
                <div class="d-flex justify-content-between align-items-center"><h2 class="h4">Informació general</h2><span class="badge text-bg-success"><?= htmlspecialchars($selectedProject['status'], ENT_QUOTES, 'UTF-8') ?></span></div>
                <dl class="mt-3 mb-0"><dt>Codi del projecte</dt><dd><?= htmlspecialchars($selectedProject['code'], ENT_QUOTES, 'UTF-8') ?></dd><dt>Nom del projecte</dt><dd><?= htmlspecialchars($selectedProject['name'], ENT_QUOTES, 'UTF-8') ?></dd><dt>Grup</dt><dd><?= htmlspecialchars($selectedProject['group'], ENT_QUOTES, 'UTF-8') ?></dd><dt>Descripció</dt><dd class="mb-0"><?= nl2br(htmlspecialchars($selectedProject['description'], ENT_QUOTES, 'UTF-8')) ?></dd></dl>
            </div></article></div>

            <div class="col-12 col-lg-7">
                <!-- Show the students directly related to this project, with a grading shortcut. -->
                <article class="card border-0 shadow-sm mb-4"><div class="card-body">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3"><h2 class="h4 mb-0">Alumnat assignat</h2><span class="badge text-bg-light border"><?= count($projectStudents) ?> alumnes</span></div>
                    <?php if ($projectStudents === []): ?><p class="text-secondary mb-0">Encara no hi ha alumnat assignat.</p><?php else: ?>
                        <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th scope="col">Nom complet</th><th scope="col">Grup</th><th scope="col">Acció</th></tr></thead><tbody>
                            <?php foreach ($projectStudents as $student): ?><tr><td class="fw-semibold"><?= htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($student['group'], ENT_QUOTES, 'UTF-8') ?></td><td><a class="btn btn-sm btn-outline-primary" href="/?page=grades&amp;role=teacher&amp;project_id=<?= rawurlencode($selectedProject['id']) ?>&amp;student_id=<?= rawurlencode($student['id']) ?>&amp;item_id=<?= rawurlencode($firstItemId) ?>">Qualifica</a></td></tr><?php endforeach; ?>
                        </tbody></table></div>
                    <?php endif; ?>
                </div></article>

                <!-- The item list identifies which evaluation criteria belong to this project. -->
                <article class="card border-0 shadow-sm"><div class="card-body">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3"><h2 class="h4 mb-0">Ítems avaluables</h2><span class="badge text-bg-light border"><?= count($projectItems) ?> ítems</span></div>
                    <?php if ($projectItems === []): ?><p class="text-secondary mb-0">Aquest projecte encara no té ítems avaluables.</p><?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($projectItems as $item): ?><a class="list-group-item list-group-item-action px-0" href="/?page=grades&amp;role=teacher&amp;project_id=<?= rawurlencode($selectedProject['id']) ?>&amp;student_id=<?= rawurlencode($firstStudentId) ?>&amp;item_id=<?= rawurlencode($item['id']) ?>"><span class="fw-semibold"><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></span><span class="small text-secondary d-block"><?= htmlspecialchars($item['code'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8') ?></span></a><?php endforeach; ?>
                        </div>
                        <a class="btn btn-outline-primary mt-3" href="/?page=grades&amp;role=teacher&amp;project_id=<?= rawurlencode($selectedProject['id']) ?>&amp;student_id=<?= rawurlencode($firstStudentId) ?>&amp;item_id=<?= rawurlencode($firstItemId) ?>">Qualifica aquest projecte</a>
                    <?php endif; ?>
                </div></article>
            </div>
        </div>
    <?php endif; ?>
</section>
