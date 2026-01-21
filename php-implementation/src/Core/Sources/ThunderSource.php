<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Sources;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\Pipeline\Interfaces\SourceInterface;
use XAlgorithm\ML\Phoenix\PhoenixClientInterface;

class ThunderSource implements SourceInterface
{
    private string $name;
    private array $thunderClient;
    private int $defaultLimit;

    public function __construct(?array $thunderClient = null, int $defaultLimit = 100)
    {
        $this->name = 'ThunderSource';
        $this->thunderClient = $thunderClient ?? [];
        $this->defaultLimit = $defaultLimit;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function fetch(ScoredPostsQuery $query): array
    {
        $candidates = [];
        
        if ($query->inNetworkOnly) {
            $candidates = $this->fetchInNetworkCandidates($query);
        } else {
            $candidates = array_merge(
                $this->fetchInNetworkCandidates($query),
                []
            );
        }

        return [
            'candidates' => $candidates,
            'source' => $this->name,
        ];
    }

    private function fetchInNetworkCandidates(ScoredPostsQuery $query): array
    {
        return [];
    }

    public function setDefaultLimit(int $limit): self
    {
        $this->defaultLimit = $limit;
        return $this;
    }
}
