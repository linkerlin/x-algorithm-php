<?php

declare(strict_types=1);

namespace XAlgorithm\Core\DataStructures;

/**
 * 用户特征数据
 */
class UserFeatures
{
    public array $followingList = [];
    public array $preferences = [];
    public array $mutedKeywords = [];
    public array $blockedAuthors = [];
    public array $followedAuthors = [];
    public int $accountAgeDays = 0;
    public int $followerCount = 0;
    public int $followingCount = 0;
    public int $tweetCount = 0;
    public bool $isVerified = false;
    public ?string $languageCode = null;
    public ?string $countryCode = null;

    public function __construct(array $data = [])
    {
        $this->followingList = $data['following_list'] ?? [];
        $this->preferences = $data['preferences'] ?? [];
        $this->mutedKeywords = $data['muted_keywords'] ?? [];
        $this->blockedAuthors = $data['blocked_authors'] ?? [];
        $this->followedAuthors = $data['followed_authors'] ?? [];
        $this->accountAgeDays = $data['account_age_days'] ?? 0;
        $this->followerCount = $data['follower_count'] ?? 0;
        $this->followingCount = $data['following_count'] ?? 0;
        $this->tweetCount = $data['tweet_count'] ?? 0;
        $this->isVerified = $data['is_verified'] ?? false;
        $this->languageCode = $data['language_code'] ?? null;
        $this->countryCode = $data['country_code'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'following_list' => $this->followingList,
            'preferences' => $this->preferences,
            'muted_keywords' => $this->mutedKeywords,
            'blocked_authors' => $this->blockedAuthors,
            'followed_authors' => $this->followedAuthors,
            'account_age_days' => $this->accountAgeDays,
            'follower_count' => $this->followerCount,
            'following_count' => $this->followingCount,
            'tweet_count' => $this->tweetCount,
            'is_verified' => $this->isVerified,
            'language_code' => $this->languageCode,
            'country_code' => $this->countryCode,
        ];
    }
}

/**
 * 用户行为序列项
 */
class UserActionItem
{
    public int $tweetId;
    public int $authorId;
    public int $actionType;
    public int $timestampMs;
    public ?int $productSurface = null;

    public function __construct(array $data = [])
    {
        $this->tweetId = $data['tweet_id'] ?? 0;
        $this->authorId = $data['author_id'] ?? 0;
        $this->actionType = $data['action_type'] ?? 0;
        $this->timestampMs = $data['timestamp_ms'] ?? 0;
        $this->productSurface = $data['product_surface'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'tweet_id' => $this->tweetId,
            'author_id' => $this->authorId,
            'action_type' => $this->actionType,
            'timestamp_ms' => $this->timestampMs,
            'product_surface' => $this->productSurface,
        ];
    }
}

/**
 * 用户行为序列
 */
class UserActionSequence
{
    public int $userId;
    public array $actions;
    public int $createdAtMs;

    public function __construct(array $data = [])
    {
        $this->userId = $data['user_id'] ?? 0;
        $this->createdAtMs = $data['created_at_ms'] ?? 0;
        
        $this->actions = [];
        foreach ($data['actions'] ?? [] as $action) {
            $this->actions[] = new UserActionItem($action);
        }
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'actions' => array_map(fn($a) => $a->toArray(), $this->actions),
            'created_at_ms' => $this->createdAtMs,
        ];
    }

    public function getRecentActions(int $limit = 32): array
    {
        $actions = $this->actions;
        usort($actions, fn($a, $b) => $b->timestampMs - $a->timestampMs);
        
        return array_slice($actions, 0, $limit);
    }

    public function getActionsByType(int $actionType): array
    {
        return array_filter($this->actions, fn($a) => $a->actionType === $actionType);
    }
}
