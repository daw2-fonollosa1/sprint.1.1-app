(() => {
    const admin = window.AdminDemo;
    if (!admin || admin.page !== 'users') return;

    const {
        page: currentPage,
        data: demoData,
        escapeHtml,
        showFeedback,
        openModal,
        closeModal,
        renderRows,
        rowActions,
        saveRecord,
        removeRecord,
        prepareForm,
    } = admin;

    // Keep a deleted or reclassified user out of all saved sample assignments.
    function removeUserAssignments(userId) {
        demoData.projects.forEach((project) => {
            project.teacherIds = project.teacherIds.filter((id) => id !== userId);
            project.studentIds = project.studentIds.filter((id) => id !== userId);
        });
    }

    // User page: separate teacher and student lists, while using one reusable form.
    if (currentPage === 'users') {
        const list = document.querySelector('[data-user-list]');
        const search = document.querySelector('[data-user-search]');
        const form = document.querySelector('[data-user-form]');
        const modal = document.getElementById('user-modal');
        const title = document.getElementById('user-modal-title');
        const role = document.getElementById('user-role');
        const groupField = document.querySelector('[data-student-group-field]');
        let activeRole = 'teacher';

        // Show only users matching the selected role and search text.
        function renderUsers() {
            const searchText = search.value.trim().toLocaleLowerCase('ca');
            const visibleUsers = demoData.users.filter((user) => {
                const searchableText = `${user.name} ${user.username} ${user.group}`.toLocaleLowerCase('ca');
                return user.role === activeRole && searchableText.includes(searchText);
            });

            renderRows(list, visibleUsers, 4, 'No s’han trobat usuaris.', (user) => `
                <tr>
                    <td class="fw-semibold">${escapeHtml(user.name)}</td>
                    <td>${escapeHtml(user.username)}</td>
                    <td>${user.role === 'teacher' ? 'Professor/a' : escapeHtml(user.group || 'Sense grup')}</td>
                    ${rowActions(user.id, 'user')}
                </tr>
            `);

            document.querySelector('[data-user-count="all"]').textContent = demoData.users.length;
            document.querySelector('[data-user-count="teacher"]').textContent = demoData.users.filter((user) => user.role === 'teacher').length;
            document.querySelector('[data-user-count="student"]').textContent = demoData.users.filter((user) => user.role === 'student').length;
        }

        // A group is relevant for students only, so hide and disable it for teachers.
        function updateGroupField() {
            const isStudent = role.value === 'student';
            groupField.classList.toggle('d-none', !isStudent);
            groupField.querySelector('input').disabled = !isStudent;
        }

        document.querySelector('[data-create-user]').addEventListener('click', () => {
            prepareForm(form, modal, title, 'Crea un usuari', null, ['name', 'username', 'role', 'group']);
            role.value = 'teacher';
            updateGroupField();
        });

        // Change the active role filter when either role button is selected.
        document.querySelectorAll('[data-user-filter]').forEach((button) => {
            button.addEventListener('click', () => {
                activeRole = button.dataset.userFilter;
                document.querySelectorAll('[data-user-filter]').forEach((filter) => {
                    const isActive = filter.dataset.userFilter === activeRole;
                    filter.classList.toggle('active', isActive);
                    filter.setAttribute('aria-pressed', String(isActive));
                });
                renderUsers();
            });
        });

        // Handle row actions on the table body; the rows themselves are recreated after edits.
        list.addEventListener('click', (event) => {
            const editButton = event.target.closest('[data-edit-user]');
            const deleteButton = event.target.closest('[data-delete-user]');

            if (editButton) {
                const user = demoData.users.find((item) => item.id === editButton.dataset.editUser);
                if (user) {
                    prepareForm(form, modal, title, 'Edita un usuari', user, ['name', 'username', 'role', 'group']);
                    role.value = user.role;
                    updateGroupField();
                }
            }

            if (deleteButton) {
                const user = demoData.users.find((item) => item.id === deleteButton.dataset.deleteUser);
                if (user && window.confirm(`Vols eliminar el compte de ${user.name}?`)) {
                    removeRecord(demoData.users, user.id);
                    removeUserAssignments(user.id);
                    showFeedback(`S’ha eliminat el compte de ${user.name}.`);
                    renderUsers();
                }
            }
        });

        // Save the form to the browser's temporary array, then refresh the visible list.
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const result = saveRecord(demoData.users, form, ['name', 'username', 'role', 'group'], 'username', 'user', 'Aquest nom d’usuari ja existeix.');
            if (!result) return;
            if (result.previousRole && result.previousRole !== result.record.role) removeUserAssignments(result.record.id);

            activeRole = result.record.role;
            document.querySelectorAll('[data-user-filter]').forEach((button) => {
                const isActive = button.dataset.userFilter === activeRole;
                button.classList.toggle('active', isActive);
                button.setAttribute('aria-pressed', String(isActive));
            });
            showFeedback(`S’han desat les dades de ${result.record.name}.`);
            renderUsers();
            closeModal(modal);
        });

        search.addEventListener('input', renderUsers);
        role.addEventListener('change', updateGroupField);
        renderUsers();
    }
})();
