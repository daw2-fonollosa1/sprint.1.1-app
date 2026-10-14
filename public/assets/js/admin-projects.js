(() => {
    const admin = window.AdminDemo;
    if (!admin || admin.page !== 'projects') return;

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
})();
