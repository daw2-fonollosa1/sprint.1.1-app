<?php

declare(strict_types=1);

/**
 * Holds the shared records used by the administrator, teacher, and student previews.
 * These records are examples only; a later database model can replace this class.
 */
class DemoData
{
    private const USERS = [
        ['id' => 'teacher-1', 'name' => 'Marta Vila Puig', 'username' => 'mvila', 'role' => 'teacher', 'group' => ''],
        ['id' => 'teacher-2', 'name' => 'Joan Marcet Soler', 'username' => 'jmarcet', 'role' => 'teacher', 'group' => ''],
        ['id' => 'student-1', 'name' => 'Marc Gómez Ribas', 'username' => 'mgomez', 'role' => 'student', 'group' => 'DAM2A'],
        ['id' => 'student-2', 'name' => 'Laura Pons Bosch', 'username' => 'lpons', 'role' => 'student', 'group' => 'DAM2B'],
    ];

    private const ITEMS = [
        ['id' => 'item-1', 'code' => 'IT-001', 'name' => 'Documentació', 'description' => 'Qualitat i claredat de la documentació del projecte.'],
        ['id' => 'item-2', 'code' => 'IT-002', 'name' => 'Proves', 'description' => 'Execució i resultats de les proves del projecte.'],
    ];

    private const PROJECTS = [
        [
            'id' => 'project-1',
            'code' => 'PRJ-001',
            'name' => 'Aplicació de gestió',
            'description' => 'Prototip d’una aplicació web de gestió acadèmica.',
            'group' => 'DAM2A',
            'status' => 'Actiu',
            'teacherIds' => ['teacher-1', 'teacher-2'],
            'studentIds' => ['student-1'],
            'itemIds' => ['item-1', 'item-2'],
        ],
        [
            'id' => 'project-2',
            'code' => 'PRJ-002',
            'name' => 'Catàleg digital',
            'description' => 'Disseny d’un catàleg digital amb cerca i filtres.',
            'group' => 'DAM2B',
            'status' => 'Actiu',
            'teacherIds' => ['teacher-1', 'teacher-2'],
            'studentIds' => ['student-2'],
            'itemIds' => ['item-2'],
        ],
        [
            'id' => 'project-3',
            'code' => 'PRJ-003',
            'name' => 'Portal de recursos del cicle',
            'description' => 'Organització de recursos digitals per a l’alumnat de DAM2A.',
            'group' => 'DAM2A',
            'status' => 'Actiu',
            'teacherIds' => ['teacher-1', 'teacher-2'],
            'studentIds' => ['student-1'],
            'itemIds' => ['item-1', 'item-2'],
        ],
    ];

    // Each grade record stores one student's grades from all teachers for one project item.
    private const GRADES = [
        [
            'projectId' => 'project-1',
            'studentId' => 'student-1',
            'itemId' => 'item-1',
            'teacherGrades' => ['teacher-1' => 8.5, 'teacher-2' => 9.0],
        ],
        [
            'projectId' => 'project-1',
            'studentId' => 'student-1',
            'itemId' => 'item-2',
            'teacherGrades' => ['teacher-2' => 7.5],
        ],
        [
            'projectId' => 'project-2',
            'studentId' => 'student-2',
            'itemId' => 'item-2',
            'teacherGrades' => ['teacher-1' => 8.0],
        ],
        [
            'projectId' => 'project-3',
            'studentId' => 'student-1',
            'itemId' => 'item-2',
            'teacherGrades' => ['teacher-1' => 8.0, 'teacher-2' => 9.0],
        ],
    ];

    /** Return the same starting records to whichever prototype role needs them. */
    public static function all(): array
    {
        return [
            'users' => self::USERS,
            'items' => self::ITEMS,
            'projects' => self::PROJECTS,
            'grades' => self::GRADES,
        ];
    }
}
