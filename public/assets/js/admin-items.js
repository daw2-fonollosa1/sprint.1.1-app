(() => {
    const admin = window.AdminDemo;
    if (!admin || admin.page !== 'evaluable-items') return;

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
})();
