<?php

// This page uses the controller's database results and normal HTML POST forms.
// No JavaScript array is used to create projects or assignments.
$escapeProjectValue = static fn (string|int $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$operationData = $createOperationsData;
$updateData = $updateOperationsData;
$deleteData = $deleteOperationsData;
$submittedProject = $operationData['submitted'];
$formUrl = '/?page=projects&amp;role=admin';
$renderNames = static function (array $names) use ($escapeProjectValue): string {
    if ($names === []) {
        return 'Cap';
    }

    return implode(', ', array_map($escapeProjectValue, $names));
};
?>
<section aria-labelledby="projects-title">
    <div class="mb-4">
        <p class="eyebrow mb-2">Administració</p>
        <h1 class="h2 mb-1" id="projects-title">Gestió de projectes</h1>
        <p class="text-secondary mb-0">Crea projectes i assigna-hi professorat, alumnat i ítems avaluables.</p>
    </div>

    <?php if ($operationData['feedback'] !== ''): ?>
        <div class="alert alert-success" role="status"><?= $escapeProjectValue($operationData['feedback']) ?></div>
    <?php endif; ?>
    <?php if ($operationData['accessError'] !== ''): ?>
        <div class="alert alert-warning" role="alert"><?= $escapeProjectValue($operationData['accessError']) ?></div>
    <?php endif; ?>
    <?php if ($operationData['databaseError'] !== ''): ?>
        <div class="alert alert-danger" role="alert"><?= $escapeProjectValue($operationData['databaseError']) ?></div>
    <?php endif; ?>
    <?php if ($operationData['validationErrors'] !== []): ?>
        <div class="alert alert-danger" role="alert">
            <p class="mb-2">Revisa aquestes dades:</p>
            <ul class="mb-0">
                <?php foreach ($operationData['validationErrors'] as $error): ?>
                    <li><?= $escapeProjectValue($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <?php if ($updateData['feedback'] !== ''): ?>
        <div class="alert alert-success" role="status"><?= $escapeProjectValue($updateData['feedback']) ?></div>
    <?php endif; ?>
    <?php if ($updateData['accessError'] !== ''): ?>
        <div class="alert alert-warning" role="alert"><?= $escapeProjectValue($updateData['accessError']) ?></div>
    <?php endif; ?>
    <?php if ($updateData['databaseError'] !== ''): ?>
        <div class="alert alert-danger" role="alert"><?= $escapeProjectValue($updateData['databaseError']) ?></div>
    <?php endif; ?>
    <?php if ($updateData['validationErrors'] !== []): ?>
        <div class="alert alert-danger" role="alert">
            <p class="mb-2">Revisa aquestes dades:</p>
            <ul class="mb-0">
                <?php foreach ($updateData['validationErrors'] as $error): ?>
                    <li><?= $escapeProjectValue($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <?php if ($deleteData['feedback'] !== ''): ?>
        <div class="alert alert-success" role="status"><?= $escapeProjectValue($deleteData['feedback']) ?></div>
    <?php endif; ?>
    <?php if ($deleteData['accessError'] !== ''): ?>
        <div class="alert alert-warning" role="alert"><?= $escapeProjectValue($deleteData['accessError']) ?></div>
    <?php endif; ?>
    <?php if ($deleteData['databaseError'] !== ''): ?>
        <div class="alert alert-danger" role="alert"><?= $escapeProjectValue($deleteData['databaseError']) ?></div>
    <?php endif; ?>
    <?php if ($deleteData['validationErrors'] !== []): ?>
        <div class="alert alert-danger" role="alert">
            <p class="mb-2">No s’ha pogut completar l’eliminació:</p>
            <ul class="mb-0">
                <?php foreach ($deleteData['validationErrors'] as $error): ?>
                    <li><?= $escapeProjectValue($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <article class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h4">Crea un projecte</h2>
            <p class="text-secondary">El codi ha de ser únic. Els camps corresponen a la taula de projectes.</p>
            <form method="post" action="<?= $formUrl ?>">
                <input type="hidden" name="action" value="create_project">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="project-code">Codi</label>
                        <input class="form-control" id="project-code" name="code" maxlength="50" required value="<?= $escapeProjectValue($submittedProject['code'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label" for="project-name">Nom</label>
                        <input class="form-control" id="project-name" name="name" maxlength="150" required value="<?= $escapeProjectValue($submittedProject['name'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="project-description">Descripció</label>
                        <textarea class="form-control" id="project-description" name="description" rows="3" required><?= $escapeProjectValue($submittedProject['description'] ?? '') ?></textarea>
                    </div>
                </div>
                <button class="btn btn-primary mt-3" type="submit">Crea el projecte</button>
            </form>
        </div>
    </article>

    <article class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h4">Projectes existents</h2>
            <p class="text-secondary">Projectes i assignacions actuals llegits de MariaDB.</p>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th scope="col">Codi</th><th scope="col">Nom</th><th scope="col">Descripció</th><th scope="col">Professorat</th><th scope="col">Alumnat</th><th scope="col">Ítems</th><th scope="col">Accions</th></tr></thead>
                    <tbody>
                        <?php foreach ($operationData['projects'] as $project): ?>
                            <?php
                            $projectId = (int) $project['id'];
                            $isEditingProject = $updateData['editType'] === 'project'
                                && $updateData['editId'] === (string) $projectId;
                            $projectFormValues = $isEditingProject ? $updateData['submitted'] : $project;
                            $isEditingAssignments = $updateData['editType'] === 'assignments'
                                && $updateData['editId'] === (string) $projectId;
                            $submittedAssignmentIds = $isEditingAssignments
                                ? ($updateData['submitted']['assignments'] ?? [])
                                : [];
                            $projectAssignments = $operationData['projectAssignments'][(int) $project['id']] ?? [
                                'teachers' => [],
                                'students' => [],
                                'items' => [],
                            ];
                            ?>
                            <tr>
                                <td><?= $escapeProjectValue($project['code']) ?></td>
                                <td class="fw-semibold"><?= $escapeProjectValue($project['name']) ?></td>
                                <td><?= $escapeProjectValue($project['description']) ?></td>
                                <td><?= $renderNames($projectAssignments['teachers']) ?></td>
                                <td><?= $renderNames($projectAssignments['students']) ?></td>
                                <td><?= $renderNames($projectAssignments['items']) ?></td>
                                <td class="d-flex flex-column align-items-start gap-2">
                                    <!-- Project fields come from the database and are changed only by this POST form. -->
                                    <details<?= $isEditingProject ? ' open' : '' ?>>
                                        <summary class="btn btn-sm btn-outline-secondary">Edita el projecte</summary>
                                        <form class="mt-3" method="post" action="<?= $formUrl ?>">
                                            <input type="hidden" name="action" value="update_project">
                                            <input type="hidden" name="id" value="<?= $projectId ?>">
                                            <label class="form-label" for="project-code-edit-<?= $projectId ?>">Codi</label>
                                            <input class="form-control mb-2" id="project-code-edit-<?= $projectId ?>" name="code" maxlength="50" required value="<?= $escapeProjectValue($projectFormValues['code']) ?>">
                                            <label class="form-label" for="project-name-edit-<?= $projectId ?>">Nom</label>
                                            <input class="form-control mb-2" id="project-name-edit-<?= $projectId ?>" name="name" maxlength="150" required value="<?= $escapeProjectValue($projectFormValues['name']) ?>">
                                            <label class="form-label" for="project-description-edit-<?= $projectId ?>">Descripció</label>
                                            <textarea class="form-control mb-2" id="project-description-edit-<?= $projectId ?>" name="description" rows="3" required><?= $escapeProjectValue($projectFormValues['description']) ?></textarea>
                                            <button class="btn btn-primary btn-sm" type="submit">Desa els canvis</button>
                                        </form>
                                    </details>

                                    <!-- The assignment form shows the saved links checked before editing. -->
                                    <details<?= $isEditingAssignments ? ' open' : '' ?>>
                                        <summary class="btn btn-sm btn-outline-primary">Edita assignacions</summary>
                                        <form class="mt-3" method="post" action="<?= $formUrl ?>">
                                            <input type="hidden" name="action" value="update_assignments">
                                            <input type="hidden" name="project_id" value="<?= $projectId ?>">
                                            <?php
                                            $assignmentEditGroups = [
                                                ['key' => 'teachers', 'field' => 'teacher_ids[]', 'title' => 'Professorat', 'records' => $operationData['teachers'], 'label' => static fn (array $record): string => $record['full_name'] . ' (@' . $record['username'] . ')'],
                                                ['key' => 'students', 'field' => 'student_ids[]', 'title' => 'Alumnat', 'records' => $operationData['students'], 'label' => static fn (array $record): string => $record['full_name'] . ' (@' . $record['username'] . ')'],
                                                ['key' => 'items', 'field' => 'item_ids[]', 'title' => 'Ítems avaluables', 'records' => $operationData['items'], 'label' => static fn (array $record): string => $record['code'] . ' · ' . $record['name']],
                                            ];
                                            ?>
                                            <p class="small text-secondary">Marca totes les relacions que vols conservar en aquest projecte.</p>
                                            <?php foreach ($assignmentEditGroups as $group): ?>
                                                <?php
                                                $currentIds = $operationData['projectAssignmentIds'][$projectId][$group['key']] ?? [];
                                                $selectedIds = $isEditingAssignments
                                                    ? ($submittedAssignmentIds[$group['key']] ?? [])
                                                    : $currentIds;
                                                ?>
                                                <fieldset class="mb-3">
                                                    <legend class="form-label fs-6"><?= $escapeProjectValue($group['title']) ?></legend>
                                                    <?php foreach ($group['records'] as $record): ?>
                                                        <?php $recordId = (int) $record['id']; ?>
                                                        <div class="form-check">
                                                            <input class="form-check-input" id="edit-<?= $group['key'] ?>-<?= $projectId ?>-<?= $recordId ?>" type="checkbox" name="<?= $escapeProjectValue($group['field']) ?>" value="<?= $recordId ?>"<?= in_array($recordId, $selectedIds, true) ? ' checked' : '' ?>>
                                                            <label class="form-check-label" for="edit-<?= $group['key'] ?>-<?= $projectId ?>-<?= $recordId ?>"><?= $escapeProjectValue($group['label']($record)) ?></label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                    <?php if ($group['records'] === []): ?>
                                                        <p class="small text-secondary mb-0">No hi ha elements disponibles.</p>
                                                    <?php endif; ?>
                                                </fieldset>
                                            <?php endforeach; ?>
                                            <button class="btn btn-primary btn-sm" type="submit">Desa les assignacions</button>
                                        </form>
                                    </details>

                                    <!-- Separate POST forms remove one assignment at a time. -->
                                    <details>
                                        <summary class="btn btn-sm btn-outline-danger">Elimina assignacions</summary>
                                        <div class="mt-3">
                                            <?php
                                            $assignmentDeleteGroups = [
                                                ['key' => 'teachers', 'title' => 'Professorat', 'records' => $operationData['teachers'], 'label' => static fn (array $record): string => $record['full_name'] . ' (@' . $record['username'] . ')'],
                                                ['key' => 'students', 'title' => 'Alumnat', 'records' => $operationData['students'], 'label' => static fn (array $record): string => $record['full_name'] . ' (@' . $record['username'] . ')'],
                                                ['key' => 'items', 'title' => 'Ítems avaluables', 'records' => $operationData['items'], 'label' => static fn (array $record): string => $record['code'] . ' · ' . $record['name']],
                                            ];
                                            ?>
                                            <p class="small text-secondary">Les assignacions amb qualificacions no es poden eliminar.</p>
                                            <?php foreach ($assignmentDeleteGroups as $group): ?>
                                                <?php $assignedIds = $operationData['projectAssignmentIds'][$projectId][$group['key']] ?? []; ?>
                                                <h3 class="h6 mt-3"><?= $escapeProjectValue($group['title']) ?></h3>
                                                <?php foreach ($assignedIds as $assignedId): ?>
                                                    <?php foreach ($group['records'] as $record): ?>
                                                        <?php if ((int) $record['id'] !== (int) $assignedId) { continue; } ?>
                                                        <form class="d-flex flex-wrap align-items-center justify-content-between gap-2 border-top py-2" method="post" action="<?= $formUrl ?>" onsubmit="return confirm('Vols eliminar aquesta assignació? Si té qualificacions vinculades, l’operació serà rebutjada.')">
                                                            <input type="hidden" name="action" value="delete_assignment">
                                                            <input type="hidden" name="project_id" value="<?= $projectId ?>">
                                                            <input type="hidden" name="assignment_type" value="<?= $escapeProjectValue($group['key']) ?>">
                                                            <input type="hidden" name="record_id" value="<?= (int) $assignedId ?>">
                                                            <span class="small"><?= $escapeProjectValue($group['label']($record)) ?></span>
                                                            <button class="btn btn-sm btn-outline-danger" type="submit">Elimina</button>
                                                        </form>
                                                    <?php endforeach; ?>
                                                <?php endforeach; ?>
                                                <?php if ($assignedIds === []): ?>
                                                    <p class="small text-secondary">No hi ha assignacions.</p>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </details>

                                    <form method="post" action="<?= $formUrl ?>" onsubmit="return confirm('Vols eliminar aquest projecte? PHP només el podrà eliminar si no té assignacions ni qualificacions.')">
                                        <input type="hidden" name="action" value="delete_project">
                                        <input type="hidden" name="id" value="<?= $projectId ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Elimina el projecte</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($operationData['projects'] === []): ?>
                            <tr><td colspan="7" class="text-center text-secondary py-4">Encara no hi ha projectes.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </article>

    <!-- Each form adds new relationship rows; it does not replace or remove assignments. -->
    <div class="mb-3">
        <h2 class="h3">Crea assignacions</h2>
        <p class="text-secondary">Selecciona un projecte i un o més elements. Si una assignació ja existeix, MariaDB la rebutjarà.</p>
    </div>
    <?php
    $assignmentGroups = [
        ['action' => 'assign_teachers', 'type' => 'teachers', 'title' => 'Assigna professorat', 'records' => $operationData['teachers'], 'label' => static fn (array $record): string => $record['full_name'] . ' (@' . $record['username'] . ')'],
        ['action' => 'assign_students', 'type' => 'students', 'title' => 'Assigna alumnat', 'records' => $operationData['students'], 'label' => static fn (array $record): string => $record['full_name'] . ' (@' . $record['username'] . ')'],
        ['action' => 'assign_items', 'type' => 'items', 'title' => 'Assigna ítems avaluables', 'records' => $operationData['items'], 'label' => static fn (array $record): string => $record['code'] . ' · ' . $record['name']],
    ];
    ?>
    <div class="row g-4">
        <?php foreach ($assignmentGroups as $group): ?>
            <div class="col-12 col-lg-4">
                <article class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h3 class="h5"><?= $escapeProjectValue($group['title']) ?></h3>
                        <form method="post" action="<?= $formUrl ?>">
                            <input type="hidden" name="action" value="<?= $escapeProjectValue($group['action']) ?>">
                            <div class="mb-3">
                                <label class="form-label" for="project-for-<?= $escapeProjectValue($group['type']) ?>">Projecte</label>
                                <select class="form-select" id="project-for-<?= $escapeProjectValue($group['type']) ?>" name="project_id" required>
                                    <option value="">Selecciona un projecte</option>
                                    <?php foreach ($operationData['projects'] as $project): ?>
                                        <option value="<?= (int) $project['id'] ?>"><?= $escapeProjectValue($project['code'] . ' · ' . $project['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <fieldset class="mb-3">
                                <legend class="form-label fs-6">Elements per assignar</legend>
                                <?php foreach ($group['records'] as $record): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" id="<?= $escapeProjectValue($group['type'] . '-' . $record['id']) ?>" type="checkbox" name="record_ids[]" value="<?= (int) $record['id'] ?>">
                                        <label class="form-check-label" for="<?= $escapeProjectValue($group['type'] . '-' . $record['id']) ?>"><?= $escapeProjectValue($group['label']($record)) ?></label>
                                    </div>
                                <?php endforeach; ?>
                                <?php if ($group['records'] === []): ?>
                                    <p class="small text-secondary mb-0">No hi ha elements disponibles.</p>
                                <?php endif; ?>
                            </fieldset>
                            <button class="btn btn-outline-primary" type="submit"<?= $group['records'] === [] || $operationData['projects'] === [] ? ' disabled' : '' ?>>Crea assignacions</button>
                        </form>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
</section>
