<?php

// The controller only provides projects, students, and items assigned in MariaDB
// to the teacher identity stored in the server-side session.
$escapeGradeValue = static fn (string|int $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$operationData = $createOperationsData;
$selectedProjectId = $operationData['selectedProjectId'];
$submitted = $operationData['submitted'];
$requestedStudentId = $submitted['student_id'] ?? ($_GET['student_id'] ?? '');
$requestedItemId = $submitted['item_id'] ?? ($_GET['item_id'] ?? '');
$selectedStudentId = (string) ($operationData['gradeStudents'][0]['id'] ?? '');
$selectedItemId = (string) ($operationData['gradeItems'][0]['id'] ?? '');

// Keep a requested value only when it is one of the database choices for this project.
foreach ($operationData['gradeStudents'] as $student) {
    if (is_string($requestedStudentId) && (string) $student['id'] === $requestedStudentId) {
        $selectedStudentId = $requestedStudentId;
        break;
    }
}
foreach ($operationData['gradeItems'] as $item) {
    if (is_string($requestedItemId) && (string) $item['id'] === $requestedItemId) {
        $selectedItemId = $requestedItemId;
        break;
    }
}
$selectedProject = null;
foreach ($operationData['teacherProjects'] as $project) {
    if ((string) $project['id'] === $selectedProjectId) {
        $selectedProject = $project;
        break;
    }
}
?>
<section aria-labelledby="teacher-grades-title">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
        <div><p class="eyebrow mb-2">Mòdul d’avaluació</p><h1 class="h2 mb-1" id="teacher-grades-title">Qualificacions</h1><p class="text-secondary mb-0">Desa una qualificació individual per al projecte, l’alumne i l’ítem seleccionats.</p></div>
        <a class="btn btn-outline-secondary" href="/?page=projects&amp;role=teacher">Torna als projectes</a>
    </div>

    <?php if ($operationData['feedback'] !== ''): ?>
        <div class="alert alert-success" role="status"><?= $escapeGradeValue($operationData['feedback']) ?></div>
    <?php endif; ?>
    <?php if ($operationData['accessError'] !== ''): ?>
        <div class="alert alert-warning" role="alert"><?= $escapeGradeValue($operationData['accessError']) ?></div>
    <?php endif; ?>
    <?php if ($operationData['databaseError'] !== ''): ?>
        <div class="alert alert-danger" role="alert"><?= $escapeGradeValue($operationData['databaseError']) ?></div>
    <?php endif; ?>
    <?php if ($operationData['validationErrors'] !== []): ?>
        <div class="alert alert-danger" role="alert">
            <p class="mb-2">Revisa aquestes dades:</p>
            <ul class="mb-0">
                <?php foreach ($operationData['validationErrors'] as $error): ?>
                    <li><?= $escapeGradeValue($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- A normal GET request refreshes the student and item options for the selected project. -->
    <form class="card border-0 shadow-sm mb-4" method="get" action="/">
        <input type="hidden" name="page" value="grades">
        <input type="hidden" name="role" value="teacher">
        <div class="card-body">
            <label class="form-label" for="grade-project">Projecte</label>
            <div class="d-flex flex-column flex-sm-row gap-2">
                <select class="form-select" id="grade-project" name="project_id" required<?= $operationData['teacherProjects'] === [] ? ' disabled' : '' ?>>
                    <?php foreach ($operationData['teacherProjects'] as $project): ?>
                        <option value="<?= (int) $project['id'] ?>"<?= (string) $project['id'] === $selectedProjectId ? ' selected' : '' ?>><?= $escapeGradeValue($project['code'] . ' · ' . $project['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-outline-primary text-nowrap" type="submit"<?= $operationData['teacherProjects'] === [] ? ' disabled' : '' ?>>Mostra alumnat i ítems</button>
            </div>
        </div>
    </form>

    <form class="card border-0 shadow-sm" method="post" action="/?page=grades&amp;role=teacher">
        <div class="card-body">
            <input type="hidden" name="action" value="create_grade">
            <input type="hidden" name="project_id" value="<?= $escapeGradeValue($selectedProjectId) ?>">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label" for="grade-student">Alumne/a</label>
                    <select class="form-select" id="grade-student" name="student_id" required<?= $operationData['gradeStudents'] === [] ? ' disabled' : '' ?>>
                        <?php foreach ($operationData['gradeStudents'] as $student): ?>
                            <option value="<?= (int) $student['id'] ?>"<?= (string) $student['id'] === $selectedStudentId ? ' selected' : '' ?>><?= $escapeGradeValue($student['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="grade-item">Ítem avaluable</label>
                    <select class="form-select" id="grade-item" name="item_id" required<?= $operationData['gradeItems'] === [] ? ' disabled' : '' ?>>
                        <?php foreach ($operationData['gradeItems'] as $item): ?>
                            <option value="<?= (int) $item['id'] ?>"<?= (string) $item['id'] === $selectedItemId ? ' selected' : '' ?>><?= $escapeGradeValue($item['code'] . ' · ' . $item['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label" for="grade-value">Qualificació individual</label>
                    <input class="form-control form-control-lg" id="grade-value" name="grade_value" type="number" step="any" inputmode="decimal" required value="<?= $escapeGradeValue($submitted['grade_value'] ?? '') ?>" aria-describedby="grade-help">
                    <div class="form-text" id="grade-help">La base de dades admet fins a sis xifres enteres i quatre decimals. L’escala final encara està pendent de definir.</div>
                </div>
                <div class="col-12 col-lg-6 d-flex align-items-end">
                    <button class="btn btn-primary btn-lg" type="submit"<?= $selectedProject === null || $operationData['gradeStudents'] === [] || $operationData['gradeItems'] === [] ? ' disabled' : '' ?>>Desa la qualificació</button>
                </div>
            </div>
            <?php if ($selectedProject !== null): ?>
                <p class="small text-secondary mt-3 mb-0">La nota s’afegirà a «<?= $escapeGradeValue($selectedProject['name']) ?>» com una qualificació individual del docent autenticat.</p>
            <?php endif; ?>
        </div>
    </form>

    <div class="alert alert-info mt-4 mb-0" role="status">Cada qualificació es guarda com una nota individual del docent seleccionat.</div>
</section>
