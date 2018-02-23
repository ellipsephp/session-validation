<?php

use function Eloquent\Phony\Kahlan\mock;
use function Eloquent\Phony\Kahlan\stub;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Ellipse\Session\ValidateSessionMiddleware;
use Ellipse\Session\Exceptions\OwnershipSignatureTypeException;

describe('ValidateSessionMiddleware', function () {

    beforeEach(function () {

        $_SESSION = [];

        $this->signature = stub();

        $this->middleware = new ValidateSessionMiddleware($this->signature);

    });

    it('should implement MiddlewareInterface', function () {

        expect($this->middleware)->toBeAnInstanceOf(MiddlewareInterface::class);

    });

    describe('->process()', function () {

        beforeEach(function () {

            $this->request = mock(ServerRequestInterface::class)->get();
            $this->response = mock(ResponseInterface::class)->get();

            $this->handler = mock(RequestHandlerInterface::class);

            $this->handler->handle->returns($this->response);

        });

        it('should proxy the request handler ->handle() method', function () {

            $this->signature->with($this->request)->returns([]);

            $test = $this->middleware->process($this->request, $this->handler->get());

            expect($test)->toBe($this->response);

        });

        it('should add the produced signature values to the session metadata', function () {

            $this->signature->with($this->request)->returns(['clientip' => 'ip']);

            $this->middleware->process($this->request, $this->handler->get());

            expect($_SESSION)->toEqual([
                ValidateSessionMiddleware::METADATA_KEY => [
                    'clientip' => 'ip',
                ],
            ]);

        });

        context('when the produced signature is not an array', function () {

            it('should throw an OwnershipSignatureNotValidException', function () {

                $this->signature->with($this->request)->returns('signature');

                $test = function () {

                    $this->middleware->process($this->request, $this->handler->get());

                };

                $exception = new OwnershipSignatureTypeException('signature');

                expect($test)->toThrow($exception);

            });

        });

        context('when the produced signature is an empty array', function () {

            it('should not update the session data', function () {

                $_SESSION = ['key' => 'value'];

                $this->signature->with($this->request)->returns([]);

                $this->middleware->process($this->request, $this->handler->get());

                expect($_SESSION)->toContainKey('key');
                expect($_SESSION['key'])->toEqual('value');

            });

        });

        context('when the session does not contain the signature array keys', function () {

            it('should not update the session data', function () {

                $_SESSION = [
                    'key' => 'value',
                    ValidateSessionMiddleware::METADATA_KEY => [
                        'useragent' => 'browser',
                    ],
                ];

                $this->signature->with($this->request)->returns(['clientip' => 'ip']);

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

                $this->signature->with($this->request)->returns(['clientip' => 'ip']);

                $this->regenerate = false;

                allow('session_regenerate_id')->toBeCalled()->andRun(function () {

                    $this->regenerate = true;

                });

            });

            it('should unset and regenerate the session id', function () {

                $this->middleware->process($this->request, $this->handler->get());

                expect($this->regenerate)->toBeTruthy();

            });

            it('should empty the current session data', function () {

                $this->middleware->process($this->request, $this->handler->get());

                expect($_SESSION)->not->toContainKey('key');

            });

        });

    });

});
