<?php

// The controller has already loaded database rows and processed any submitted form.
// This view only turns that prepared data into HTML; it does not run SQL queries.
$users = $adminUserPageData['users'];
$feedback = $adminUserPageData['feedback'];
$databaseError = $adminUserPageData['databaseError'];
$validationErrors = $adminUserPageData['validationErrors'];
$formValues = $adminUserPageData['formValues'];
$formAction = $adminUserPageData['formAction'];
$activeFilter = $adminUserPageData['activeFilter'];
// Escape every database or form value before placing it in HTML. This prevents a name
// containing HTML characters from being interpreted as page markup by the browser.
$escapeUserValue = static fn (string|int $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

// Calculate role totals on the server so the first page render has correct counts
// even before the optional JavaScript search or role filter is used.
$teacherCount = count(array_filter($users, static fn (array $user): bool => $user['role'] === 'teacher'));
$studentCount = count(array_filter($users, static fn (array $user): bool => $user['role'] === 'student'));

// Preserve the selected role in every form action so the same tab remains selected after POST.
$formUrl = '/?page=users&amp;role=admin&amp;filter=' . $escapeUserValue($activeFilter);
?>
<section aria-labelledby="users-title">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <p class="eyebrow mb-2">Administració</p>
            <h1 class="h2 mb-1" id="users-title">Gestió d’usuaris</h1>
            <p class="text-secondary mb-0">Consulta i edita els comptes de professorat i alumnat.</p>
        </div>
        <button class="btn btn-primary" type="button" data-create-user>Crea un usuari</button>
    </div>

    <?php if ($feedback !== ''): ?>
        <div class="alert alert-success" role="status"><?= $escapeUserValue($feedback) ?></div>
    <?php endif; ?>

    <?php if ($databaseError !== ''): ?>
        <div class="alert alert-danger" role="alert"><?= $escapeUserValue($databaseError) ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
                <!-- These controls filter the user rows already rendered by PHP; they do not fetch records. -->
                <div class="btn-group" role="group" aria-label="Filtra els usuaris">
                    <button class="btn btn-outline-primary<?= $activeFilter === 'teacher' ? ' active' : '' ?>" type="button" data-user-filter="teacher" aria-pressed="<?= $activeFilter === 'teacher' ? 'true' : 'false' ?>">Professorat</button>
                    <button class="btn btn-outline-primary<?= $activeFilter === 'student' ? ' active' : '' ?>" type="button" data-user-filter="student" aria-pressed="<?= $activeFilter === 'student' ? 'true' : 'false' ?>">Alumnat</button>
                </div>
                <label class="visually-hidden" for="user-search">Cerca usuaris</label>
                <input class="form-control" id="user-search" type="search" placeholder="Cerca per nom o usuari" data-user-search>
            </div>

            <p class="small text-secondary" aria-live="polite">
                Usuaris: <span data-user-count="all"><?= count($users) ?></span> ·
                Professorat: <span data-user-count="teacher"><?= $teacherCount ?></span> ·
                Alumnat: <span data-user-count="student"><?= $studentCount ?></span>
            </p>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Nom</th>
                            <th scope="col">Usuari</th>
                            <th scope="col">Tipus de compte</th>
                            <th scope="col" class="text-end">Accions</th>
                        </tr>
                    </thead>
                    <tbody data-user-list>
                        <!-- PHP loops over the database result and creates one table row per account. -->
                        <?php foreach ($users as $user): ?>
                            <?php
                            $userId = (int) $user['id'];
                            $userRole = (string) $user['role'];
                            ?>
                            <tr data-user-row data-user-role="<?= $escapeUserValue($userRole) ?>">
                                <td class="fw-semibold"><?= $escapeUserValue($user['full_name']) ?></td>
                                <td><?= $escapeUserValue($user['username']) ?></td>
                                <td><?= $userRole === 'teacher' ? 'Professor/a' : 'Alumne/a' ?></td>
                                <td class="text-end text-nowrap">
                                    <!-- The edit button stores only fields that are safe to put in its HTML data attributes. -->
                                    <button
                                        class="btn btn-sm btn-outline-secondary"
                                        type="button"
                                        data-edit-user
                                        data-user-id="<?= $userId ?>"
                                        data-user-name="<?= $escapeUserValue($user['full_name']) ?>"
                                        data-user-username="<?= $escapeUserValue($user['username']) ?>"
                                        data-user-role-value="<?= $escapeUserValue($userRole) ?>"
                                    >Edita</button>
                                    <!-- Deletion is a normal POST form, so PHP performs the database change. -->
                                    <form
                                        class="d-inline"
                                        method="post"
                                        action="<?= $formUrl ?>"
                                        data-delete-user-form
                                        data-user-name="<?= $escapeUserValue($user['full_name']) ?>"
                                    >
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $userId ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Elimina</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="d-none" data-user-empty>
                            <td colspan="4" class="text-center text-secondary py-4">No s’han trobat usuaris.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- The database schema uses cascading foreign keys for these linked records. -->
            <p class="small text-secondary mb-0">En eliminar un compte també s’eliminaran les assignacions i qualificacions vinculades.</p>
        </div>
    </div>

    <!-- One Bootstrap modal handles both operations. JavaScript fills it for editing; PHP
         decides from the hidden action value whether the POST creates or updates a row. -->
    <div
        class="modal fade"
        id="user-modal"
        tabindex="-1"
        aria-labelledby="user-modal-title"
        aria-hidden="true"
        data-user-modal
        data-open-on-load="<?= $adminUserPageData['reopenModal'] ? 'true' : 'false' ?>"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" action="<?= $formUrl ?>" data-user-form>
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="user-modal-title"><?= $formAction === 'update' ? 'Edita un usuari' : 'Crea un usuari' ?></h2>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tanca"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Hidden fields tell the controller which account and operation to process. -->
                        <input type="hidden" name="action" value="<?= $escapeUserValue($formAction) ?>" data-user-action>
                        <input type="hidden" name="id" value="<?= $escapeUserValue($formValues['id']) ?>" data-user-id-field>

                        <!-- Validation runs in PHP, so messages still appear if JavaScript is unavailable. -->
                        <?php if ($validationErrors !== []): ?>
                            <div class="alert alert-danger" role="alert">
                                <p class="mb-2">Revisa aquestes dades:</p>
                                <ul class="mb-0">
                                    <?php foreach ($validationErrors as $error): ?>
                                        <li><?= $escapeUserValue($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- The required account fields match the users table and are checked again by PHP. -->
                        <div class="mb-3">
                            <label class="form-label" for="user-name">Nom complet</label>
                            <input class="form-control" id="user-name" name="full_name" maxlength="150" autocomplete="name" required value="<?= $escapeUserValue($formValues['full_name']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="user-username">Nom d’usuari</label>
                            <input class="form-control" id="user-username" name="username" maxlength="80" autocomplete="username" required value="<?= $escapeUserValue($formValues['username']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="user-role">Tipus de compte</label>
                            <select class="form-select" id="user-role" name="role" required>
                                <option value="teacher"<?= $formValues['role'] === 'teacher' ? ' selected' : '' ?>>Professor/a</option>
                                <option value="student"<?= $formValues['role'] === 'student' ? ' selected' : '' ?>>Alumne/a</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="user-password">Contrasenya</label>
                            <input
                                class="form-control"
                                id="user-password"
                                name="password"
                                type="password"
                                autocomplete="new-password"
                                <?= $formAction === 'create' ? 'required' : '' ?>
                                data-user-password
                            >
                            <!-- Passwords are never repopulated after an error and are never shown from the database. -->
                            <div class="form-text">Obligatòria en crear. En editar, deixa-la buida si no la vols canviar.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel·la</button>
                        <button class="btn btn-primary" type="submit" data-user-submit><?= $formAction === 'update' ? 'Desa els canvis' : 'Crea el compte' ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
