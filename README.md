# laminas-router

[![Build Status](https://travis-ci.com/laminas/laminas-router.svg?branch=master)](https://travis-ci.com/laminas/laminas-router)
[![Coverage Status](https://coveralls.io/repos/github/laminas/laminas-router/badge.svg?branch=master)](https://coveralls.io/github/laminas/laminas-router?branch=master)

laminas-router provides flexible HTTP routing.

Routing currently works against the [laminas-http](https://github.com/laminas/laminas-http)
request and responses, and provides capabilities around:

- Literal path matches
- Path segment matches (at path boundaries, and optionally validated using regex)
- Regular expression path matches
- HTTP request scheme
- HTTP request method
- Hostname

Additionally, it supports combinations of different route types in tree
structures, allowing for fast, b-tree lookups.

## Installation

Run the following to install this library:

```bash
$ composer require laminas/laminas-router
```

## Documentation

Browse the documentation online at https://docs.laminas.dev/laminas-router/

## Support

* [Issues](https://github.com/laminas/laminas-router/issues/)
* [Chat](https://laminas.dev/chat/)
* [Forum](https://discourse.laminas.dev/)
