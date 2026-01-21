<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Hydrators;

use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\DataStructures\ScoredPostsQuery;

class GizmoduckCandidateHydrator implements CandidateHydratorInterface
{
    private ?object $gizmoduckClient = null;

    public function setGizmoduckClient(object $client): void
    {
        $this->gizmoduckClient = $client;
    }

    public function hydrate(ScoredPostsQuery $query, array $candidates): array
    {
        if (empty($candidates)) {
            return [];
        }

        $authorIds = array_unique(array_map(fn($c) => $c->authorId, $candidates));
        $retweetedUserIds = array_unique(array_filter(array_map(fn($c) => $c->retweetedUserId, $candidates)));

        $userData = $this->fetchUserData(array_merge($authorIds, $retweetedUserIds));

        return array_map(function (PostCandidate $candidate) use ($userData) {
            $authorData = $userData[$candidate->authorId] ?? null;
            $retweetAuthorData = $candidate->retweetedUserId !== null 
                ? ($userData[$candidate->retweetedUserId] ?? null) 
                : null;

            return new PostCandidate([
                'tweet_id' => $candidate->tweetId,
                'author_id' => $candidate->authorId,
                'author_followers_count' => $authorData['followers_count'] ?? null,
                'author_screen_name' => $authorData['screen_name'] ?? null,
                'retweeted_screen_name' => $retweetAuthorData['screen_name'] ?? null,
            ]);
        }, $candidates);
    }

    private function fetchUserData(array $userIds): array
    {
        return [];
    }

    public function getName(): string
    {
        return 'GizmoduckCandidateHydrator';
    }
}
