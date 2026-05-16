<?php

namespace Cafeteria\CQRS\Bus;

use Cafeteria\CQRS\CommandInterface;
use Cafeteria\CQRS\CommandHandlerInterface;

/**
 * CommandBus: recebe um Command e delega para o Handler correto.
 * Isola completamente os arquivos PHP de conhecer a lógica de negócio.
 */
class CommandBus
{
    /** @var array<string, CommandHandlerInterface> */
    private array $handlers = [];

    public function register(string $commandClass, CommandHandlerInterface $handler): void
    {
        $this->handlers[$commandClass] = $handler;
    }

    public function dispatch(CommandInterface $command): void
    {
        $class = get_class($command);

        if (!isset($this->handlers[$class])) {
            throw new \RuntimeException("Nenhum handler registrado para o command: $class");
        }

        $this->handlers[$class]->handle($command);
    }
}
