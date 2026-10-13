<section aria-labelledby="items-title">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div><p class="eyebrow mb-2">Administració</p><h1 class="h2 mb-1" id="items-title">Ítems avaluables</h1><p class="text-secondary mb-0">Administra els elements que es poden avaluar en un projecte.</p></div>
        <button class="btn btn-primary" type="button" data-create-item>Crea un ítem</button>
    </div>
    <div class="alert alert-success d-none" role="status" data-admin-feedback></div>

    <div class="card border-0 shadow-sm"><div class="card-body">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-3">
            <h2 class="h5 mb-0">Llista d’ítems</h2>
            <label class="visually-hidden" for="item-search">Cerca ítems</label><input class="form-control" id="item-search" type="search" placeholder="Cerca per codi o nom" data-item-search>
        </div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th scope="col">Codi</th><th scope="col">Nom</th><th scope="col">Descripció</th><th scope="col" class="text-end">Accions</th></tr></thead>
            <tbody data-item-list></tbody>
        </table></div>
    </div></div>

    <div class="modal fade" id="item-modal" tabindex="-1" aria-labelledby="item-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form data-item-form>
            <div class="modal-header"><h2 class="modal-title fs-5" id="item-modal-title">Crea un ítem avaluable</h2><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tanca"></button></div>
            <div class="modal-body"><input type="hidden" name="id"><div class="alert alert-danger d-none" data-form-error role="alert"></div>
                <div class="mb-3"><label class="form-label" for="item-code">Codi</label><input class="form-control" id="item-code" name="code" required></div>
                <div class="mb-3"><label class="form-label" for="item-name">Nom</label><input class="form-control" id="item-name" name="name" required></div>
                <div><label class="form-label" for="item-description">Descripció</label><textarea class="form-control" id="item-description" name="description" rows="3" required></textarea></div>
            </div>
            <div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel·la</button><button class="btn btn-primary" type="submit">Desa</button></div>
        </form></div></div>
    </div>
</section>
