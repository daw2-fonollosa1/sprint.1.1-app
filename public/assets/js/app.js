// The shared application layout loads this script where the role selector is available.
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
