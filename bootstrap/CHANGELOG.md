# Changelog

This file describes changes in the framework.

### Added
- Event system:
  - `Framework\Core\EventManager` + `EventHandlerInterface` and `EventsHandlerInterface`.
  - `Core/native_events.json` as a source of truth for native event names.
- Kernel modularization:
  - `Framework\Kernel\KernelBootstrap`, `KernelEvents`, `KernelContext`, `KernelOptions`.
  - Kernel subscribers (`Framework\Kernel\Subscribers\*`) for step-based bootstrapping:
    - `ErrorHandlingSubscriber`, `DebugSubscriber`, `ViewEngineSubscriber`, `OrmSubscriber`, `AddonsSubscriber`, `RouterSubscriber`, `RequestLifecycleSubscriber`.
- RequestContext:
  - `Framework\Http\Objects\RequestContext` (requestId, ip, method, uri, startedAtFloat, durationSeconds).
  - New event: `request.context.created`.
- Prometheus addon (observability):
  - `Framework\Addons\Prometheus\MetricRegistry` + `RedisDriver`.
  - `PrometheusEventsHandler` + `PrometheusRequestCompletedHandler`.
  - `Framework\Addons\Prometheus\Controller` (the `/metrics` endpoint).

### Changed
- Kernel:
  - `Kernel.php` is now a thin orchestrator; bootstrapping logic is split into `kernel.configure_*` events and subscribers.
  - Subscribers are built via DI (no manual `new ...`), and runtime configuration is moved into `KernelOptions`.
- Router:
  - Response sending now emits `response.sent`.
  - `request.failed` has a standardized payload and includes `container` and `router`.
- Error handling:
  - Improvements in `Error/AppErrorHandler.php` (more consistent request lifecycle handling).

### Events
Added/maintained native events (see `Core/native_events.json`):
- Kernel lifecycle:
  - `kernel.boot`
  - `kernel.configure_error_handling`
  - `kernel.configure_debug`
  - `kernel.configure_view_engine`
  - `kernel.configure_orm`
  - `kernel.configure_addons`
  - `kernel.configure_router`
  - `kernel.handle_request`
- Request lifecycle:
  - `request.context.created`
  - `request.received`
  - `response.sent`
  - `request.completed`
  - `request.failed`

### Migration notes
- If you have custom event handlers or addons:
  - You can access `RequestContext` via `Container->resolve(Framework\Http\Objects\RequestContext::class)` (available after `request.context.created`).
  - If you previously relied on loosely-typed kernel context arrays, prefer `KernelContext` now (the legacy `set` callable still exists for compatibility).

### Security / Dependencies
- Prometheus storage requires access to Redis (driver based on a redis client). In the target application, make sure the extension/client is available at runtime.
