<?php

use function Eloquent\Phony\Kahlan\mock;
use function Eloquent\Phony\Kahlan\stub;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

use Interop\Http\Server\MiddlewareInterface;
use Interop\Http\Server\RequestHandlerInterface;

use Ellipse\Session\ValidateSessionMiddleware;

describe('ValidateSessionMiddleware', function () {

    beforeEach(function () {

        $signature = stub()->returns([]);

        $this->middleware = new ValidateSessionMiddleware($signature);

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

            $test = $this->middleware->process($this->request, $this->handler->get());

            expect($test)->toBe($this->response);

        });

        it('should put the array returned by the signature method to the session array metadata', function () {

            $data = ['key' => 'value'];

            $signature = stub()->returns($data);

            $middleware = new ValidateSessionMiddleware($signature);

            $middleware->process($this->request, $this->handler->get());

            expect($_SESSION[ValidateSessionMiddleware::METADATA_KEY])->toEqual($data);

        });

    });

});
