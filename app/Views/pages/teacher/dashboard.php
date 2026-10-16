<?php

// The controller has already limited these projects and students to the signed-in teacher.
$projectCount = count($teacherData['projects']);
$studentCount = count($teacherData['students']);
$gradeCount = $teacherData['summary']['gradeCount'];
$pendingGradeCount = $teacherData['summary']['pendingGradeCount'];
?>
<section aria-labelledby="teacher-dashboard-title">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <p class="eyebrow mb-2">Mòdul docent</p>
            <h1 class="h2 mb-1" id="teacher-dashboard-title">Tauler del professorat</h1>
            <p class="text-secondary mb-0">Resum dels projectes i l’alumnat que tens assignats.</p>
        </div>
    </div>

    <!-- The cards use counts returned from the current teacher's database assignments. -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3"><article class="card h-100 border-0 shadow-sm"><div class="card-body">
            <p class="small text-secondary text-uppercase fw-semibold mb-2">Projectes assignats</p>
            <p class="display-6 fw-semibold mb-0"><?= $projectCount ?></p>
            <a class="small" href="/?page=projects&amp;role=teacher">Consulta els projectes</a>
        </div></article></div>
        <div class="col-12 col-sm-6 col-xl-3"><article class="card h-100 border-0 shadow-sm"><div class="card-body">
            <p class="small text-secondary text-uppercase fw-semibold mb-2">Alumnes</p>
            <p class="display-6 fw-semibold mb-0"><?= $studentCount ?></p>
            <a class="small" href="/?page=students&amp;role=teacher">Consulta l’alumnat</a>
        </div></article></div>
        <div class="col-12 col-sm-6 col-xl-3"><article class="card h-100 border-0 shadow-sm"><div class="card-body">
            <p class="small text-secondary text-uppercase fw-semibold mb-2">Notes introduïdes per tu</p>
            <p class="display-6 fw-semibold mb-0"><?= $gradeCount ?></p>
            <a class="small" href="/?page=grades&amp;role=teacher">Ves a qualificacions</a>
        </div></article></div>
        <div class="col-12 col-sm-6 col-xl-3"><article class="card h-100 border-0 shadow-sm"><div class="card-body">
            <p class="small text-secondary text-uppercase fw-semibold mb-2">Qualificacions pendents</p>
            <p class="display-6 fw-semibold mb-0"><?= $pendingGradeCount ?></p>
            <a class="small" href="/?page=grades&amp;role=teacher">Introdueix notes</a>
        </div></article></div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <article class="card border-0 shadow-sm h-100"><div class="card-body p-0">
                <div class="d-flex justify-content-between align-items-center gap-2 p-3 p-lg-4">
                    <div><h2 class="h4 mb-1">Projectes assignats</h2><p class="text-secondary mb-0">Només es mostren els projectes del teu compte.</p></div>
                    <a href="/?page=projects&amp;role=teacher">Veure tots</a>
                </div>
                <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th scope="col">Projecte</th><th scope="col">Alumnes</th><th scope="col">Ítems</th><th scope="col">Accions</th></tr></thead>
                    <tbody>
                        <?php foreach ($teacherData['projects'] as $project): ?>
                            <tr>
                                <td><span class="fw-semibold"><?= htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8') ?></span><br><span class="small text-secondary"><?= htmlspecialchars($project['code'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td><?= count($project['students']) ?></td>
                                <td><?= count($project['items']) ?></td>
                                <td class="text-nowrap"><a class="btn btn-sm btn-outline-secondary" href="/?page=project-details&amp;role=teacher&amp;project_id=<?= (int) $project['id'] ?>">Detalls</a> <a class="btn btn-sm btn-primary" href="/?page=grades&amp;role=teacher&amp;project_id=<?= (int) $project['id'] ?>">Qualifica</a></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($teacherData['projects'] === []): ?>
                            <tr><td colspan="4" class="text-center text-secondary py-4">No tens projectes assignats.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table></div>
            </div></article>
        </div>

        <div class="col-12 col-xl-4"><div class="card border-0 shadow-sm h-100"><div class="card-body">
            <h2 class="h4">Accés ràpid a qualificacions</h2>
            <p class="text-secondary">Tria un projecte per obrir-ne la graella.</p>
            <form action="/" method="get">
                <input type="hidden" name="page" value="grades">
                <input type="hidden" name="role" value="teacher">
                <label class="form-label" for="quick-grade-project">Projecte</label>
                <select class="form-select mb-3" id="quick-grade-project" name="project_id" required<?= $teacherData['projects'] === [] ? ' disabled' : '' ?>>
                    <?php foreach ($teacherData['projects'] as $project): ?>
                        <option value="<?= (int) $project['id'] ?>"><?= htmlspecialchars($project['code'] . ' · ' . $project['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-outline-primary w-100" type="submit"<?= $teacherData['projects'] === [] ? ' disabled' : '' ?>>Obri la graella de notes</button>
            </form>
            <hr>
            <p class="mb-0">Alumnes associats als teus projectes: <strong><?= $studentCount ?></strong></p>
        </div></div></div>
    </div>
</section>
