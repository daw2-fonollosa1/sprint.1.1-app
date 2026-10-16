<?php

// The new project form and its list use the PDO data prepared by the controller.
$escapeTeacherProject = static fn (string|int $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$operationData = $createOperationsData;
$submittedProject = $operationData['submitted'];
?>
<section aria-labelledby="teacher-projects-title">
    <div class="mb-4"><p class="eyebrow mb-2">Mòdul docent</p><h1 class="h2 mb-1" id="teacher-projects-title">Els meus projectes</h1><p class="text-secondary mb-0">Projectes assignats al teu compte de professorat.</p></div>

    <?php if ($operationData['feedback'] !== ''): ?>
        <div class="alert alert-success" role="status"><?= $escapeTeacherProject($operationData['feedback']) ?></div>
    <?php endif; ?>
    <?php if ($operationData['accessError'] !== ''): ?>
        <div class="alert alert-warning" role="alert"><?= $escapeTeacherProject($operationData['accessError']) ?></div>
    <?php endif; ?>
    <?php if ($operationData['databaseError'] !== ''): ?>
        <div class="alert alert-danger" role="alert"><?= $escapeTeacherProject($operationData['databaseError']) ?></div>
    <?php endif; ?>
    <?php if ($operationData['validationErrors'] !== []): ?>
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                <?php foreach ($operationData['validationErrors'] as $error): ?>
                    <li><?= $escapeTeacherProject($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <article class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h4">Crea un projecte</h2>
            <p class="text-secondary">Quan un professor crea un projecte, també queda assignat al seu compte.</p>
            <form method="post" action="/?page=projects&amp;role=teacher">
                <input type="hidden" name="action" value="create_project">
                <div class="row g-3">
                    <div class="col-12 col-md-4"><label class="form-label" for="teacher-project-code">Codi</label><input class="form-control" id="teacher-project-code" name="code" maxlength="50" required value="<?= $escapeTeacherProject($submittedProject['code'] ?? '') ?>"></div>
                    <div class="col-12 col-md-8"><label class="form-label" for="teacher-project-name">Nom</label><input class="form-control" id="teacher-project-name" name="name" maxlength="150" required value="<?= $escapeTeacherProject($submittedProject['name'] ?? '') ?>"></div>
                    <div class="col-12"><label class="form-label" for="teacher-project-description">Descripció</label><textarea class="form-control" id="teacher-project-description" name="description" rows="3" required><?= $escapeTeacherProject($submittedProject['description'] ?? '') ?></textarea></div>
                </div>
                <button class="btn btn-primary mt-3" type="submit">Crea el projecte</button>
            </form>
        </div>
    </article>

    <article class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h4">Projectes desats i assignats al teu compte</h2>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th scope="col">Codi</th><th scope="col">Projecte</th><th scope="col">Descripció</th></tr></thead>
                    <tbody>
                        <?php foreach ($operationData['teacherProjects'] as $project): ?>
                            <tr><td><?= $escapeTeacherProject($project['code']) ?></td><td class="fw-semibold"><?= $escapeTeacherProject($project['name']) ?></td><td><?= $escapeTeacherProject($project['description']) ?></td></tr>
                        <?php endforeach; ?>
                        <?php if ($operationData['teacherProjects'] === []): ?>
                            <tr><td colspan="3" class="text-center text-secondary py-4">No hi ha projectes desats per al compte de servidor actual.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </article>

    <!-- These existing links still demonstrate the sample project detail pages. -->
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

    <div class="alert alert-info mt-4 mb-0" role="status">Les targetes següents són dades de demostració. Les creacions noves es desen a MariaDB quan hi ha una sessió docent vàlida.</div>
</section>
