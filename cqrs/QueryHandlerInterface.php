<?php

namespace Cafeteria\CQRS;

interface QueryHandlerInterface
{
    public function handle(QueryInterface $query): mixed;
}
