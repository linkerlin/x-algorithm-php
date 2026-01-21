<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Filters;

use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\Pipeline\Interfaces\FilterResult;

class DedupConversationFilter implements \XAlgorithm\Core\Pipeline\Interfaces\FilterInterface
{
    public function getName(): string
    {
        return 'DedupConversationFilter';
    }

    public function filter(ScoredPostsQuery $query, array $candidates): FilterResult
    {
        $kept = [];
        $removed = [];
        $bestPerConvo = [];

        foreach ($candidates as $candidate) {
            $conversationId = $this->getConversationId($candidate);
            $score = $candidate->score ?? 0.0;

            if (isset($bestPerConvo[$conversationId])) {
                [$keptIdx, $bestScore] = $bestPerConvo[$conversationId];
                if ($score > $bestScore) {
                    $previous = $kept[$keptIdx];
                    $kept[$keptIdx] = $candidate;
                    $removed[] = $previous;
                    $bestPerConvo[$conversationId] = [$keptIdx, $score];
                } else {
                    $removed[] = $candidate;
                }
            } else {
                $idx = count($kept);
                $kept[] = $candidate;
                $bestPerConvo[$conversationId] = [$idx, $score];
            }
        }

        return new FilterResult($kept, $removed);
    }

    private function getConversationId(PostCandidate $candidate): int
    {
        if ($candidate->inReplyToTweetId !== null) {
            return (int) $candidate->inReplyToTweetId;
        }
        
        $ancestors = $candidate->ancestors ?? [];
        if (!empty($ancestors)) {
            return min($ancestors);
        }
        return (int) $candidate->tweetId;
    }
}
