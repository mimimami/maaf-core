<?php

namespace PHPSTORM_META {

    // DI Container support
    override(\Psr\Container\ContainerInterface::get(0), type(0));
    override(\DI\Container::get(0), type(0));
    
    // Application class methods
    override(\MAAF\Core\Application::getContainer(), type(\Psr\Container\ContainerInterface::class));
    override(\MAAF\Core\Application::getKernel(), type(\MAAF\Core\Http\HttpKernel::class));
    override(\MAAF\Core\Application::getRouter(), type(\MAAF\Core\Routing\Router::class));
    override(\MAAF\Core\Application::getLoader(), type(\MAAF\Core\Module\ModuleLoader::class));
    
    // HttpKernel methods
    override(\MAAF\Core\Http\HttpKernel::handle(0), type(\MAAF\Core\Http\Response::class));
    
    // Router methods
    override(\MAAF\Core\Routing\Router::addRoute(0), type(\MAAF\Core\Routing\Router::class));
    
    // Request methods
    override(\MAAF\Core\Http\Request::getQuery(0), type(0));
    override(\MAAF\Core\Http\Request::getBodyValue(0), type(0));
    override(\MAAF\Core\Http\Request::getHeader(0), type('string|null'));
    
    // Response static methods
    override(\MAAF\Core\Http\Response::json(0), type(\MAAF\Core\Http\Response::class));
    override(\MAAF\Core\Http\Response::text(0), type(\MAAF\Core\Http\Response::class));
    override(\MAAF\Core\Http\Response::html(0), type(\MAAF\Core\Http\Response::class));
    override(\MAAF\Core\Http\Response::empty(0), type(\MAAF\Core\Http\Response::class));
    
    // ModuleLoader methods
    override(\MAAF\Core\Module\ModuleLoader::discover(), type(\MAAF\Core\Module\ModuleLoader::class));
    override(\MAAF\Core\Module\ModuleLoader::registerServices(0), type(\MAAF\Core\Module\ModuleLoader::class));
    override(\MAAF\Core\Module\ModuleLoader::registerRoutes(0), type(\MAAF\Core\Module\ModuleLoader::class));
    
    // Expected return types
    expectedReturnValues(
        \MAAF\Core\Http\Request::getMethod(),
        'GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD'
    );
    
    expectedReturnValues(
        \MAAF\Core\Http\Response::getStatusCode(),
        200, 201, 204, 400, 401, 403, 404, 405, 422, 500
    );
}

