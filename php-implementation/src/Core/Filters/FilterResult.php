<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Filters;

class FilterResult
{
    public array $kept;
    public array $removed;

    public function __construct(array $kept = [], array $removed = [])
    {
        $this->kept = $kept;
        $this->removed = $removed;
    }

    public function toArray(): array
    {
        return [
            'kept' => array_map(fn($c) => $c->toArray(), $this->kept),
            'removed' => array_map(fn($c) => $c->toArray(), $this->removed),
        ];
    }
}
