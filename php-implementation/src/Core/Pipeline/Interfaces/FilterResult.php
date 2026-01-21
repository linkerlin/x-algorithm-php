<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Pipeline\Interfaces;

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
            'kept_count' => count($this->kept),
            'removed_count' => count($this->removed),
        ];
    }

    public static function fromArrays(array $kept, array $removed): self
    {
        return new self($kept, $removed);
    }

    public function getKeptCount(): int
    {
        return count($this->kept);
    }

    public function getRemovedCount(): int
    {
        return count($this->removed);
    }
}
