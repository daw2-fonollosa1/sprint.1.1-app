<?php
// Count unique students across every project assigned to this sample teacher.
$assignedStudentIds = [];
foreach ($teacherProjects as $project) {
    $assignedStudentIds = array_merge($assignedStudentIds, $project['studentIds']);
}
$studentCount = count(array_unique($assignedStudentIds));

// Average the available per-item teacher means to provide a simple dashboard summary.
$itemAverages = [];
foreach ($teacherDemoData['grades'] as $gradeEntry) {
    $values = array_map('floatval', array_values($gradeEntry['teacherGrades']));
    if ($values !== []) {
        $itemAverages[] = array_sum($values) / count($values);
    }
}
$overallGradeAverage = $itemAverages === [] ? null : array_sum($itemAverages) / count($itemAverages);

// Count project/student/item combinations where the current teacher has not entered a grade.
$pendingGrades = 0;
foreach ($teacherProjects as $project) {
    foreach ($project['studentIds'] as $studentId) {
        foreach ($project['itemIds'] as $itemId) {
            $gradeEntry = null;
            foreach ($teacherDemoData['grades'] as $candidate) {
                if ($candidate['projectId'] === $project['id'] && $candidate['studentId'] === $studentId && $candidate['itemId'] === $itemId) {
                    $gradeEntry = $candidate;
                    break;
                }
            }
            if ($gradeEntry === null || !array_key_exists($teacherId, $gradeEntry['teacherGrades'])) {
                $pendingGrades++;
            }
        }
    }
}
?>
<section aria-labelledby="teacher-dashboard-title">
    <!-- The teacher home page summarizes assigned work and offers a direct grading shortcut. -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div><p class="eyebrow mb-2">Mòdul docent</p><h1 class="h2 mb-1" id="teacher-dashboard-title">Tauler del professorat</h1><p class="text-secondary mb-0">Resum dels projectes i les qualificacions assignades.</p></div>
    </div>

    <!-- These cards are calculated from the teacher's temporary sample data. -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3"><article class="card h-100 border-0 shadow-sm"><div class="card-body">
            <p class="small text-secondary text-uppercase fw-semibold mb-2">Projectes assignats</p><p class="display-6 fw-semibold mb-0"><?= count($teacherProjects) ?></p><a class="small" href="/?page=projects&amp;role=teacher">Consulta els projectes</a>
        </div></article></div>
        <div class="col-12 col-sm-6 col-xl-3"><article class="card h-100 border-0 shadow-sm"><div class="card-body">
            <p class="small text-secondary text-uppercase fw-semibold mb-2">Alumnes</p><p class="display-6 fw-semibold mb-0"><?= $studentCount ?></p><a class="small" href="/?page=students&amp;role=teacher">Consulta l’alumnat</a>
        </div></article></div>
        <div class="col-12 col-sm-6 col-xl-3"><article class="card h-100 border-0 shadow-sm"><div class="card-body">
            <p class="small text-secondary text-uppercase fw-semibold mb-2">Qualificacions pendents</p><p class="display-6 fw-semibold mb-0"><?= $pendingGrades ?></p><a class="small" href="/?page=grades&amp;role=teacher">Introdueix notes</a>
        </div></article></div>
        <div class="col-12 col-sm-6 col-xl-3"><article class="card h-100 border-0 shadow-sm"><div class="card-body">
            <p class="small text-secondary text-uppercase fw-semibold mb-2">Mitjana de qualificacions</p><p class="display-6 fw-semibold mb-0"><?= $overallGradeAverage === null ? '—' : number_format($overallGradeAverage, 2, '.', '') ?></p><p class="small text-secondary mb-0">Escala 0–10 · mitjana per ítem</p>
        </div></article></div>
    </div>

    <div class="row g-4">
        <!-- The table provides a quick way to open a project or start grading it. -->
        <div class="col-12 col-xl-8"><div class="card border-0 shadow-sm h-100"><div class="card-body p-0">
            <div class="d-flex justify-content-between align-items-center gap-2 p-3 p-lg-4"><div><h2 class="h4 mb-1">Projectes assignats</h2><p class="text-secondary mb-0">Pots tindre més d’un projecte assignat.</p></div><a href="/?page=projects&amp;role=teacher">Veure tots</a></div>
            <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th scope="col">Projecte</th><th scope="col">Grup</th><th scope="col">Alumnes</th><th scope="col">Accions</th></tr></thead>
                <tbody>
                    <?php foreach ($teacherProjects as $project): ?>
                        <tr>
                            <td><span class="fw-semibold"><?= htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8') ?></span><br><span class="small text-secondary"><?= htmlspecialchars($project['code'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td><?= htmlspecialchars($project['group'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= count($project['studentIds']) ?></td>
                            <td class="text-nowrap"><a class="btn btn-sm btn-outline-secondary" href="/?page=project-details&amp;role=teacher&amp;project_id=<?= rawurlencode($project['id']) ?>">Detalls</a> <a class="btn btn-sm btn-primary" href="/?page=grades&amp;role=teacher&amp;project_id=<?= rawurlencode($project['id']) ?>">Qualifica</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table></div>
        </div></div></div>

        <!-- A regular GET form lets PHP select the project before rendering the grading page. -->
        <div class="col-12 col-xl-4"><div class="card border-0 shadow-sm h-100"><div class="card-body">
            <h2 class="h4">Accés ràpid a qualificacions</h2><p class="text-secondary">Tria un projecte per obrir-ne la graella de qualificació.</p>
            <form action="/" method="get"><input type="hidden" name="page" value="grades"><input type="hidden" name="role" value="teacher">
                <label class="form-label" for="quick-grade-project">Projecte</label>
                <select class="form-select mb-3" id="quick-grade-project" name="project_id">
                    <?php foreach ($teacherProjects as $project): ?><option value="<?= htmlspecialchars($project['id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($project['code'] . ' · ' . $project['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                </select>
                <button class="btn btn-outline-primary w-100" type="submit">Obri la graella de notes</button>
            </form>
            <hr><h3 class="h6">Resum d’alumnat</h3><p class="mb-0">Alumnes associats als teus projectes: <strong><?= $studentCount ?></strong></p>
        </div></div></div>
    </div>

    <div class="alert alert-info mt-4 mb-0" role="status">Demostració: les qualificacions s’emmagatzemen només en la memòria de la pàgina i no es desen permanentment.</div>
</section>
