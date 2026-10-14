<?php
// Choose the first assigned student and item when the URL did not specify either one.
$gradeStudentIds = $selectedProject['studentIds'] ?? [];
$gradeItemIds = $selectedProject['itemIds'] ?? [];
$gradeStudentId = in_array($selectedStudentId, $gradeStudentIds, true) ? $selectedStudentId : ($gradeStudentIds[0] ?? '');
$gradeItemId = in_array($selectedItemId, $gradeItemIds, true) ? $selectedItemId : ($gradeItemIds[0] ?? '');
?>
<section
    aria-labelledby="teacher-grades-title"
    data-grade-page
    data-selected-project="<?= htmlspecialchars($selectedProjectId, ENT_QUOTES, 'UTF-8') ?>"
    data-selected-student="<?= htmlspecialchars($gradeStudentId, ENT_QUOTES, 'UTF-8') ?>"
    data-selected-item="<?= htmlspecialchars($gradeItemId, ENT_QUOTES, 'UTF-8') ?>"
>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
        <div><p class="eyebrow mb-2">Mòdul d’avaluació</p><h1 class="h2 mb-1" id="teacher-grades-title">Qualificacions</h1><p class="text-secondary mb-0">Selecciona un projecte, un alumne i un ítem per introduir una nota.</p></div>
        <a class="btn btn-outline-secondary" href="/?page=projects&amp;role=teacher">Torna als projectes</a>
    </div>

    <!-- The selectors make the project, student, and item for this grade explicit. -->
    <form class="card border-0 shadow-sm mb-4" data-grade-form novalidate>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-lg-4"><label class="form-label" for="grade-project">Projecte</label>
                    <select class="form-select" id="grade-project" data-grade-project required>
                        <?php foreach ($teacherProjects as $project): ?><option value="<?= htmlspecialchars($project['id'], ENT_QUOTES, 'UTF-8') ?>"<?= $project['id'] === $selectedProjectId ? ' selected' : '' ?>><?= htmlspecialchars($project['code'] . ' · ' . $project['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-lg-4"><label class="form-label" for="grade-student">Alumne/a</label>
                    <select class="form-select" id="grade-student" data-grade-student required>
                        <?php foreach ($gradeStudentIds as $studentId): ?>
                            <?php $student = array_values(array_filter($teacherDemoData['users'], static fn (array $user): bool => $user['id'] === $studentId))[0] ?? null; ?>
                            <?php if ($student !== null): ?><option value="<?= htmlspecialchars($student['id'], ENT_QUOTES, 'UTF-8') ?>"<?= $student['id'] === $gradeStudentId ? ' selected' : '' ?>><?= htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-lg-4"><label class="form-label" for="grade-item">Ítem avaluable</label>
                    <select class="form-select" id="grade-item" data-grade-item required>
                        <?php foreach ($gradeItemIds as $itemId): ?>
                            <?php $item = array_values(array_filter($teacherDemoData['items'], static fn (array $candidate): bool => $candidate['id'] === $itemId))[0] ?? null; ?>
                            <?php if ($item !== null): ?><option value="<?= htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') ?>"<?= $item['id'] === $gradeItemId ? ' selected' : '' ?>><?= htmlspecialchars($item['code'] . ' · ' . $item['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <hr class="my-4">
            <div class="row g-4 align-items-end">
                <div class="col-12 col-md-5 col-lg-4"><label class="form-label" for="grade-value">La teua qualificació (0–10)</label>
                    <input class="form-control form-control-lg" id="grade-value" name="grade" type="number" min="0" max="10" step="0.1" inputmode="decimal" required data-grade-value aria-describedby="grade-scale-help">
                    <div class="form-text" id="grade-scale-help">Es permet un decimal, per exemple 8.5.</div>
                </div>
                <div class="col-12 col-md-7 col-lg-8"><p class="mb-2" data-grade-selection aria-live="polite"></p><button class="btn btn-primary btn-lg" type="submit">Desa la qualificació</button></div>
            </div>
            <div class="alert alert-danger mt-3 mb-0 d-none" role="alert" data-grade-error></div>
            <div class="alert alert-success mt-3 mb-0 d-none" role="status" data-grade-feedback></div>
        </div>
    </form>

    <!-- The calculated mean is shown next to the individual teacher grades. -->
    <div class="row g-4">
        <div class="col-12 col-lg-7"><article class="card border-0 shadow-sm h-100"><div class="card-body">
            <h2 class="h4">Qualificacions del professorat</h2>
            <p class="text-secondary">Les notes de més d’un docent per al mateix projecte, alumne i ítem es combinen amb la mitjana aritmètica.</p>
            <div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th scope="col">Docent</th><th scope="col">Qualificació</th></tr></thead><tbody data-teacher-grade-list><tr><td colspan="2" class="text-secondary">Selecciona les dades per veure les notes.</td></tr></tbody></table></div>
        </div></article></div>
        <div class="col-12 col-lg-5"><article class="card border-0 shadow-sm h-100"><div class="card-body">
            <p class="small text-secondary text-uppercase fw-semibold mb-2">Mitjana aritmètica</p><p class="display-5 fw-semibold mb-2"><span data-grade-average>—</span><span class="fs-5 text-secondary"> / 10</span></p><p class="text-secondary mb-0" data-grade-average-help>La mitjana es calcula amb les notes introduïdes pels docents.</p>
        </div></article></div>
    </div>

    <div class="alert alert-info mt-4 mb-0" role="status">Les notes són dades de demostració temporals. En canviar de pàgina, es torna a carregar la mostra inicial.</div>
</section>
