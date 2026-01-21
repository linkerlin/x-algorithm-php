<?php

declare(strict_types=1);

namespace XAlgorithm\Core\DataStructures;

class UserActionItem
{
    public int $tweetId;
    public int $authorId;
    public int $actionType;
    public int $timestampMs;
    public int $productSurface;

    public function __construct(array $data = [])
    {
        $this->tweetId = $data['tweet_id'] ?? 0;
        $this->authorId = $data['author_id'] ?? 0;
        $this->actionType = $data['action_type'] ?? 0;
        $this->timestampMs = $data['timestamp_ms'] ?? 0;
        $this->productSurface = $data['product_surface'] ?? 0;
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

    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}
