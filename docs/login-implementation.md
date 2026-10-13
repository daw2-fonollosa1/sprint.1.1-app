# Login Prototype Implementation

## Scope

This change adds a login prototype to the PHP application. The mockup guides the placement of controls; Bootstrap 5 provides the responsive layout and visual styling. Users enter a username and password, there is no public registration, and the application has administrator, teacher, and student roles.

The prototype does not provide real authentication or access control. It does not store passwords, create sessions, or save the remember-me choice. A demo username selects a role, and any non-empty password is accepted for demonstration.

All text shown in the interface is in Catalan. Source code, comments, and this document are in English.

## Files created or changed

- `public/index.php` — Changed the front controller to show the login page by default and dispatch other page requests to the existing dashboard controller.
- `app/Controllers/LoginController.php` — Added the server-side demo login flow. It checks that both fields have values, maps the demo usernames to roles, displays an error for an unknown username, and redirects a valid demo user to the existing role preview.
- `app/Views/layouts/auth.php` — Added an HTML document layout for the login page. It loads Bootstrap 5, the shared stylesheet, and the application JavaScript without showing the role navigation on the login screen.
- `app/Views/pages/login.php` — Added the login form with username, password, remember-me checkbox, and submit button. Bootstrap classes provide the responsive grid, card, spacing, form controls, alerts, and button. It also shows demo instructions, validation feedback, and the prototype limitation.
- `public/assets/css/styles.css` — Keeps the shared application styles used by the dashboard. The login page relies on Bootstrap instead of custom login-specific styles.
- `public/assets/js/app.js` — Added client-side required-field validation and kept the existing role-preview form enhancement.
- `app/Views/pages/placeholder.php` — Updated the role destination notice so it clearly says that login and permissions are simulated and data is not saved.
- `docs/login-implementation.md` — Added this implementation guide.

## How the login page is assembled

1. A browser request to `/` reaches `public/index.php`.
2. The front controller reads the optional `page` query parameter. If it is missing, the page is `login`.
3. For the login page, `public/index.php` loads `LoginController` and calls its `index()` method.
4. `LoginController` prepares any validation errors or demo login errors.
5. The controller includes `app/Views/layouts/auth.php`.
6. The authentication layout creates the HTML document and includes `app/Views/pages/login.php` inside its main content area.
7. The browser receives the rendered HTML, loads Bootstrap and `public/assets/css/styles.css`, then loads `public/assets/js/app.js`.
8. A valid demo submission is handled by PHP and redirects to the existing dashboard page for the selected role.

The login layout is separate from `main.php` because the login screen has no application navigation bar. Bootstrap's grid and utility classes center the card and adjust its width for different screen sizes. The existing dashboard layout and destinations are reused after the demo login.

## How JavaScript validation works

The login form has the `data-login-form` attribute. `app.js` finds this form and collects its fields marked `required`.

### `validateRequiredField(field)`

This function checks whether one required field contains non-whitespace text.

- It reads the field's Catalan error message from `data-required-message`.
- `setCustomValidity()` sets or clears the browser's custom validation state.
- The Bootstrap class `is-invalid` turns on the field's invalid appearance and feedback text.
- `aria-invalid` tells assistive technology whether the field is invalid.
- The matching `invalid-feedback` element displays the message in Catalan.

### Submit event

The `submit` event validates all required fields before the browser sends the form. If any field is empty, the handler calls `preventDefault()`, displays a Catalan error summary, and focuses the first invalid field. If every field is valid, JavaScript allows the normal POST request to continue to PHP.

### Input event

Each required field listens for the `input` event. When the user starts correcting an invalid field, the function updates its message and invalid style. When all required fields contain text, the summary is hidden.

The form uses `novalidate` so the custom messages are controlled by this JavaScript. `LoginController` repeats the required-field checks on the server so empty fields are still handled if JavaScript is disabled or bypassed.

## How the role demonstration works

`LoginController` contains a small map of demo usernames to role keys:

- `admin` maps to the administrator preview.
- `teacher` maps to the teacher preview.
- `student` maps to the student preview.

When the username is recognized and the password contains non-whitespace text, PHP sends a `303` redirect to `/?page=dashboard&role=<role>`. The existing `DashboardController` then renders the matching role navigation and destination. The administrator now sees the administrator dashboard; teacher and student destinations still use the shared placeholder.

The password is not compared with a stored value or saved anywhere. The username and role in the URL are only demonstration inputs. A visitor can also open a role destination directly, so this flow must not be used to protect real application data.

The “Recorda’m” checkbox has no persistence behavior. Password recovery is not offered in this prototype.

## Demonstration inputs and expected results

The password must contain at least one non-whitespace character. Any such value is accepted with a recognized demo username.

| Username | Password | Expected result |
| --- | --- | --- |
| `admin` | `demo123` | Redirect to `/?page=dashboard&role=admin`; administrator navigation appears. |
| `teacher` | `demo123` | Redirect to `/?page=dashboard&role=teacher`; teacher navigation appears. |
| `student` | `demo123` | Redirect to `/?page=dashboard&role=student`; student navigation appears. |
| empty | `demo123` | Stay on login; show “Introdueix el teu nom d’usuari.” and the form summary. |
| `admin` | empty | Stay on login; show “Introdueix la contrasenya.” and the form summary. |
| empty | empty | Stay on login; show both field messages and the form summary. |
| `unknown` | `demo123` | Client-side required checks pass; PHP stays on login and shows that the username is not a demo account. |

Whitespace-only values count as empty. For example, a password containing only spaces is invalid.

## Limitations

- There is no real user authentication or password verification.
- There are no PHP sessions or persistent login state.
- The role parameter is visible in the URL and is not an access-control check.
- The prototype does not connect to MariaDB or save application data.
- The remember-me checkbox is visual only.
- Password recovery is not offered in this prototype.
- Teacher and student destination screens are still simple placeholders, not full dashboards.
- Bootstrap is loaded from a CDN, so the browser needs an internet connection to load Bootstrap assets.

## Run or view the prototype

From the repository root, start PHP's built-in development server:

```bash
php -S 127.0.0.1:8000 -t public
```

Open `http://127.0.0.1:8000/` to see the login page. Use the demonstration table above to visit each role destination. The layout is responsive; reduce the browser width to see the mobile version.

## Verification performed

- PHP syntax checks passed for the front controller, both controllers, the authentication layout, and the login and destination views.
- Node.js syntax checking passed for `public/assets/js/app.js`.
- A client-side event harness confirmed that empty fields show both Catalan field messages, reveal the summary, focus the first invalid field, and prevent submission. It also confirmed that correcting the fields clears the errors and allows a valid form to submit.
- Local HTTP checks confirmed that `admin`, `teacher`, and `student` with `demo123` each receive a `303` redirect to the matching role destination.
- Local HTTP checks confirmed that an unknown username stays on the login page with an error, and empty username/password values stay on the page with the expected Catalan server-side messages.
- A rendered GET response was checked for the login labels, username and password fields, remember-me checkbox, and prototype limitation notice.
