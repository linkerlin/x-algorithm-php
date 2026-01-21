<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Scorers;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\Pipeline\Interfaces\ScorerInterface;
use XAlgorithm\ML\Phoenix\PhoenixClientInterface;

class PhoenixScorer implements ScorerInterface
{
    private string $name;
    private PhoenixClientInterface $phoenixClient;
    private int $predictionRequestId;
    private int $lastScoredAtMs;

    public function __construct(PhoenixClientInterface $phoenixClient)
    {
        $this->name = 'PhoenixScorer';
        $this->phoenixClient = $phoenixClient;
        $this->predictionRequestId = 0;
        $this->lastScoredAtMs = 0;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function score(ScoredPostsQuery $query, array $candidates): array
    {
        $this->predictionRequestId = $this->generateRequestId();
        $this->lastScoredAtMs = $this->currentTimestampMs();

        if ($query->userActionSequence === null || empty($candidates)) {
            return $candidates;
        }

        $tweetInfos = array_map(function (PostCandidate $candidate) {
            return [
                'tweet_id' => $candidate->getLookupTweetId(),
                'author_id' => $candidate->getLookupAuthorId(),
            ];
        }, $candidates);

        try {
            $response = $this->phoenixClient->predict(
                $query->userId,
                $query->userActionSequence->toArray(),
                $tweetInfos
            );

            $predictionsMap = $this->buildPredictionsMap($response);

            return array_map(function (PostCandidate $candidate) use ($predictionsMap) {
                $lookupTweetId = $candidate->getLookupTweetId();
                
                $phoenixScores = $predictionsMap[$lookupTweetId] ?? [];
                
                return new PostCandidate([
                    'tweet_id' => $candidate->tweetId,
                    'author_id' => $candidate->authorId,
                    'tweet_text' => $candidate->tweetText,
                    'in_reply_to_tweet_id' => $candidate->inReplyToTweetId,
                    'retweeted_tweet_id' => $candidate->retweetedTweetId,
                    'retweeted_user_id' => $candidate->retweetedUserId,
                    'phoenix_scores' => $phoenixScores,
                    'prediction_request_id' => $this->predictionRequestId,
                    'last_scored_at_ms' => $this->lastScoredAtMs,
                    'weighted_score' => $candidate->weightedScore,
                    'score' => $candidate->score,
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
        } catch (\Exception $e) {
            error_log('Phoenix scoring failed: ' . $e->getMessage());
            return $candidates;
        }
    }

    private function buildPredictionsMap(array $response): array
    {
        $predictionsMap = [];

        foreach ($response['predictions'] ?? [] as $prediction) {
            $tweetId = $prediction['tweet_id'];
            $predictionsMap[$tweetId] = $prediction['scores'];
        }

        return $predictionsMap;
    }

    private function generateRequestId(): int
    {
        return mt_rand(1, 999999999);
    }

    private function currentTimestampMs(): int
    {
        return (int)(microtime(true) * 1000);
    }
}
