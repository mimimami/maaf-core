<?php

declare(strict_types=1);

namespace MAAF\Core\ModuleGenerator\Templates;

use MAAF\Core\ModuleGenerator\GeneratedFile;
use MAAF\Core\ModuleGenerator\ModuleMetadata;
use MAAF\Core\ModuleGenerator\ModuleTemplate;

final class BasicModuleTemplate implements ModuleTemplate
{
    public function getName(): string
    {
        return 'basic';
    }

    public function getDescription(): string
    {
        return 'Hexagonal / Clean Architecture alap modul skeleton.';
    }

    /**
     * @return GeneratedFile[]
     */
    public function getFiles(ModuleMetadata $metadata): array
    {
        $moduleName = $metadata->name;                 // pl. "Demo"
        $className  = ucfirst($moduleName);            // "Demo"
        $namespace  = $metadata->namespace . '\\' . $moduleName;  // "Modules\Demo"

        return [
            // Gyökér szint
            new GeneratedFile(
                $className . 'Module.php',
                $this->getModuleClassContent($namespace, $className, $moduleName, $metadata),
            ),
            new GeneratedFile(
                'composer.json',
                $this->getComposerJsonContent($namespace, $moduleName, $metadata),
            ),
            new GeneratedFile(
                'routes.php',
                $this->getRoutesContent($namespace, $className),
            ),

            // Domain réteg
            new GeneratedFile(
                'Domain/Model/' . $className . '.php',
                $this->getDomainModelContent($namespace, $className),
            ),
            new GeneratedFile(
                'Domain/Repository/' . $className . 'RepositoryInterface.php',
                $this->getDomainRepositoryInterfaceContent($namespace, $className),
            ),
            new GeneratedFile(
                'Domain/Service/' . $className . 'ServiceInterface.php',
                $this->getDomainServiceInterfaceContent($namespace, $className),
            ),
            new GeneratedFile(
                'Domain/Exception/' . $className . 'NotFoundException.php',
                $this->getDomainNotFoundExceptionContent($namespace, $className),
            ),

            // Application réteg
            new GeneratedFile(
                'Application/DTO/' . $className . 'RequestDTO.php',
                $this->getApplicationRequestDtoContent($namespace, $className),
            ),
            new GeneratedFile(
                'Application/DTO/' . $className . 'ResponseDTO.php',
                $this->getApplicationResponseDtoContent($namespace, $className),
            ),
            new GeneratedFile(
                'Application/Validator/' . $className . 'RequestValidator.php',
                $this->getApplicationRequestValidatorContent($namespace, $className),
            ),
            new GeneratedFile(
                'Application/Service/' . $className . 'Service.php',
                $this->getApplicationServiceContent($namespace, $className),
            ),

            // Infrastructure réteg
            new GeneratedFile(
                'Infrastructure/Repository/InMemory' . $className . 'Repository.php',
                $this->getInfrastructureRepositoryContent($namespace, $className),
            ),

            // Presentation réteg (HTTP)
            new GeneratedFile(
                'Presentation/Http/Request/' . $className . 'Request.php',
                $this->getPresentationRequestContent($namespace, $className),
            ),
            new GeneratedFile(
                'Presentation/Http/Response/' . $className . 'Response.php',
                $this->getPresentationResponseContent($namespace, $className),
            ),
            new GeneratedFile(
                'Presentation/Http/Controller/' . $className . 'Controller.php',
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

final class {$className}Module extends BaseModule
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
            Domain\\Repository\\{$className}RepositoryInterface::class => DI\create(Infrastructure\\Repository\\InMemory{$className}Repository::class),
            Domain\\Service\\{$className}ServiceInterface::class => DI\create(Application\\Service\\{$className}Service::class)
                ->constructor(
                    DI\get(Domain\\Repository\\{$className}RepositoryInterface::class),
                    DI\create(Application\\Validator\\{$className}RequestValidator::class)
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
        $packageName = strtolower(str_replace('\\', '/', $namespace));
        
        return <<<JSON
{
    "name": "{$packageName}",
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

    private function getRoutesContent(string $namespace, string $className): string
    {
        $base = strtolower($className);

        return <<<PHP
<?php

use MAAF\\Core\\Routing\\Router;
use {$namespace}\\Presentation\\Http\\Controller\\{$className}Controller;

return function (Router \$router): void {
    \$router->get('/{$base}', [{$className}Controller::class, 'index']);
    \$router->get('/{$base}/{id}', [{$className}Controller::class, 'show']);
};

PHP;
    }

    private function getDomainModelContent(string $namespace, string $className): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$namespace}\\Domain\\Model;

        final class {$className}
        {
            public function __construct(
                private string \$id,
                private string \$name,
            ) {}

            public function getId(): string
            {
                return \$this->id;
            }

            public function getName(): string
            {
                return \$this->name;
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

        use {$namespace}\\Domain\\Model\\{$className};

        interface {$className}RepositoryInterface
        {
            /**
             * @return {$className}[]
             */
            public function findAll(): array;

            public function findById(string \$id): ?{$className};
        }

        PHP;
    }

    private function getDomainServiceInterfaceContent(string $namespace, string $className): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$namespace}\\Domain\\Service;

        use {$namespace}\\Domain\\Model\\{$className};

        interface {$className}ServiceInterface
        {
            /**
             * @return {$className}[]
             */
            public function list(): array;

            public function get(string \$id): {$className};
        }

        PHP;
    }

    private function getDomainNotFoundExceptionContent(string $namespace, string $className): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$namespace}\\Domain\\Exception;

        final class {$className}NotFoundException extends \\RuntimeException
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

        final class {$className}RequestDTO
        {
            public function __construct(
                private ?string \$id = null,
                private ?string \$name = null,
            ) {}

            public function getId(): ?string
            {
                return \$this->id;
            }

            public function getName(): ?string
            {
                return \$this->name;
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

        final class {$className}ResponseDTO
        {
            public function __construct(
                private string \$id,
                private string \$name,
            ) {}

            public function getId(): string
            {
                return \$this->id;
            }

            public function getName(): string
            {
                return \$this->name;
            }

            public function toArray(): array
            {
                return [
                    'id'   => \$this->id,
                    'name' => \$this->name,
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

        use {$namespace}\\Application\\DTO\\{$className}RequestDTO;

        final class {$className}RequestValidator
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
            public function validateForGet({$className}RequestDTO \$dto): array
            {
                \$errors = [];

                if (\$dto->getId() === null || \$dto->getId() === '') {
                    \$errors[] = 'ID is required';
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

        use {$namespace}\\Application\\DTO\\{$className}RequestDTO;
        use {$namespace}\\Application\\DTO\\{$className}ResponseDTO;
        use {$namespace}\\Application\\Validator\\{$className}RequestValidator;
        use {$namespace}\\Domain\\Exception\\{$className}NotFoundException;
        use {$namespace}\\Domain\\Repository\\{$className}RepositoryInterface;
        use {$namespace}\\Domain\\Service\\{$className}ServiceInterface;

        final class {$className}Service implements {$className}ServiceInterface
        {
            public function __construct(
                private {$className}RepositoryInterface \$repository,
                private {$className}RequestValidator \$validator,
            ) {}

            public function list(): array
            {
                \$items = \$this->repository->findAll();

                return array_map(
                    fn(\$item) => new {$className}ResponseDTO(\$item->getId(), \$item->getName()),
                    \$items
                );
            }

            public function get(string \$id)
            {
                \$dto = new {$className}RequestDTO(id: \$id);

                \$errors = \$this->validator->validateForGet(\$dto);
                if (\$errors !== []) {
                    // Itt lehetne saját ValidationException is
                    throw new \\InvalidArgumentException(implode('; ', \$errors));
                }

                \$item = \$this->repository->findById(\$id);
                if (!\$item) {
                    throw new {$className}NotFoundException("{$className} not found: {\$id}");
                }

                return new {$className}ResponseDTO(\$item->getId(), \$item->getName());
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

        use {$namespace}\\Domain\\Model\\{$className};
        use {$namespace}\\Domain\\Repository\\{$className}RepositoryInterface;

        /**
         * In-memory repository példa.
         * Később cserélhető adatbázisos implementációra.
         */
        final class InMemory{$className}Repository implements {$className}RepositoryInterface
        {
            /** @var array<string, {$className}> */
            private array \$items = [];

            public function __construct()
            {
                \$this->items = [
                    '1' => new {$className}('1', 'First'),
                    '2' => new {$className}('2', 'Second'),
                ];
            }

            public function findAll(): array
            {
                return array_values(\$this->items);
            }

            public function findById(string \$id): ?{$className}
            {
                return \$this->items[\$id] ?? null;
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
        final class {$className}Request
        {
            public function __construct(
                private array \$queryParams = [],
                private array \$bodyParams = [],
            ) {}

            public function getId(): ?string
            {
                return \$this->queryParams['id'] ?? null;
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

        use {$namespace}\\Application\\DTO\\{$className}ResponseDTO;

        final class {$className}Response
        {
            /**
             * @param {$className}ResponseDTO[] \$items
             */
            public static function list(array \$items): array
            {
                return array_map(
                    fn({$className}ResponseDTO \$dto) => \$dto->toArray(),
                    \$items
                );
            }

            public static function single({$className}ResponseDTO \$dto): array
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

        use {$namespace}\\Application\\Service\\{$className}Service;
        use {$namespace}\\Presentation\\Http\\Request\\{$className}Request;
        use {$namespace}\\Presentation\\Http\\Response\\{$className}Response;

        final class {$className}Controller
        {
            public function __construct(
                private {$className}Service \$service,
            ) {}

            public function index(): array
            {
                \$items = \$this->service->list();

                return {$className}Response::list(\$items);
            }

            public function show(string \$id): array
            {
                \$item = \$this->service->get(\$id);

                return {$className}Response::single(\$item);
            }
        }

        PHP;
    }
}

