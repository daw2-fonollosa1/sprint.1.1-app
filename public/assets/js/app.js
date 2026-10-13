// This script is loaded by both layouts, so first check whether the role selector exists.
const rolePreview = document.querySelector('[data-role-preview]');

if (rolePreview && rolePreview.form) {
    // The role selector belongs to a normal GET form in the navigation bar.
    // Submitting that form asks PHP for a newly rendered page with the chosen role.
    // requestSubmit() behaves like clicking the form's submit button, so the page
    // keeps its regular browser navigation instead of switching views in JavaScript.
    rolePreview.addEventListener('change', () => {
        rolePreview.form.requestSubmit();
    });
}

// The login form exists only on the authentication page, so other pages can safely skip this block.
const loginForm = document.querySelector('[data-login-form]');

if (loginForm) {
    // Find every input marked as required in the form. Saving this list once lets
    // submit and input events validate the same fields without repeating selectors.
    const requiredFields = Array.from(loginForm.querySelectorAll('[required]'));
    // The summary displays one general message when the visitor submits invalid data.
    const errorSummary = document.querySelector('[data-login-summary]');

    // Validate one input and keep its message, Bootstrap style, and accessibility
    // attributes in sync. The function returns true when the input is filled in.
    function validateRequiredField(field) {
        // The view gives each input a related message element. For example, username
        // is paired with an element whose ID is username-feedback.
        const feedback = document.getElementById(`${field.id}-feedback`);
        // trim() removes spaces at the beginning and end before checking for emptiness.
        const isEmpty = field.value.trim() === '';
        // Keep user-facing wording in the HTML data attribute so it stays with its field.
        const message = isEmpty ? field.dataset.requiredMessage : '';

        // setCustomValidity() records the browser's validation state. The page uses
        // novalidate, so we also apply Bootstrap's invalid class and show our own text.
        // aria-invalid communicates the same error state to assistive technologies.
        field.setCustomValidity(message);
        field.classList.toggle('is-invalid', isEmpty);
        field.setAttribute('aria-invalid', String(isEmpty));

        // Put the field-specific message into its matching feedback area, if present.
        if (feedback) {
            feedback.textContent = message;
        }

        // The submit handler uses this result to decide whether to send the form to PHP.
        return !isEmpty;
    }

    // Run client-side checks before the browser sends the form's POST request to PHP.
    loginForm.addEventListener('submit', (event) => {
        // map() checks each field and returns its true/false result. every(Boolean)
        // is true only when every required field passed the validation function.
        const isValid = requiredFields.map(validateRequiredField).every(Boolean);

        if (!isValid) {
            // Keep the visitor on this page so they can fix the errors before submitting again.
            event.preventDefault();
            // Reveal a general explanation in addition to the message beside each empty field.
            errorSummary.hidden = false;
            errorSummary.textContent = 'Revisa els camps indicats abans de continuar.';
            // Move keyboard focus to the first invalid field to make it easier to correct.
            loginForm.querySelector('.is-invalid')?.focus();
        }
    });

    // Listen to typing in every required input so an error can clear as soon as it is fixed.
    requiredFields.forEach((field) => {
        field.addEventListener('input', () => {
            // Do not show errors before the first submit. Once a field is marked invalid,
            // revalidate it on each edit so its Bootstrap style and message can be removed.
            if (field.classList.contains('is-invalid')) {
                validateRequiredField(field);
            }

            // The general summary should stay visible while any required field is blank.
            // Check all fields because editing one input may not fix another empty input.
            const hasInvalidField = requiredFields.some((item) => item.value.trim() === '');
            if (!hasInvalidField) {
                // All required inputs now contain text, so clear the general message.
                errorSummary.hidden = true;
                errorSummary.textContent = '';
            }
        });
    });
}

// The PHP layout adds this marker only when the administrator preview is open.
// Teacher, student, and login pages therefore skip the management code below.
const adminRoot = document.querySelector('[data-admin-page]');

if (adminRoot) {
    // PHP stores the example records in a JSON script element. Parse them into
    // JavaScript objects so the demo can temporarily add, edit, or remove records.
    const dataElement = document.getElementById('admin-demo-data');
    const demoData = JSON.parse(dataElement.textContent);
    const currentPage = adminRoot.dataset.adminPage;

    // Escape special characters before inserting user-entered values into table HTML.
    // This prevents text such as <script> from being interpreted as markup.
    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, (character) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
        })[character]);
    }

    // Show a success message after a demo action and keep it visible in the page flow.
    function showFeedback(message) {
        const alert = document.querySelector('[data-admin-feedback]');
        alert.textContent = message;
        alert.classList.remove('d-none');
    }

    // Display or clear a duplicate-code/name error inside the form being edited.
    function showFormError(form, message = '') {
        const error = form.querySelector('[data-form-error]');
        error.textContent = message;
        error.classList.toggle('d-none', message === '');
    }

    // Bootstrap creates the modal behavior; these helpers keep its calls in one place.
    function openModal(modal) {
        bootstrap.Modal.getOrCreateInstance(modal).show();
    }

    function closeModal(modal) {
        bootstrap.Modal.getOrCreateInstance(modal).hide();
    }

    // Render records into a table body, or show a friendly row when there are none.
    function renderRows(list, records, columnCount, emptyMessage, makeRow) {
        if (records.length === 0) {
            list.innerHTML = `<tr><td colspan="${columnCount}" class="text-center text-secondary py-4">${emptyMessage}</td></tr>`;
            return;
        }

        list.innerHTML = records.map(makeRow).join('');
    }

    // Build the Edit and Delete buttons used in each management table row.
    function rowActions(id, type) {
        const safeId = escapeHtml(id);
        return `<td class="text-end text-nowrap">
            <button class="btn btn-sm btn-outline-secondary" type="button" data-edit-${type}="${safeId}">Edita</button>
            <button class="btn btn-sm btn-outline-danger" type="button" data-delete-${type}="${safeId}">Elimina</button>
        </td>`;
    }

    // Read the submitted fields, reject duplicate codes/usernames, then add or update
    // one object in the in-memory array. Returning null means the form showed an error.
    function saveRecord(records, form, fieldNames, uniqueField, idPrefix, duplicateMessage) {
        const formData = new FormData(form);
        const record = { id: String(formData.get('id') || '') };

        fieldNames.forEach((fieldName) => {
            record[fieldName] = String(formData.get(fieldName) || '').trim();
        });

        const isDuplicate = records.some((existingRecord) =>
            existingRecord[uniqueField].toLocaleLowerCase('ca') === record[uniqueField].toLocaleLowerCase('ca') &&
            existingRecord.id !== record.id,
        );

        if (isDuplicate) {
            showFormError(form, duplicateMessage);
            return null;
        }

        const existingRecord = records.find((item) => item.id === record.id);
        if (existingRecord) {
            const previousRole = existingRecord.role;
            fieldNames.forEach((fieldName) => {
                existingRecord[fieldName] = record[fieldName];
            });
            return { record: existingRecord, previousRole, isNew: false };
        }

        // The generated ID only needs to be unique during this open-page demonstration.
        record.id = `${idPrefix}-${Date.now()}-${Math.floor(Math.random() * 1000)}`;
        records.push(record);
        return { record, previousRole: '', isNew: true };
    }

    // Remove one item by ID and return it so callers can use its name in feedback.
    function removeRecord(records, id) {
        const index = records.findIndex((record) => record.id === id);
        return index === -1 ? null : records.splice(index, 1)[0];
    }

    // Fill a shared create/edit form. An empty record starts a new item; an existing
    // record fills the inputs so the same form handles both actions.
    function prepareForm(form, modal, title, heading, record, fieldNames) {
        form.reset();
        showFormError(form);
        form.elements.namedItem('id').value = record ? record.id : '';

        fieldNames.forEach((fieldName) => {
            form.elements.namedItem(fieldName).value = record ? record[fieldName] || '' : '';
        });

        title.textContent = heading;
        openModal(modal);
    }

    // Keep a deleted or reclassified user out of every project's saved assignments.
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

    // Project page: edit project details and save teacher, student, and item assignments.
    if (currentPage === 'projects') {
        const list = document.querySelector('[data-project-list]');
        const form = document.querySelector('[data-project-form]');
        const modal = document.getElementById('project-modal');
        const title = document.getElementById('project-modal-title');
        const picker = document.querySelector('[data-project-picker]');
        const assignmentPanel = document.querySelector('[data-project-assignments]');
        const emptyMessage = document.querySelector('[data-no-projects]');
        let selectedProjectId = demoData.projects[0]?.id || '';

        // Fill the table, project selector, and assignment checkboxes from the sample data.
        function renderProjects() {
            renderRows(list, demoData.projects, 4, 'Encara no hi ha projectes.', (project) => `
                <tr class="${project.id === selectedProjectId ? 'table-active' : ''}">
                    <td>${escapeHtml(project.code)}</td>
                    <td><button class="btn btn-link p-0 text-start fw-semibold" type="button" data-select-project="${escapeHtml(project.id)}">${escapeHtml(project.name)}</button><p class="small text-secondary mb-0">${escapeHtml(project.description)}</p></td>
                    <td><span class="badge text-bg-success">Actiu</span></td>
                    ${rowActions(project.id, 'project')}
                </tr>
            `);

            picker.innerHTML = demoData.projects.map((project) =>
                `<option value="${escapeHtml(project.id)}" ${project.id === selectedProjectId ? 'selected' : ''}>${escapeHtml(project.code)} · ${escapeHtml(project.name)}</option>`,
            ).join('');
            assignmentPanel.classList.toggle('d-none', demoData.projects.length === 0);
            emptyMessage.classList.toggle('d-none', demoData.projects.length > 0);
            renderAssignments();
        }

        // Rebuild the checkbox lists for the project currently selected in the picker.
        function renderAssignments() {
            const project = demoData.projects.find((item) => item.id === selectedProjectId);
            if (!project) return;

            const groups = [
                ['teachers', 'teacherIds', demoData.users.filter((user) => user.role === 'teacher'), (user) => `${user.name} (@${user.username})`],
                ['students', 'studentIds', demoData.users.filter((user) => user.role === 'student'), (user) => `${user.name} · ${user.group || 'Sense grup'}`],
                ['items', 'itemIds', demoData.items, (item) => `${item.code} · ${item.name}`],
            ];

            groups.forEach(([groupName, property, records, makeLabel]) => {
                const container = document.querySelector(`[data-assignment-options="${groupName}"]`);
                container.innerHTML = records.map((record) => {
                    const checkboxId = `assignment-${groupName}-${record.id}`;
                    const checked = project[property].includes(record.id) ? 'checked' : '';
                    return `<div class="form-check"><input class="form-check-input" id="${checkboxId}" type="checkbox" value="${escapeHtml(record.id)}" ${checked}><label class="form-check-label" for="${checkboxId}">${escapeHtml(makeLabel(record))}</label></div>`;
                }).join('') || '<p class="small text-secondary mb-0">No hi ha opcions disponibles.</p>';
            });
        }

        document.querySelector('[data-create-project]').addEventListener('click', () => {
            prepareForm(form, modal, title, 'Crea un projecte', null, ['code', 'name', 'description']);
        });

        list.addEventListener('click', (event) => {
            const selectButton = event.target.closest('[data-select-project]');
            const editButton = event.target.closest('[data-edit-project]');
            const deleteButton = event.target.closest('[data-delete-project]');

            if (selectButton) {
                selectedProjectId = selectButton.dataset.selectProject;
                renderProjects();
            }
            if (editButton) {
                const project = demoData.projects.find((item) => item.id === editButton.dataset.editProject);
                if (project) prepareForm(form, modal, title, 'Edita el projecte', project, ['code', 'name', 'description']);
            }
            if (deleteButton) {
                const project = demoData.projects.find((item) => item.id === deleteButton.dataset.deleteProject);
                if (project && window.confirm(`Vols eliminar el projecte ${project.code}?`)) {
                    removeRecord(demoData.projects, project.id);
                    if (selectedProjectId === project.id) selectedProjectId = demoData.projects[0]?.id || '';
                    showFeedback(`S’ha eliminat el projecte ${project.code}.`);
                    renderProjects();
                }
            }
        });

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const result = saveRecord(demoData.projects, form, ['code', 'name', 'description'], 'code', 'project', 'Aquest codi de projecte ja existeix.');
            if (!result) return;
            if (result.isNew) {
                result.record.teacherIds = [];
                result.record.studentIds = [];
                result.record.itemIds = [];
                selectedProjectId = result.record.id;
            }
            showFeedback(`S’han desat les dades del projecte ${result.record.code}.`);
            renderProjects();
            closeModal(modal);
        });

        picker.addEventListener('change', () => {
            selectedProjectId = picker.value;
            renderProjects();
        });

        // Each Save button reads its own checkbox group and updates that project property.
        const assignmentProperties = { teachers: 'teacherIds', students: 'studentIds', items: 'itemIds' };
        const assignmentLabels = { teachers: 'professorat', students: 'alumnat', items: 'ítems avaluables' };
        document.querySelectorAll('[data-save-assignments]').forEach((button) => {
            button.addEventListener('click', () => {
                const project = demoData.projects.find((item) => item.id === selectedProjectId);
                if (!project) return;
                const groupName = button.dataset.saveAssignments;
                const container = document.querySelector(`[data-assignment-options="${groupName}"]`);
                project[assignmentProperties[groupName]] = Array.from(container.querySelectorAll(':checked'), (input) => input.value);
                showFeedback(`S’ha actualitzat l’assignació de ${assignmentLabels[groupName]} al projecte ${project.code}.`);
            });
        });

        renderProjects();
    }

    // Evaluable-item page: search and maintain the sample criteria list.
    if (currentPage === 'evaluable-items') {
        const list = document.querySelector('[data-item-list]');
        const search = document.querySelector('[data-item-search]');
        const form = document.querySelector('[data-item-form]');
        const modal = document.getElementById('item-modal');
        const title = document.getElementById('item-modal-title');

        function renderItems() {
            const searchText = search.value.trim().toLocaleLowerCase('ca');
            const visibleItems = demoData.items.filter((item) =>
                `${item.code} ${item.name} ${item.description}`.toLocaleLowerCase('ca').includes(searchText),
            );

            renderRows(list, visibleItems, 4, 'No s’han trobat ítems avaluables.', (item) => `
                <tr><td>${escapeHtml(item.code)}</td><td class="fw-semibold">${escapeHtml(item.name)}</td>
                <td>${escapeHtml(item.description)}</td>${rowActions(item.id, 'item')}</tr>
            `);
        }

        document.querySelector('[data-create-item]').addEventListener('click', () => {
            prepareForm(form, modal, title, 'Crea un ítem avaluable', null, ['code', 'name', 'description']);
        });

        list.addEventListener('click', (event) => {
            const editButton = event.target.closest('[data-edit-item]');
            const deleteButton = event.target.closest('[data-delete-item]');

            if (editButton) {
                const item = demoData.items.find((record) => record.id === editButton.dataset.editItem);
                if (item) prepareForm(form, modal, title, 'Edita l’ítem avaluable', item, ['code', 'name', 'description']);
            }
            if (deleteButton) {
                const item = demoData.items.find((record) => record.id === deleteButton.dataset.deleteItem);
                if (item && window.confirm(`Vols eliminar l’ítem ${item.code}?`)) {
                    removeRecord(demoData.items, item.id);
                    demoData.projects.forEach((project) => {
                        project.itemIds = project.itemIds.filter((id) => id !== item.id);
                    });
                    showFeedback(`S’ha eliminat l’ítem ${item.code}.`);
                    renderItems();
                }
            }
        });

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const result = saveRecord(demoData.items, form, ['code', 'name', 'description'], 'code', 'item', 'Aquest codi d’ítem ja existeix.');
            if (!result) return;
            showFeedback(`S’han desat les dades de l’ítem ${result.record.code}.`);
            renderItems();
            closeModal(modal);
        });

        search.addEventListener('input', renderItems);
        renderItems();
    }
}
