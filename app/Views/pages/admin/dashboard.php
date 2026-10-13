<?php
// Calculate the counts here so the dashboard summary is rendered by PHP from its sample data.
$teacherCount = count(array_filter($adminDemoData['users'], static fn (array $user): bool => $user['role'] === 'teacher'));
$studentCount = count(array_filter($adminDemoData['users'], static fn (array $user): bool => $user['role'] === 'student'));
?>
<section aria-labelledby="admin-dashboard-title">
    <!-- This heading introduces the administrator preview and links to its main management areas. -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <p class="eyebrow mb-2">Vista d’administració</p>
            <h1 class="h2 mb-1" id="admin-dashboard-title">Tauler d’administració</h1>
            <p class="text-secondary mb-0">Resum de les dades de demostració de l’aplicació.</p>
        </div>
        <a class="btn btn-primary" href="/?page=projects&amp;role=admin">Gestiona els projectes</a>
    </div>

    <!-- Summary cards show the sample totals supplied by the controller. -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <article class="card h-100 border-0 shadow-sm"><div class="card-body">
                <p class="small text-secondary text-uppercase fw-semibold mb-2">Projectes</p>
                <p class="display-6 fw-semibold mb-0"><?= count($adminDemoData['projects']) ?></p>
                <a class="small" href="/?page=projects&amp;role=admin">Obre els projectes</a>
            </div></article>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <article class="card h-100 border-0 shadow-sm"><div class="card-body">
                <p class="small text-secondary text-uppercase fw-semibold mb-2">Professorat</p>
                <p class="display-6 fw-semibold mb-0"><?= $teacherCount ?></p>
                <a class="small" href="/?page=users&amp;role=admin">Obre els usuaris</a>
            </div></article>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <article class="card h-100 border-0 shadow-sm"><div class="card-body">
                <p class="small text-secondary text-uppercase fw-semibold mb-2">Alumnat</p>
                <p class="display-6 fw-semibold mb-0"><?= $studentCount ?></p>
                <a class="small" href="/?page=users&amp;role=admin">Gestiona els comptes</a>
            </div></article>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <article class="card h-100 border-0 shadow-sm"><div class="card-body">
                <p class="small text-secondary text-uppercase fw-semibold mb-2">Ítems avaluables</p>
                <p class="display-6 fw-semibold mb-0"><?= count($adminDemoData['items']) ?></p>
                <a class="small" href="/?page=evaluable-items&amp;role=admin">Obre els ítems</a>
            </div></article>
        </div>
    </div>

    <!-- Keep recent sample projects visible as a quick overview on the administrator home page. -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="p-3 p-lg-4">
                <h2 class="h4 mb-1">Projectes de demostració</h2>
                <p class="text-secondary mb-0">Aquests registres només s’utilitzen per provar la interfície.</p>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th scope="col">Codi</th><th scope="col">Nom</th><th scope="col">Descripció</th></tr></thead>
                    <tbody>
                        <?php foreach ($adminDemoData['projects'] as $project): ?>
                            <tr>
                                <td><?= htmlspecialchars($project['code'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($project['description'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="alert alert-info mt-4 mb-0" role="status">
        Prototip: les dades es mantenen només mentre no es canvie de pàgina. No hi ha autenticació ni permisos reals.
    </div>
</section>
