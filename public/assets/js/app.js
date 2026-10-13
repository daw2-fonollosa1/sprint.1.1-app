// This script is loaded by both layouts, so first check whether the role selector exists.
const rolePreview = document.querySelector('[data-role-preview]');

if (rolePreview && rolePreview.form) {
    // When the selection changes, submit its regular HTML GET form.
    // The browser requests a new page and PHP renders it; this is not SPA navigation.
    rolePreview.addEventListener('change', () => {
        rolePreview.form.requestSubmit();
    });
}

// The login form exists only on the authentication page, so other pages can safely skip this block.
const loginForm = document.querySelector('[data-login-form]');

if (loginForm) {
    // Collect required inputs once so the same validation function can check both fields.
    const requiredFields = Array.from(loginForm.querySelectorAll('[required]'));
    // This summary gives both screen readers and sighted users an overview of the form errors.
    const errorSummary = document.querySelector('[data-login-summary]');

    // Check one required field and update its visible and accessible error state.
    function validateRequiredField(field) {
        // Each input has a matching feedback element whose ID ends in "-feedback".
        const feedback = document.getElementById(`${field.id}-feedback`);
        // Spaces alone count as empty, so whitespace cannot satisfy a required field.
        const isEmpty = field.value.trim() === '';
        // The input's data-required-message attribute contains the Catalan message to display.
        const message = isEmpty ? field.dataset.requiredMessage : '';

        // Store the message for browser validation, apply Bootstrap's invalid style,
        // and expose the same state to assistive technology using aria-invalid.
        field.setCustomValidity(message);
        field.classList.toggle('is-invalid', isEmpty);
        field.setAttribute('aria-invalid', String(isEmpty));

        if (feedback) {
            feedback.textContent = message;
        }

        // Return true only when the field contains something other than whitespace.
        return !isEmpty;
    }

    // Validate when the visitor tries to submit the form to the PHP controller.
    loginForm.addEventListener('submit', (event) => {
        // Check every field; every(Boolean) is true only if all checks passed.
        const isValid = requiredFields.map(validateRequiredField).every(Boolean);

        if (!isValid) {
            // Stop the POST so PHP does not process missing fields, then show helpful feedback.
            event.preventDefault();
            errorSummary.hidden = false;
            errorSummary.textContent = 'Revisa els camps indicats abans de continuar.';
            loginForm.querySelector('.is-invalid')?.focus();
        }
    });

    // Recheck a field as the visitor types so its message disappears when corrected.
    requiredFields.forEach((field) => {
        field.addEventListener('input', () => {
            // Do not show errors before the first submit; update only fields already marked invalid.
            if (field.classList.contains('is-invalid')) {
                validateRequiredField(field);
            }

            // Hide the summary after neither required field is empty anymore.
            const hasInvalidField = requiredFields.some((item) => item.value.trim() === '');
            if (!hasInvalidField) {
                errorSummary.hidden = true;
                errorSummary.textContent = '';
            }
        });
    });
}
