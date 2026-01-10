<?php

declare(strict_types=1);

namespace MAAF\Core\Testing;

use MAAF\Core\Container\Container;
use MAAF\Core\Container\ContainerInterface;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base Test Case
 * 
 * Alap teszt osztály a MAAF framework-hez.
 * 
 * @version 1.0.0
 */
abstract class TestCase extends PHPUnitTestCase
{
    protected ContainerInterface $container;
    protected ModuleTestHelper $moduleHelper;
    protected UseCaseTestHelper $useCaseHelper;
    protected AdapterMockHelper $adapterHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = Container::fromDefinitions([]);
        $this->moduleHelper = new ModuleTestHelper($this->container);
        $this->useCaseHelper = new UseCaseTestHelper($this->container);
        $this->adapterHelper = new AdapterMockHelper();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Cleanup if needed
    }
}
