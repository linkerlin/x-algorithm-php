<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Hydrators;

use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\DataStructures\ScoredPostsQuery;

class VFCandidateHydrator implements CandidateHydratorInterface
{
    public function hydrate(ScoredPostsQuery $query, array $candidates): array
    {
        return array_map(function (PostCandidate $candidate) {
            $vfStatus = $candidate->vfStatus ?? '';
            $isSafe = empty($vfStatus);

            return new PostCandidate([
                'tweet_id' => $candidate->tweetId,
                'author_id' => $candidate->authorId,
                'vf_status' => $vfStatus,
                'is_vf_safe' => $isSafe,
            ]);
        }, $candidates);
    }

    public function getName(): string
    {
        return 'VFCandidateHydrator';
    }
}
