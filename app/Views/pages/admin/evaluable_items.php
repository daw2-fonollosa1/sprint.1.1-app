<?php

// The controller prepared all rows and feedback; this view only renders HTML.
$escapeItemValue = static fn (string|int $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$operationData = $createOperationsData;
$updateData = $updateOperationsData;
$deleteData = $deleteOperationsData;
$submittedItem = $operationData['submitted'];
$formUrl = '/?page=evaluable-items&amp;role=admin';
?>
<section aria-labelledby="items-title">
    <div class="mb-4">
        <p class="eyebrow mb-2">Administració</p>
        <h1 class="h2 mb-1" id="items-title">Ítems avaluables</h1>
        <p class="text-secondary mb-0">Crea elements que després es poden assignar a projectes.</p>
    </div>

    <?php if ($operationData['feedback'] !== ''): ?>
        <div class="alert alert-success" role="status"><?= $escapeItemValue($operationData['feedback']) ?></div>
    <?php endif; ?>
    <?php if ($operationData['accessError'] !== ''): ?>
        <div class="alert alert-warning" role="alert"><?= $escapeItemValue($operationData['accessError']) ?></div>
    <?php endif; ?>
    <?php if ($operationData['databaseError'] !== ''): ?>
        <div class="alert alert-danger" role="alert"><?= $escapeItemValue($operationData['databaseError']) ?></div>
    <?php endif; ?>
    <?php if ($operationData['validationErrors'] !== []): ?>
        <div class="alert alert-danger" role="alert">
            <p class="mb-2">Revisa aquestes dades:</p>
            <ul class="mb-0">
                <?php foreach ($operationData['validationErrors'] as $error): ?>
                    <li><?= $escapeItemValue($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <?php if ($updateData['feedback'] !== ''): ?>
        <div class="alert alert-success" role="status"><?= $escapeItemValue($updateData['feedback']) ?></div>
    <?php endif; ?>
    <?php if ($updateData['accessError'] !== ''): ?>
        <div class="alert alert-warning" role="alert"><?= $escapeItemValue($updateData['accessError']) ?></div>
    <?php endif; ?>
    <?php if ($updateData['databaseError'] !== ''): ?>
        <div class="alert alert-danger" role="alert"><?= $escapeItemValue($updateData['databaseError']) ?></div>
    <?php endif; ?>
    <?php if ($updateData['validationErrors'] !== []): ?>
        <div class="alert alert-danger" role="alert">
            <p class="mb-2">Revisa aquestes dades:</p>
            <ul class="mb-0">
                <?php foreach ($updateData['validationErrors'] as $error): ?>
                    <li><?= $escapeItemValue($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <?php if ($deleteData['feedback'] !== ''): ?>
        <div class="alert alert-success" role="status"><?= $escapeItemValue($deleteData['feedback']) ?></div>
    <?php endif; ?>
    <?php if ($deleteData['accessError'] !== ''): ?>
        <div class="alert alert-warning" role="alert"><?= $escapeItemValue($deleteData['accessError']) ?></div>
    <?php endif; ?>
    <?php if ($deleteData['databaseError'] !== ''): ?>
        <div class="alert alert-danger" role="alert"><?= $escapeItemValue($deleteData['databaseError']) ?></div>
    <?php endif; ?>
    <?php if ($deleteData['validationErrors'] !== []): ?>
        <div class="alert alert-danger" role="alert">
            <p class="mb-2">No s’ha pogut completar l’eliminació:</p>
            <ul class="mb-0">
                <?php foreach ($deleteData['validationErrors'] as $error): ?>
                    <li><?= $escapeItemValue($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <article class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h4">Crea un ítem avaluable</h2>
            <p class="text-secondary">El codi ha de ser únic. Els camps corresponen a la taula d’ítems avaluables.</p>
            <form method="post" action="<?= $formUrl ?>">
                <input type="hidden" name="action" value="create_item">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="item-code">Codi</label>
                        <input class="form-control" id="item-code" name="code" maxlength="50" required value="<?= $escapeItemValue($submittedItem['code'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label" for="item-name">Nom</label>
                        <input class="form-control" id="item-name" name="name" maxlength="150" required value="<?= $escapeItemValue($submittedItem['name'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="item-description">Descripció</label>
                        <textarea class="form-control" id="item-description" name="description" rows="3" required><?= $escapeItemValue($submittedItem['description'] ?? '') ?></textarea>
                    </div>
                </div>
                <button class="btn btn-primary mt-3" type="submit">Crea l’ítem</button>
            </form>
        </div>
    </article>

    <article class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h4">Ítems existents</h2>
            <p class="text-secondary">Aquesta llista es llig de MariaDB per comprovar les creacions desades.</p>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th scope="col">Codi</th><th scope="col">Nom</th><th scope="col">Descripció</th><th scope="col">Accions</th></tr></thead>
                    <tbody>
                        <?php foreach ($operationData['items'] as $item): ?>
                            <?php
                            $itemId = (int) $item['id'];
                            $isEditingItem = $updateData['editType'] === 'item'
                                && $updateData['editId'] === (string) $itemId;
                            $itemFormValues = $isEditingItem ? $updateData['submitted'] : $item;
                            ?>
                            <tr>
                                <td><?= $escapeItemValue($item['code']) ?></td>
                                <td class="fw-semibold"><?= $escapeItemValue($item['name']) ?></td>
                                <td><?= $escapeItemValue($item['description']) ?></td>
                                <td>
                                    <!-- Native details keeps each edit form usable without extra JavaScript. -->
                                    <details<?= $isEditingItem ? ' open' : '' ?>>
                                        <summary class="btn btn-sm btn-outline-secondary">Edita</summary>
                                        <form class="mt-3" method="post" action="<?= $formUrl ?>">
                                            <input type="hidden" name="action" value="update_item">
                                            <input type="hidden" name="id" value="<?= $itemId ?>">
                                            <label class="form-label" for="item-code-<?= $itemId ?>">Codi</label>
                                            <input class="form-control mb-2" id="item-code-<?= $itemId ?>" name="code" maxlength="50" required value="<?= $escapeItemValue($itemFormValues['code']) ?>">
                                            <label class="form-label" for="item-name-<?= $itemId ?>">Nom</label>
                                            <input class="form-control mb-2" id="item-name-<?= $itemId ?>" name="name" maxlength="150" required value="<?= $escapeItemValue($itemFormValues['name']) ?>">
                                            <label class="form-label" for="item-description-<?= $itemId ?>">Descripció</label>
                                            <textarea class="form-control mb-2" id="item-description-<?= $itemId ?>" name="description" rows="3" required><?= $escapeItemValue($itemFormValues['description']) ?></textarea>
                                            <button class="btn btn-primary btn-sm" type="submit">Desa els canvis</button>
                                        </form>
                                    </details>
                                    <form class="mt-2" method="post" action="<?= $formUrl ?>" onsubmit="return confirm('Vols eliminar aquest ítem avaluable? PHP només el podrà eliminar si no està assignat a cap projecte.')">
                                        <input type="hidden" name="action" value="delete_item">
                                        <input type="hidden" name="id" value="<?= $itemId ?>">
                                        <button class="btn btn-outline-danger btn-sm" type="submit">Elimina l’ítem</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($operationData['items'] === []): ?>
                            <tr><td colspan="4" class="text-center text-secondary py-4">Encara no hi ha ítems avaluables.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </article>
</section>
