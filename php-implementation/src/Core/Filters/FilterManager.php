<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Filters;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\Pipeline\Interfaces\FilterInterface;
use XAlgorithm\Core\Pipeline\Interfaces\FilterResult;

/**
 * 过滤器管理器
 * 统一管理和执行多个过滤器
 */
class FilterManager implements FilterInterface
{
    private string $name;
    private array $filters;

    public function __construct()
    {
        $this->name = 'FilterManager';
        $this->filters = [];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function addFilter(FilterInterface $filter): self
    {
        $this->filters[] = $filter;
        return $this;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function filter(ScoredPostsQuery $query, array $candidates): FilterResult
    {
        $currentCandidates = $candidates;

        foreach ($this->filters as $filter) {
            $result = $filter->filter($query, $currentCandidates);
            $currentCandidates = $result->kept;

            if (empty($currentCandidates)) {
                break;
            }
        }

        return new FilterResult($currentCandidates, []);
    }

    public function removeFilter(string $filterName): self
    {
        $this->filters = array_filter($this->filters, fn($f) => $f->getName() !== $filterName);
        return $this;
    }

    public function hasFilter(string $filterName): bool
    {
        foreach ($this->filters as $filter) {
            if ($filter->getName() === $filterName) {
                return true;
            }
        }
        return false;
    }
}
