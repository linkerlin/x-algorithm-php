<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Scorers;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\Pipeline\Interfaces\ScorerInterface;

class OONScorer implements ScorerInterface
{
    private string $name;
    private float $boostFactor;

    public function __construct(float $boostFactor = 1.0)
    {
        $this->name = 'OONScorer';
        $this->boostFactor = $boostFactor;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function score(ScoredPostsQuery $query, array $candidates): array
    {
        return array_map(function (PostCandidate $candidate) {
            $isOutOfNetwork = !$candidate->inNetwork ?? true;
            
            if ($isOutOfNetwork && $candidate->weightedScore !== null) {
                $boostedScore = $candidate->weightedScore * $this->boostFactor;
                
                return new PostCandidate([
                    'tweet_id' => $candidate->tweetId,
                    'author_id' => $candidate->authorId,
                    'tweet_text' => $candidate->tweetText,
                    'in_reply_to_tweet_id' => $candidate->inReplyToTweetId,
                    'retweeted_tweet_id' => $candidate->retweetedTweetId,
                    'retweeted_user_id' => $candidate->retweetedUserId,
                    'phoenix_scores' => $candidate->phoenixScores->toArray(),
                    'prediction_request_id' => $candidate->predictionRequestId,
                    'last_scored_at_ms' => $candidate->lastScoredAtMs,
                    'weighted_score' => $boostedScore,
                    'score' => $boostedScore,
                    'served_type' => $candidate->servedType,
                    'in_network' => false,
                    'ancestors' => $candidate->ancestors,
                    'video_duration_ms' => $candidate->videoDurationMs,
                    'author_followers_count' => $candidate->authorFollowersCount,
                    'author_screen_name' => $candidate->authorScreenName,
                    'retweeted_screen_name' => $candidate->retweetedScreenName,
                    'subscription_author_id' => $candidate->subscriptionAuthorId,
                ]);
            }

            return $candidate;
        }, $candidates);
    }

    public function setBoostFactor(float $factor): self
    {
        $this->boostFactor = $factor;
        return $this;
    }
}
