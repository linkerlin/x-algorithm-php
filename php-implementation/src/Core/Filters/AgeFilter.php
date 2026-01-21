<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Filters;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\Pipeline\Interfaces\FilterInterface;
use XAlgorithm\Core\Pipeline\Interfaces\FilterResult;
use XAlgorithm\Utility\RequestIdGenerator;

/**
 * 年龄过滤器
 * 移除超过指定时间的帖子
 */
class AgeFilter implements FilterInterface
{
    private int $maxAgeSeconds;
    private string $name;

    public function __construct(int $maxAgeSeconds = 86400)
    {
        $this->maxAgeSeconds = $maxAgeSeconds;
        $this->name = 'AgeFilter';
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setMaxAge(int $seconds): self
    {
        $this->maxAgeSeconds = $seconds;
        return $this;
    }

    public function filter(ScoredPostsQuery $query, array $candidates): FilterResult
    {
        $kept = [];
        $removed = [];

        foreach ($candidates as $candidate) {
            if ($this->isWithinAge($candidate)) {
                $kept[] = $candidate;
            } else {
                $removed[] = $candidate;
            }
        }

        return new FilterResult($kept, $removed);
    }

    private function isWithinAge(PostCandidate $candidate): bool
    {
        $ageMs = RequestIdGenerator::durationSinceSnowflake($candidate->tweetId);
        
        if ($ageMs === null) {
            return false;
        }

        $ageSeconds = (int)($ageMs / 1000);
        return $ageSeconds <= $this->maxAgeSeconds;
    }
}
