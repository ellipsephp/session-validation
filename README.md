# Session validation

This package provides a [Psr-15 middleware](https://www.php-fig.org/psr/psr-15/) allowing to validate the user session ownership.

**Require** php >= 7.1

**Installation** `composer require ellipse/session-validation`

**Run tests** `./vendor/bin/kahlan`

- [Using the validate session middleware](#using-the-validate-session-middleware)

# Using the validate session middleware

This middleware let the developper define a callable producing a client signature from the processed Psr-7 request. This signature is stored in the session so the subsequent request client signatures can be compared to the saved one. When they do not match the session data are unset and the session id gets regenerated.

The callable passed to the middleware is called with the Psr-7 request being processed and must return an associative array containing client specific data. For example it's ip address or user agent.

The middleware can of course be used after the `StartSessionMiddleware` from [ellipse/session-start](https://github.com/ellipsephp/session-start) in order to start a session.

```php
<?php

namespace App;

use Psr\Http\Message\ServerRequestInterface;

use Ellipse\Session\ValidateSessionMiddleware;

// This callable must return an associative array.
$signature = function (ServerRequestInterface $request) {

    // Here we assume another middleware set the client ip attribute of the request.
    return [
        'client-ip' => $request->getAttribute('client-ip'),
    ];

};

// When using this middleware the session data are tied to the client ip address.
// Any attempt to access the session data with a different client ip address would
// invalidate the client session. Obvioulsy this middleware should only be processed
// after the session has started.
$middleware = new ValidateSessionMiddleware($signature);
```
