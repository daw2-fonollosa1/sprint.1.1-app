-- Create the development database with character support for Catalan text.
CREATE DATABASE IF NOT EXISTS daw2_project_manager
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE daw2_project_manager;

-- Store all application accounts and their role.
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('administrator', 'teacher', 'student') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Store the projects managed by teachers and administrators.
CREATE TABLE IF NOT EXISTS projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    description TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Store reusable evaluable items that can be linked to several projects.
CREATE TABLE IF NOT EXISTS evaluable_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    description TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Connect teachers to projects. A teacher can have many projects.
CREATE TABLE IF NOT EXISTS teacher_projects (
    teacher_id INT UNSIGNED NOT NULL,
    project_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (teacher_id, project_id),
    CONSTRAINT fk_teacher_projects_teacher
        FOREIGN KEY (teacher_id) REFERENCES users (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_teacher_projects_project
        FOREIGN KEY (project_id) REFERENCES projects (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Connect students to projects. A student can have many projects.
CREATE TABLE IF NOT EXISTS student_projects (
    student_id INT UNSIGNED NOT NULL,
    project_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (student_id, project_id),
    CONSTRAINT fk_student_projects_student
        FOREIGN KEY (student_id) REFERENCES users (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_student_projects_project
        FOREIGN KEY (project_id) REFERENCES projects (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Connect evaluable items to projects. Items can be reused in many projects.
CREATE TABLE IF NOT EXISTS project_evaluable_items (
    project_id INT UNSIGNED NOT NULL,
    evaluable_item_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (project_id, evaluable_item_id),
    CONSTRAINT fk_project_items_project
        FOREIGN KEY (project_id) REFERENCES projects (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_project_items_item
        FOREIGN KEY (evaluable_item_id) REFERENCES evaluable_items (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Store one grade from one teacher for one student, project, and item.
CREATE TABLE IF NOT EXISTS grades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    evaluable_item_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    grade_value DECIMAL(10, 4) NOT NULL,
    CONSTRAINT uq_grade_per_teacher
        UNIQUE (project_id, student_id, evaluable_item_id, teacher_id),
    CONSTRAINT fk_grades_teacher_project
        FOREIGN KEY (teacher_id, project_id)
        REFERENCES teacher_projects (teacher_id, project_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_grades_student_project
        FOREIGN KEY (student_id, project_id)
        REFERENCES student_projects (student_id, project_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_grades_project_item
        FOREIGN KEY (project_id, evaluable_item_id)
        REFERENCES project_evaluable_items (project_id, evaluable_item_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
