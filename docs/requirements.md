### FR-01: Authentication

- The application must provide a login system.
- Users must authenticate to access the application.
- The application does not allow public user registration.
- The application has three user roles.

### FR-02: User Roles

The application must support three user roles:

#### Administrator

- There is only one administrator.
- The administrator has full control over the application.
- The administrator can create teachers and students.
- The administrator can create projects.
- The administrator can create evaluable items.
- The administrator has access to a global dashboard.

#### Teacher

- Teachers can create projects.
- Teachers can be assigned to multiple projects.
- Teachers can grade students assigned to their projects.
- Teachers have access to a teacher dashboard.

#### Student

- Students can be assigned to multiple projects.
- Students can view information about their projects.
- Students can view their grades.
- Students have access to a student dashboard.

### FR-03: User Management

The application must support CRUD operations for users.

Only the administrator can create teachers and students.

The required user fields have not yet been
fully specified.

### FR-04: Project Management

The application must support CRUD operations
for projects.

Each project must contain:

- Code
- Name
- Description

Only administrators and teachers can create projects.

### FR-05: Evaluable Item Management

The application must support CRUD operations
for evaluable items.

Each evaluable item must contain:

- Code
- Name
- Description

Only the administrator can create evaluable items.

### FR-06: Project Assignments

The application must support the following
relationships:

#### Teacher Assignments

- A teacher can be assigned to multiple projects.
- A project can have multiple teachers.

#### Student Assignments

- A student can be assigned to multiple projects.
- A project can have multiple students.

#### Evaluable Item Assignments

- An evaluable item can be assigned to
  multiple projects.

  ### FR-07: Grading System

- Teachers must be able to grade students
  assigned to their projects.
- Students must be graded using the evaluable
  items associated with each project.
- Multiple teachers may grade the same student
  for the same evaluable item within a project.

When multiple teachers provide a grade,
the resulting grade must be calculated
using the arithmetic mean.

Arithmetic mean = Sum of grades / Number of grades

### FR-08: Dashboards

#### Administrator Dashboard

The administrator must have access to aggregated
information about the application as a whole.

#### Teacher Dashboard

Teachers must have access to aggregated
information about:

- Their projects.
- Their students.
- Grades.

#### Student Dashboard

Students must have access to aggregated
information about:

- Their projects.
- Their grades.


## 2. Technical Requirements

### TR-01: Frontend

- HTML5 will be used to structure the interface.
- CSS3 will be used for styling.
- JavaScript must be used for client-side functionality.
- The interface must be responsive.

### TR-02: Backend

- The backend must be developed using PHP.
- PHP frameworks are not allowed.
- Object-oriented programming must be used.
- The MVC architectural pattern must be applied.

### TR-03: Database

- MariaDB must be used.

### TR-04: Deployment

- Docker or Docker Compose must be used.
- The application services must run in
  separate containers.
- GitHub Actions must be configured to run
  automated project checks.

### TR-05: Version Control

- Git and GitHub must be used.
- Development tasks must be managed using
  GitHub Issues and Projects.
- Issues, branches, commits and pull requests
  must remain traceable.

### TR-06: Language

- The user interface must be in Catalan.
- Source code and code comments must be in English.
- Project documentation must be in English.


## 3. Development Scope

### 3.1 Initial Frontend Prototype

The initial development stage will focus on:

- Creating application wireframes.
- Creating application mockups.
- Developing the application interfaces.
- Implementing responsive layouts.
- Implementing basic client-side interactions.
- Validating forms using JavaScript.
- Using mock data when necessary.

The initial frontend prototype will not
provide real authentication or persistent
data storage.

### 3.2 Future Backend Development

Backend development will begin when
the required technologies have been
covered in class.

This stage will include:

- PHP implementation.
- Object-oriented programming.
- MVC architecture.
- MariaDB integration.
- User authentication and sessions.
- Database CRUD operations.
- Role-based access control.
- Project assignments.
- Persistent grading functionality.