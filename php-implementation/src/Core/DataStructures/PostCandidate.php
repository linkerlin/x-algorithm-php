<?php

declare(strict_types=1);

namespace XAlgorithm\Core\DataStructures;

use XAlgorithm\VisibilityFiltering\Models\FilteredReason;

/**
 * 帖子候选对象
 * 代表信息流中的一个候选帖子
 */
class PostCandidate
{
    public int $tweetId;
    public int $authorId;
    public string $tweetText;
    public ?int $inReplyToTweetId = null;
    public ?int $retweetedTweetId = null;
    public ?int $retweetedUserId = null;
    public PhoenixScores $phoenixScores;
    public ?int $predictionRequestId = null;
    public ?int $lastScoredAtMs = null;
    public ?float $weightedScore = null;
    public ?float $score = null;
    public ?int $servedType = null;
    public ?bool $inNetwork = null;
    public array $ancestors = [];
    public ?int $videoDurationMs = null;
    public ?int $authorFollowersCount = null;
    public ?string $authorScreenName = null;
    public ?string $retweetedScreenName = null;
    public ?FilteredReason $visibilityReason = null;
    public ?int $subscriptionAuthorId = null;

    public function __construct(array $data = [])
    {
        $this->tweetId = $data['tweet_id'] ?? 0;
        $this->authorId = $data['author_id'] ?? 0;
        $this->tweetText = $data['tweet_text'] ?? '';
        $this->inReplyToTweetId = $data['in_reply_to_tweet_id'] ?? null;
        $this->retweetedTweetId = $data['retweeted_tweet_id'] ?? null;
        $this->retweetedUserId = $data['retweeted_user_id'] ?? null;
        $this->phoenixScores = isset($data['phoenix_scores']) 
            ? PhoenixScores::fromArray($data['phoenix_scores']) 
            : new PhoenixScores();
        $this->predictionRequestId = $data['prediction_request_id'] ?? null;
        $this->lastScoredAtMs = $data['last_scored_at_ms'] ?? null;
        $this->weightedScore = $data['weighted_score'] ?? null;
        $this->score = $data['score'] ?? null;
        $this->servedType = $data['served_type'] ?? null;
        $this->inNetwork = $data['in_network'] ?? null;
        $this->ancestors = $data['ancestors'] ?? [];
        $this->videoDurationMs = $data['video_duration_ms'] ?? null;
        $this->authorFollowersCount = $data['author_followers_count'] ?? null;
        $this->authorScreenName = $data['author_screen_name'] ?? null;
        $this->retweetedScreenName = $data['retweeted_screen_name'] ?? null;
        $this->subscriptionAuthorId = $data['subscription_author_id'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'tweet_id' => $this->tweetId,
            'author_id' => $this->authorId,
            'tweet_text' => $this->tweetText,
            'in_reply_to_tweet_id' => $this->inReplyToTweetId,
            'retweeted_tweet_id' => $this->retweetedTweetId,
            'retweeted_user_id' => $this->retweetedUserId,
            'phoenix_scores' => $this->phoenixScores->toArray(),
            'prediction_request_id' => $this->predictionRequestId,
            'last_scored_at_ms' => $this->lastScoredAtMs,
            'weighted_score' => $this->weightedScore,
            'score' => $this->score,
            'served_type' => $this->servedType,
            'in_network' => $this->inNetwork,
            'ancestors' => $this->ancestors,
            'video_duration_ms' => $this->videoDurationMs,
            'author_followers_count' => $this->authorFollowersCount,
            'author_screen_name' => $this->authorScreenName,
            'retweeted_screen_name' => $this->retweetedScreenName,
            'subscription_author_id' => $this->subscriptionAuthorId,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function getScreenNames(): array
    {
        $screenNames = [];
        
        if ($this->authorScreenName !== null) {
            $screenNames[$this->authorId] = $this->authorScreenName;
        }
        
        if ($this->retweetedScreenName !== null && $this->retweetedUserId !== null) {
            $screenNames[$this->retweetedUserId] = $this->retweetedScreenName;
        }
        
        return $screenNames;
    }

    public function getLookupTweetId(): int
    {
        return $this->retweetedTweetId ?? $this->tweetId;
    }

    public function getLookupAuthorId(): int
    {
        return $this->retweetedUserId ?? $this->authorId;
    }
}
