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
