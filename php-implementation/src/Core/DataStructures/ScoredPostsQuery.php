<?php

declare(strict_types=1);

namespace XAlgorithm\Core\DataStructures;

use XAlgorithm\Utility\RequestIdGenerator;

/**
 * 用户查询请求
 * 包含用户ID、上下文信息和过滤条件
 */
class ScoredPostsQuery
{
    public int $userId;
    public int $clientAppId;
    public string $countryCode;
    public string $languageCode;
    public array $seenIds;
    public array $servedIds;
    public bool $inNetworkOnly;
    public bool $isBottomRequest;
    public array $bloomFilterEntries;
    public ?UserActionSequence $userActionSequence;
    public UserFeatures $userFeatures;
    public string $requestId;

    public function __construct(array $data = [])
    {
        $this->userId = $data['user_id'] ?? 0;
        $this->clientAppId = $data['client_app_id'] ?? 0;
        $this->countryCode = $data['country_code'] ?? 'US';
        $this->languageCode = $data['language_code'] ?? 'en';
        $this->seenIds = $data['seen_ids'] ?? [];
        $this->servedIds = $data['served_ids'] ?? [];
        $this->inNetworkOnly = $data['in_network_only'] ?? false;
        $this->isBottomRequest = $data['is_bottom_request'] ?? false;
        $this->bloomFilterEntries = $data['bloom_filter_entries'] ?? [];
        $this->userActionSequence = isset($data['user_action_sequence']) 
            ? new UserActionSequence($data['user_action_sequence']) 
            : null;
        $this->userFeatures = isset($data['user_features']) 
            ? new UserFeatures($data['user_features']) 
            : new UserFeatures();
        $this->requestId = $data['request_id'] ?? RequestIdGenerator::generate($this->userId);
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'client_app_id' => $this->clientAppId,
            'country_code' => $this->countryCode,
            'language_code' => $this->languageCode,
            'seen_ids' => $this->seenIds,
            'served_ids' => $this->servedIds,
            'in_network_only' => $this->inNetworkOnly,
            'is_bottom_request' => $this->isBottomRequest,
            'bloom_filter_entries' => $this->bloomFilterEntries,
            'user_action_sequence' => $this->userActionSequence?->toArray(),
            'user_features' => $this->userFeatures->toArray(),
            'request_id' => $this->requestId,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function getSeenServedCombined(): array
    {
        return array_unique(array_merge($this->seenIds, $this->servedIds));
    }

    public function hasSeenTweet(int $tweetId): bool
    {
        return in_array($tweetId, $this->seenIds, true);
    }

    public function hasServedTweet(int $tweetId): bool
    {
        return in_array($tweetId, $this->servedIds, true);
    }

    public function isMutedKeyword(string $text): bool
    {
        foreach ($this->userFeatures->mutedKeywords as $keyword) {
            if (stripos($text, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    public function isBlockedAuthor(int $authorId): bool
    {
        return in_array($authorId, $this->userFeatures->blockedAuthors, true);
    }

    public function isFollowingAuthor(int $authorId): bool
    {
        return in_array($authorId, $this->userFeatures->followedAuthors, true);
    }
}
