<?php

namespace Cafeteria\CQRS;

interface CommandHandlerInterface
{
    public function handle(CommandInterface $command): void;
}
