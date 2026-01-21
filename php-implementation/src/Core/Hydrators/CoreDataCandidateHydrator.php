<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Hydrators;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\Pipeline\Interfaces\HydratorInterface;

/**
 * 核心数据候选水合器
 * 补充帖子的核心元数据
 */
class CoreDataCandidateHydrator implements HydratorInterface
{
    private string $name;
    private array $dataProviders;

    public function __construct(array $dataProviders = [])
    {
        $this->name = 'CoreDataCandidateHydrator';
        $this->dataProviders = $dataProviders;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function hydrate(ScoredPostsQuery $query, array $candidates): array
    {
        if (empty($candidates)) {
            return $candidates;
        }

        $tweetIds = array_map(fn($c) => $c->tweetId, $candidates);
        $authorIds = array_map(fn($c) => $c->authorId, $candidates);

        $coreData = $this->fetchCoreData($tweetIds, $authorIds);

        return array_map(function (PostCandidate $candidate) use ($coreData) {
            $tweetData = $coreData['tweets'][$candidate->tweetId] ?? [];
            $authorData = $coreData['authors'][$candidate->authorId] ?? [];

            return new PostCandidate([
                'tweet_id' => $candidate->tweetId,
                'author_id' => $candidate->authorId,
                'tweet_text' => $tweetData['text'] ?? $candidate->tweetText,
                'in_reply_to_tweet_id' => $tweetData['in_reply_to_tweet_id'] ?? $candidate->inReplyToTweetId,
                'retweeted_tweet_id' => $candidate->retweetedTweetId,
                'retweeted_user_id' => $candidate->retweetedUserId,
                'phoenix_scores' => $candidate->phoenixScores->toArray(),
                'prediction_request_id' => $candidate->predictionRequestId,
                'last_scored_at_ms' => $candidate->lastScoredAtMs,
                'weighted_score' => $candidate->weightedScore,
                'score' => $candidate->score,
                'served_type' => $candidate->servedType,
                'in_network' => $candidate->inNetwork,
                'ancestors' => $tweetData['ancestors'] ?? $candidate->ancestors,
                'video_duration_ms' => $tweetData['video_duration_ms'] ?? $candidate->videoDurationMs,
                'author_followers_count' => $authorData['followers_count'] ?? $candidate->authorFollowersCount,
                'author_screen_name' => $authorData['screen_name'] ?? $candidate->authorScreenName,
                'retweeted_screen_name' => $candidate->retweetedScreenName,
                'subscription_author_id' => $candidate->subscriptionAuthorId,
            ]);
        }, $candidates);
    }

    private function fetchCoreData(array $tweetIds, array $authorIds): array
    {
        return [
            'tweets' => [],
            'authors' => [],
        ];
    }

    public function addDataProvider(string $name, callable $provider): self
    {
        $this->dataProviders[$name] = $provider;
        return $this;
    }
}
