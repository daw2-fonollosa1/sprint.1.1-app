<?php

// The controller fills this summary with current records from MariaDB.
$projectCount = count($adminDashboardData['projects']);
$teacherCount = count($adminDashboardData['teachers']);
$studentCount = count($adminDashboardData['students']);
$itemCount = count($adminDashboardData['items']);
?>
<section aria-labelledby="admin-dashboard-title">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <p class="eyebrow mb-2">Vista d’administració</p>
            <h1 class="h2 mb-1" id="admin-dashboard-title">Tauler d’administració</h1>
            <p class="text-secondary mb-0">Resum de les dades actuals de l’aplicació.</p>
        </div>
        <a class="btn btn-primary" href="/?page=projects&amp;role=admin">Gestiona els projectes</a>
    </div>

    <!-- Each number is counted from the records loaded by the PHP controller. -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3"><article class="card h-100 border-0 shadow-sm"><div class="card-body">
            <p class="small text-secondary text-uppercase fw-semibold mb-2">Projectes</p>
            <p class="display-6 fw-semibold mb-0"><?= $projectCount ?></p>
            <a class="small" href="/?page=projects&amp;role=admin">Obre els projectes</a>
        </div></article></div>
        <div class="col-12 col-sm-6 col-xl-3"><article class="card h-100 border-0 shadow-sm"><div class="card-body">
            <p class="small text-secondary text-uppercase fw-semibold mb-2">Professorat</p>
            <p class="display-6 fw-semibold mb-0"><?= $teacherCount ?></p>
            <a class="small" href="/?page=users&amp;role=admin">Obre els usuaris</a>
        </div></article></div>
        <div class="col-12 col-sm-6 col-xl-3"><article class="card h-100 border-0 shadow-sm"><div class="card-body">
            <p class="small text-secondary text-uppercase fw-semibold mb-2">Alumnat</p>
            <p class="display-6 fw-semibold mb-0"><?= $studentCount ?></p>
            <a class="small" href="/?page=users&amp;role=admin">Gestiona els comptes</a>
        </div></article></div>
        <div class="col-12 col-sm-6 col-xl-3"><article class="card h-100 border-0 shadow-sm"><div class="card-body">
            <p class="small text-secondary text-uppercase fw-semibold mb-2">Ítems avaluables</p>
            <p class="display-6 fw-semibold mb-0"><?= $itemCount ?></p>
            <a class="small" href="/?page=evaluable-items&amp;role=admin">Obre els ítems</a>
        </div></article></div>
        <div class="col-12 col-sm-6 col-xl-3"><article class="card h-100 border-0 shadow-sm"><div class="card-body">
            <p class="small text-secondary text-uppercase fw-semibold mb-2">Qualificacions individuals</p>
            <p class="display-6 fw-semibold mb-0"><?= $adminDashboardData['gradeCount'] ?></p>
            <p class="small text-secondary mb-0">Notes registrades per projecte, alumne i ítem</p>
        </div></article></div>
    </div>

    <article class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="p-3 p-lg-4">
                <h2 class="h4 mb-1">Projectes existents</h2>
                <p class="text-secondary mb-0">Llista llegida de MariaDB.</p>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th scope="col">Codi</th><th scope="col">Nom</th><th scope="col">Descripció</th></tr></thead>
                    <tbody>
                        <?php foreach ($adminDashboardData['projects'] as $project): ?>
                            <tr>
                                <td><?= htmlspecialchars($project['code'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($project['description'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($adminDashboardData['projects'] === []): ?>
                            <tr><td colspan="3" class="text-center text-secondary py-4">Encara no hi ha projectes.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </article>
</section>
