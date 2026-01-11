<?php

declare(strict_types=1);

namespace MAAF\Core\ModuleGenerator\Templates;

use MAAF\Core\ModuleGenerator\GeneratedFile;
use MAAF\Core\ModuleGenerator\ModuleMetadata;
use MAAF\Core\ModuleGenerator\ModuleTemplate;

/**
 * Course Module Template
 * 
 * Kurzus modul sablon Hexagonal / Clean Architecture struktúrával.
 * 
 * @version 1.0.0
 */
final class CourseModuleTemplate implements ModuleTemplate
{
    public function getName(): string
    {
        return 'course';
    }

    public function getDescription(): string
    {
        return 'Course module with institution and enrollment management (Hexagonal Architecture)';
    }

    /**
     * @return GeneratedFile[]
     */
    public function getFiles(ModuleMetadata $metadata): array
    {
        $moduleName = $metadata->name;
        $className  = 'Course';
        $namespace  = $metadata->namespace . '\\' . $moduleName;

        return [
            // Gyökér szint
            new GeneratedFile(
                'CourseModule.php',
                $this->getModuleClassContent($namespace, $className, $moduleName, $metadata),
            ),
            new GeneratedFile(
                'composer.json',
                $this->getComposerJsonContent($namespace, $moduleName, $metadata),
            ),
            new GeneratedFile(
                'routes.php',
                $this->getRoutesContent($namespace),
            ),

            // Domain réteg
            new GeneratedFile(
                'Domain/Model/Course.php',
                $this->getDomainModelContent($namespace, $className),
            ),
            new GeneratedFile(
                'Domain/Repository/CourseRepositoryInterface.php',
                $this->getDomainRepositoryInterfaceContent($namespace, $className),
            ),
            new GeneratedFile(
                'Domain/Service/CourseServiceInterface.php',
                $this->getDomainServiceInterfaceContent($namespace, $className),
            ),
            new GeneratedFile(
                'Domain/Exception/CourseNotFoundException.php',
                $this->getDomainNotFoundExceptionContent($namespace, $className),
            ),

            // Application réteg
            new GeneratedFile(
                'Application/DTO/CourseRequestDTO.php',
                $this->getApplicationRequestDtoContent($namespace, $className),
            ),
            new GeneratedFile(
                'Application/DTO/CourseResponseDTO.php',
                $this->getApplicationResponseDtoContent($namespace, $className),
            ),
            new GeneratedFile(
                'Application/Validator/CourseRequestValidator.php',
                $this->getApplicationRequestValidatorContent($namespace, $className),
            ),
            new GeneratedFile(
                'Application/Service/CourseService.php',
                $this->getApplicationServiceContent($namespace, $className),
            ),

            // Infrastructure réteg
            new GeneratedFile(
                'Infrastructure/Repository/InMemoryCourseRepository.php',
                $this->getInfrastructureRepositoryContent($namespace, $className),
            ),

            // Presentation réteg (HTTP)
            new GeneratedFile(
                'Presentation/Http/Request/CourseRequest.php',
                $this->getPresentationRequestContent($namespace, $className),
            ),
            new GeneratedFile(
                'Presentation/Http/Response/CourseResponse.php',
                $this->getPresentationResponseContent($namespace, $className),
            ),
            new GeneratedFile(
                'Presentation/Http/Controller/CourseController.php',
                $this->getPresentationControllerContent($namespace, $className),
            ),
        ];
    }

    private function getModuleClassContent(string $namespace, string $className, string $moduleName, ModuleMetadata $metadata): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use MAAF\\Module\\Module as BaseModule;
use DI\\ContainerBuilder;
use MAAF\\Core\\Routing\\Router;

final class CourseModule extends BaseModule
{
    public function getName(): string
    {
        return '{$moduleName}';
    }

    public function getVersion(): string
    {
        return '{$metadata->version}';
    }

    public function getDescription(): string
    {
        return '{$metadata->description}';
    }

    public function registerServices(ContainerBuilder \$builder): void
    {
        \$builder->addDefinitions([
            Domain\\Repository\\CourseRepositoryInterface::class => DI\create(Infrastructure\\Repository\\InMemoryCourseRepository::class),
            Domain\\Service\\CourseServiceInterface::class => DI\create(Application\\Service\\CourseService::class)
                ->constructor(
                    DI\get(Domain\\Repository\\CourseRepositoryInterface::class),
                    DI\create(Application\\Validator\\CourseRequestValidator::class)
                ),
        ]);
    }

    public function registerRoutes(Router \$router): void
    {
        \$routes = require __DIR__ . '/routes.php';
        \$routes(\$router);
    }

    public function boot(): void
    {
        // Called when module is loaded
    }

    public function activate(): void
    {
        // Called when module is activated
    }

    public function deactivate(): void
    {
        // Called when module is deactivated
    }
}

PHP;
    }

    private function getComposerJsonContent(string $namespace, string $moduleName, ModuleMetadata $metadata): string
    {
        \$packageName = strtolower(str_replace('\\\\', '/', \$namespace));
        
        return <<<JSON
{
    "name": "{\$packageName}",
    "description": "{$metadata->description}",
    "type": "maaf-module",
    "keywords": ["maaf", "module", "{$moduleName}"],
    "license": "MIT",
    "authors": [
        {
            "name": "{$metadata->author}",
            "email": ""
        }
    ],
    "require": {
        "php": ">=8.1",
        "maaf/core": "^2.0",
        "maaf/module": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "{$namespace}\\\\": ""
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}

JSON;
    }

    private function getRoutesContent(string $namespace): string
    {
        \$base = 'courses';

        return <<<PHP
<?php

use MAAF\\Core\\Routing\\Router;
use {$namespace}\\Presentation\\Http\\Controller\\CourseController;

return function (Router \$router): void {
    \$router->get('/{$base}', [CourseController::class, 'index']);
    \$router->get('/{$base}/{id}', [CourseController::class, 'show']);
    \$router->post('/{$base}', [CourseController::class, 'create']);
    \$router->put('/{$base}/{id}', [CourseController::class, 'update']);
    \$router->delete('/{$base}/{id}', [CourseController::class, 'delete']);
    \$router->get('/{$base}/institution/{institutionId}', [CourseController::class, 'getByInstitution']);
};

PHP;
    }

    private function getDomainModelContent(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Domain\\Model;

final class Course
{
    public function __construct(
        private string \$id,
        private string \$title,
        private string \$description,
        private string \$institutionId,
        private string \$code,
        private int \$credits = 0,
        private int \$duration = 0,
        private string \$status = 'draft',
        private ?string \$prerequisites = null,
        private ?\DateTimeImmutable \$startDate = null,
        private ?\DateTimeImmutable \$endDate = null,
    ) {}

    public function getId(): string
    {
        return \$this->id;
    }

    public function getTitle(): string
    {
        return \$this->title;
    }

    public function getDescription(): string
    {
        return \$this->description;
    }

    public function getInstitutionId(): string
    {
        return \$this->institutionId;
    }

    public function getCode(): string
    {
        return \$this->code;
    }

    public function getCredits(): int
    {
        return \$this->credits;
    }

    public function getDuration(): int
    {
        return \$this->duration;
    }

    public function getStatus(): string
    {
        return \$this->status;
    }

    public function getPrerequisites(): ?string
    {
        return \$this->prerequisites;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return \$this->startDate;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return \$this->endDate;
    }

    public function updateTitle(string \$title): void
    {
        \$this->title = \$title;
    }

    public function updateDescription(string \$description): void
    {
        \$this->description = \$description;
    }

    public function updateStatus(string \$status): void
    {
        \$this->status = \$status;
    }

    public function publish(): void
    {
        \$this->status = 'published';
    }

    public function archive(): void
    {
        \$this->status = 'archived';
    }
}

PHP;
    }

    private function getDomainRepositoryInterfaceContent(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Domain\\Repository;

use {$namespace}\\Domain\\Model\\Course;

interface CourseRepositoryInterface
{
    /**
     * @return Course[]
     */
    public function findAll(): array;

    public function findById(string \$id): ?Course;

    /**
     * @return Course[]
     */
    public function findByInstitutionId(string \$institutionId): array;

    public function findByCode(string \$code): ?Course;

    public function save(Course \$course): Course;

    public function delete(string \$id): bool;
}

PHP;
    }

    private function getDomainServiceInterfaceContent(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Domain\\Service;

use {$namespace}\\Domain\\Model\\Course;

interface CourseServiceInterface
{
    /**
     * @return Course[]
     */
    public function list(): array;

    public function get(string \$id): Course;

    /**
     * @return Course[]
     */
    public function getByInstitution(string \$institutionId): array;

    public function create(string \$title, string \$description, string \$institutionId, string \$code, int \$credits = 0): Course;

    public function update(string \$id, array \$data): Course;

    public function delete(string \$id): void;

    public function publish(string \$id): Course;

    public function archive(string \$id): Course;
}

PHP;
    }

    private function getDomainNotFoundExceptionContent(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Domain\\Exception;

final class CourseNotFoundException extends \\RuntimeException
{
}

PHP;
    }

    private function getApplicationRequestDtoContent(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Application\\DTO;

final class CourseRequestDTO
{
    public function __construct(
        private ?string \$id = null,
        private ?string \$title = null,
        private ?string \$description = null,
        private ?string \$institutionId = null,
        private ?string \$code = null,
        private ?int \$credits = null,
        private ?int \$duration = null,
        private ?string \$status = null,
        private ?string \$prerequisites = null,
        private ?string \$startDate = null,
        private ?string \$endDate = null,
    ) {}

    public function getId(): ?string
    {
        return \$this->id;
    }

    public function getTitle(): ?string
    {
        return \$this->title;
    }

    public function getDescription(): ?string
    {
        return \$this->description;
    }

    public function getInstitutionId(): ?string
    {
        return \$this->institutionId;
    }

    public function getCode(): ?string
    {
        return \$this->code;
    }

    public function getCredits(): ?int
    {
        return \$this->credits;
    }

    public function getDuration(): ?int
    {
        return \$this->duration;
    }

    public function getStatus(): ?string
    {
        return \$this->status;
    }

    public function getPrerequisites(): ?string
    {
        return \$this->prerequisites;
    }

    public function getStartDate(): ?string
    {
        return \$this->startDate;
    }

    public function getEndDate(): ?string
    {
        return \$this->endDate;
    }
}

PHP;
    }

    private function getApplicationResponseDtoContent(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Application\\DTO;

final class CourseResponseDTO
{
    public function __construct(
        private string \$id,
        private string \$title,
        private string \$description,
        private string \$institutionId,
        private string \$code,
        private int \$credits,
        private int \$duration,
        private string \$status,
        private ?string \$prerequisites = null,
        private ?string \$startDate = null,
        private ?string \$endDate = null,
    ) {}

    public function getId(): string
    {
        return \$this->id;
    }

    public function getTitle(): string
    {
        return \$this->title;
    }

    public function getDescription(): string
    {
        return \$this->description;
    }

    public function getInstitutionId(): string
    {
        return \$this->institutionId;
    }

    public function getCode(): string
    {
        return \$this->code;
    }

    public function getCredits(): int
    {
        return \$this->credits;
    }

    public function getDuration(): int
    {
        return \$this->duration;
    }

    public function getStatus(): string
    {
        return \$this->status;
    }

    public function getPrerequisites(): ?string
    {
        return \$this->prerequisites;
    }

    public function getStartDate(): ?string
    {
        return \$this->startDate;
    }

    public function getEndDate(): ?string
    {
        return \$this->endDate;
    }

    public function toArray(): array
    {
        return [
            'id' => \$this->id,
            'title' => \$this->title,
            'description' => \$this->description,
            'institution_id' => \$this->institutionId,
            'code' => \$this->code,
            'credits' => \$this->credits,
            'duration' => \$this->duration,
            'status' => \$this->status,
            'prerequisites' => \$this->prerequisites,
            'start_date' => \$this->startDate,
            'end_date' => \$this->endDate,
        ];
    }
}

PHP;
    }

    private function getApplicationRequestValidatorContent(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Application\\Validator;

use {$namespace}\\Application\\DTO\\CourseRequestDTO;

final class CourseRequestValidator
{
    /**
     * @return string[] Hibák listája
     */
    public function validateForList(): array
    {
        return [];
    }

    /**
     * @return string[] Hibák listája
     */
    public function validateForGet(CourseRequestDTO \$dto): array
    {
        \$errors = [];

        if (\$dto->getId() === null || \$dto->getId() === '') {
            \$errors[] = 'ID is required';
        }

        return \$errors;
    }

    /**
     * @return string[] Hibák listája
     */
    public function validateForCreate(CourseRequestDTO \$dto): array
    {
        \$errors = [];

        if (\$dto->getTitle() === null || \$dto->getTitle() === '') {
            \$errors[] = 'Title is required';
        }

        if (\$dto->getDescription() === null || \$dto->getDescription() === '') {
            \$errors[] = 'Description is required';
        }

        if (\$dto->getInstitutionId() === null || \$dto->getInstitutionId() === '') {
            \$errors[] = 'Institution ID is required';
        }

        if (\$dto->getCode() === null || \$dto->getCode() === '') {
            \$errors[] = 'Course code is required';
        }

        if (\$dto->getCredits() !== null && \$dto->getCredits() < 0) {
            \$errors[] = 'Credits must be non-negative';
        }

        return \$errors;
    }

    /**
     * @return string[] Hibák listája
     */
    public function validateForUpdate(CourseRequestDTO \$dto): array
    {
        \$errors = [];

        if (\$dto->getId() === null || \$dto->getId() === '') {
            \$errors[] = 'ID is required';
        }

        if (\$dto->getCredits() !== null && \$dto->getCredits() < 0) {
            \$errors[] = 'Credits must be non-negative';
        }

        return \$errors;
    }
}

PHP;
    }

    private function getApplicationServiceContent(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Application\\Service;

use {$namespace}\\Application\\DTO\\CourseRequestDTO;
use {$namespace}\\Application\\DTO\\CourseResponseDTO;
use {$namespace}\\Application\\Validator\\CourseRequestValidator;
use {$namespace}\\Domain\\Exception\\CourseNotFoundException;
use {$namespace}\\Domain\\Repository\\CourseRepositoryInterface;
use {$namespace}\\Domain\\Service\\CourseServiceInterface;

final class CourseService implements CourseServiceInterface
{
    public function __construct(
        private CourseRepositoryInterface \$repository,
        private CourseRequestValidator \$validator,
    ) {}

    public function list(): array
    {
        \$items = \$this->repository->findAll();

        return array_map(
            fn(\$item) => new CourseResponseDTO(
                \$item->getId(),
                \$item->getTitle(),
                \$item->getDescription(),
                \$item->getInstitutionId(),
                \$item->getCode(),
                \$item->getCredits(),
                \$item->getDuration(),
                \$item->getStatus(),
                \$item->getPrerequisites(),
                \$item->getStartDate()?->format('Y-m-d'),
                \$item->getEndDate()?->format('Y-m-d')
            ),
            \$items
        );
    }

    public function get(string \$id)
    {
        \$dto = new CourseRequestDTO(id: \$id);

        \$errors = \$this->validator->validateForGet(\$dto);
        if (\$errors !== []) {
            throw new \\InvalidArgumentException(implode('; ', \$errors));
        }

        \$item = \$this->repository->findById(\$id);
        if (!\$item) {
            throw new CourseNotFoundException("Course not found: {\$id}");
        }

        return new CourseResponseDTO(
            \$item->getId(),
            \$item->getTitle(),
            \$item->getDescription(),
            \$item->getInstitutionId(),
            \$item->getCode(),
            \$item->getCredits(),
            \$item->getDuration(),
            \$item->getStatus(),
            \$item->getPrerequisites(),
            \$item->getStartDate()?->format('Y-m-d'),
            \$item->getEndDate()?->format('Y-m-d')
        );
    }

    public function getByInstitution(string \$institutionId): array
    {
        \$items = \$this->repository->findByInstitutionId(\$institutionId);

        return array_map(
            fn(\$item) => new CourseResponseDTO(
                \$item->getId(),
                \$item->getTitle(),
                \$item->getDescription(),
                \$item->getInstitutionId(),
                \$item->getCode(),
                \$item->getCredits(),
                \$item->getDuration(),
                \$item->getStatus(),
                \$item->getPrerequisites(),
                \$item->getStartDate()?->format('Y-m-d'),
                \$item->getEndDate()?->format('Y-m-d')
            ),
            \$items
        );
    }

    public function create(string \$title, string \$description, string \$institutionId, string \$code, int \$credits = 0): \{$namespace}\\Domain\\Model\\Course
    {
        \$dto = new CourseRequestDTO(
            title: \$title,
            description: \$description,
            institutionId: \$institutionId,
            code: \$code,
            credits: \$credits
        );

        \$errors = \$this->validator->validateForCreate(\$dto);
        if (\$errors !== []) {
            throw new \\InvalidArgumentException(implode('; ', \$errors));
        }

        \$course = new \{$namespace}\\Domain\\Model\\Course(
            id: uniqid('course_', true),
            title: \$title,
            description: \$description,
            institutionId: \$institutionId,
            code: \$code,
            credits: \$credits
        );

        return \$this->repository->save(\$course);
    }

    public function update(string \$id, array \$data): \{$namespace}\\Domain\\Model\\Course
    {
        \$dto = new CourseRequestDTO(
            id: \$id,
            title: \$data['title'] ?? null,
            description: \$data['description'] ?? null,
            institutionId: \$data['institution_id'] ?? null,
            code: \$data['code'] ?? null,
            credits: \$data['credits'] ?? null,
            duration: \$data['duration'] ?? null,
            status: \$data['status'] ?? null,
            prerequisites: \$data['prerequisites'] ?? null,
            startDate: \$data['start_date'] ?? null,
            endDate: \$data['end_date'] ?? null,
        );

        \$errors = \$this->validator->validateForUpdate(\$dto);
        if (\$errors !== []) {
            throw new \\InvalidArgumentException(implode('; ', \$errors));
        }

        \$course = \$this->repository->findById(\$id);
        if (!\$course) {
            throw new CourseNotFoundException("Course not found: {\$id}");
        }

        if (\$dto->getTitle() !== null) {
            \$course->updateTitle(\$dto->getTitle());
        }
        if (\$dto->getDescription() !== null) {
            \$course->updateDescription(\$dto->getDescription());
        }
        if (\$dto->getStatus() !== null) {
            \$course->updateStatus(\$dto->getStatus());
        }

        return \$this->repository->save(\$course);
    }

    public function delete(string \$id): void
    {
        \$course = \$this->repository->findById(\$id);
        if (!\$course) {
            throw new CourseNotFoundException("Course not found: {\$id}");
        }

        \$this->repository->delete(\$id);
    }

    public function publish(string \$id): \{$namespace}\\Domain\\Model\\Course
    {
        \$course = \$this->repository->findById(\$id);
        if (!\$course) {
            throw new CourseNotFoundException("Course not found: {\$id}");
        }

        \$course->publish();
        return \$this->repository->save(\$course);
    }

    public function archive(string \$id): \{$namespace}\\Domain\\Model\\Course
    {
        \$course = \$this->repository->findById(\$id);
        if (!\$course) {
            throw new CourseNotFoundException("Course not found: {\$id}");
        }

        \$course->archive();
        return \$this->repository->save(\$course);
    }
}

PHP;
    }

    private function getInfrastructureRepositoryContent(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Infrastructure\\Repository;

use {$namespace}\\Domain\\Model\\Course;
use {$namespace}\\Domain\\Repository\\CourseRepositoryInterface;

/**
 * In-memory repository példa.
 * Később cserélhető adatbázisos implementációra.
 */
final class InMemoryCourseRepository implements CourseRepositoryInterface
{
    /** @var array<string, Course> */
    private array \$items = [];

    public function __construct()
    {
        \$this->items = [
            'course_1' => new Course('course_1', 'Introduction to Programming', 'Basic programming concepts', 'inst_1', 'CS101', 3, 30),
            'course_2' => new Course('course_2', 'Database Systems', 'Database design and management', 'inst_1', 'CS201', 4, 40),
        ];
    }

    public function findAll(): array
    {
        return array_values(\$this->items);
    }

    public function findById(string \$id): ?Course
    {
        return \$this->items[\$id] ?? null;
    }

    /**
     * @return Course[]
     */
    public function findByInstitutionId(string \$institutionId): array
    {
        return array_values(array_filter(
            \$this->items,
            fn(Course \$course) => \$course->getInstitutionId() === \$institutionId
        ));
    }

    public function findByCode(string \$code): ?Course
    {
        foreach (\$this->items as \$course) {
            if (\$course->getCode() === \$code) {
                return \$course;
            }
        }
        return null;
    }

    public function save(Course \$course): Course
    {
        \$this->items[\$course->getId()] = \$course;
        return \$course;
    }

    public function delete(string \$id): bool
    {
        if (isset(\$this->items[\$id])) {
            unset(\$this->items[\$id]);
            return true;
        }
        return false;
    }
}

PHP;
    }

    private function getPresentationRequestContent(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Presentation\\Http\\Request;

/**
 * HTTP request wrapper példa.
 * Később integrálható konkrét HTTP request objektummal.
 */
final class CourseRequest
{
    public function __construct(
        private array \$queryParams = [],
        private array \$bodyParams = [],
    ) {}

    public function getId(): ?string
    {
        return \$this->queryParams['id'] ?? null;
    }

    public function getTitle(): ?string
    {
        return \$this->bodyParams['title'] ?? null;
    }

    public function getDescription(): ?string
    {
        return \$this->bodyParams['description'] ?? null;
    }

    public function getInstitutionId(): ?string
    {
        return \$this->bodyParams['institution_id'] ?? null;
    }

    public function getCode(): ?string
    {
        return \$this->bodyParams['code'] ?? null;
    }

    public function getAll(): array
    {
        return [
            'query' => \$this->queryParams,
            'body'  => \$this->bodyParams,
        ];
    }
}

PHP;
    }

    private function getPresentationResponseContent(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Presentation\\Http\\Response;

use {$namespace}\\Application\\DTO\\CourseResponseDTO;

final class CourseResponse
{
    /**
     * @param CourseResponseDTO[] \$items
     */
    public static function list(array \$items): array
    {
        return array_map(
            fn(CourseResponseDTO \$dto) => \$dto->toArray(),
            \$items
        );
    }

    public static function single(CourseResponseDTO \$dto): array
    {
        return \$dto->toArray();
    }
}

PHP;
    }

    private function getPresentationControllerContent(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Presentation\\Http\\Controller;

use {$namespace}\\Application\\Service\\CourseService;
use {$namespace}\\Presentation\\Http\\Request\\CourseRequest;
use {$namespace}\\Presentation\\Http\\Response\\CourseResponse;

final class CourseController
{
    public function __construct(
        private CourseService \$service,
    ) {}

    public function index(): array
    {
        \$items = \$this->service->list();

        return CourseResponse::list(\$items);
    }

    public function show(string \$id): array
    {
        \$item = \$this->service->get(\$id);

        return CourseResponse::single(\$item);
    }

    public function create(CourseRequest \$request): array
    {
        \$course = \$this->service->create(
            \$request->getTitle() ?? '',
            \$request->getDescription() ?? '',
            \$request->getInstitutionId() ?? '',
            \$request->getCode() ?? '',
            (int) (\$request->getAll()['body']['credits'] ?? 0)
        );

        return [
            'id' => \$course->getId(),
            'title' => \$course->getTitle(),
            'code' => \$course->getCode(),
        ];
    }

    public function update(string \$id, CourseRequest \$request): array
    {
        \$data = \$request->getAll()['body'];
        \$course = \$this->service->update(\$id, \$data);

        return [
            'id' => \$course->getId(),
            'title' => \$course->getTitle(),
            'status' => \$course->getStatus(),
        ];
    }

    public function delete(string \$id): array
    {
        \$this->service->delete(\$id);
        return ['message' => 'Course deleted'];
    }

    public function getByInstitution(string \$institutionId): array
    {
        \$items = \$this->service->getByInstitution(\$institutionId);
        return CourseResponse::list(\$items);
    }
}

PHP;
    }
}
