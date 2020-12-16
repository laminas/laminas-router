# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 3.4.4 - 2020-12-16


-----

### Release Notes for [3.4.4](https://github.com/laminas/laminas-router/milestone/8)

3.4.x bugfix release (patch)

### 3.4.4

- Total issues resolved: **1**
- Total pull requests resolved: **1**
- Total contributors: **1**

#### Bug

 - [24: cast to string when calling rawurlencode() within Wildcard router](https://github.com/laminas/laminas-router/pull/24) thanks to @gkralik

## 3.4.3 - 2020-12-07


-----

### Release Notes for [3.4.3](https://github.com/laminas/laminas-router/milestone/7)

3.4.x bugfix release (patch)

### 3.4.3

- Total issues resolved: **1**
- Total pull requests resolved: **2**
- Total contributors: **3**

#### Bug

 - [21: Fix type issue in Hostname route uncovered by strict types](https://github.com/laminas/laminas-router/pull/21) thanks to @Xerkus and @lklapa

#### Bug,Enhancement

 - [19: Removed redundant cast](https://github.com/laminas/laminas-router/pull/19) thanks to @matbech

## 3.4.2 - 2020-11-23


-----

### Release Notes for [3.4.2](https://github.com/laminas/laminas-router/milestone/6)

3.4.x bugfix release (patch)

### 3.4.2

- Total issues resolved: **0**
- Total pull requests resolved: **1**
- Total contributors: **1**

#### Bug

 - [17: bugfix: re-add `zendframework/zend-router` replacement](https://github.com/laminas/laminas-router/pull/17) thanks to @boesing

## 3.4.1 - 2020-11-19


-----

### Release Notes for [3.4.1](https://github.com/laminas/laminas-router/milestone/4)

3.4.x bugfix release (patch)

### 3.4.1

- Total issues resolved: **1**
- Total pull requests resolved: **1**
- Total contributors: **2**

#### Bug

 - [15: Cast to string when calling rawurlencode within Regex router](https://github.com/laminas/laminas-router/pull/15) thanks to @weierophinney and @matbech

## 4.0.0 - TBD

### Added

- [#12](https://github.com/laminas/laminas-router/pull/12) Adds PHP 8.0 support

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- [zendframework/zend-router#44](https://github.com/zendframework/zend-router/pull/44) removes support
  for PHP versions below PHP 7.1.

### Fixed

- Nothing.

## 3.3.2 - 2020-03-29

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Fixed `replace` version constraint in composer.json so repository can be used as replacement of `zendframework/zend-router:^3.3.0`.

## 3.3.1 - 2020-01-03

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#9](https://github.com/laminas/laminas-router/pull/9) fixes tests PHP 7.4 compatibility

## 3.3.0 - 2019-02-26

### Added

- [zendframework/zend-router#53](https://github.com/zendframework/zend-router/pull/53) adds support for PHP 7.3.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- [zendframework/zend-router#53](https://github.com/zendframework/zend-router/pull/53) removes support for laminas-stdlib v2 releases.

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

- [zendframework/zend-router#50](https://github.com/zendframework/zend-router/pull/54) Corrected PHPDoc
  for `RouterInterface#factory()` return type 

## 3.2.0 - 2018-08-01

### Added

- [zendframework/zend-router#50](https://github.com/zendframework/zend-router/pull/50) adds `Laminas\Router\Http\Placeholder`, which can be used within reusable
  modules to indicate a route with child routes where the root route may be
  overridden. By default, the `Placeholder` route always matches, passing on
  further matching to the defined child routes.

### Changed

- [zendframework/zend-router#38](https://github.com/zendframework/zend-router/pull/38) bumps the minimum supported laminas-http version to 2.8.1.

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

- [zendframework/zend-router#34](https://github.com/zendframework/zend-router/pull/34) dropped php 5.5 support

### Fixed

- [zendframework/zend-router#47](https://github.com/zendframework/zend-router/pull/47) fixes how the `Wildcard` URL assembly works. Previously, it would
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

- [zendframework/zend-router#5](https://github.com/zendframework/zend-router/pull/5) marks laminas-mvc
  versions less than 3.0.0 as conflicts.

## 3.0.1 - 2016-04-18

### Added

- [zendframework/zend-router#3](https://github.com/zendframework/zend-router/pull/3) adds a
  `config-provider` entry in `composer.json`, pointing to
  `Laminas\Router\ConfigProvider`.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-router#3](https://github.com/zendframework/zend-router/pull/3) fixes the
  `component` entry in `composer.json` to properly read `Laminas\Router`.

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
