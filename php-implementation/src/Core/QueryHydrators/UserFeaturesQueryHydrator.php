<?php

declare(strict_types=1);

namespace XAlgorithm\Core\QueryHydrators;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\UserFeatures;

class UserFeaturesQueryHydrator implements QueryHydratorInterface
{
    public function hydrate(ScoredPostsQuery $query): ScoredPostsQuery
    {
        $userId = $query->userId;
        
        $userFeatures = $this->fetchUserFeatures($userId);

        return new ScoredPostsQuery([
            'user_id' => $query->userId,
            'client_app_id' => $query->clientAppId,
            'country_code' => $query->countryCode,
            'language_code' => $query->languageCode,
            'user_features' => $userFeatures,
            'request_id' => $query->requestId,
        ]);
    }

    private function fetchUserFeatures(int $userId): UserFeatures
    {
        return new UserFeatures([
            'following_list' => $this->fetchFollowingList($userId),
            'muted_keywords' => $this->fetchMutedKeywords($userId),
            'blocked_authors' => $this->fetchBlockedAuthors($userId),
        ]);
    }

    private function fetchFollowingList(int $userId): array
    {
        return [];
    }

    private function fetchMutedKeywords(int $userId): array
    {
        return [];
    }

    private function fetchBlockedAuthors(int $userId): array
    {
        return [];
    }

    public function getName(): string
    {
        return 'UserFeaturesQueryHydrator';
    }
}
