<section aria-labelledby="teacher-students-title">
    <div class="mb-4"><p class="eyebrow mb-2">Mòdul docent</p><h1 class="h2 mb-1" id="teacher-students-title">Alumnat dels meus projectes</h1><p class="text-secondary mb-0">Cada alumne apareix amb els projectes on està assignat.</p></div>

    <!-- Build a unique list of students and collect the names of their assigned projects. -->
    <?php
    $assignedStudents = [];
    foreach ($teacherProjects as $project) {
        foreach ($project['studentIds'] as $studentId) {
            if (!isset($assignedStudents[$studentId])) {
                $assignedStudents[$studentId] = ['projectIds' => []];
            }
            $assignedStudents[$studentId]['projectIds'][] = $project['id'];
        }
    }
    ?>
    <div class="card border-0 shadow-sm"><div class="card-body">
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th scope="col">Nom complet</th><th scope="col">Grup</th><th scope="col">Projectes assignats</th><th scope="col">Acció</th></tr></thead>
            <tbody>
                <?php foreach ($assignedStudents as $studentId => $studentData): ?>
                    <?php $student = array_values(array_filter($teacherDemoData['users'], static fn (array $user): bool => $user['id'] === $studentId))[0] ?? null; ?>
                    <?php if ($student !== null): ?>
                        <?php
                        $studentProjects = array_values(array_filter($teacherProjects, static fn (array $project): bool => in_array($project['id'], $studentData['projectIds'], true)));
                        $firstProject = $studentProjects[0];
                        $firstItemId = $firstProject['itemIds'][0] ?? '';
                        ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($student['group'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?php foreach ($studentProjects as $project): ?><a class="badge text-bg-light border text-decoration-none me-1" href="/?page=project-details&amp;role=teacher&amp;project_id=<?= rawurlencode($project['id']) ?>"><?= htmlspecialchars($project['code'], ENT_QUOTES, 'UTF-8') ?></a><?php endforeach; ?></td>
                            <td><a class="btn btn-sm btn-outline-primary" href="/?page=grades&amp;role=teacher&amp;project_id=<?= rawurlencode($firstProject['id']) ?>&amp;student_id=<?= rawurlencode($student['id']) ?>&amp;item_id=<?= rawurlencode($firstItemId) ?>">Qualifica</a></td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php if ($assignedStudents === []): ?><p class="text-secondary mb-0">No hi ha alumnat assignat als teus projectes.</p><?php endif; ?>
    </div></div>
</section>
