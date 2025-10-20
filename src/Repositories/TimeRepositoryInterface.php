<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Repositories;

use Tkachikov\Chronos\Decorators\TimeDecorator;

interface TimeRepositoryInterface
{
    /**
     * @return array<int, TimeDecorator>
     */
    public function get(): array;
}