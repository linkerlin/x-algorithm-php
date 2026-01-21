<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Scorers;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\Pipeline\Interfaces\ScorerInterface;

class WeightedScorer implements ScorerInterface
{
    private string $name;
    private array $weights;

    public function __construct(array $weights = [])
    {
        $this->name = 'WeightedScorer';
        $this->weights = array_merge([
            'favorite_score' => 0.05,
            'reply_score' => 0.5,
            'retweet_score' => 0.3,
            'share_score' => 0.4,
            'dwell_score' => 0.1,
            'quote_score' => 0.35,
            'click_score' => 0.15,
            'profile_click_score' => 0.1,
        ], $weights);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function score(ScoredPostsQuery $query, array $candidates): array
    {
        return array_map(function (PostCandidate $candidate) {
            $weightedScore = $this->calculateWeightedScore($candidate->phoenixScores);
            
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
                'weighted_score' => $weightedScore,
                'score' => $weightedScore,
                'served_type' => $candidate->servedType,
                'in_network' => $candidate->inNetwork,
                'ancestors' => $candidate->ancestors,
                'video_duration_ms' => $candidate->videoDurationMs,
                'author_followers_count' => $candidate->authorFollowersCount,
                'author_screen_name' => $candidate->authorScreenName,
                'retweeted_screen_name' => $candidate->retweetedScreenName,
                'subscription_author_id' => $candidate->subscriptionAuthorId,
            ]);
        }, $candidates);
    }

    private function calculateWeightedScore($phoenixScores): float
    {
        $score = 0.0;

        foreach ($this->weights as $field => $weight) {
            $camelField = $this->toCamelCase($field);
            $value = $phoenixScores->$camelField ?? 0;
            $score += $value * $weight;
        }

        return $score;
    }

    private function toCamelCase(string $snakeCase): string
    {
        $parts = explode('_', $snakeCase);
        return $parts[0] . implode('', array_map('ucfirst', array_slice($parts, 1)));
    }

    public function setWeight(string $field, float $weight): self
    {
        $this->weights[$field] = $weight;
        return $this;
    }

    public function getWeights(): array
    {
        return $this->weights;
    }
}
