<?php

// This view combines the existing create form with projects loaded for the current teacher.
$escapeTeacherProject = static fn (string|int $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$operationData = $createOperationsData;
$updateData = $updateOperationsData;
$submittedProject = $operationData['submitted'];
?>
<section aria-labelledby="teacher-projects-title">
    <div class="mb-4">
        <p class="eyebrow mb-2">Mòdul docent</p>
        <h1 class="h2 mb-1" id="teacher-projects-title">Els meus projectes</h1>
        <p class="text-secondary mb-0">Projectes assignats al teu compte de professorat.</p>
    </div>

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
    <?php if ($updateData['feedback'] !== ''): ?>
        <div class="alert alert-success" role="status"><?= $escapeTeacherProject($updateData['feedback']) ?></div>
    <?php endif; ?>
    <?php if ($updateData['accessError'] !== ''): ?>
        <div class="alert alert-warning" role="alert"><?= $escapeTeacherProject($updateData['accessError']) ?></div>
    <?php endif; ?>
    <?php if ($updateData['databaseError'] !== ''): ?>
        <div class="alert alert-danger" role="alert"><?= $escapeTeacherProject($updateData['databaseError']) ?></div>
    <?php endif; ?>
    <?php if ($updateData['validationErrors'] !== []): ?>
        <div class="alert alert-danger" role="alert">
            <p class="mb-2">Revisa aquestes dades:</p>
            <ul class="mb-0">
                <?php foreach ($updateData['validationErrors'] as $error): ?>
                    <li><?= $escapeTeacherProject($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <article class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h4">Crea un projecte</h2>
            <p class="text-secondary">El projecte queda assignat al professor/a que l’ha creat.</p>
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

    <h2 class="h3 mb-3">Projectes assignats</h2>
    <div class="row g-3">
        <?php foreach ($teacherData['projects'] as $project): ?>
            <?php
            $projectId = (int) $project['id'];
            $isEditingProject = $updateData['editType'] === 'project'
                && $updateData['editId'] === (string) $projectId;
            $projectFormValues = $isEditingProject ? $updateData['submitted'] : $project;
            ?>
            <div class="col-12 col-lg-6">
                <article class="card border-0 shadow-sm h-100"><div class="card-body d-flex flex-column">
                    <p class="small text-secondary mb-1"><?= $escapeTeacherProject($project['code']) ?></p>
                    <h3 class="h4"><?= $escapeTeacherProject($project['name']) ?></h3>
                    <p class="text-secondary"><?= $escapeTeacherProject($project['description']) ?></p>
                    <dl class="row small mb-4">
                        <dt class="col-7">Alumnes associats</dt><dd class="col-5"><?= count($project['students']) ?></dd>
                        <dt class="col-7">Ítems avaluables</dt><dd class="col-5"><?= count($project['items']) ?></dd>
                    </dl>
                    <div class="d-flex flex-wrap gap-2 mt-auto">
                        <a class="btn btn-outline-secondary" href="/?page=project-details&amp;role=teacher&amp;project_id=<?= $projectId ?>">Obri els detalls</a>
                        <a class="btn btn-primary" href="/?page=grades&amp;role=teacher&amp;project_id=<?= $projectId ?>">Qualifica l’alumnat</a>
                    </div>
                    <!-- Teachers can edit only projects already present in their assigned project list. -->
                    <details class="mt-3"<?= $isEditingProject ? ' open' : '' ?>>
                        <summary class="btn btn-sm btn-outline-secondary">Edita el projecte</summary>
                        <form class="mt-3" method="post" action="/?page=projects&amp;role=teacher">
                            <input type="hidden" name="action" value="update_project">
                            <input type="hidden" name="id" value="<?= $projectId ?>">
                            <label class="form-label" for="teacher-project-code-edit-<?= $projectId ?>">Codi</label>
                            <input class="form-control mb-2" id="teacher-project-code-edit-<?= $projectId ?>" name="code" maxlength="50" required value="<?= $escapeTeacherProject($projectFormValues['code']) ?>">
                            <label class="form-label" for="teacher-project-name-edit-<?= $projectId ?>">Nom</label>
                            <input class="form-control mb-2" id="teacher-project-name-edit-<?= $projectId ?>" name="name" maxlength="150" required value="<?= $escapeTeacherProject($projectFormValues['name']) ?>">
                            <label class="form-label" for="teacher-project-description-edit-<?= $projectId ?>">Descripció</label>
                            <textarea class="form-control mb-2" id="teacher-project-description-edit-<?= $projectId ?>" name="description" rows="3" required><?= $escapeTeacherProject($projectFormValues['description']) ?></textarea>
                            <button class="btn btn-primary btn-sm" type="submit">Desa els canvis</button>
                        </form>
                    </details>
                </div></article>
            </div>
        <?php endforeach; ?>
        <?php if ($teacherData['projects'] === []): ?>
            <div class="col-12"><p class="alert alert-info mb-0">No tens projectes assignats.</p></div>
        <?php endif; ?>
    </div>
</section>
