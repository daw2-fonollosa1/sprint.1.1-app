<?php

// Show each project and its items from the current student's database assignments.
$escapeStudentValue = static fn (string|int $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$studentName = $studentData['student']['full_name'];
?>
<section aria-labelledby="student-projects-title">
    <div class="mb-4">
        <p class="eyebrow mb-2">Mòdul de l’alumnat</p>
        <h1 class="h2 mb-1" id="student-projects-title">Els meus projectes</h1>
        <p class="text-secondary mb-0">Projectes assignats a <?= $escapeStudentValue($studentName) ?>.</p>
    </div>

    <div class="row g-3">
        <?php foreach ($studentData['projects'] as $project): ?>
            <?php
            $gradedCount = 0;
            foreach ($project['items'] as $item) {
                if ($item['average'] !== null) {
                    $gradedCount++;
                }
            }
            ?>
            <div class="col-12 col-lg-6">
                <article class="card border-0 shadow-sm h-100"><div class="card-body d-flex flex-column">
                    <p class="small text-secondary mb-1"><?= $escapeStudentValue($project['code']) ?></p>
                    <h2 class="h4"><?= $escapeStudentValue($project['name']) ?></h2>
                    <p class="text-secondary"><?= $escapeStudentValue($project['description']) ?></p>
                    <dl class="row small mb-4">
                        <dt class="col-7">Ítems avaluables</dt><dd class="col-5"><?= count($project['items']) ?></dd>
                        <dt class="col-7">Ítems qualificats</dt><dd class="col-5"><?= $gradedCount ?> / <?= count($project['items']) ?></dd>
                    </dl>
                    <a class="btn btn-primary mt-auto align-self-start" href="/?page=project-details&amp;role=student&amp;project_id=<?= (int) $project['id'] ?>">Obri els detalls</a>
                </div></article>
            </div>
        <?php endforeach; ?>
        <?php if ($studentData['projects'] === []): ?>
            <div class="col-12"><p class="alert alert-info mb-0">No tens projectes assignats.</p></div>
        <?php endif; ?>
    </div>
</section>
