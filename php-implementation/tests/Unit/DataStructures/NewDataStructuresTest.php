<?php

declare(strict_types=1);

namespace XAlgorithm\Tests\Unit\DataStructures;

use XAlgorithm\Core\DataStructures\CandidateFeatures;
use XAlgorithm\Core\DataStructures\QueryFeatures;
use XAlgorithm\Core\DataStructures\UserActionItem;
use XAlgorithm\Core\DataStructures\UserActionSequence;

class NewDataStructuresTest extends \PHPUnit\Framework\TestCase
{
    public function testCandidateFeatures(): void
    {
        $features = new CandidateFeatures([
            'weighted_score' => 0.5,
            'diversity_score' => 0.3,
            'oon_score' => 0.2,
            'final_score' => 0.4,
            'rank' => 1,
            'filter_count' => 2,
            'source_index' => 0,
            'scorer_names' => ['WeightedScorer', 'OONScorer'],
        ]);

        $this->assertEquals(0.5, $features->weightedScore);
        $this->assertEquals(0.3, $features->diversityScore);
        $this->assertEquals(1, $features->rank);
        $this->assertCount(2, $features->scorerNames);
    }

    public function testCandidateFeaturesToArray(): void
    {
        $features = new CandidateFeatures(['weighted_score' => 0.5]);
        $array = $features->toArray();

        $this->assertArrayHasKey('weighted_score', $array);
        $this->assertEquals(0.5, $array['weighted_score']);
    }

    public function testQueryFeatures(): void
    {
        $features = new QueryFeatures([
            'user_id' => 123,
            'country_code' => 'US',
            'language_code' => 'en',
            'following_list' => [100, 200],
            'muted_keywords' => ['spam'],
            'blocked_authors' => [500],
            'account_age_days' => 365,
            'is_verified' => true,
        ]);

        $this->assertEquals(123, $features->userId);
        $this->assertEquals('US', $features->countryCode);
        $this->assertCount(2, $features->followingList);
        $this->assertTrue($features->isVerified);
    }

    public function testUserActionItem(): void
    {
        $item = new UserActionItem([
            'tweet_id' => 100,
            'author_id' => 50,
            'action_type' => 1,
            'timestamp_ms' => 1000000,
            'product_surface' => 1,
        ]);

        $this->assertEquals(100, $item->tweetId);
        $this->assertEquals(1, $item->actionType);
    }

    public function testUserActionSequence(): void
    {
        $sequence = new UserActionSequence([
            'user_id' => 123,
            'actions' => [
                ['tweet_id' => 100, 'author_id' => 50, 'action_type' => 1, 'timestamp_ms' => 1000000, 'product_surface' => 1],
                ['tweet_id' => 200, 'author_id' => 60, 'action_type' => 2, 'timestamp_ms' => 2000000, 'product_surface' => 1],
            ],
            'created_at_ms' => 3000000,
        ]);

        $this->assertEquals(123, $sequence->userId);
        $this->assertCount(2, $sequence->actions);

        $recent = $sequence->getRecentActions(1);
        $this->assertCount(1, $recent);
        $this->assertEquals(200, $recent[0]->tweetId);

        $likes = $sequence->getActionsByType(1);
        $this->assertCount(1, $likes);
    }

    public function testUserActionSequenceWithEmptyActions(): void
    {
        $sequence = new UserActionSequence([
            'user_id' => 123,
            'actions' => [],
        ]);

        $this->assertCount(0, $sequence->actions);
        $this->assertEmpty($sequence->getRecentActions(10));
        $this->assertEmpty($sequence->getActionsByType(1));
    }
}
