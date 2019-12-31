# laminas-router

[![Build Status](https://travis-ci.org/laminas/laminas-router.svg?branch=master)](https://travis-ci.org/laminas/laminas-router)
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

- File issues at https://github.com/laminas/laminas-router/issues
- Documentation is at https://docs.laminas.dev/laminas-router
