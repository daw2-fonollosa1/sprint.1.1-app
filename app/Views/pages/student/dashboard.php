<?php

// MariaDB supplies the student and their assigned projects to this view.
$studentName = $studentData['student']['full_name'];
$projectCount = count($studentData['projects']);
$formatGrade = static function (string|int|float $value): string {
    $value = rtrim(rtrim((string) $value, '0'), '.');

    return str_replace('.', ',', $value);
};
?>
<section aria-labelledby="student-dashboard-title">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <p class="eyebrow mb-2">Mòdul de l’alumnat · <?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?></p>
            <h1 class="h2 mb-1" id="student-dashboard-title">Tauler de l’alumne</h1>
            <p class="text-secondary mb-0">Consulta els projectes assignats i les qualificacions de cada ítem.</p>
        </div>
    </div>

    <!-- The counts refer only to this student's assigned projects and items. -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-4"><article class="card border-0 shadow-sm h-100"><div class="card-body">
            <p class="small text-secondary text-uppercase fw-semibold mb-2">Projectes assignats</p>
            <p class="display-6 fw-semibold mb-0"><?= $projectCount ?></p>
            <a class="small" href="/?page=projects&amp;role=student">Consulta els meus projectes</a>
        </div></article></div>
        <div class="col-12 col-sm-6 col-xl-4"><article class="card border-0 shadow-sm h-100"><div class="card-body">
            <p class="small text-secondary text-uppercase fw-semibold mb-2">Ítems qualificats</p>
            <p class="display-6 fw-semibold mb-0"><?= $gradedItemCount ?> / <?= $assignedItemCount ?></p>
            <a class="small" href="/?page=grades&amp;role=student">Consulta les qualificacions</a>
        </div></article></div>
        <div class="col-12 col-sm-6 col-xl-4"><article class="card border-0 shadow-sm h-100"><div class="card-body">
            <p class="small text-secondary text-uppercase fw-semibold mb-2">Ítems pendents</p>
            <p class="display-6 fw-semibold mb-0"><?= $pendingItemCount ?></p>
            <p class="small text-secondary mb-0">Encara no tenen cap qualificació.</p>
        </div></article></div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-7">
            <article class="card border-0 shadow-sm h-100"><div class="card-body p-0">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 p-3 p-lg-4">
                    <div><h2 class="h4 mb-1">Els meus projectes</h2><p class="text-secondary mb-0">Projectes vinculats al teu compte.</p></div>
                    <a href="/?page=projects&amp;role=student">Veure tots</a>
                </div>
                <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th scope="col">Projecte</th><th scope="col">Ítems</th><th scope="col">Acció</th></tr></thead>
                    <tbody>
                        <?php foreach ($studentData['projects'] as $project): ?>
                            <tr>
                                <td><span class="fw-semibold"><?= htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8') ?></span><br><span class="small text-secondary"><?= htmlspecialchars($project['code'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td><?= count($project['items']) ?></td>
                                <td><a class="btn btn-sm btn-outline-primary" href="/?page=project-details&amp;role=student&amp;project_id=<?= (int) $project['id'] ?>">Veure projecte</a></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($studentData['projects'] === []): ?>
                            <tr><td colspan="3" class="text-center text-secondary py-4">No tens projectes assignats.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table></div>
            </div></article>
        </div>

        <div class="col-12 col-xl-5">
            <article class="card border-0 shadow-sm h-100"><div class="card-body">
                <h2 class="h4">Resum de qualificacions</h2>
                <p class="text-secondary">Les mitjanes es calculen separadament per projecte i ítem.</p>
                <?php if ($gradedItemCount === 0): ?>
                    <p class="mb-0">Encara no hi ha qualificacions disponibles.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($studentGradeRows as $gradeRow): ?>
                            <?php if ($gradeRow['average'] !== null): ?>
                                <li class="list-group-item px-0 d-flex justify-content-between align-items-start gap-3">
                                    <span>
                                        <span class="fw-semibold d-block"><?= htmlspecialchars($gradeRow['itemName'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="small text-secondary"><?= htmlspecialchars($gradeRow['projectCode'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </span>
                                    <span class="badge text-bg-dark fs-6"><?= htmlspecialchars($formatGrade($gradeRow['average']), ENT_QUOTES, 'UTF-8') ?></span>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                    <a class="btn btn-outline-primary mt-3" href="/?page=grades&amp;role=student">Veure totes les notes</a>
                <?php endif; ?>
            </div></article>
        </div>
    </div>
</section>
