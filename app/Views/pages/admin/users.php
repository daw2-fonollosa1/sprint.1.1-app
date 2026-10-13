<section aria-labelledby="users-title">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <p class="eyebrow mb-2">Administració</p>
            <h1 class="h2 mb-1" id="users-title">Gestió d’usuaris</h1>
            <p class="text-secondary mb-0">Consulta i edita els comptes de professorat i alumnat.</p>
        </div>
        <button class="btn btn-primary" type="button" data-create-user>Crea un usuari</button>
    </div>

    <!-- JavaScript shows action feedback here without sending demo changes to a server. -->
    <div class="alert alert-success d-none" role="status" data-admin-feedback></div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
                <!-- These buttons filter the same user list by role. -->
                <div class="btn-group" role="group" aria-label="Filtra els usuaris">
                    <button class="btn btn-outline-primary active" type="button" data-user-filter="teacher" aria-pressed="true">Professorat</button>
                    <button class="btn btn-outline-primary" type="button" data-user-filter="student" aria-pressed="false">Alumnat</button>
                </div>
                <label class="visually-hidden" for="user-search">Cerca usuaris</label>
                <input class="form-control" id="user-search" type="search" placeholder="Cerca per nom o usuari" data-user-search>
            </div>

            <p class="small text-secondary" aria-live="polite">
                Usuaris: <span data-user-count="all">0</span> · Professorat: <span data-user-count="teacher">0</span> · Alumnat: <span data-user-count="student">0</span>
            </p>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th scope="col">Nom</th><th scope="col">Usuari</th><th scope="col">Rol o grup</th><th scope="col" class="text-end">Accions</th></tr></thead>
                    <tbody data-user-list></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- One Bootstrap modal is reused for creating and editing either type of account. -->
    <div class="modal fade" id="user-modal" tabindex="-1" aria-labelledby="user-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
            <form data-user-form>
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="user-modal-title">Crea un usuari</h2>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tanca"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id">
                    <div class="alert alert-danger d-none" data-form-error role="alert"></div>
                    <div class="mb-3"><label class="form-label" for="user-name">Nom complet</label><input class="form-control" id="user-name" name="name" required></div>
                    <div class="mb-3"><label class="form-label" for="user-username">Nom d’usuari</label><input class="form-control" id="user-username" name="username" required></div>
                    <div class="mb-3"><label class="form-label" for="user-role">Tipus de compte</label>
                        <select class="form-select" id="user-role" name="role"><option value="teacher">Professor/a</option><option value="student">Alumne/a</option></select>
                    </div>
                    <div class="mb-3 d-none" data-student-group-field><label class="form-label" for="user-group">Grup</label><input class="form-control" id="user-group" name="group"></div>
                </div>
                <div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel·la</button><button class="btn btn-primary" type="submit">Desa</button></div>
            </form>
        </div></div>
    </div>
</section>
