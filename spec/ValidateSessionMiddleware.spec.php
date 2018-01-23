<?php

use function Eloquent\Phony\Kahlan\mock;
use function Eloquent\Phony\Kahlan\stub;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Ellipse\Session\ValidateSessionMiddleware;
use Ellipse\Session\Exceptions\OwnershipSignatureNotValidException;

describe('ValidateSessionMiddleware', function () {

    beforeAll(function () {

        session_cache_limiter('');
        session_start(['use_cookies' => false]);

    });

    it('should implement MiddlewareInterface', function () {

        $signature = stub();

        $test = new ValidateSessionMiddleware($signature);

        expect($test)->toBeAnInstanceOf(MiddlewareInterface::class);

    });

    describe('->process()', function () {

        beforeEach(function () {

            $this->request = mock(ServerRequestInterface::class)->get();
            $this->response = mock(ResponseInterface::class)->get();

            $this->handler = mock(RequestHandlerInterface::class);

            $this->handler->handle->returns($this->response);

        });

        it('should proxy the request handler ->handle() method', function () {

            $signature = stub()->returns([]);

            $middleware = new ValidateSessionMiddleware($signature);

            $test = $middleware->process($this->request, $this->handler->get());

            expect($test)->toBe($this->response);

        });

        it('should add the signature values to the session metadata', function () {

            $signature = stub()->returns(['clientip' => 'ip']);

            $middleware = new ValidateSessionMiddleware($signature);

            $middleware->process($this->request, $this->handler->get());

            expect($_SESSION)->toEqual([
                ValidateSessionMiddleware::METADATA_KEY => [
                    'clientip' => 'ip',
                ],
            ]);

        });

        context('when the signature is not an array', function () {

            it('should throw an OwnershipSignatureNotValidException', function () {

                $signature = stub()->returns('signature');

                $middleware = new ValidateSessionMiddleware($signature);

                $test = function () use ($middleware) {

                    $middleware->process($this->request, $this->handler->get());

                };

                $exception = new OwnershipSignatureNotValidException('signature');

                expect($test)->toThrow($exception);

            });

        });

        context('when the signature is an empty array', function () {

            beforeEach(function () {

                $_SESSION = ['key' => 'value'];

                $signature = stub()->returns([]);

                $this->middleware = new ValidateSessionMiddleware($signature);

            });

            it('should not regenerate the session id', function () {

                $session_id = session_id();

                $this->middleware->process($this->request, $this->handler->get());

                $test = session_id();

                expect($test)->toEqual($session_id);

            });

            it('should not update the session data', function () {

                $this->middleware->process($this->request, $this->handler->get());

                expect($_SESSION)->toContainKey('key');
                expect($_SESSION['key'])->toEqual('value');

            });

        });

        context('when the session does not contain the signature array keys', function () {

            beforeEach(function () {

                $_SESSION = [
                    'key' => 'value',
                    ValidateSessionMiddleware::METADATA_KEY => [
                        'useragent' => 'browser',
                    ],
                ];

                $signature = stub()->returns(['clientip' => 'ip']);

                $this->middleware = new ValidateSessionMiddleware($signature);

            });

            it('should not regenerate the session id', function () {

                $session_id = session_id();

                $this->middleware->process($this->request, $this->handler->get());

                $test = session_id();

                expect($test)->toEqual($session_id);

            });

            it('should not update the session data', function () {

                $this->middleware->process($this->request, $this->handler->get());

                expect($_SESSION)->toContainKey('key');
                expect($_SESSION['key'])->toEqual('value');

            });

        });

        context('when the signature and the session metadata does not match', function () {

            beforeEach(function () {

                $_SESSION = [
                    'key' => 'value',
                    ValidateSessionMiddleware::METADATA_KEY => [
                        'clientip' => 'anotherip',
                    ],
                ];

                $signature = stub()->returns(['clientip' => 'ip']);

                $this->middleware = new ValidateSessionMiddleware($signature);

            });

            it('should regenerate the session id', function () {

                $session_id = session_id();

                $this->middleware->process($this->request, $this->handler->get());

                $test = session_id();

                expect($test)->not->toEqual($session_id);

            });

            it('should empty the current session data', function () {

                $this->middleware->process($this->request, $this->handler->get());

                expect($_SESSION)->not->toContainKey('key');

            });

        });

    });

});
