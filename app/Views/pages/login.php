<section class="row justify-content-center w-100" aria-labelledby="login-title">
    <!-- Bootstrap's responsive grid changes the card width at each screen size. -->
    <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
        <!-- Bootstrap card, spacing, border, and shadow classes provide the visual design. -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
                <p class="small text-uppercase text-secondary fw-semibold mb-2">Gestió de projectes</p>
                <h1 class="h2 mb-4" id="login-title">Iniciar sessió</h1>

                <?php if ($loginNotice !== ''): ?>
                    <div class="alert alert-success" role="status">
                        <?= htmlspecialchars($loginNotice, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <!-- PHP prints this alert for incorrect credentials, a stale form, or a database problem. -->
                <?php if ($loginError !== ''): ?>
                    <div class="alert alert-danger" role="alert" data-server-error>
                        <?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <!-- JavaScript reveals this Bootstrap alert if one or both required fields are empty. -->
                <div class="alert alert-danger" role="alert" data-login-summary hidden></div>

                <!-- PHP checks the database credentials; JavaScript only validates required fields in the browser. -->
                <form action="/?page=login" method="post" data-login-form novalidate>
                    <!-- The token lets PHP reject a form that did not come from this session. -->
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
                    >
                    <!-- PHP re-displays the username after an error so the visitor does not retype it. -->
                    <div class="mb-3">
                        <label class="form-label" for="username">Usuari</label>
                        <!-- Bootstrap's is-invalid class makes a server-side validation error visible. -->
                        <input
                            class="form-control<?= isset($formErrors['username']) ? ' is-invalid' : '' ?>"
                            id="username"
                            name="username"
                            type="text"
                            placeholder="nom.usuari"
                            autocomplete="username"
                            value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>"
                            data-required-message="Introdueix el teu nom d’usuari."
                            aria-describedby="username-feedback"
                            aria-invalid="<?= isset($formErrors['username']) ? 'true' : 'false' ?>"
                            required
                        >
                        <div class="invalid-feedback" id="username-feedback">
                            <?= htmlspecialchars($formErrors['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>

                    <!-- Password input masks typed characters; PHP compares it with the stored password hash. -->
                    <div class="mb-3">
                        <label class="form-label" for="password">Contrasenya</label>
                        <input
                            class="form-control<?= isset($formErrors['password']) ? ' is-invalid' : '' ?>"
                            id="password"
                            name="password"
                            type="password"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            data-required-message="Introdueix la contrasenya."
                            aria-describedby="password-feedback"
                            aria-invalid="<?= isset($formErrors['password']) ? 'true' : 'false' ?>"
                            required
                        >
                        <div class="invalid-feedback" id="password-feedback">
                            <?= htmlspecialchars($formErrors['password'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>

                    <button class="btn btn-primary btn-lg w-100" type="submit">Iniciar sessió</button>
                </form>

                <!-- Explain how accounts are provided without suggesting that visitors can self-register. -->
                <div class="border-top mt-4 pt-3">
                    <p class="small text-body-secondary mb-2">
                        Introdueix les dades del compte que t’ha proporcionat l’administrador.
                    </p>
                    <p class="small text-body-secondary mb-0">
                        No hi ha registre públic. El sistema comprova el compte i el rol abans de mostrar les dades.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
