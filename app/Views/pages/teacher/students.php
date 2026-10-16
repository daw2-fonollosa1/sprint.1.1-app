<?php

// The teacher model returns one student row with the projects assigned to that student.
$escapeStudentValue = static fn (string|int $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<section aria-labelledby="teacher-students-title">
    <div class="mb-4">
        <p class="eyebrow mb-2">Mòdul docent</p>
        <h1 class="h2 mb-1" id="teacher-students-title">Alumnat dels meus projectes</h1>
        <p class="text-secondary mb-0">Es mostren els alumnes associats als teus projectes.</p>
    </div>

    <div class="card border-0 shadow-sm"><div class="card-body p-0">
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th scope="col">Nom complet</th><th scope="col">Projectes assignats</th><th scope="col">Acció</th></tr></thead>
            <tbody>
                <?php foreach ($teacherData['students'] as $student): ?>
                    <?php $firstProject = $student['projects'][0] ?? null; ?>
                    <tr>
                        <td class="fw-semibold"><?= $escapeStudentValue($student['full_name']) ?></td>
                        <td>
                            <?php foreach ($student['projects'] as $project): ?>
                                <a class="badge text-bg-light border text-decoration-none me-1" href="/?page=project-details&amp;role=teacher&amp;project_id=<?= (int) $project['id'] ?>"><?= $escapeStudentValue($project['code']) ?></a>
                            <?php endforeach; ?>
                        </td>
                        <td>
                            <?php if ($firstProject !== null): ?>
                                <a class="btn btn-sm btn-outline-primary" href="/?page=project-details&amp;role=teacher&amp;project_id=<?= (int) $firstProject['id'] ?>">Obri el projecte</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($teacherData['students'] === []): ?>
                    <tr><td colspan="3" class="text-center text-secondary py-4">No hi ha alumnat assignat als teus projectes.</td></tr>
                <?php endif; ?>
            </tbody>
        </table></div>
    </div></div>
</section>
