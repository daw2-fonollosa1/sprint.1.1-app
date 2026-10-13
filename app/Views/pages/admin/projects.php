<section aria-labelledby="projects-title">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div><p class="eyebrow mb-2">Administració</p><h1 class="h2 mb-1" id="projects-title">Gestió de projectes</h1><p class="text-secondary mb-0">Crea projectes i assigna-hi professorat, alumnat i ítems.</p></div>
        <button class="btn btn-primary" type="button" data-create-project>Crea un projecte</button>
    </div>
    <div class="alert alert-success d-none" role="status" data-admin-feedback></div>

    <div class="card border-0 shadow-sm mb-4"><div class="card-body">
        <h2 class="h5">Projectes</h2>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th scope="col">Codi</th><th scope="col">Projecte</th><th scope="col">Estat</th><th scope="col" class="text-end">Accions</th></tr></thead>
            <tbody data-project-list></tbody>
        </table></div>
    </div></div>

    <!-- Assignment checkboxes are regenerated when the selected project changes. -->
    <div class="card border-0 shadow-sm"><div class="card-body">
        <h2 class="h4 mb-1">Assignacions</h2>
        <p class="text-secondary">Selecciona un projecte per revisar-ne les assignacions.</p>
        <label class="form-label" for="project-picker">Projecte</label>
        <select class="form-select mb-4" id="project-picker" data-project-picker></select>
        <div data-project-assignments>
            <h3 class="h6">Professorat</h3><div class="mb-3" data-assignment-options="teachers"></div>
            <button class="btn btn-sm btn-outline-primary mb-4" type="button" data-save-assignments="teachers">Desa professorat</button>
            <h3 class="h6">Alumnat</h3><div class="mb-3" data-assignment-options="students"></div>
            <button class="btn btn-sm btn-outline-primary mb-4" type="button" data-save-assignments="students">Desa alumnat</button>
            <h3 class="h6">Ítems avaluables</h3><div class="mb-3" data-assignment-options="items"></div>
            <button class="btn btn-sm btn-outline-primary" type="button" data-save-assignments="items">Desa ítems</button>
        </div>
        <p class="small text-secondary mb-0 d-none" data-no-projects>Encara no hi ha projectes per assignar.</p>
    </div></div>

    <div class="modal fade" id="project-modal" tabindex="-1" aria-labelledby="project-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form data-project-form>
            <div class="modal-header"><h2 class="modal-title fs-5" id="project-modal-title">Crea un projecte</h2><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tanca"></button></div>
            <div class="modal-body"><input type="hidden" name="id"><div class="alert alert-danger d-none" data-form-error role="alert"></div>
                <div class="mb-3"><label class="form-label" for="project-code">Codi</label><input class="form-control" id="project-code" name="code" required></div>
                <div class="mb-3"><label class="form-label" for="project-name">Nom</label><input class="form-control" id="project-name" name="name" required></div>
                <div><label class="form-label" for="project-description">Descripció</label><textarea class="form-control" id="project-description" name="description" rows="3" required></textarea></div>
            </div>
            <div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel·la</button><button class="btn btn-primary" type="submit">Desa</button></div>
        </form></div></div>
    </div>
</section>
