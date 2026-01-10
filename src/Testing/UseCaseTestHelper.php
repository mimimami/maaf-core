<?php

declare(strict_types=1);

namespace MAAF\Core\Testing;

use MAAF\Core\Container\ContainerInterface;
use MAAF\Core\Http\Request;
use MAAF\Core\Http\Response;
use PHPUnit\Framework\TestCase;

/**
 * Use Case Test Helper
 * 
 * Segédeszközök use case-ek teszteléséhez.
 * 
 * @version 1.0.0
 */
final class UseCaseTestHelper
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    /**
     * Create a test request
     * 
     * @param string $method HTTP method
     * @param string $uri Request URI
     * @param array<string, mixed> $query Query parameters
     * @param array<string, mixed> $post POST parameters
     * @param array<string, string> $headers Headers
     * @param string|null $body Request body
     * @return Request
     */
    public function createRequest(
        string $method = 'GET',
        string $uri = '/',
        array $query = [],
        array $post = [],
        array $headers = [],
        ?string $body = null
    ): Request {
        $server = [
            'REQUEST_METHOD' => $method,
            'REQUEST_URI' => $uri,
            'QUERY_STRING' => http_build_query($query),
        ];

        // Add headers to server array
        foreach ($headers as $name => $value) {
            $serverKey = 'HTTP_' . str_replace('-', '_', strtoupper($name));
            $server[$serverKey] = $value;
        }

        return new Request(
            server: $server,
            get: $query,
            post: $post,
            files: [],
            cookies: [],
            body: $body
        );
    }

    /**
     * Create JSON request
     * 
     * @param string $method HTTP method
     * @param string $uri Request URI
     * @param mixed $data JSON data
     * @param array<string, string> $headers Additional headers
     * @return Request
     */
    public function createJsonRequest(
        string $method,
        string $uri,
        mixed $data,
        array $headers = []
    ): Request {
        $headers['Content-Type'] = 'application/json';
        $headers['Accept'] = 'application/json';

        return $this->createRequest(
            method: $method,
            uri: $uri,
            headers: $headers,
            body: json_encode($data, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * Execute a controller action
     * 
     * @param string|array $handler Controller handler (class name or [class, method])
     * @param Request $request HTTP request
     * @param array<string, string> $routeParams Route parameters
     * @return Response
     */
    public function executeController(
        string|array $handler,
        Request $request,
        array $routeParams = []
    ): Response {
        if (is_string($handler)) {
            $instance = $this->container->make($handler);
            $method = 'index';
        } else {
            [$class, $method] = $handler;
            $instance = $this->container->has($class)
                ? $this->container->get($class)
                : $this->container->make($class);
        }

        if (!method_exists($instance, $method)) {
            throw new \RuntimeException("Method '{$method}' not found in " . get_class($instance));
        }

        // Resolve method arguments
        $reflection = new \ReflectionMethod($instance, $method);
        $arguments = $this->resolveArguments($reflection, $request, $routeParams);

        $result = $instance->$method(...$arguments);

        if (!$result instanceof Response) {
            throw new \RuntimeException("Controller method must return Response instance");
        }

        return $result;
    }

    /**
     * Execute a service method
     * 
     * @param string $serviceClass Service class name
     * @param string $method Method name
     * @param array<int, mixed> $arguments Method arguments
     * @return mixed
     */
    public function executeService(string $serviceClass, string $method, array $arguments = []): mixed
    {
        $service = $this->container->has($serviceClass)
            ? $this->container->get($serviceClass)
            : $this->container->make($serviceClass);

        if (!method_exists($service, $method)) {
            throw new \RuntimeException("Method '{$method}' not found in {$serviceClass}");
        }

        return $service->$method(...$arguments);
    }

    /**
     * Assert response is JSON
     * 
     * @param Response $response HTTP response
     * @param TestCase $testCase PHPUnit test case
     * @return array<string, mixed> Decoded JSON data
     */
    public function assertJsonResponse(Response $response, TestCase $testCase): array
    {
        $testCase->assertEquals(200, $response->getStatusCode());
        
        $headers = $response->getHeaders();
        $testCase->assertArrayHasKey('Content-Type', $headers);
        $testCase->assertStringContainsString('application/json', $headers['Content-Type']);

        $body = $response->getBody();
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Response is not valid JSON: ' . json_last_error_msg());
        }

        return $data;
    }

    /**
     * Assert response status code
     * 
     * @param Response $response HTTP response
     * @param int $expectedStatusCode Expected status code
     * @param TestCase $testCase PHPUnit test case
     * @return void
     */
    public function assertStatusCode(Response $response, int $expectedStatusCode, TestCase $testCase): void
    {
        $testCase->assertEquals($expectedStatusCode, $response->getStatusCode());
    }

    /**
     * Assert response contains data
     * 
     * @param Response $response HTTP response
     * @param array<string, mixed> $expectedData Expected data
     * @param TestCase $testCase PHPUnit test case
     * @return void
     */
    public function assertResponseContains(Response $response, array $expectedData, TestCase $testCase): void
    {
        $data = $this->assertJsonResponse($response, $testCase);

        foreach ($expectedData as $key => $value) {
            $testCase->assertArrayHasKey($key, $data);
            $testCase->assertEquals($value, $data[$key]);
        }
    }

    /**
     * Resolve method arguments using dependency injection
     * 
     * @param \ReflectionMethod $reflection Method reflection
     * @param Request $request HTTP request
     * @param array<string, string> $routeParams Route parameters
     * @return array<int, mixed>
     */
    private function resolveArguments(\ReflectionMethod $reflection, Request $request, array $routeParams): array
    {
        $arguments = [];

        foreach ($reflection->getParameters() as $param) {
            $type = $param->getType();

            if ($type instanceof \ReflectionNamedType) {
                $typeName = $type->getName();

                if ($typeName === Request::class) {
                    $arguments[] = $request;
                    continue;
                }

                if ($typeName === Response::class) {
                    continue;
                }

                // Check if param name matches route param
                if (isset($routeParams[$param->getName()])) {
                    $arguments[] = $routeParams[$param->getName()];
                    continue;
                }

                // Try to resolve from container
                if ($this->container->has($typeName)) {
                    $arguments[] = $this->container->get($typeName);
                    continue;
                }
            }

            // Use default value if available
            if ($param->isDefaultValueAvailable()) {
                $arguments[] = $param->getDefaultValue();
            }
        }

        return $arguments;
    }
}
