<?php declare(strict_types=1);

namespace Ellipse\Session;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Ellipse\Session\Exceptions\OwnershipSignatureNotValidException;

class ValidateSessionMiddleware implements MiddlewareInterface
{
    /**
     * The value of the key the ownership metadata should be stored under.
     *
     * @var string
     */
    const METADATA_KEY = '_ownership';

    /**
     * The callback producing an user signature from the request.
     *
     * @var callable
     */
    private $signature;

    /**
     * Set up a session validation middleware with the given signature callable.
     *
     * @param callable $signature
     */
    public function __construct(callable $signature)
    {
        $this->signature = $signature;
    }

    /**
     * Build a signature and compare it to the ownership metadata stored in
     * session. Invalidate the session when any key does not match. Process the
     * request and save the current signature in session.
     *
     * @param \Psr\Http\Message\ServerRequestInterface  $request
     * @param \Psr\Http\Server\RequestHandlerInterface  $handler
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Get a valid signature.
        $signature = ($this->signature)($request);

        if (! is_array($signature)) {

            throw new OwnershipSignatureNotValidException($signature);

        }

        // Invalidate the session when signature and session does not match.
        $metadata = $_SESSION[self::METADATA_KEY] ?? [];

        if (! $this->compare($signature, $metadata)) {

            session_unset();
            session_regenerate_id();

        }

        // Process the request and save the current signature.
        $response = $handler->handle($request);

        $_SESSION[self::METADATA_KEY] = $signature;

        return $response;
    }

    /**
     * Return whether all keys of the given signature match the given metadata.
     *
     * @param array $signature
     * @param array $metadata
     * @return bool
     */
    private function compare(array $signature, array $metadata): bool
    {
        foreach ($signature as $key => $v1) {

            $v2 = $metadata[$key] ?? null;

            if (! is_null($v2) && $v1 !== $v2) return false;

        }

        return true;
    }
}
