<?php declare(strict_types=1);

namespace Ellipse\Session;

use Exception;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

use Interop\Http\Server\MiddlewareInterface;
use Interop\Http\Server\RequestHandlerInterface;

class ValidateSessionMiddleware implements MiddlewareInterface
{
    /**
     * The value of the key the metadata should be stored under.
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
     * Compare the list of key => value pairs to validate with the session
     * metadata and invalidate the session store if the metadata is set and
     * doesn't match.
     *
     * @param \Psr\Http\Message\ServerRequestInterface      $request
     * @param \Interop\Http\Server\RequestHandlerInterface  $handler
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $signature = ($this->signature)($request);

        $metadata = $_SESSION[self::METADATA_KEY] ?? [];

        if (! $this->validate($signature, $metadata)) {

            session_unset();
            session_regenerate_id();

        }

        $response = $handler->handle($request);

        $_SESSION[self::METADATA_KEY] = $signature;

        return $response;
    }

    /**
     * Return whether the given signature matches the given data.
     *
     * @param array $signature
     * @param array $data
     * @return bool
     */
    private function validate(array $signature, array $data): bool
    {
        foreach ($signature as $key => $value) {

            $metadata = $_SESSION[self::METADATA_KEY][$key] ?? null;

            if (! $this->match($value, $metadata)) {

                return false;

            }

        }

        return true;
    }

    /**
     * Return whether the second value is not null and equals the first one.
     *
     * @param string $v1
     * @param string $v2
     * @return bool
     */
    private function match($value, $metadata): bool
    {
        if (is_null($metadata)) return true;

        return $value === $metadata;
    }
}
