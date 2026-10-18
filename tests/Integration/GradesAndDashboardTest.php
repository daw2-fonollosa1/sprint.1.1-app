<?php

declare(strict_types=1);

require_once __DIR__ . '/DatabaseTestCase.php';

/** Check individual teacher grade rows and the student's filtered arithmetic means. */
final class GradesAndDashboardTest extends DatabaseTestCase
{
    public function testTeacherDashboardShowsOnlyAssignedProjectsStudentsAndItems(): void
    {
        $assignedProject = $this->createProject('teacher_assigned_project');
        $otherProject = $this->createProject('teacher_other_project');
        $teacher = $this->createUser('teacher', 'selected_teacher');
        $otherTeacher = $this->createUser('teacher', 'other_teacher');
        $assignedStudent = $this->createUser('student', 'assigned_student');
        $otherStudent = $this->createUser('student', 'other_student');
        $assignedItem = $this->createItem('teacher_assigned_item');
        $otherItem = $this->createItem('teacher_other_item');

        $this->assignTeacher($teacher, $assignedProject);
        $this->assignTeacher($otherTeacher, $otherProject);
        $this->assignStudent($assignedStudent, $assignedProject);
        $this->assignStudent($otherStudent, $otherProject);
        $this->assignItem($assignedItem, $assignedProject);
        $this->assignItem($otherItem, $otherProject);

        $data = (new TeacherDashboardData($this->connection))->build($teacher);

        self::assertSame([$assignedProject], array_column($data['projects'], 'id'));
        self::assertSame([$assignedStudent], array_column($data['projects'][0]['students'], 'id'));
        self::assertSame([$assignedItem], array_column($data['projects'][0]['items'], 'id'));
        self::assertSame(0, $data['summary']['gradeCount']);
    }

    public function testTeachersKeepSeparateGradesWhenTheyGradeTheSameItem(): void
    {
        $projectId = $this->createProject('grade_project');
        $teacherOne = $this->createUser('teacher', 'grade_teacher_one');
        $teacherTwo = $this->createUser('teacher', 'grade_teacher_two');
        $studentId = $this->createUser('student', 'grade_student');
        $itemId = $this->createItem('grade_item');
        $this->assignTeacher($teacherOne, $projectId);
        $this->assignTeacher($teacherTwo, $projectId);
        $this->assignStudent($studentId, $projectId);
        $this->assignItem($itemId, $projectId);

        $grades = new GradeModel($this->connection);
        $firstGradeId = $this->createGrade($projectId, $studentId, $itemId, $teacherOne, '8.5000');
        $secondGradeId = $this->createGrade($projectId, $studentId, $itemId, $teacherTwo, '9.5000');

        self::assertCount(1, $grades->getGradesForTeacherProject($teacherOne, $projectId));
        self::assertCount(1, $grades->getGradesForTeacherProject($teacherTwo, $projectId));
        self::assertCount(2, array_filter(
            $grades->getAllForAdministrator(),
            fn (array $row): bool => (int) $row['id'] === $firstGradeId
                || (int) $row['id'] === $secondGradeId,
        ));

        try {
            $grades->create($projectId, $studentId, $itemId, $teacherOne, '7.0000');
            self::fail('The same teacher should not be able to create a duplicate grade row.');
        } catch (PDOException) {
            self::assertSame(2, $this->rowCount('grades', 'project_id', $projectId));
        }

        $grades->updateOwnGrade($firstGradeId, $teacherOne, '7.2500');
        $updated = $grades->findForTeacher($firstGradeId, $teacherOne);
        self::assertNotNull($updated);
        self::assertSame($studentId, (int) $updated['student_id']);
        self::assertSame($projectId, (int) $updated['project_id']);
        self::assertSame($itemId, (int) $updated['evaluable_item_id']);
        self::assertSame($teacherOne, (int) $updated['teacher_id']);
        self::assertEqualsWithDelta(7.25, (float) $updated['grade_value'], 0.0001);

        self::assertTrue($grades->deleteOne($firstGradeId, $teacherOne));
        self::assertNull($grades->findForTeacher($firstGradeId, $teacherOne));
        self::assertNotNull($grades->findForTeacher($secondGradeId, $teacherTwo));
        self::assertSame(1, $this->rowCount('grades', 'project_id', $projectId));
    }

    public function testStudentDashboardAveragesOnlyMatchingStudentProjectAndItem(): void
    {
        $firstProject = $this->createProject('average_project_one');
        $secondProject = $this->createProject('average_project_two');
        $teacherOne = $this->createUser('teacher', 'average_teacher_one');
        $teacherTwo = $this->createUser('teacher', 'average_teacher_two');
        $selectedStudent = $this->createUser('student', 'average_selected_student');
        $otherStudent = $this->createUser('student', 'average_other_student');
        $sharedItem = $this->createItem('average_shared_item');
        $secondItem = $this->createItem('average_second_item');

        $this->assignTeacher($teacherOne, $firstProject);
        $this->assignTeacher($teacherTwo, $firstProject);
        $this->assignTeacher($teacherOne, $secondProject);
        $this->assignStudent($selectedStudent, $firstProject);
        $this->assignStudent($selectedStudent, $secondProject);
        $this->assignStudent($otherStudent, $firstProject);
        $this->assignItem($sharedItem, $firstProject);
        $this->assignItem($secondItem, $firstProject);
        $this->assignItem($sharedItem, $secondProject);

        // Two teachers grade the same combination; the other values belong to
        // different projects, items, or a different student and must not mix in.
        $this->createGrade($firstProject, $selectedStudent, $sharedItem, $teacherOne, '8.5000');
        $this->createGrade($firstProject, $selectedStudent, $sharedItem, $teacherTwo, '9.5000');
        $this->createGrade($secondProject, $selectedStudent, $sharedItem, $teacherOne, '4.0000');
        $this->createGrade($firstProject, $selectedStudent, $secondItem, $teacherOne, '2.0000');
        $this->createGrade($firstProject, $otherStudent, $sharedItem, $teacherOne, '1.0000');

        $data = (new StudentDashboardData($this->connection))->build($selectedStudent, null);

        self::assertSame($selectedStudent, $data['student']['id']);
        $visibleProjectIds = array_column($data['projects'], 'id');
        sort($visibleProjectIds);
        $expectedProjectIds = [$firstProject, $secondProject];
        sort($expectedProjectIds);
        self::assertSame($expectedProjectIds, $visibleProjectIds);
        self::assertCount(3, $data['gradeRows']);

        $averages = [];
        foreach ($data['gradeRows'] as $row) {
            $averages[$row['projectId'] . ':' . $row['itemId']] = [
                'average' => (float) $row['average'],
                'grade_count' => $row['gradeCount'],
                'teacher_grades' => $row['teacherGrades'],
            ];
        }

        self::assertEqualsWithDelta(9.0, $averages[$firstProject . ':' . $sharedItem]['average'], 0.0001);
        self::assertSame(2, $averages[$firstProject . ':' . $sharedItem]['grade_count']);
        self::assertCount(2, $averages[$firstProject . ':' . $sharedItem]['teacher_grades']);
        $visibleTeacherValues = array_map(
            static fn (array $grade): float => (float) $grade['value'],
            $averages[$firstProject . ':' . $sharedItem]['teacher_grades'],
        );
        sort($visibleTeacherValues);
        self::assertSame([8.5, 9.5], $visibleTeacherValues);
        self::assertEqualsWithDelta(4.0, $averages[$secondProject . ':' . $sharedItem]['average'], 0.0001);
        self::assertEqualsWithDelta(2.0, $averages[$firstProject . ':' . $secondItem]['average'], 0.0001);

        // A different student has a grade for the same project and item, but it is
        // absent from this dashboard and does not change the expected mean of 9.
        self::assertSame(2, count($averages[$firstProject . ':' . $sharedItem]['teacher_grades']));
        self::assertEqualsWithDelta(9.0, $averages[$firstProject . ':' . $sharedItem]['average'], 0.0001);
    }
}
