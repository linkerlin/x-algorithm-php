<?php

declare(strict_types=1);

namespace XAlgorithm\Core\SideEffects;

use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\DataStructures\ScoredPostsQuery;

interface SideEffectInterface
{
    public function execute(ScoredPostsQuery $query, array $candidates): void;
    public function getName(): string;
}

class CacheRequestInfoSideEffect implements SideEffectInterface
{
    private ?string $cacheClient = null;

    public function execute(ScoredPostsQuery $query, array $candidates): void
    {
        $requestInfo = [
            'request_id' => $query->requestId,
            'user_id' => $query->userId,
            'candidate_count' => count($candidates),
            'timestamp_ms' => (int)(microtime(true) * 1000),
        ];

        $this->cacheRequestInfo($query->requestId, $requestInfo);
    }

    private function cacheRequestInfo(string $requestId, array $info): void
    {
    }

    public function getName(): string
    {
        return 'CacheRequestInfoSideEffect';
    }
}
