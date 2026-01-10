<?php

declare(strict_types=1);

namespace MAAF\Core\Tests\Testing;

use MAAF\Core\Testing\ModuleTestHelper;
use PHPUnit\Framework\TestCase;

/**
 * Module Test Helper Test
 * 
 * Példa teszt a ModuleTestHelper használatára.
 */
final class ModuleTestHelperTest extends TestCase
{
    private ModuleTestHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->helper = new ModuleTestHelper();
    }

    public function testLoadModule(): void
    {
        // Example module class would be loaded here
        // $this->helper->loadModule(ExampleModule::class, 'Example');
        // $this->helper->assertModuleLoaded('Example');
        
        $this->assertInstanceOf(ModuleTestHelper::class, $this->helper);
    }

    public function testRegisterServices(): void
    {
        $this->helper->registerServices([
            'test.service' => 'test-value',
        ]);

        $container = $this->helper->getContainer();
        $this->assertTrue($container->has('test.service'));
        $this->assertEquals('test-value', $container->get('test.service'));
    }

    public function testGetRouter(): void
    {
        $router = $this->helper->getRouter();
        $this->assertNotNull($router);
    }
}
