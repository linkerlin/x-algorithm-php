<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Hydrators;

use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\DataStructures\ScoredPostsQuery;

class InNetworkCandidateHydrator implements CandidateHydratorInterface
{
    public function hydrate(ScoredPostsQuery $query, array $candidates): array
    {
        $followedIds = array_flip($query->userFeatures->followingList ?? []);
        $viewerId = $query->userId;

        return array_map(function (PostCandidate $candidate) use ($viewerId, $followedIds) {
            $isSelf = $candidate->authorId === $viewerId;
            $isInNetwork = $isSelf || isset($followedIds[$candidate->authorId]);

            return new PostCandidate([
                'tweet_id' => $candidate->tweetId,
                'author_id' => $candidate->authorId,
                'in_network' => $isInNetwork,
            ]);
        }, $candidates);
    }

    public function getName(): string
    {
        return 'InNetworkCandidateHydrator';
    }
}
