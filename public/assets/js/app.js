const rolePreview = document.querySelector('[data-role-preview]');

if (rolePreview && rolePreview.form) {
    // Submit a regular GET request so PHP renders the selected role preview.
    rolePreview.addEventListener('change', () => {
        rolePreview.form.requestSubmit();
    });
}
