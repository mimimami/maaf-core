<?php

declare(strict_types=1);

namespace MAAF\Core\Tests\Testing;

use MAAF\Core\Container\Container;
use MAAF\Core\Http\Request;
use MAAF\Core\Http\Response;
use MAAF\Core\Testing\UseCaseTestHelper;
use PHPUnit\Framework\TestCase;

/**
 * Use Case Test Helper Test
 * 
 * Példa teszt a UseCaseTestHelper használatára.
 */
final class UseCaseTestHelperTest extends TestCase
{
    private UseCaseTestHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        $container = Container::fromDefinitions([]);
        $this->helper = new UseCaseTestHelper($container);
    }

    public function testCreateRequest(): void
    {
        $request = $this->helper->createRequest('GET', '/test', ['id' => '123']);

        $this->assertInstanceOf(Request::class, $request);
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/test', $request->getPath());
        $this->assertEquals('123', $request->getQuery('id'));
    }

    public function testCreateJsonRequest(): void
    {
        $data = ['name' => 'Test', 'value' => 123];
        $request = $this->helper->createJsonRequest('POST', '/test', $data);

        $this->assertInstanceOf(Request::class, $request);
        $this->assertTrue($request->isJson());
        
        $parsedBody = $request->getParsedBody();
        $this->assertEquals('Test', $parsedBody['name']);
        $this->assertEquals(123, $parsedBody['value']);
    }

    public function testAssertJsonResponse(): void
    {
        $response = Response::json(['message' => 'Success']);
        $data = $this->helper->assertJsonResponse($response, $this);

        $this->assertEquals('Success', $data['message']);
    }

    public function testAssertStatusCode(): void
    {
        $response = Response::json(['error' => 'Not Found'], 404);
        $this->helper->assertStatusCode($response, 404, $this);
    }
}
