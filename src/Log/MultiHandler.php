<?php


namespace W3\Log;


use Monolog\Handler\HandlerInterface;

class MultiHandler implements HandlerInterface
{
    protected
        /** @var HandlerInterface[] */
        $handlers = [];

    /**
     * MultiHandler constructor.
     * @param array $handlers
     */
    public function __construct(array $handlers)
    {
        $this->addHandlers($handlers);
    }

    /**
     * @param HandlerInterface $handler
     */
    public function addHandler(HandlerInterface $handler)
    {
        $this->handlers[] = $handler;
    }

    /**
     * @param HandlerInterface[] $handlers
     */
    public function addHandlers(array $handlers)
    {
        foreach($handlers as $handler) {
            $this->addHandler($handler);
        }
    }

    /**
     * @inheritDoc
     * @param array $record
     * @return bool
     */
    public function isHandling(array $record): bool
    {

        return true;
    }

    /**
     * @inheritDoc
     * @param array $record
     * @return bool
     */
    public function handle(array $record): bool
    {
        /** @var HandlerInterface $handler */
        foreach($this->handlers as $handler) {
            $handler->handle($record);
        }

        return false;
    }

    /**
     * @inheritDoc
     * @param array $records
     */
    public function handleBatch(array $records): void
    {
        /** @var HandlerInterface $handler */
        foreach ($this->handlers as $handler) {
            $handler->handleBatch($records);
        }
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        /** @var HandlerInterface $handler */
        foreach ($this->handlers as $handler) {
            $handler->close();
        }
    }
}