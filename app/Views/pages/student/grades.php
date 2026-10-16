<?php

// Display database decimal values without adding an application-specific rounding rule.
$escapeStudentValue = static fn (string|int $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$formatGrade = static function (string|int|float $value): string {
    $value = rtrim(rtrim((string) $value, '0'), '.');

    return str_replace('.', ',', $value);
};
?>
<section aria-labelledby="student-grades-title">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
        <div>
            <p class="eyebrow mb-2">Mòdul de l’alumnat · <?= $escapeStudentValue($studentData['student']['full_name']) ?></p>
            <h1 class="h2 mb-1" id="student-grades-title">Les meues qualificacions</h1>
            <p class="text-secondary mb-0">Les notes es mostren per projecte i ítem avaluable.</p>
        </div>
        <a class="btn btn-outline-secondary" href="/?page=projects&amp;role=student">Torna als projectes</a>
    </div>

    <?php if ($studentData['filterError'] !== ''): ?>
        <div class="alert alert-warning" role="status"><?= $escapeStudentValue($studentData['filterError']) ?></div>
    <?php endif; ?>

    <!-- PHP accepts this filter only if the project belongs to the authenticated student. -->
    <form class="card border-0 shadow-sm mb-4" action="/" method="get">
        <div class="card-body">
            <input type="hidden" name="page" value="grades">
            <input type="hidden" name="role" value="student">
            <label class="form-label" for="student-grade-project">Filtra per projecte</label>
            <div class="d-flex flex-column flex-sm-row gap-2">
                <select class="form-select" id="student-grade-project" name="project_id">
                    <option value="">Tots els meus projectes</option>
                    <?php foreach ($studentData['projects'] as $project): ?>
                        <option value="<?= (int) $project['id'] ?>"<?= (string) $project['id'] === $selectedGradeProjectId ? ' selected' : '' ?>><?= $escapeStudentValue($project['code'] . ' · ' . $project['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-outline-primary" type="submit">Aplica el filtre</button>
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm"><div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th scope="col">Projecte i ítem</th><th scope="col">Notes del professorat</th><th scope="col">Mitjana aritmètica</th></tr></thead>
                <tbody>
                    <?php if ($visibleStudentGradeRows === []): ?>
                        <tr><td class="p-4 text-secondary" colspan="3">No hi ha ítems o qualificacions per mostrar.</td></tr>
                    <?php else: ?>
                        <?php foreach ($visibleStudentGradeRows as $gradeRow): ?>
                            <tr>
                                <td>
                                    <a class="fw-semibold" href="/?page=project-details&amp;role=student&amp;project_id=<?= (int) $gradeRow['projectId'] ?>"><?= $escapeStudentValue($gradeRow['projectName']) ?></a>
                                    <span class="small text-secondary d-block"><?= $escapeStudentValue($gradeRow['projectCode'] . ' · ' . $gradeRow['itemCode'] . ' · ' . $gradeRow['itemName']) ?></span>
                                </td>
                                <td>
                                    <?php if ($gradeRow['teacherGrades'] === []): ?>
                                        <span class="text-secondary">Pendent</span>
                                    <?php else: ?>
                                        <ul class="list-unstyled small mb-0">
                                            <?php foreach ($gradeRow['teacherGrades'] as $teacherGrade): ?>
                                                <li><?= $escapeStudentValue($teacherGrade['name']) ?>: <?= $escapeStudentValue($formatGrade($teacherGrade['value'])) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($gradeRow['average'] === null): ?>
                                        <span class="badge text-bg-light border">Pendent</span>
                                    <?php else: ?>
                                        <span class="fw-semibold"><?= $escapeStudentValue($formatGrade($gradeRow['average'])) ?></span>
                                        <?php if ($gradeRow['gradeCount'] > 1): ?>
                                            <?php $values = array_map($formatGrade, array_column($gradeRow['teacherGrades'], 'value')); ?>
                                            <span class="small text-secondary d-block">(<?= $escapeStudentValue(implode(' + ', $values)) ?>) ÷ <?= (int) $gradeRow['gradeCount'] ?></span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div></div>

    <div class="alert alert-info mt-4 mb-0" role="note">
        Si diversos docents qualifiquen el mateix ítem, se sumen les seues notes i es divideixen pel nombre de notes. Per exemple: (8,5 + 9,0) ÷ 2 = 8,75.
    </div>
</section>
