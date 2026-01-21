<?php

declare(strict_types=1);

namespace XAlgorithm\Core\QueryHydrators;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\UserActionSequence;
use XAlgorithm\Core\DataStructures\UserActionItem;

class UserActionSeqQueryHydrator implements QueryHydratorInterface
{
    public function hydrate(ScoredPostsQuery $query): ScoredPostsQuery
    {
        $userId = $query->userId;
        
        $actionSequence = $this->fetchUserActionSequence($userId);

        return new ScoredPostsQuery([
            'user_id' => $query->userId,
            'client_app_id' => $query->clientAppId,
            'country_code' => $query->countryCode,
            'language_code' => $query->languageCode,
            'user_action_sequence' => $actionSequence,
            'request_id' => $query->requestId,
        ]);
    }

    private function fetchUserActionSequence(int $userId): UserActionSequence
    {
        $actions = $this->fetchRecentActions($userId);

        return new UserActionSequence([
            'user_id' => $userId,
            'actions' => $actions,
            'created_at_ms' => (int)(microtime(true) * 1000),
        ]);
    }

    private function fetchRecentActions(int $userId): array
    {
        return [];
    }

    public function getName(): string
    {
        return 'UserActionSeqQueryHydrator';
    }
}
