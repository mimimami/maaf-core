<?php

declare(strict_types=1);

namespace MAAF\Core\ModuleGenerator\Templates;

use MAAF\Core\ModuleGenerator\GeneratedFile;
use MAAF\Core\ModuleGenerator\ModuleMetadata;
use MAAF\Core\ModuleGenerator\ModuleTemplate;

/**
 * Institution Module Template
 * 
 * Intézmény modul sablon Hexagonal / Clean Architecture struktúrával.
 * 
 * @version 1.0.0
 */
final class InstitutionModuleTemplate implements ModuleTemplate
{
    public function getName(): string
    {
        return 'institution';
    }

    public function getDescription(): string
    {
        return 'Institution module with contact and address management (Hexagonal Architecture)';
    }

    /**
     * @return GeneratedFile[]
     */
    public function getFiles(ModuleMetadata $metadata): array
    {
        $moduleName = $metadata->name;
        $className  = 'Institution';
        $namespace  = $metadata->namespace . '\\' . $moduleName;

        return [
            // Gyökér szint
            new GeneratedFile(
                'InstitutionModule.php',
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
                'Domain/Model/Institution.php',
                $this->getDomainModelContent($namespace, $className),
            ),
            new GeneratedFile(
                'Domain/Repository/InstitutionRepositoryInterface.php',
                $this->getDomainRepositoryInterfaceContent($namespace, $className),
            ),
            new GeneratedFile(
                'Domain/Service/InstitutionServiceInterface.php',
                $this->getDomainServiceInterfaceContent($namespace, $className),
            ),
            new GeneratedFile(
                'Domain/Exception/InstitutionNotFoundException.php',
                $this->getDomainNotFoundExceptionContent($namespace, $className),
            ),

            // Application réteg
            new GeneratedFile(
                'Application/DTO/InstitutionRequestDTO.php',
                $this->getApplicationRequestDtoContent($namespace, $className),
            ),
            new GeneratedFile(
                'Application/DTO/InstitutionResponseDTO.php',
                $this->getApplicationResponseDtoContent($namespace, $className),
            ),
            new GeneratedFile(
                'Application/Validator/InstitutionRequestValidator.php',
                $this->getApplicationRequestValidatorContent($namespace, $className),
            ),
            new GeneratedFile(
                'Application/Service/InstitutionService.php',
                $this->getApplicationServiceContent($namespace, $className),
            ),

            // Infrastructure réteg
            new GeneratedFile(
                'Infrastructure/Repository/InMemoryInstitutionRepository.php',
                $this->getInfrastructureRepositoryContent($namespace, $className),
            ),

            // Presentation réteg (HTTP)
            new GeneratedFile(
                'Presentation/Http/Request/InstitutionRequest.php',
                $this->getPresentationRequestContent($namespace, $className),
            ),
            new GeneratedFile(
                'Presentation/Http/Response/InstitutionResponse.php',
                $this->getPresentationResponseContent($namespace, $className),
            ),
            new GeneratedFile(
                'Presentation/Http/Controller/InstitutionController.php',
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

final class InstitutionModule extends BaseModule
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
            Domain\\Repository\\InstitutionRepositoryInterface::class => DI\create(Infrastructure\\Repository\\InMemoryInstitutionRepository::class),
            Domain\\Service\\InstitutionServiceInterface::class => DI\create(Application\\Service\\InstitutionService::class)
                ->constructor(
                    DI\get(Domain\\Repository\\InstitutionRepositoryInterface::class),
                    DI\create(Application\\Validator\\InstitutionRequestValidator::class)
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

    private function getRoutesContent(string $namespace): string
    {
        $base = 'institutions';

        return <<<PHP
<?php

use MAAF\\Core\\Routing\\Router;
use {$namespace}\\Presentation\\Http\\Controller\\InstitutionController;

return function (Router \$router): void {
    \$router->get('/{$base}', [InstitutionController::class, 'index']);
    \$router->get('/{$base}/{id}', [InstitutionController::class, 'show']);
    \$router->post('/{$base}', [InstitutionController::class, 'create']);
    \$router->put('/{$base}/{id}', [InstitutionController::class, 'update']);
    \$router->delete('/{$base}/{id}', [InstitutionController::class, 'delete']);
};

PHP;
    }

    private function getDomainModelContent(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Domain\\Model;

final class Institution
{
    public function __construct(
        private string \$id,
        private string \$name,
        private string \$type,
        private string \$address,
        private string \$city,
        private string \$country,
        private string \$postalCode,
        private string \$email,
        private string \$phone,
        private ?string \$website = null,
        private ?string \$description = null,
        private bool \$isActive = true,
    ) {}

    public function getId(): string
    {
        return \$this->id;
    }

    public function getName(): string
    {
        return \$this->name;
    }

    public function getType(): string
    {
        return \$this->type;
    }

    public function getAddress(): string
    {
        return \$this->address;
    }

    public function getCity(): string
    {
        return \$this->city;
    }

    public function getCountry(): string
    {
        return \$this->country;
    }

    public function getPostalCode(): string
    {
        return \$this->postalCode;
    }

    public function getEmail(): string
    {
        return \$this->email;
    }

    public function getPhone(): string
    {
        return \$this->phone;
    }

    public function getWebsite(): ?string
    {
        return \$this->website;
    }

    public function getDescription(): ?string
    {
        return \$this->description;
    }

    public function isActive(): bool
    {
        return \$this->isActive;
    }

    public function updateName(string \$name): void
    {
        \$this->name = \$name;
    }

    public function updateAddress(string \$address, string \$city, string \$country, string \$postalCode): void
    {
        \$this->address = \$address;
        \$this->city = \$city;
        \$this->country = \$country;
        \$this->postalCode = \$postalCode;
    }

    public function updateContact(string \$email, string \$phone, ?string \$website = null): void
    {
        \$this->email = \$email;
        \$this->phone = \$phone;
        \$this->website = \$website;
    }

    public function updateDescription(?string \$description): void
    {
        \$this->description = \$description;
    }

    public function activate(): void
    {
        \$this->isActive = true;
    }

    public function deactivate(): void
    {
        \$this->isActive = false;
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

use {$namespace}\\Domain\\Model\\Institution;

interface InstitutionRepositoryInterface
{
    /**
     * @return Institution[]
     */
    public function findAll(): array;

    public function findById(string \$id): ?Institution;

    /**
     * @return Institution[]
     */
    public function findByType(string \$type): array;

    /**
     * @return Institution[]
     */
    public function findByCity(string \$city): array;

    public function findByEmail(string \$email): ?Institution;

    public function save(Institution \$institution): Institution;

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

use {$namespace}\\Domain\\Model\\Institution;

interface InstitutionServiceInterface
{
    /**
     * @return Institution[]
     */
    public function list(): array;

    public function get(string \$id): Institution;

    /**
     * @return Institution[]
     */
    public function getByType(string \$type): array;

    /**
     * @return Institution[]
     */
    public function getByCity(string \$city): array;

    public function create(
        string \$name,
        string \$type,
        string \$address,
        string \$city,
        string \$country,
        string \$postalCode,
        string \$email,
        string \$phone,
        ?string \$website = null
    ): Institution;

    public function update(string \$id, array \$data): Institution;

    public function delete(string \$id): void;

    public function activate(string \$id): Institution;

    public function deactivate(string \$id): Institution;
}

PHP;
    }

    private function getDomainNotFoundExceptionContent(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Domain\\Exception;

final class InstitutionNotFoundException extends \\RuntimeException
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

final class InstitutionRequestDTO
{
    public function __construct(
        private ?string \$id = null,
        private ?string \$name = null,
        private ?string \$type = null,
        private ?string \$address = null,
        private ?string \$city = null,
        private ?string \$country = null,
        private ?string \$postalCode = null,
        private ?string \$email = null,
        private ?string \$phone = null,
        private ?string \$website = null,
        private ?string \$description = null,
        private ?bool \$isActive = null,
    ) {}

    public function getId(): ?string
    {
        return \$this->id;
    }

    public function getName(): ?string
    {
        return \$this->name;
    }

    public function getType(): ?string
    {
        return \$this->type;
    }

    public function getAddress(): ?string
    {
        return \$this->address;
    }

    public function getCity(): ?string
    {
        return \$this->city;
    }

    public function getCountry(): ?string
    {
        return \$this->country;
    }

    public function getPostalCode(): ?string
    {
        return \$this->postalCode;
    }

    public function getEmail(): ?string
    {
        return \$this->email;
    }

    public function getPhone(): ?string
    {
        return \$this->phone;
    }

    public function getWebsite(): ?string
    {
        return \$this->website;
    }

    public function getDescription(): ?string
    {
        return \$this->description;
    }

    public function getIsActive(): ?bool
    {
        return \$this->isActive;
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

final class InstitutionResponseDTO
{
    public function __construct(
        private string \$id,
        private string \$name,
        private string \$type,
        private string \$address,
        private string \$city,
        private string \$country,
        private string \$postalCode,
        private string \$email,
        private string \$phone,
        private ?string \$website = null,
        private ?string \$description = null,
        private bool \$isActive = true,
    ) {}

    public function getId(): string
    {
        return \$this->id;
    }

    public function getName(): string
    {
        return \$this->name;
    }

    public function getType(): string
    {
        return \$this->type;
    }

    public function getAddress(): string
    {
        return \$this->address;
    }

    public function getCity(): string
    {
        return \$this->city;
    }

    public function getCountry(): string
    {
        return \$this->country;
    }

    public function getPostalCode(): string
    {
        return \$this->postalCode;
    }

    public function getEmail(): string
    {
        return \$this->email;
    }

    public function getPhone(): string
    {
        return \$this->phone;
    }

    public function getWebsite(): ?string
    {
        return \$this->website;
    }

    public function getDescription(): ?string
    {
        return \$this->description;
    }

    public function isActive(): bool
    {
        return \$this->isActive;
    }

    public function toArray(): array
    {
        return [
            'id' => \$this->id,
            'name' => \$this->name,
            'type' => \$this->type,
            'address' => \$this->address,
            'city' => \$this->city,
            'country' => \$this->country,
            'postal_code' => \$this->postalCode,
            'email' => \$this->email,
            'phone' => \$this->phone,
            'website' => \$this->website,
            'description' => \$this->description,
            'is_active' => \$this->isActive,
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

use {$namespace}\\Application\\DTO\\InstitutionRequestDTO;

final class InstitutionRequestValidator
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
    public function validateForGet(InstitutionRequestDTO \$dto): array
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
    public function validateForCreate(InstitutionRequestDTO \$dto): array
    {
        \$errors = [];

        if (\$dto->getName() === null || \$dto->getName() === '') {
            \$errors[] = 'Name is required';
        }

        if (\$dto->getType() === null || \$dto->getType() === '') {
            \$errors[] = 'Type is required';
        }

        if (\$dto->getAddress() === null || \$dto->getAddress() === '') {
            \$errors[] = 'Address is required';
        }

        if (\$dto->getCity() === null || \$dto->getCity() === '') {
            \$errors[] = 'City is required';
        }

        if (\$dto->getCountry() === null || \$dto->getCountry() === '') {
            \$errors[] = 'Country is required';
        }

        if (\$dto->getEmail() === null || \$dto->getEmail() === '') {
            \$errors[] = 'Email is required';
        } elseif (!filter_var(\$dto->getEmail(), FILTER_VALIDATE_EMAIL)) {
            \$errors[] = 'Email is invalid';
        }

        if (\$dto->getPhone() === null || \$dto->getPhone() === '') {
            \$errors[] = 'Phone is required';
        }

        return \$errors;
    }

    /**
     * @return string[] Hibák listája
     */
    public function validateForUpdate(InstitutionRequestDTO \$dto): array
    {
        \$errors = [];

        if (\$dto->getId() === null || \$dto->getId() === '') {
            \$errors[] = 'ID is required';
        }

        if (\$dto->getEmail() !== null && !filter_var(\$dto->getEmail(), FILTER_VALIDATE_EMAIL)) {
            \$errors[] = 'Email is invalid';
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

use {$namespace}\\Application\\DTO\\InstitutionRequestDTO;
use {$namespace}\\Application\\DTO\\InstitutionResponseDTO;
use {$namespace}\\Application\\Validator\\InstitutionRequestValidator;
use {$namespace}\\Domain\\Exception\\InstitutionNotFoundException;
use {$namespace}\\Domain\\Repository\\InstitutionRepositoryInterface;
use {$namespace}\\Domain\\Service\\InstitutionServiceInterface;

final class InstitutionService implements InstitutionServiceInterface
{
    public function __construct(
        private InstitutionRepositoryInterface \$repository,
        private InstitutionRequestValidator \$validator,
    ) {}

    public function list(): array
    {
        \$items = \$this->repository->findAll();

        return array_map(
            fn(\$item) => new InstitutionResponseDTO(
                \$item->getId(),
                \$item->getName(),
                \$item->getType(),
                \$item->getAddress(),
                \$item->getCity(),
                \$item->getCountry(),
                \$item->getPostalCode(),
                \$item->getEmail(),
                \$item->getPhone(),
                \$item->getWebsite(),
                \$item->getDescription(),
                \$item->isActive()
            ),
            \$items
        );
    }

    public function get(string \$id)
    {
        \$dto = new InstitutionRequestDTO(id: \$id);

        \$errors = \$this->validator->validateForGet(\$dto);
        if (\$errors !== []) {
            throw new \\InvalidArgumentException(implode('; ', \$errors));
        }

        \$item = \$this->repository->findById(\$id);
        if (!\$item) {
            throw new InstitutionNotFoundException("Institution not found: {\$id}");
        }

        return new InstitutionResponseDTO(
            \$item->getId(),
            \$item->getName(),
            \$item->getType(),
            \$item->getAddress(),
            \$item->getCity(),
            \$item->getCountry(),
            \$item->getPostalCode(),
            \$item->getEmail(),
            \$item->getPhone(),
            \$item->getWebsite(),
            \$item->getDescription(),
            \$item->isActive()
        );
    }

    public function getByType(string \$type): array
    {
        \$items = \$this->repository->findByType(\$type);

        return array_map(
            fn(\$item) => new InstitutionResponseDTO(
                \$item->getId(),
                \$item->getName(),
                \$item->getType(),
                \$item->getAddress(),
                \$item->getCity(),
                \$item->getCountry(),
                \$item->getPostalCode(),
                \$item->getEmail(),
                \$item->getPhone(),
                \$item->getWebsite(),
                \$item->getDescription(),
                \$item->isActive()
            ),
            \$items
        );
    }

    public function getByCity(string \$city): array
    {
        \$items = \$this->repository->findByCity(\$city);

        return array_map(
            fn(\$item) => new InstitutionResponseDTO(
                \$item->getId(),
                \$item->getName(),
                \$item->getType(),
                \$item->getAddress(),
                \$item->getCity(),
                \$item->getCountry(),
                \$item->getPostalCode(),
                \$item->getEmail(),
                \$item->getPhone(),
                \$item->getWebsite(),
                \$item->getDescription(),
                \$item->isActive()
            ),
            \$items
        );
    }

    public function create(
        string \$name,
        string \$type,
        string \$address,
        string \$city,
        string \$country,
        string \$postalCode,
        string \$email,
        string \$phone,
        ?string \$website = null
    ): \{$namespace}\\Domain\\Model\\Institution
    {
        \$dto = new InstitutionRequestDTO(
            name: \$name,
            type: \$type,
            address: \$address,
            city: \$city,
            country: \$country,
            postalCode: \$postalCode,
            email: \$email,
            phone: \$phone,
            website: \$website
        );

        \$errors = \$this->validator->validateForCreate(\$dto);
        if (\$errors !== []) {
            throw new \\InvalidArgumentException(implode('; ', \$errors));
        }

        \$institution = new \{$namespace}\\Domain\\Model\\Institution(
            id: uniqid('inst_', true),
            name: \$name,
            type: \$type,
            address: \$address,
            city: \$city,
            country: \$country,
            postalCode: \$postalCode,
            email: \$email,
            phone: \$phone,
            website: \$website
        );

        return \$this->repository->save(\$institution);
    }

    public function update(string \$id, array \$data): \{$namespace}\\Domain\\Model\\Institution
    {
        \$dto = new InstitutionRequestDTO(
            id: \$id,
            name: \$data['name'] ?? null,
            type: \$data['type'] ?? null,
            address: \$data['address'] ?? null,
            city: \$data['city'] ?? null,
            country: \$data['country'] ?? null,
            postalCode: \$data['postal_code'] ?? null,
            email: \$data['email'] ?? null,
            phone: \$data['phone'] ?? null,
            website: \$data['website'] ?? null,
            description: \$data['description'] ?? null,
            isActive: \$data['is_active'] ?? null,
        );

        \$errors = \$this->validator->validateForUpdate(\$dto);
        if (\$errors !== []) {
            throw new \\InvalidArgumentException(implode('; ', \$errors));
        }

        \$institution = \$this->repository->findById(\$id);
        if (!\$institution) {
            throw new InstitutionNotFoundException("Institution not found: {\$id}");
        }

        if (\$dto->getName() !== null) {
            \$institution->updateName(\$dto->getName());
        }
        if (\$dto->getAddress() !== null && \$dto->getCity() !== null && \$dto->getCountry() !== null && \$dto->getPostalCode() !== null) {
            \$institution->updateAddress(
                \$dto->getAddress(),
                \$dto->getCity(),
                \$dto->getCountry(),
                \$dto->getPostalCode()
            );
        }
        if (\$dto->getEmail() !== null && \$dto->getPhone() !== null) {
            \$institution->updateContact(
                \$dto->getEmail(),
                \$dto->getPhone(),
                \$dto->getWebsite()
            );
        }
        if (\$dto->getDescription() !== null) {
            \$institution->updateDescription(\$dto->getDescription());
        }
        if (\$dto->getIsActive() !== null) {
            if (\$dto->getIsActive()) {
                \$institution->activate();
            } else {
                \$institution->deactivate();
            }
        }

        return \$this->repository->save(\$institution);
    }

    public function delete(string \$id): void
    {
        \$institution = \$this->repository->findById(\$id);
        if (!\$institution) {
            throw new InstitutionNotFoundException("Institution not found: {\$id}");
        }

        \$this->repository->delete(\$id);
    }

    public function activate(string \$id): \{$namespace}\\Domain\\Model\\Institution
    {
        \$institution = \$this->repository->findById(\$id);
        if (!\$institution) {
            throw new InstitutionNotFoundException("Institution not found: {\$id}");
        }

        \$institution->activate();
        return \$this->repository->save(\$institution);
    }

    public function deactivate(string \$id): \{$namespace}\\Domain\\Model\\Institution
    {
        \$institution = \$this->repository->findById(\$id);
        if (!\$institution) {
            throw new InstitutionNotFoundException("Institution not found: {\$id}");
        }

        \$institution->deactivate();
        return \$this->repository->save(\$institution);
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

use {$namespace}\\Domain\\Model\\Institution;
use {$namespace}\\Domain\\Repository\\InstitutionRepositoryInterface;

/**
 * In-memory repository példa.
 * Később cserélhető adatbázisos implementációra.
 */
final class InMemoryInstitutionRepository implements InstitutionRepositoryInterface
{
    /** @var array<string, Institution> */
    private array \$items = [];

    public function __construct()
    {
        \$this->items = [
            'inst_1' => new Institution(
                'inst_1',
                'University of Technology',
                'university',
                '123 Main Street',
                'Budapest',
                'Hungary',
                '1011',
                'info@university.edu',
                '+36-1-123-4567',
                'https://university.edu'
            ),
            'inst_2' => new Institution(
                'inst_2',
                'Business School',
                'college',
                '456 Business Avenue',
                'Budapest',
                'Hungary',
                '1022',
                'contact@businessschool.edu',
                '+36-1-987-6543'
            ),
        ];
    }

    public function findAll(): array
    {
        return array_values(\$this->items);
    }

    public function findById(string \$id): ?Institution
    {
        return \$this->items[\$id] ?? null;
    }

    /**
     * @return Institution[]
     */
    public function findByType(string \$type): array
    {
        return array_values(array_filter(
            \$this->items,
            fn(Institution \$institution) => \$institution->getType() === \$type
        ));
    }

    /**
     * @return Institution[]
     */
    public function findByCity(string \$city): array
    {
        return array_values(array_filter(
            \$this->items,
            fn(Institution \$institution) => \$institution->getCity() === \$city
        ));
    }

    public function findByEmail(string \$email): ?Institution
    {
        foreach (\$this->items as \$institution) {
            if (\$institution->getEmail() === \$email) {
                return \$institution;
            }
        }
        return null;
    }

    public function save(Institution \$institution): Institution
    {
        \$this->items[\$institution->getId()] = \$institution;
        return \$institution;
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
final class InstitutionRequest
{
    public function __construct(
        private array \$queryParams = [],
        private array \$bodyParams = [],
    ) {}

    public function getId(): ?string
    {
        return \$this->queryParams['id'] ?? null;
    }

    public function getName(): ?string
    {
        return \$this->bodyParams['name'] ?? null;
    }

    public function getType(): ?string
    {
        return \$this->bodyParams['type'] ?? null;
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

use {$namespace}\\Application\\DTO\\InstitutionResponseDTO;

final class InstitutionResponse
{
    /**
     * @param InstitutionResponseDTO[] \$items
     */
    public static function list(array \$items): array
    {
        return array_map(
            fn(InstitutionResponseDTO \$dto) => \$dto->toArray(),
            \$items
        );
    }

    public static function single(InstitutionResponseDTO \$dto): array
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

use {$namespace}\\Application\\Service\\InstitutionService;
use {$namespace}\\Presentation\\Http\\Request\\InstitutionRequest;
use {$namespace}\\Presentation\\Http\\Response\\InstitutionResponse;

final class InstitutionController
{
    public function __construct(
        private InstitutionService \$service,
    ) {}

    public function index(): array
    {
        \$items = \$this->service->list();

        return InstitutionResponse::list(\$items);
    }

    public function show(string \$id): array
    {
        \$item = \$this->service->get(\$id);

        return InstitutionResponse::single(\$item);
    }

    public function create(InstitutionRequest \$request): array
    {
        \$data = \$request->getAll()['body'];
        \$institution = \$this->service->create(
            \$data['name'] ?? '',
            \$data['type'] ?? '',
            \$data['address'] ?? '',
            \$data['city'] ?? '',
            \$data['country'] ?? '',
            \$data['postal_code'] ?? '',
            \$data['email'] ?? '',
            \$data['phone'] ?? '',
            \$data['website'] ?? null
        );

        return [
            'id' => \$institution->getId(),
            'name' => \$institution->getName(),
            'type' => \$institution->getType(),
        ];
    }

    public function update(string \$id, InstitutionRequest \$request): array
    {
        \$data = \$request->getAll()['body'];
        \$institution = \$this->service->update(\$id, \$data);

        return [
            'id' => \$institution->getId(),
            'name' => \$institution->getName(),
            'is_active' => \$institution->isActive(),
        ];
    }

    public function delete(string \$id): array
    {
        \$this->service->delete(\$id);
        return ['message' => 'Institution deleted'];
    }
}

PHP;
    }
}
