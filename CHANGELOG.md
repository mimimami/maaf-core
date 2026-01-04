# Changelog

All notable changes to MAAF Core will be documented in this file.

## [1.1.0] - 2026-01-04

### Added
- **Application** - fő alkalmazás osztály egyszerű bootstrap és konfigurációhoz
- Opcionális konfigurációs fájl támogatás (`config/maaf.php`)
- Konvenció alapú automatikus fájlkeresés

## [1.0.0] - 2026-01-04

### Added
- ModuleLoader - automatikus modul felfedezés és regisztráció
- ModuleRegistry - modulok nyilvántartása
- ModuleMetadata - modul metadata osztály
- Request - HTTP kérés kezelés body parsing-szel
- Response - PSR-7 kompatibilis HTTP válasz
- HttpKernel - HTTP kernel request/response kezeléssel
- ControllerResolver - kontroller feloldás DI konténerből
- Router - route regisztráció és dispatch
- ContainerFactory - DI konténer helper
- **MiddlewarePipeline** - teljes middleware pipeline rendszer
- **MiddlewareInterface** - middleware interface
- **LoggingMiddleware** - példa logging middleware
- **CorsMiddleware** - példa CORS middleware
- **RateLimitingMiddleware** - példa rate limiting middleware

### Changed
- HttpKernel mostantól Router-t fogad konstruktorban (könyv szerint)
- HttpKernel middleware pipeline támogatással bővítve

### Deprecated
- `HttpKernel::setMiddleware()` - használj `addMiddleware()` helyette

