<section aria-labelledby="student-grades-title">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
        <div>
            <p class="eyebrow mb-2">Mòdul de l’alumnat · <?= htmlspecialchars($studentDemoData['student']['name'], ENT_QUOTES, 'UTF-8') ?></p>
            <h1 class="h2 mb-1" id="student-grades-title">Les meues qualificacions</h1>
            <p class="text-secondary mb-0">Les qualificacions es mostren per projecte i ítem avaluable.</p>
        </div>
        <a class="btn btn-outline-secondary" href="/?page=projects&amp;role=student">Torna als projectes</a>
    </div>

    <!-- A normal GET form asks PHP to filter the same student's grades by assigned project. -->
    <form class="card border-0 shadow-sm mb-4" action="/" method="get">
        <div class="card-body">
            <input type="hidden" name="page" value="grades">
            <input type="hidden" name="role" value="student">
            <label class="form-label" for="student-grade-project">Filtra per projecte</label>
            <div class="d-flex flex-column flex-sm-row gap-2">
                <select class="form-select" id="student-grade-project" name="project_id">
                    <option value="">Tots els meus projectes</option>
                    <?php foreach ($studentDemoData['projects'] as $project): ?>
                        <option value="<?= htmlspecialchars($project['id'], ENT_QUOTES, 'UTF-8') ?>"<?= $project['id'] === $selectedGradeProjectId ? ' selected' : '' ?>><?= htmlspecialchars($project['code'] . ' · ' . $project['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-outline-primary" type="submit">Aplica el filtre</button>
            </div>
        </div>
    </form>

    <!-- Each row is already limited to this student's assigned project and item in the controller. -->
    <div class="card border-0 shadow-sm"><div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th scope="col">Projecte i ítem</th><th scope="col">Notes del professorat</th><th scope="col">Mitjana aritmètica</th></tr></thead>
                <tbody>
                    <?php if ($visibleStudentGradeRows === []): ?>
                        <tr><td class="p-4 text-secondary" colspan="3">No hi ha qualificacions per a aquest projecte.</td></tr>
                    <?php else: ?>
                        <?php foreach ($visibleStudentGradeRows as $gradeRow): ?>
                            <tr>
                                <td>
                                    <a class="fw-semibold" href="/?page=project-details&amp;role=student&amp;project_id=<?= rawurlencode($gradeRow['projectId']) ?>"><?= htmlspecialchars($gradeRow['projectName'], ENT_QUOTES, 'UTF-8') ?></a>
                                    <span class="small text-secondary d-block"><?= htmlspecialchars($gradeRow['projectCode'] . ' · ' . $gradeRow['itemCode'] . ' · ' . $gradeRow['itemName'], ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td>
                                    <?php if ($gradeRow['teacherGrades'] === []): ?>
                                        <span class="text-secondary">Pendent</span>
                                    <?php else: ?>
                                        <ul class="list-unstyled small mb-0">
                                            <?php foreach ($gradeRow['teacherGrades'] as $teacherGrade): ?>
                                                <li><?= htmlspecialchars($teacherGrade['name'], ENT_QUOTES, 'UTF-8') ?>: <?= number_format($teacherGrade['value'], 1, ',', '') ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($gradeRow['average'] === null): ?>
                                        <span class="badge text-bg-light border">Pendent</span>
                                    <?php else: ?>
                                        <span class="fw-semibold"><?= number_format($gradeRow['average'], 2, ',', '') ?> / 10</span>
                                        <?php if ($gradeRow['gradeCount'] > 1): ?>
                                            <span class="small text-secondary d-block">(<?= implode(' + ', array_map(static fn (array $grade): string => number_format($grade['value'], 1, ',', ''), $gradeRow['teacherGrades'])) ?>) ÷ <?= $gradeRow['gradeCount'] ?></span>
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

    <!-- Explain both the calculation and why the page does not combine different items globally. -->
    <div class="alert alert-info mt-4 mb-2" role="note">
        Si diversos docents qualifiquen el mateix ítem, la mitjana és la suma de les seues notes dividida pel nombre de notes. Per exemple: (8,5 + 9,0) ÷ 2 = 8,75.
    </div>
    <p class="small text-secondary mb-0">Els requisits no defineixen una mitjana global entre ítems diferents. Les dades mostrades són temporals i pertanyen a l’alumne de demostració.</p>
</section>
