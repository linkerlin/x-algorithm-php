<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Filters;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\Pipeline\Interfaces\FilterInterface;
use XAlgorithm\Core\Pipeline\Interfaces\FilterResult;

/**
 * 对话去重过滤器
 */
class DedupConversationFilter implements FilterInterface
{
    private string $name;
    private array $seenConversations;

    public function __construct()
    {
        $this->name = 'DedupConversationFilter';
        $this->seenConversations = [];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function filter(ScoredPostsQuery $query, array $candidates): FilterResult
    {
        $kept = [];
        $removed = [];

        foreach ($candidates as $candidate) {
            $conversationId = $this->getConversationId($candidate);

            if ($conversationId !== null) {
                if (isset($this->seenConversations[$conversationId])) {
                    $removed[] = $candidate;
                    continue;
                }
                $this->seenConversations[$conversationId] = true;
            }

            $kept[] = $candidate;
        }

        return new FilterResult($kept, $removed);
    }

    private function getConversationId(PostCandidate $candidate): ?int
    {
        if (!empty($candidate->ancestors)) {
            return $candidate->ancestors[0] ?? null;
        }

        return $candidate->inReplyToTweetId;
    }

    public function reset(): void
    {
        $this->seenConversations = [];
    }
}
