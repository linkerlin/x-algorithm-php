<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Pipeline;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\Pipeline\Interfaces\FilterInterface;
use XAlgorithm\Core\Pipeline\Interfaces\ScorerInterface;
use XAlgorithm\Core\Pipeline\Interfaces\SelectorInterface;
use XAlgorithm\Core\Pipeline\Interfaces\HydratorInterface;
use XAlgorithm\Core\Pipeline\Interfaces\SourceInterface;
use XAlgorithm\Core\Pipeline\Interfaces\SideEffectInterface;

/**
 * 候选管道
 * 协调来源、过滤、评分和选择过程
 */
class CandidatePipeline
{
    private string $name;
    private array $sources;
    private array $hydrators;
    private array $filters;
    private array $scorers;
    private ?SelectorInterface $selector;
    private array $postSelectionHydrators;
    private array $postSelectionFilters;
    private array $sideEffects;

    public function __construct(string $name = 'CandidatePipeline')
    {
        $this->name = $name;
        $this->sources = [];
        $this->hydrators = [];
        $this->filters = [];
        $this->scorers = [];
        $this->selector = null;
        $this->postSelectionHydrators = [];
        $this->postSelectionFilters = [];
        $this->sideEffects = [];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function addSource(SourceInterface $source): self
    {
        $this->sources[] = $source;
        return $this;
    }

    public function addHydrator(HydratorInterface $hydrator): self
    {
        $this->hydrators[] = $hydrator;
        return $this;
    }

    public function addFilter(FilterInterface $filter): self
    {
        $this->filters[] = $filter;
        return $this;
    }

    public function addScorer(ScorerInterface $scorer): self
    {
        $this->scorers[] = $scorer;
        return $this;
    }

    public function setSelector(SelectorInterface $selector): self
    {
        $this->selector = $selector;
        return $this;
    }

    public function addPostSelectionHydrator(HydratorInterface $hydrator): self
    {
        $this->postSelectionHydrators[] = $hydrator;
        return $this;
    }

    public function addPostSelectionFilter(FilterInterface $filter): self
    {
        $this->postSelectionFilters[] = $filter;
        return $this;
    }

    public function addSideEffect(SideEffectInterface $sideEffect): self
    {
        $this->sideEffects[] = $sideEffect;
        return $this;
    }

    public function execute(ScoredPostsQuery $query, int $limit = 50): array
    {
        $candidates = $this->fetchFromSources($query);
        $candidates = $this->applyHydrators($query, $candidates);
        $candidates = $this->applyFilters($query, $candidates);
        $candidates = $this->applyScorers($query, $candidates);
        $candidates = $this->selectTopK($query, $candidates, $limit);
        $candidates = $this->applyPostSelectionHydrators($query, $candidates);
        $candidates = $this->applyPostSelectionFilters($query, $candidates);
        $this->executeSideEffects($query, $candidates);

        $stats = $this->getStats();

        return [
            'candidates' => $candidates,
            'request_id' => $query->requestId,
            'count' => count($candidates),
            'pipeline_stats' => $stats,
        ];
    }

    private function fetchFromSources(ScoredPostsQuery $query): array
    {
        $allCandidates = [];

        foreach ($this->sources as $source) {
            $result = $source->fetch($query);
            if (isset($result['candidates']) && is_array($result['candidates'])) {
                $allCandidates = array_merge($allCandidates, $result['candidates']);
            }
        }

        return $allCandidates;
    }

    private function applyHydrators(ScoredPostsQuery $query, array $candidates): array
    {
        foreach ($this->hydrators as $hydrator) {
            $candidates = $hydrator->hydrate($query, $candidates);
        }
        return $candidates;
    }

    private function applyFilters(ScoredPostsQuery $query, array $candidates): array
    {
        foreach ($this->filters as $filter) {
            $result = $filter->filter($query, $candidates);
            $candidates = $result->kept;

            if (empty($candidates)) {
                break;
            }
        }
        return $candidates;
    }

    private function applyScorers(ScoredPostsQuery $query, array $candidates): array
    {
        foreach ($this->scorers as $scorer) {
            $candidates = $scorer->score($query, $candidates);
        }
        return $candidates;
    }

    private function selectTopK(ScoredPostsQuery $query, array $candidates, int $limit): array
    {
        if ($this->selector === null) {
            usort($candidates, fn($a, $b) => ($b->weightedScore ?? 0) <=> ($a->weightedScore ?? 0));
            return array_slice($candidates, 0, $limit);
        }

        return $this->selector->select($query, $candidates, $limit);
    }

    private function applyPostSelectionHydrators(ScoredPostsQuery $query, array $candidates): array
    {
        foreach ($this->postSelectionHydrators as $hydrator) {
            $candidates = $hydrator->hydrate($query, $candidates);
        }
        return $candidates;
    }

    private function applyPostSelectionFilters(ScoredPostsQuery $query, array $candidates): array
    {
        foreach ($this->postSelectionFilters as $filter) {
            $result = $filter->filter($query, $candidates);
            $candidates = $result->kept;
        }
        return $candidates;
    }

    private function executeSideEffects(ScoredPostsQuery $query, array $candidates): void
    {
        foreach ($this->sideEffects as $sideEffect) {
            $sideEffect->execute($query, $candidates);
        }
    }

    public function getStats(): array
    {
        return [
            'sources' => count($this->sources),
            'hydrators' => count($this->hydrators),
            'filters' => count($this->filters),
            'scorers' => count($this->scorers),
            'selector' => $this->selector !== null,
        ];
    }
}
