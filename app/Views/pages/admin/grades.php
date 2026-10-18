<?php

// This list identifies individual grade rows; the delete controller validates every request.
$escapeGradeAdmin = static fn (string|int $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$deleteData = $deleteOperationsData;
?>
<section aria-labelledby="admin-grades-title">
    <div class="mb-4">
        <p class="eyebrow mb-2">Administració</p>
        <h1 class="h2 mb-1" id="admin-grades-title">Gestió de qualificacions</h1>
        <p class="text-secondary mb-0">Consulta les notes individuals i elimina només el registre seleccionat.</p>
    </div>

    <?php if ($deleteData['feedback'] !== ''): ?>
        <div class="alert alert-success" role="status"><?= $escapeGradeAdmin($deleteData['feedback']) ?></div>
    <?php endif; ?>
    <?php if ($deleteData['accessError'] !== ''): ?>
        <div class="alert alert-warning" role="alert"><?= $escapeGradeAdmin($deleteData['accessError']) ?></div>
    <?php endif; ?>
    <?php if ($deleteData['databaseError'] !== ''): ?>
        <div class="alert alert-danger" role="alert"><?= $escapeGradeAdmin($deleteData['databaseError']) ?></div>
    <?php endif; ?>
    <?php if ($deleteData['validationErrors'] !== []): ?>
        <div class="alert alert-danger" role="alert">
            <p class="mb-2">No s’ha pogut completar l’eliminació:</p>
            <ul class="mb-0">
                <?php foreach ($deleteData['validationErrors'] as $error): ?>
                    <li><?= $escapeGradeAdmin($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <article class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h4">Qualificacions desades</h2>
            <p class="text-secondary">Cada fila correspon a una nota d’un professor/a. Eliminar-la no modifica altres notes del mateix projecte, alumne i ítem.</p>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Projecte</th>
                            <th scope="col">Alumne/a</th>
                            <th scope="col">Ítem avaluable</th>
                            <th scope="col">Professor/a</th>
                            <th scope="col">Nota</th>
                            <th scope="col">Accions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deleteData['adminGrades'] as $grade): ?>
                            <tr>
                                <td><?= $escapeGradeAdmin($grade['project_code'] . ' · ' . $grade['project_name']) ?></td>
                                <td><?= $escapeGradeAdmin($grade['student_name']) ?></td>
                                <td><?= $escapeGradeAdmin($grade['item_code'] . ' · ' . $grade['item_name']) ?></td>
                                <td><?= $escapeGradeAdmin($grade['teacher_name']) ?></td>
                                <td><?= $escapeGradeAdmin($grade['grade_value']) ?></td>
                                <td>
                                    <form method="post" action="/?page=grades&amp;role=admin" onsubmit="return confirm('Vols eliminar només aquesta qualificació? Les altres notes es conservaran.')">
                                        <input type="hidden" name="action" value="delete_grade">
                                        <input type="hidden" name="id" value="<?= (int) $grade['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Elimina la nota</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($deleteData['adminGrades'] === []): ?>
                            <tr><td colspan="6" class="text-center text-secondary py-4">Encara no hi ha qualificacions desades.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </article>
</section>
