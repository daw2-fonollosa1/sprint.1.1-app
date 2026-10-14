// This file is loaded only for the teacher preview. PHP still renders each page.
const gradePage = document.querySelector('[data-grade-page]');

if (gradePage) {
    // PHP places the teacher's assigned projects, students, items, and sample grades in JSON.
    const dataElement = document.getElementById('teacher-demo-data');
    const demoData = JSON.parse(dataElement.textContent);

    // Keep references to the grade form controls and result areas used below.
    const form = document.querySelector('[data-grade-form]');
    const projectSelect = document.querySelector('[data-grade-project]');
    const studentSelect = document.querySelector('[data-grade-student]');
    const itemSelect = document.querySelector('[data-grade-item]');
    const gradeInput = document.querySelector('[data-grade-value]');
    const selectionText = document.querySelector('[data-grade-selection]');
    const gradeList = document.querySelector('[data-teacher-grade-list]');
    const averageText = document.querySelector('[data-grade-average]');
    const averageHelp = document.querySelector('[data-grade-average-help]');
    const errorMessage = document.querySelector('[data-grade-error]');
    const feedbackMessage = document.querySelector('[data-grade-feedback]');

    // Find the selected project and return only records linked to it.
    function getProject() {
        return demoData.projects.find((project) => project.id === projectSelect.value) || null;
    }

    function getUser(userId) {
        return demoData.users.find((user) => user.id === userId) || null;
    }

    function getItem(itemId) {
        return demoData.items.find((item) => item.id === itemId) || null;
    }

    function getGradeEntry(projectId, studentId, itemId) {
        return demoData.grades.find((entry) =>
            entry.projectId === projectId && entry.studentId === studentId && entry.itemId === itemId,
        ) || null;
    }

    // Replace a select's options with safe DOM options instead of inserting data as HTML.
    function fillSelect(select, records, getId, getLabel, preferredId) {
        select.replaceChildren();
        records.forEach((record) => {
            select.add(new Option(getLabel(record), getId(record)));
        });

        if (records.some((record) => getId(record) === preferredId)) {
            select.value = preferredId;
        }
        select.disabled = records.length === 0;
    }

    // Rebuild student and item choices after the teacher changes projects. Keep the
    // previous choice when it belongs to the newly selected project; otherwise use
    // that project's first available student or item.
    function refreshProjectOptions() {
        const project = getProject();
        if (!project) {
            fillSelect(studentSelect, [], () => '', () => '', '');
            fillSelect(itemSelect, [], () => '', () => '', '');
            form.querySelector('button[type="submit"]').disabled = true;
            return;
        }

        const currentStudentId = studentSelect.value;
        const currentItemId = itemSelect.value;
        const students = demoData.users.filter((user) => project.studentIds.includes(user.id));
        const items = demoData.items.filter((item) => project.itemIds.includes(item.id));
        const defaultStudentId = gradePage.dataset.selectedStudent || students[0]?.id || '';
        const defaultItemId = gradePage.dataset.selectedItem || items[0]?.id || '';

        fillSelect(studentSelect, students, (user) => user.id, (user) => user.name, currentStudentId || defaultStudentId);
        fillSelect(itemSelect, items, (item) => item.id, (item) => `${item.code} · ${item.name}`, currentItemId || defaultItemId);
        form.querySelector('button[type="submit"]').disabled = students.length === 0 || items.length === 0;
    }

    // Calculate the unweighted arithmetic mean for this exact project/student/item.
    // Grades from other projects or evaluation items are never included in this mean.
    function calculateAverage(teacherGrades) {
        const values = Object.values(teacherGrades).map(Number).filter(Number.isFinite);
        if (values.length === 0) return null;

        const total = values.reduce((sum, grade) => sum + grade, 0);
        return total / values.length;
    }

    // Show each assigned teacher's individual grade and the calculated mean.
    function renderGradeSummary() {
        const project = getProject();
        const student = getUser(studentSelect.value);
        const item = getItem(itemSelect.value);

        if (!project || !student || !item) {
            selectionText.textContent = 'Selecciona un projecte amb alumnat i ítems avaluables assignats.';
            gradeList.innerHTML = '<tr><td colspan="2" class="text-secondary">No hi ha dades per a aquesta selecció.</td></tr>';
            averageText.textContent = '—';
            averageHelp.textContent = 'No es pot calcular la mitjana sense qualificacions.';
            gradeInput.value = '';
            return;
        }

        // Identify all three parts of the current grade so the teacher can confirm the target.
        selectionText.textContent = `Projecte: ${project.name} · Alumne/a: ${student.name} · Ítem: ${item.name}`;

        const entry = getGradeEntry(project.id, student.id, item.id);
        const teacherGrades = entry ? entry.teacherGrades : {};
        const assignedTeachers = demoData.users.filter((user) =>
            user.role === 'teacher' && project.teacherIds.includes(user.id),
        );
        const gradeRows = assignedTeachers.filter((teacher) =>
            Object.prototype.hasOwnProperty.call(teacherGrades, teacher.id),
        );

        gradeList.replaceChildren();
        if (gradeRows.length === 0) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 2;
            cell.className = 'text-secondary';
            cell.textContent = 'Encara no hi ha notes per a aquesta combinació.';
            row.append(cell);
            gradeList.append(row);
        } else {
            gradeRows.forEach((teacher) => {
                const row = document.createElement('tr');
                const nameCell = document.createElement('td');
                const gradeCell = document.createElement('td');
                nameCell.textContent = teacher.name;
                gradeCell.textContent = Number(teacherGrades[teacher.id]).toFixed(1);
                row.append(nameCell, gradeCell);
                gradeList.append(row);
            });
        }

        // Put this teacher's existing grade in the input, if one has already been entered.
        const ownGrade = teacherGrades[demoData.teacherId];
        gradeInput.value = ownGrade === undefined ? '' : Number(ownGrade).toFixed(1);

        const average = calculateAverage(teacherGrades);
        averageText.textContent = average === null ? '—' : average.toFixed(2);
        averageHelp.textContent = average === null
            ? 'La mitjana apareixerà quan algun docent introduïsca una nota.'
            : `Mitjana de ${gradeRows.length} ${gradeRows.length === 1 ? 'docent' : 'docents'} per a aquest ítem.`;
    }

    // Clear old feedback whenever the teacher starts a different grading action.
    function clearMessages() {
        errorMessage.classList.add('d-none');
        feedbackMessage.classList.add('d-none');
        errorMessage.textContent = '';
        feedbackMessage.textContent = '';
    }

    projectSelect.addEventListener('change', () => {
        // Reset the selected student/item to valid choices for the newly selected project.
        gradePage.dataset.selectedStudent = '';
        gradePage.dataset.selectedItem = '';
        refreshProjectOptions();
        clearMessages();
        renderGradeSummary();
    });

    studentSelect.addEventListener('change', () => {
        clearMessages();
        renderGradeSummary();
    });

    itemSelect.addEventListener('change', () => {
        clearMessages();
        renderGradeSummary();
    });

    // Save the teacher's grade only in the JavaScript data object for this page visit.
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        clearMessages();

        const project = getProject();
        const student = getUser(studentSelect.value);
        const item = getItem(itemSelect.value);
        const enteredValue = gradeInput.value.trim();
        const grade = Number(enteredValue);

        // Validate the scale shown in the approved grading wireframe (0 through 10).
        const hasMoreThanOneDecimal = Number.isFinite(grade) && Math.round(grade * 10) !== grade * 10;
        if (!project || !student || !item || enteredValue === '' || !Number.isFinite(grade) || grade < 0 || grade > 10 || hasMoreThanOneDecimal) {
            errorMessage.textContent = 'Selecciona un projecte, un alumne i un ítem, i introdueix una nota entre 0 i 10 amb un màxim d’un decimal.';
            errorMessage.classList.remove('d-none');
            gradeInput.focus();
            return;
        }

        // Store one decimal place, matching the sample values shown in the wireframe.
        const roundedGrade = Math.round(grade * 10) / 10;
        let entry = getGradeEntry(project.id, student.id, item.id);
        if (!entry) {
            entry = {
                projectId: project.id,
                studentId: student.id,
                itemId: item.id,
                teacherGrades: {},
            };
            demoData.grades.push(entry);
        }

        // Update only the current teacher's grade; other teachers' values stay in the entry.
        entry.teacherGrades[demoData.teacherId] = roundedGrade;
        renderGradeSummary();
        feedbackMessage.textContent = `S’ha desat la nota de ${roundedGrade.toFixed(1)}. La mitjana aritmètica actual és ${calculateAverage(entry.teacherGrades).toFixed(2)}.`;
        feedbackMessage.classList.remove('d-none');
    });

    // Draw the initial grade screen using the project and IDs selected by the PHP controller.
    refreshProjectOptions();
    renderGradeSummary();
}
