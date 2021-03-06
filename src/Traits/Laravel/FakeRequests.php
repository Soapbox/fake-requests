<?php

namespace JSHayes\FakeRequests\Traits\Laravel;

use JSHayes\FakeRequests\MockHandler;
use JSHayes\FakeRequests\ClientFactory;

trait FakeRequests
{
    protected $mockHandler;

    protected function fakeRequests(): MockHandler
    {
        $factory = new ClientFactory();
        $factory->setHandler($this->mockHandler = new MockHandler());
        app()->instance(ClientFactory::class, $factory);
        return $this->mockHandler;
    }

    /**
     * @after
     */
    protected function checkHandler()
    {
        if (!is_null($this->mockHandler) && !$this->mockHandler->isEmpty()) {
            $handler = $this->mockHandler->getHandlers()->first();
            $this->fail("A {$handler->getMethod()} request to \"{$handler->getUri()}\" was expected.");
        }
    }
}
