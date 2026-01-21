<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Filters;

use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\Pipeline\Interfaces\FilterResult;

class RetweetDeduplicationFilter implements \XAlgorithm\Core\Pipeline\Interfaces\FilterInterface
{
    public function getName(): string
    {
        return 'RetweetDeduplicationFilter';
    }

    public function filter(ScoredPostsQuery $query, array $candidates): FilterResult
    {
        $seenTweetIds = [];
        $kept = [];
        $removed = [];

        foreach ($candidates as $candidate) {
            $retweetedId = $candidate->retweetedTweetId;

            if ($retweetedId !== null) {
                $id = (int) $retweetedId;
                if (!in_array($id, $seenTweetIds)) {
                    $seenTweetIds[] = $id;
                    $kept[] = $candidate;
                } else {
                    $removed[] = $candidate;
                }
            } else {
                $seenTweetIds[] = (int) $candidate->tweetId;
                $kept[] = $candidate;
            }
        }

        return new FilterResult($kept, $removed);
    }
}
