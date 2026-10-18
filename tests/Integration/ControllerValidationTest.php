<?php

declare(strict_types=1);

require_once __DIR__ . '/DatabaseTestCase.php';
require_once dirname(__DIR__, 2) . '/app/Controllers/AdminUsersController.php';
require_once dirname(__DIR__, 2) . '/app/Controllers/CreateOperationsController.php';
require_once dirname(__DIR__, 2) . '/app/Controllers/UpdateOperationsController.php';

/** Check that current PHP form handlers reject bad values and unauthorized roles. */
final class ControllerValidationTest extends DatabaseTestCase
{
    private array $previousSession = [];

    private array $previousPost = [];

    private array $previousGet = [];

    private array $previousServer = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousSession = $_SESSION ?? [];
        $this->previousPost = $_POST ?? [];
        $this->previousGet = $_GET ?? [];
        $this->previousServer = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->previousSession;
        $_POST = $this->previousPost;
        $_GET = $this->previousGet;
        $_SERVER = $this->previousServer;
        parent::tearDown();
    }

    public function testCreateControllerRejectsEmptyRequiredProjectFields(): void
    {
        $administratorId = $this->createUser('administrator', 'validation_admin');
        $this->setPost(['action' => 'create_project'], $administratorId, 'administrator');

        $state = (new CreateOperationsController())->handle('projects', 'admin');

        self::assertNotEmpty($state['validationErrors']);
        self::assertFalse((new ProjectModel($this->connection))->codeExists(''));
    }

    public function testCreateControllerRejectsAnInvalidGradeFormat(): void
    {
        $teacherId = $this->createUser('teacher', 'validation_teacher');
        $this->setPost([
            'action' => 'create_grade',
            'project_id' => '1',
            'student_id' => '1',
            'item_id' => '1',
            'grade_value' => 'not-a-number',
        ], $teacherId, 'teacher');

        $gradesBefore = (int) $this->connection->query('SELECT COUNT(*) FROM grades')->fetchColumn();
        $state = (new CreateOperationsController())->handle('grades', 'teacher');

        self::assertNotEmpty($state['validationErrors']);
        self::assertSame($gradesBefore, (int) $this->connection->query('SELECT COUNT(*) FROM grades')->fetchColumn());
    }

    public function testUpdateControllerRejectsEmptyRequiredItemFields(): void
    {
        $administratorId = $this->createUser('administrator', 'update_validation_admin');
        $this->setPost([
            'action' => 'update_item',
            'id' => '1',
            'code' => '',
            'name' => '',
            'description' => '',
        ], $administratorId, 'administrator');

        $state = (new UpdateOperationsController())->handle('evaluable-items', 'admin');

        self::assertNotEmpty($state['validationErrors']);
        $emptyCode = $this->connection->query("SELECT COUNT(*) FROM evaluable_items WHERE code = ''");
        self::assertSame(0, (int) $emptyCode->fetchColumn());
    }

    public function testUserControllerRejectsMissingRequiredAccountFields(): void
    {
        $administratorId = $this->createUser('administrator', 'user_validation_admin');
        $this->setPost([
            'action' => 'create',
            'full_name' => '',
            'username' => '',
            'password' => '',
            'role' => 'teacher',
        ], $administratorId, 'administrator');

        $state = (new AdminUsersController())->handle([]);

        self::assertNotEmpty($state['validationErrors']);
        $emptyUsername = $this->connection->query("SELECT COUNT(*) FROM users WHERE username = ''");
        self::assertSame(0, (int) $emptyUsername->fetchColumn());
    }

    public function testStudentSessionCannotCreateAnAdministratorManagedUser(): void
    {
        $studentId = $this->createUser('student', 'unauthorized_student');
        $username = $this->codePrefix . '_must_not_be_created';
        $this->setPost([
            'action' => 'create',
            'full_name' => 'Should Not Exist',
            'username' => $username,
            'password' => 'ignored',
            'role' => 'teacher',
        ], $studentId, 'student');

        $state = (new AdminUsersController())->handle([]);

        self::assertSame([], $state['validationErrors']);
        self::assertNotSame('', $state['accessError']);
        self::assertNull((new UserModel($this->connection))->findByUsername($username));
    }

    public function testStudentSessionCannotSubmitTeacherGradeCreation(): void
    {
        $studentId = $this->createUser('student', 'grade_restriction_student');
        $this->setPost([
            'action' => 'create_grade',
            'project_id' => '1',
            'student_id' => '1',
            'item_id' => '1',
            'grade_value' => '8.0',
        ], $studentId, 'student');

        $before = $this->rowCountForPrefix('grades');
        $state = (new CreateOperationsController())->handle('grades', 'teacher');

        self::assertNotSame('', $state['accessError']);
        self::assertSame($before, $this->rowCountForPrefix('grades'));
    }

    private function setPost(array $post, int $userId, string $role): void
    {
        http_response_code(200);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = $post;
        $_GET = [];
        $_SESSION = ['user' => ['id' => $userId, 'role' => $role]];
    }

    private function rowCountForPrefix(string $table): int
    {
        if ($table === 'grades') {
            $statement = $this->connection->prepare(
                'SELECT COUNT(*) FROM grades
                 WHERE project_id IN (SELECT id FROM projects WHERE code LIKE ?)',
            );
        } elseif ($table === 'projects') {
            $statement = $this->connection->prepare('SELECT COUNT(*) FROM projects WHERE code LIKE ?');
        } elseif ($table === 'evaluable_items') {
            $statement = $this->connection->prepare('SELECT COUNT(*) FROM evaluable_items WHERE code LIKE ?');
        } else {
            throw new InvalidArgumentException('Unsupported table in a test row count.');
        }

        $statement->execute([$this->codePrefix . '%']);

        return (int) $statement->fetchColumn();
    }
}
