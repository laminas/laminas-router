# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 3.0.0 - 2016-03-21

First release as standalone package in its own namespace. This is the first
version that will be used with laminas-mvc v3; see its [migration document](https://docs.laminas.dev/laminas-router/migration/v2-to-v3/)
for details on how to update existing routing to this version.

In particular, the `Laminas\Mvc\Router` namespace was renamed to `Laminas\Router`.

### Added

- [zendframework/zend-router#2](https://github.com/zendframework/zend-router/pull/2) adds
  `ConfigProvider`, which is an invokable class that returns dependency
  configuration for the component; in particular, this will be useful for
  mezzio-laminasrouter.
- [zendframework/zend-router#2](https://github.com/zendframework/zend-router/pull/2) adds the `Module`
  class, for use with laminas-mvc + laminas-modulemanager. It provides dependency
  configuration for the component when used in that context.
- [zendframework/zend-router#2](https://github.com/zendframework/zend-router/pull/2) adds
  laminas-component-installer configuration for the above `ConfigProvider` and
  `Module`, to allow auto-registration with the application.
- [zendframework/zend-router#2](https://github.com/zendframework/zend-router/pull/2) adds the following
  factories:
  - `Laminas\Router\RouteInvokableFactory`, which provides a custom "invokable"
    factory for routes that uses the route class' `factory()` method for
    instantiation.
  - `Laminas\Router\RoutePluginManagerFactory`, for creating a `RoutePluginManager`
    instance.
  - `Laminas\Router\Http\HttpRouterFactory`, for returning a `TreeRouteStack`
    instance.
  - `Laminas\Router\RouterFactory`, which essentially proxies to
    `Laminas\Router\Http\HttpRouterFactory`.


### Deprecated

- Nothing.

### Removed

- [zendframework/zend-router#2](https://github.com/zendframework/zend-router/pull/2) removes all
  console-related routing. These will be part of a new component,
  laminas-mvc-console.
- [zendframework/zend-router#2](https://github.com/zendframework/zend-router/pull/2) removes the `Query`
  route, as it had been deprecated starting with version 2.3.

### Fixed

- Nothing.
