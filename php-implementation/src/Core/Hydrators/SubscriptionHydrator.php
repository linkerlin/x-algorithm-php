<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Hydrators;

use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\DataStructures\ScoredPostsQuery;

class SubscriptionHydrator implements CandidateHydratorInterface
{
    public function hydrate(ScoredPostsQuery $query, array $candidates): array
    {
        $followingIds = array_flip($query->userFeatures->followingList ?? []);

        return array_map(function (PostCandidate $candidate) use ($followingIds) {
            $authorId = $candidate->retweetedUserId ?? $candidate->authorId;
            $isSubscribed = isset($followingIds[$authorId]);

            return new PostCandidate([
                'tweet_id' => $candidate->tweetId,
                'author_id' => $candidate->authorId,
                'is_subscribed' => $isSubscribed,
            ]);
        }, $candidates);
    }

    public function getName(): string
    {
        return 'SubscriptionHydrator';
    }
}
