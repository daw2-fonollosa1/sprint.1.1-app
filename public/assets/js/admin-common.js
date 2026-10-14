// Shared setup and helper functions for the administrator pages.
(() => {
    // This element is present only when PHP has selected an administrator page.
    const adminRoot = document.querySelector('[data-admin-page]');
    if (!adminRoot) return;

    // Read the initial sample records supplied by DashboardController as JSON.
    const dataElement = document.getElementById('admin-demo-data');
    const demoData = JSON.parse(dataElement.textContent);

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

    // Feature scripts use this shared object instead of copying the same helpers.
    window.AdminDemo = {
        page: adminRoot.dataset.adminPage,
        data: demoData,
        escapeHtml,
        showFeedback,
        showFormError,
        openModal,
        closeModal,
        renderRows,
        rowActions,
        saveRecord,
        removeRecord,
        prepareForm,
    };
})();
