<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Scorers;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\Pipeline\Interfaces\ScorerInterface;

class AuthorDiversityScorer implements ScorerInterface
{
    private string $name;
    private array $authorPenalties;
    private float $decayFactor;

    public function __construct(float $decayFactor = 0.9)
    {
        $this->name = 'AuthorDiversityScorer';
        $this->authorPenalties = [];
        $this->decayFactor = $decayFactor;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function score(ScoredPostsQuery $query, array $candidates): array
    {
        $this->authorPenalties = [];
        $authorOrder = [];

        foreach ($candidates as $index => $candidate) {
            $authorId = $candidate->authorId;
            
            if (!isset($authorOrder[$authorId])) {
                $authorOrder[$authorId] = [];
            }
            $authorOrder[$authorId][] = $index;
        }

        foreach ($authorOrder as $authorId => $positions) {
            $count = count($positions);
            
            foreach ($positions as $rank => $position) {
                $penalty = pow($this->decayFactor, $rank);
                $this->authorPenalties[$position] = $penalty;
            }
        }

        return array_map(function (PostCandidate $candidate, int $index) {
            $originalScore = $candidate->weightedScore ?? $candidate->score ?? 0;
            $penalty = $this->authorPenalties[$index] ?? 1.0;
            $adjustedScore = $originalScore * $penalty;

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
                'weighted_score' => $adjustedScore,
                'score' => $adjustedScore,
                'served_type' => $candidate->servedType,
                'in_network' => $candidate->inNetwork,
                'ancestors' => $candidate->ancestors,
                'video_duration_ms' => $candidate->videoDurationMs,
                'author_followers_count' => $candidate->authorFollowersCount,
                'author_screen_name' => $candidate->authorScreenName,
                'retweeted_screen_name' => $candidate->retweetedScreenName,
                'subscription_author_id' => $candidate->subscriptionAuthorId,
            ]);
        }, $candidates, array_keys($candidates));
    }

    public function setDecayFactor(float $factor): self
    {
        $this->decayFactor = $factor;
        return $this;
    }
}
