<?php

namespace JSHayes\FakeRequests;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Promise\Create;
use Illuminate\Support\Collection;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Promise\PromiseInterface;
use JSHayes\FakeRequests\Exceptions\UnhandledRequestException;

class MockHandler
{
    private $handlers;
    private $allowsUnexpected = false;
    private $requestClass;

    public function __construct()
    {
        $this->handlers = collect();
    }

    /**
     * Add a request handler for the given http method and uri
     *
     * @param string $method
     * @param string $uri
     * @return \JSHayes\FakeRequests\RequestHandler
     */
    public function expects(string $method, string $uri): RequestHandler
    {
        $handler = new RequestHandler($method, $uri);
        if ($this->requestClass) {
            $handler->extendRequest($this->requestClass);
        }
        return $this->handlers->push($handler)->last();
    }

    /**
     * Add a request handler for the get request to the given uri
     *
     * @param string $uri
     * @return \JSHayes\FakeRequests\RequestHandler
     */
    public function get(string $uri): RequestHandler
    {
        return $this->expects('GET', $uri);
    }

    /**
     * Add a request handler for the post request to the given uri
     *
     * @param string $uri
     * @return \JSHayes\FakeRequests\RequestHandler
     */
    public function post(string $uri): RequestHandler
    {
        return $this->expects('POST', $uri);
    }

    /**
     * Add a request handler for the put request to the given uri
     *
     * @param string $uri
     * @return \JSHayes\FakeRequests\RequestHandler
     */
    public function put(string $uri): RequestHandler
    {
        return $this->expects('PUT', $uri);
    }

    /**
     * Add a request handler for the patch request to the given uri
     *
     * @param string $uri
     * @return \JSHayes\FakeRequests\RequestHandler
     */
    public function patch(string $uri): RequestHandler
    {
        return $this->expects('PATCH', $uri);
    }

    /**
     * Add a request handler for the delete request to the given uri
     *
     * @param string $uri
     * @return \JSHayes\FakeRequests\RequestHandler
     */
    public function delete(string $uri): RequestHandler
    {
        return $this->expects('DELETE', $uri);
    }

    /**
     * Add a request handler for the head request to the given uri
     *
     * @param string $uri
     * @return \JSHayes\FakeRequests\RequestHandler
     */
    public function head(string $uri): RequestHandler
    {
        return $this->expects('HEAD', $uri);
    }

    /**
     * Add a request handler for the options request to the given uri
     *
     * @param string $uri
     * @return \JSHayes\FakeRequests\RequestHandler
     */
    public function options(string $uri): RequestHandler
    {
        return $this->expects('OPTIONS', $uri);
    }

    /**
     * Determine if there are not more handlers registered
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->handlers->flatten()->isEmpty();
    }

    /**
     * Get the request handlers currently registered with this handler
     *
     * @return \Illuminate\Support\Collection
     */
    public function getHandlers(): Collection
    {
        return $this->handlers;
    }

    /**
     * Allows unexpected calls to this handler. When this is set, any call that
     * does not have an expectation define will return a generic response.
     *
     * @return \JSHayes\FakeRequests\MockHandler
     */
    public function allowUnexpectedCalls(): MockHandler
    {
        $this->allowsUnexpected = true;
        return $this;
    }

    /**
     * Specify a request class to use to decorate the request that gets handled
     * by each request handler. This class must extend \JSHayes\FakeRequests\Request
     *
     * @param string $class
     * @return \JSHayes\FakeRequests\MockHandler
     */
    public function extendRequest(string $class): MockHandler
    {
        $this->requestClass = $class;
        return $this;
    }

    /**
     * Find the first request handler that matches the method and path of the
     * given request and execute it
     *
     * @param \Psr\Http\Message\RequestInterface $request
     * @param array $options
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function __invoke(RequestInterface $request, array $options): PromiseInterface
    {
        foreach ($this->handlers as $key => $handler) {
            if ($handler->shouldHandle($request, $options)) {
                $this->handlers->pull($key);
                return Create::promiseFor($handler->handle($request, $options));
            }
        }

        if ($this->allowsUnexpected) {
            return Create::promiseFor(new Response());
        }

        throw new UnhandledRequestException($request);
    }
}
