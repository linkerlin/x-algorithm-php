<?php

declare(strict_types=1);

namespace XAlgorithm\Tests\Unit\Hydrators;

use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\UserFeatures;
use XAlgorithm\Core\Hydrators\InNetworkCandidateHydrator;
use XAlgorithm\Core\Hydrators\VideoDurationCandidateHydrator;
use XAlgorithm\Core\Hydrators\SubscriptionHydrator;

class NewHydratorsTest extends \PHPUnit\Framework\TestCase
{
    public function testInNetworkCandidateHydrator(): void
    {
        $hydrator = new InNetworkCandidateHydrator();
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'user_features' => new UserFeatures([
                'following_list' => [200, 300],
            ]),
        ]);

        $candidates = [
            new PostCandidate(['tweet_id' => 1, 'author_id' => 123]),
            new PostCandidate(['tweet_id' => 2, 'author_id' => 200]),
            new PostCandidate(['tweet_id' => 3, 'author_id' => 999]),
        ];

        $result = $hydrator->hydrate($query, $candidates);

        $this->assertTrue($result[0]->inNetwork);
        $this->assertTrue($result[1]->inNetwork);
        $this->assertFalse($result[2]->inNetwork);
    }

    public function testVideoDurationCandidateHydrator(): void
    {
        $hydrator = new VideoDurationCandidateHydrator();
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $candidate = new PostCandidate([
            'tweet_id' => 1,
            'author_id' => 100,
            'media_entities' => [
                ['type' => 'video', 'video_info' => ['duration_millis' => 30000]],
            ],
        ]);

        $result = $hydrator->hydrate($query, [$candidate]);

        $this->assertEquals(30000, $result[0]->videoDurationMs);
    }

    public function testSubscriptionHydrator(): void
    {
        $hydrator = new SubscriptionHydrator();
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'user_features' => new UserFeatures([
                'following_list' => [200, 300],
            ]),
        ]);

        $candidates = [
            new PostCandidate(['tweet_id' => 1, 'author_id' => 200]),
            new PostCandidate(['tweet_id' => 2, 'author_id' => 999]),
        ];

        $result = $hydrator->hydrate($query, $candidates);

        $this->assertTrue($result[0]->isSubscribed);
        $this->assertNull($result[1]->isSubscribed);
    }

    public function testInNetworkWithEmptyFollowing(): void
    {
        $hydrator = new InNetworkCandidateHydrator();
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'user_features' => new UserFeatures(['following_list' => []]),
        ]);

        $candidate = new PostCandidate(['tweet_id' => 1, 'author_id' => 123]);
        $result = $hydrator->hydrate($query, [$candidate]);

        $this->assertTrue($result[0]->inNetwork);
    }
}
