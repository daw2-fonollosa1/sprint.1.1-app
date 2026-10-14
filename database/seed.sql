-- Development examples only. The password hashes are random placeholders,
-- not credentials that can be used to sign in.
SET NAMES utf8mb4;
USE daw2_project_manager;

INSERT INTO users (id, username, full_name, password_hash, role) VALUES
    (1, 'admin-demo', 'Administrador de demostració', '$2y$12$sIVvB05AwtVd5jaoxvJo2OCkdBBX1GZJZJyU6qw3mCjSDe6NtyKWW', 'administrator'),
    (2, 'teacher-marta', 'Marta Vila Puig', '$2y$12$n1T0uDc1nCqERk0hcodQGOhSpSUcQhylJT9IlemwysYA3by7YUtSm', 'teacher'),
    (3, 'teacher-joan', 'Joan Marcet Soler', '$2y$12$tKJHxLwcbHSv5xlaFj10nOcU.cMXGZln2zhj10JaqRBseGsjWZlOi', 'teacher'),
    (4, 'student-marc', 'Marc Gómez Ribas', '$2y$12$YEe.9Fd6LxfLol0KJ4fueug7T5j4avgnfUJIltE2/1dmrUGwvYuli', 'student'),
    (5, 'student-laura', 'Laura Pons Bosch', '$2y$12$DeTtL8UfAxWvCjVYpMa42OIg7cSCjXCJlMd4WVqdYO52.gjp/Qh/2', 'student');

INSERT INTO projects (id, code, name, description) VALUES
    (1, 'PRJ-001', 'Aplicació de gestió', 'Projecte de demostració per a DAM2A.'),
    (2, 'PRJ-002', 'Catàleg digital', 'Projecte de demostració per a DAM2B.'),
    (3, 'PRJ-003', 'Portal de recursos del cicle', 'Projecte de demostració per a DAM2A.');

INSERT INTO evaluable_items (id, code, name, description) VALUES
    (1, 'IT-001', 'Documentació', 'Revisió de la documentació del projecte.'),
    (2, 'IT-002', 'Proves', 'Revisió de les proves del projecte.');

INSERT INTO teacher_projects (teacher_id, project_id) VALUES
    (2, 1),
    (3, 1),
    (2, 2),
    (3, 2),
    (2, 3),
    (3, 3);

INSERT INTO student_projects (student_id, project_id) VALUES
    (4, 1),
    (5, 2),
    (4, 3);

INSERT INTO project_evaluable_items (project_id, evaluable_item_id) VALUES
    (1, 1),
    (1, 2),
    (2, 2),
    (3, 1),
    (3, 2);

INSERT INTO grades (id, project_id, student_id, evaluable_item_id, teacher_id, grade_value) VALUES
    (1, 1, 4, 1, 2, 8.5000),
    (2, 1, 4, 1, 3, 9.0000),
    (3, 1, 4, 2, 3, 7.5000),
    (4, 2, 5, 2, 2, 8.0000),
    (5, 3, 4, 2, 2, 8.0000),
    (6, 3, 4, 2, 3, 9.0000);
