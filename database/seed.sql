-- Development examples only. These accounts share a known test password;
-- the database stores only its password_hash value, never the plain password.
SET NAMES utf8mb4;
USE daw2_project_manager;

-- The development test password is documented separately; only its hashes are stored here.
-- Update only known sample usernames so existing user records keep their other fields.
UPDATE users
SET password_hash = CASE username
    WHEN 'admin-demo' THEN '$2y$12$avFgKtIy6zO.VTAbscPt6.eyGE1INknW2ty2bQzzrimkZoWwktjEG'
    WHEN 'teacher-marta' THEN '$2y$12$fxFTo6kc5SKDxckO4Mo8.uy3APuYaf5m71S37HZ4o61J9svXcbZmW'
    WHEN 'teacher-joan' THEN '$2y$12$STK..bB1FwqSnJwk0.DP3uEvAmNI1Hvgk.Kxgl4oMj25BZhyd7m0y'
    WHEN 'student-marc' THEN '$2y$12$LzORE7prq0i6nepO9u1dn.mrSdfc4oOXVLyP2tBn4nZaPAFOK1ohe'
    WHEN 'student-laura' THEN '$2y$12$zpNfN34KknBh0gvXraxWzuTGYpXtBK2W/V4/NQw.dBA4aLe1zqr0O'
    ELSE password_hash
END
WHERE username IN ('admin-demo', 'teacher-marta', 'teacher-joan', 'student-marc', 'student-laura');

-- Add a sample account only when its ID and username are both available.
INSERT IGNORE INTO users (id, username, full_name, password_hash, role) VALUES
    (1, 'admin-demo', 'Administrador de demostració', '$2y$12$avFgKtIy6zO.VTAbscPt6.eyGE1INknW2ty2bQzzrimkZoWwktjEG', 'administrator'),
    (2, 'teacher-marta', 'Marta Vila Puig', '$2y$12$fxFTo6kc5SKDxckO4Mo8.uy3APuYaf5m71S37HZ4o61J9svXcbZmW', 'teacher'),
    (3, 'teacher-joan', 'Joan Marcet Soler', '$2y$12$STK..bB1FwqSnJwk0.DP3uEvAmNI1Hvgk.Kxgl4oMj25BZhyd7m0y', 'teacher'),
    (4, 'student-marc', 'Marc Gómez Ribas', '$2y$12$LzORE7prq0i6nepO9u1dn.mrSdfc4oOXVLyP2tBn4nZaPAFOK1ohe', 'student'),
    (5, 'student-laura', 'Laura Pons Bosch', '$2y$12$zpNfN34KknBh0gvXraxWzuTGYpXtBK2W/V4/NQw.dBA4aLe1zqr0O', 'student');

INSERT IGNORE INTO projects (id, code, name, description) VALUES
    (1, 'PRJ-001', 'Aplicació de gestió', 'Projecte de demostració per a DAM2A.'),
    (2, 'PRJ-002', 'Catàleg digital', 'Projecte de demostració per a DAM2B.'),
    (3, 'PRJ-003', 'Portal de recursos del cicle', 'Projecte de demostració per a DAM2A.');

INSERT IGNORE INTO evaluable_items (id, code, name, description) VALUES
    (1, 'IT-001', 'Documentació', 'Revisió de la documentació del projecte.'),
    (2, 'IT-002', 'Proves', 'Revisió de les proves del projecte.');

INSERT IGNORE INTO teacher_projects (teacher_id, project_id) VALUES
    (2, 1),
    (3, 1),
    (2, 2),
    (3, 2),
    (2, 3),
    (3, 3);

INSERT IGNORE INTO student_projects (student_id, project_id) VALUES
    (4, 1),
    (5, 2),
    (4, 3);

INSERT IGNORE INTO project_evaluable_items (project_id, evaluable_item_id) VALUES
    (1, 1),
    (1, 2),
    (2, 2),
    (3, 1),
    (3, 2);

INSERT IGNORE INTO grades (id, project_id, student_id, evaluable_item_id, teacher_id, grade_value) VALUES
    (1, 1, 4, 1, 2, 8.5000),
    (2, 1, 4, 1, 3, 9.0000),
    (3, 1, 4, 2, 3, 7.5000),
    (4, 2, 5, 2, 2, 8.0000),
    (5, 3, 4, 2, 2, 8.0000),
    (6, 3, 4, 2, 3, 9.0000);
