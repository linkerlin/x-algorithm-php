<?php

declare(strict_types=1);

namespace XAlgorithm\Core\DataStructures;

class QueryFeatures
{
    public int $userId;
    public string $countryCode;
    public string $languageCode;
    public array $followingList;
    public array $mutedKeywords;
    public array $blockedAuthors;
    public int $accountAgeDays;
    public bool $isVerified;
    public array $recentActions;

    public function __construct(array $data = [])
    {
        $this->userId = $data['user_id'] ?? 0;
        $this->countryCode = $data['country_code'] ?? 'US';
        $this->languageCode = $data['language_code'] ?? 'en';
        $this->followingList = $data['following_list'] ?? [];
        $this->mutedKeywords = $data['muted_keywords'] ?? [];
        $this->blockedAuthors = $data['blocked_authors'] ?? [];
        $this->accountAgeDays = $data['account_age_days'] ?? 0;
        $this->isVerified = $data['is_verified'] ?? false;
        $this->recentActions = $data['recent_actions'] ?? [];
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'country_code' => $this->countryCode,
            'language_code' => $this->languageCode,
            'following_list' => $this->followingList,
            'muted_keywords' => $this->mutedKeywords,
            'blocked_authors' => $this->blockedAuthors,
            'account_age_days' => $this->accountAgeDays,
            'is_verified' => $this->isVerified,
            'recent_actions' => $this->recentActions,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}
