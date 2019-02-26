# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 3.3.0 - 2019-02-26

### Added

- [#53](https://github.com/zendframework/zend-router/pull/53) adds support for PHP 7.3.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- [#53](https://github.com/zendframework/zend-router/pull/53) removes support for zend-stdlib v2 releases.

### Fixed

- Nothing.

## 3.2.1 - 2019-01-09

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#50](https://github.com/zendframework/zend-router/pull/54) Corrected PHPDoc
  for `RouterInterface#factory()` return type 

## 3.2.0 - 2018-08-01

### Added

- [#50](https://github.com/zendframework/zend-router/pull/50) adds `Zend\Router\Http\Placeholder`, which can be used within reusable
  modules to indicate a route with child routes where the root route may be
  overridden. By default, the `Placeholder` route always matches, passing on
  further matching to the defined child routes.

### Changed

- [#38](https://github.com/zendframework/zend-router/pull/38) bumps the minimum supported zend-http version to 2.8.1.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 3.1.0 - 2018-06-18

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- [#34](https://github.com/zendframework/zend-router/pull/34) dropped php 5.5 support

### Fixed

- [#47](https://github.com/zendframework/zend-router/pull/47) fixes how the `Wildcard` URL assembly works. Previously, it would
  attempt to `rawurlencode()` all values provided to the method as merged with any default values.
  It now properly skips any non-scalar values when assembling the URL path. This fixes an issue
  discovered when providing an array of middleware as a `middleware` default route parameter.

## 3.0.2 - 2016-05-31

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#5](https://github.com/zendframework/zend-router/pull/5) marks zend-mvc
  versions less than 3.0.0 as conflicts.

## 3.0.1 - 2016-04-18

### Added

- [#3](https://github.com/zendframework/zend-router/pull/3) adds a
  `config-provider` entry in `composer.json`, pointing to
  `Zend\Router\ConfigProvider`.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#3](https://github.com/zendframework/zend-router/pull/3) fixes the
  `component` entry in `composer.json` to properly read `Zend\Router`.

## 3.0.0 - 2016-03-21

First release as standalone package in its own namespace. This is the first
version that will be used with zend-mvc v3; see its [migration document](https://docs.zendframework.com/zend-router/migration/v2-to-v3/)
for details on how to update existing routing to this version.

In particular, the `Zend\Mvc\Router` namespace was renamed to `Zend\Router`.

### Added

- [#2](https://github.com/zendframework/zend-router/pull/2) adds
  `ConfigProvider`, which is an invokable class that returns dependency
  configuration for the component; in particular, this will be useful for
  zend-expressive-zendrouter.
- [#2](https://github.com/zendframework/zend-router/pull/2) adds the `Module`
  class, for use with zend-mvc + zend-modulemanager. It provides dependency
  configuration for the component when used in that context.
- [#2](https://github.com/zendframework/zend-router/pull/2) adds
  zend-component-installer configuration for the above `ConfigProvider` and
  `Module`, to allow auto-registration with the application.
- [#2](https://github.com/zendframework/zend-router/pull/2) adds the following
  factories:
  - `Zend\Router\RouteInvokableFactory`, which provides a custom "invokable"
    factory for routes that uses the route class' `factory()` method for
    instantiation.
  - `Zend\Router\RoutePluginManagerFactory`, for creating a `RoutePluginManager`
    instance.
  - `Zend\Router\Http\HttpRouterFactory`, for returning a `TreeRouteStack`
    instance.
  - `Zend\Router\RouterFactory`, which essentially proxies to
    `Zend\Router\Http\HttpRouterFactory`.


### Deprecated

- Nothing.

### Removed

- [#2](https://github.com/zendframework/zend-router/pull/2) removes all
  console-related routing. These will be part of a new component,
  zend-mvc-console.
- [#2](https://github.com/zendframework/zend-router/pull/2) removes the `Query`
  route, as it had been deprecated starting with version 2.3.

### Fixed

- Nothing.
