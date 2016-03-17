# Migrating from v2 to v3

zend-router v3 makes a number of changes that may affect your application. This
document details those changes, and provides suggestions on how to update your
application to work with v3.

## Query route removal

`Zend\Mvc\Router\Http\Query` was deprecated starting in version 2.3.0. If you
were using it previously, it no longer exists. Routing based on query strings is
considered problematic, due to the fact that arbitrary keys may be specified,
which could potentially lead to nefarious actions such as selection of an
alternate controller or controller action.

If you relied on this route, you *will* need to update your code.

## Namespace change

In zend-router v2, the namespace used was `Zend\Mvc\Router`, as it originated in
the zend-mvc component. With v3, the namespace was changed to `Zend\Router`.

If you were referring to fully qualified class names or the `Zend\Mvc\Router`
namespace, you will need to update your code. You can do so by running a script
such as the following (assumes \*nix-based operating system such as Mac OS X or
Linux):

```bash
$ for code in $(grep -rl 'Zend.Mvc.Router' .);do
> sed --in-place -e 's/Zend\\Mvc\\Router/Zend\\Router/g' ${code}
> done
```

The above recursively lists files that use the old namespace, and then edits
them in place to replace the old namespace with the new one.

(Make sure you don't do this at your project root, as that could also change the
files under your `vendor/` tree, which you almost certainly do not want to do!)

## Console routes

Console routing was removed from zend-router for version 3. If you use console
routing, you will need to install the new zend-mvc-console package:

```bash
$ composer require zendframework/zend-mvc-console
```
