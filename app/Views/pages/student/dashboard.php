<?php
// Count this student's assigned evaluable items and the items that already have grades.
$assignedItemCount = count($studentGradeRows);
?>
<section aria-labelledby="student-dashboard-title">
    <!-- Identify the fixed student preview so sample grades are not mistaken for real account data. -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <p class="eyebrow mb-2">Vista de l’alumnat · <?= htmlspecialchars($studentDemoData['student']['name'], ENT_QUOTES, 'UTF-8') ?></p>
            <h1 class="h2 mb-1" id="student-dashboard-title">Tauler de l’alumne</h1>
            <p class="text-secondary mb-0">Consulta els teus projectes assignats i les qualificacions de cada ítem.</p>
        </div>
        <span class="badge rounded-pill text-bg-light border fs-6">Grup <?= htmlspecialchars($studentDemoData['student']['group'], ENT_QUOTES, 'UTF-8') ?></span>
    </div>

    <!-- These summary cards count only projects and evaluable items linked to the sample student. -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-4">
            <article class="card border-0 shadow-sm h-100"><div class="card-body">
                <p class="small text-secondary text-uppercase fw-semibold mb-2">Projectes assignats</p>
                <p class="display-6 fw-semibold mb-0"><?= count($studentDemoData['projects']) ?></p>
                <a class="small" href="/?page=projects&amp;role=student">Consulta els meus projectes</a>
            </div></article>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <article class="card border-0 shadow-sm h-100"><div class="card-body">
                <p class="small text-secondary text-uppercase fw-semibold mb-2">Ítems qualificats</p>
                <p class="display-6 fw-semibold mb-0"><?= $gradedItemCount ?> / <?= $assignedItemCount ?></p>
                <a class="small" href="/?page=grades&amp;role=student">Consulta les qualificacions</a>
            </div></article>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <article class="card border-0 shadow-sm h-100"><div class="card-body">
                <p class="small text-secondary text-uppercase fw-semibold mb-2">Ítems pendents</p>
                <p class="display-6 fw-semibold mb-0"><?= $pendingItemCount ?></p>
                <p class="small text-secondary mb-0">Encara no tenen cap qualificació.</p>
            </div></article>
        </div>
    </div>

    <div class="row g-4">
        <!-- Project rows are links to PHP-rendered detail pages, not JavaScript routes. -->
        <div class="col-12 col-xl-7">
            <article class="card border-0 shadow-sm h-100"><div class="card-body p-0">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 p-3 p-lg-4">
                    <div><h2 class="h4 mb-1">Els meus projectes</h2><p class="text-secondary mb-0">Tens més d’un projecte assignat.</p></div>
                    <a href="/?page=projects&amp;role=student">Veure tots</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th scope="col">Projecte</th><th scope="col">Estat</th><th scope="col">Acció</th></tr></thead>
                        <tbody>
                            <?php foreach ($studentDemoData['projects'] as $project): ?>
                                <tr>
                                    <td><span class="fw-semibold"><?= htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8') ?></span><br><span class="small text-secondary"><?= htmlspecialchars($project['code'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td><span class="badge text-bg-success"><?= htmlspecialchars($project['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td><a class="btn btn-sm btn-outline-primary" href="/?page=project-details&amp;role=student&amp;project_id=<?= rawurlencode($project['id']) ?>">Veure projecte</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div></article>
        </div>

        <!-- Show the exact per-item results without inventing a global average across different items. -->
        <div class="col-12 col-xl-5">
            <article class="card border-0 shadow-sm h-100"><div class="card-body">
                <h2 class="h4">Resum de qualificacions</h2>
                <p class="text-secondary">La mitjana global entre ítems no està definida als requisits del projecte.</p>
                <?php if ($gradedItemCount === 0): ?>
                    <p class="mb-0">Encara no hi ha qualificacions disponibles.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($studentGradeRows as $gradeRow): ?>
                            <?php if ($gradeRow['average'] !== null): ?>
                                <li class="list-group-item px-0 d-flex justify-content-between align-items-start gap-3">
                                    <span><span class="fw-semibold d-block"><?= htmlspecialchars($gradeRow['itemName'], ENT_QUOTES, 'UTF-8') ?></span><span class="small text-secondary"><?= htmlspecialchars($gradeRow['projectCode'], ENT_QUOTES, 'UTF-8') ?></span></span>
                                    <span class="badge text-bg-dark fs-6"><?= number_format($gradeRow['average'], 2, ',', '') ?> / 10</span>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                    <a class="btn btn-outline-primary mt-3" href="/?page=grades&amp;role=student">Veure totes les notes</a>
                <?php endif; ?>
            </div></article>
        </div>
    </div>

    <!-- Keep the temporary-data and prototype limits visible on this role's home page. -->
    <div class="alert alert-info mt-4 mb-0" role="status">Vista de demostració: les dades pertanyen a Marc i no es desen permanentment.</div>
</section>
