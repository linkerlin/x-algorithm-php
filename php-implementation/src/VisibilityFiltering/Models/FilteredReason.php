<?php

declare(strict_types=1);

namespace XAlgorithm\VisibilityFiltering\Models;

/**
 * 内容过滤原因枚举
 */
class FilteredReason
{
    public const NONE = 0;
    public const NOT_FILTERED = 1;
    public const AGE = 2;
    public const DUPLICATE = 3;
    public const SELF_TWEET = 4;
    public const BLOCKED_AUTHOR = 5;
    public const MUTED_KEYWORD = 6;
    public const PREVIOUSLY_SEEN = 7;
    public const PREVIOUSLY_SERVED = 8;
    public const INELIGIBLE_SUBSCRIPTION = 9;
    public const RETWEET_DEDUPLICATION = 10;
    public const VISIBILITY_FILTERING = 11;
    public const CONVERSATION_DEDUP = 12;
    public const AUTHOR_SOCIALGRAPH = 13;

    private int $value;
    private ?string $detail;

    public function __construct(int $value, ?string $detail = null)
    {
        $this->value = $value;
        $this->detail = $detail;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function getDetail(): ?string
    {
        return $this->detail;
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'detail' => $this->detail,
        ];
    }

    public static function none(): self
    {
        return new self(self::NOT_FILTERED);
    }

    public static function age(int $maxAgeDays): self
    {
        return new self(self::AGE, "Older than {$maxAgeDays} days");
    }

    public static function duplicate(): self
    {
        return new self(self::DUPLICATE, "Duplicate content");
    }

    public static function selfTweet(): self
    {
        return new self(self::SELF_TWEET, "Self tweet");
    }

    public static function blockedAuthor(): self
    {
        return new self(self::BLOCKED_AUTHOR, "Blocked author");
    }

    public static function mutedKeyword(string $keyword): self
    {
        return new self(self::MUTED_KEYWORD, "Muted keyword: {$keyword}");
    }

    public static function previouslySeen(): self
    {
        return new self(self::PREVIOUSLY_SEEN, "Previously seen");
    }

    public static function previouslyServed(): self
    {
        return new self(self::PREVIOUSLY_SERVED, "Previously served");
    }

    public static function ineligibleSubscription(): self
    {
        return new self(self::INELIGIBLE_SUBSCRIPTION, "Ineligible subscription");
    }

    public static function visibilityFiltering(string $reason): self
    {
        return new self(self::VISIBILITY_FILTERING, $reason);
    }
}
