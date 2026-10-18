// PHP creates the user rows and handles all database writes. This file only improves
// the interface with local filtering, confirmation, and the Bootstrap create/edit modal.
(() => {
    // This script is loaded only on the administrator user page. Stop safely if
    // the expected table is missing, for example when the script is cached elsewhere.
    const userList = document.querySelector('[data-user-list]');
    if (!userList) return;

    // Find the page controls once so event handlers can update the same elements.
    const search = document.querySelector('[data-user-search]');
    const modalElement = document.querySelector('[data-user-modal]');
    const form = document.querySelector('[data-user-form]');
    const activeFilter = document.querySelector('[data-user-filter].active');
    const title = document.getElementById('user-modal-title');
    const actionField = document.querySelector('[data-user-action]');
    const idField = document.querySelector('[data-user-id-field]');
    const fullNameField = document.getElementById('user-name');
    const usernameField = document.getElementById('user-username');
    const roleField = document.getElementById('user-role');
    const passwordField = document.querySelector('[data-user-password]');
    const submitButton = document.querySelector('[data-user-submit]');
    const emptyRow = document.querySelector('[data-user-empty]');
    const userRows = Array.from(userList.querySelectorAll('[data-user-row]'));
    let selectedRole = activeFilter ? activeFilter.dataset.userFilter : 'teacher';

    // Filter the PHP-rendered rows by role and optional search text.
    // This changes only what is visible in this browser; it does not load or save records.
    function filterUsers() {
        const searchText = search.value.trim().toLocaleLowerCase('ca');
        let visibleCount = 0;

        userRows.forEach((row) => {
            const matchesRole = row.dataset.userRole === selectedRole;
            const matchesSearch = row.textContent.toLocaleLowerCase('ca').includes(searchText);
            const isVisible = matchesRole && matchesSearch;

            row.classList.toggle('d-none', !isVisible);
            if (isVisible) visibleCount += 1;
        });

        emptyRow.classList.toggle('d-none', visibleCount > 0);
    }

    // Start a new account using the shared modal. Reset removes data left by a previous
    // edit, then the hidden action tells PHP to run the create operation on form submit.
    // PHP receives this form normally, so database saving does not depend on JavaScript.
    document.querySelector('[data-create-user]').addEventListener('click', () => {
        form.reset();
        actionField.value = 'create';
        idField.value = '';
        roleField.value = selectedRole;
        passwordField.required = true;
        title.textContent = 'Crea un usuari';
        submitButton.textContent = 'Crea el compte';
        bootstrap.Modal.getOrCreateInstance(modalElement).show();
    });

    // Event delegation listens on the table body, then finds the edit button that was clicked.
    // Its data attributes contain the selected row's public fields, but never a password.
    userList.addEventListener('click', (event) => {
        const editButton = event.target.closest('[data-edit-user]');
        if (!editButton) return;

        form.reset();
        actionField.value = 'update';
        idField.value = editButton.dataset.userId;
        fullNameField.value = editButton.dataset.userName;
        usernameField.value = editButton.dataset.userUsername;
        roleField.value = editButton.dataset.userRoleValue;
        passwordField.required = false;
        title.textContent = 'Edita un usuari';
        submitButton.textContent = 'Desa els canvis';
        bootstrap.Modal.getOrCreateInstance(modalElement).show();
    });

    // Ask before submitting a delete form. PHP will block the delete if the account
    // still has project assignments, so related grades are never removed by a cascade.
    document.querySelectorAll('[data-delete-user-form]').forEach((deleteForm) => {
        deleteForm.addEventListener('submit', (event) => {
            const userName = deleteForm.dataset.userName;
            const message = `Vols eliminar el compte de ${userName}? Si té projectes assignats, PHP bloquejarà l’eliminació.`;

            if (!window.confirm(message)) event.preventDefault();
        });
    });

    // Update the active tab and filter visible rows without requesting or changing database records.
    document.querySelectorAll('[data-user-filter]').forEach((button) => {
        button.addEventListener('click', () => {
            selectedRole = button.dataset.userFilter;

            document.querySelectorAll('[data-user-filter]').forEach((filter) => {
                const isSelected = filter.dataset.userFilter === selectedRole;
                filter.classList.toggle('active', isSelected);
                filter.setAttribute('aria-pressed', String(isSelected));
            });

            filterUsers();
        });
    });

    // Refilter as the user types, and run once so the initial table matches the selected role.
    search.addEventListener('input', filterUsers);
    filterUsers();

    // If PHP rejected a submitted form, restore the modal after rendering its Catalan errors.
    // This keeps the user in context without relying on a separate JavaScript validation result.
    if (modalElement.dataset.openOnLoad === 'true') {
        bootstrap.Modal.getOrCreateInstance(modalElement).show();
    }
})();
