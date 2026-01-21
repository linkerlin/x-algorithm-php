<?php

declare(strict_types=1);

namespace XAlgorithm\Tests\Unit\DataStructures;

use XAlgorithm\Core\DataStructures\UserActionItem;
use XAlgorithm\Core\DataStructures\UserActionSequence;

class UserActionSequenceTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructorWithActions(): void
    {
        $actions = [
            [
                'tweet_id' => 100,
                'author_id' => 50,
                'action_type' => 1,
                'timestamp_ms' => 1000000,
                'product_surface' => 1,
            ],
            [
                'tweet_id' => 200,
                'author_id' => 60,
                'action_type' => 2,
                'timestamp_ms' => 2000000,
                'product_surface' => 1,
            ],
        ];

        $sequence = new UserActionSequence([
            'user_id' => 123,
            'actions' => $actions,
            'created_at_ms' => 3000000,
        ]);

        $this->assertEquals(123, $sequence->userId);
        $this->assertCount(2, $sequence->actions);
        $this->assertEquals(3000000, $sequence->createdAtMs);
    }

    public function testGetRecentActions(): void
    {
        $actions = [
            ['tweet_id' => 100, 'author_id' => 50, 'action_type' => 1, 'timestamp_ms' => 1000000, 'product_surface' => 1],
            ['tweet_id' => 200, 'author_id' => 60, 'action_type' => 2, 'timestamp_ms' => 2000000, 'product_surface' => 1],
            ['tweet_id' => 300, 'author_id' => 70, 'action_type' => 3, 'timestamp_ms' => 3000000, 'product_surface' => 1],
        ];

        $sequence = new UserActionSequence([
            'user_id' => 123,
            'actions' => $actions,
        ]);

        $recentActions = $sequence->getRecentActions(2);

        $this->assertCount(2, $recentActions);
        $this->assertEquals(3000000, $recentActions[0]->timestampMs);
        $this->assertEquals(2000000, $recentActions[1]->timestampMs);
    }

    public function testGetActionsByType(): void
    {
        $actions = [
            ['tweet_id' => 100, 'author_id' => 50, 'action_type' => 1, 'timestamp_ms' => 1000000, 'product_surface' => 1],
            ['tweet_id' => 200, 'author_id' => 60, 'action_type' => 1, 'timestamp_ms' => 2000000, 'product_surface' => 1],
            ['tweet_id' => 300, 'author_id' => 70, 'action_type' => 2, 'timestamp_ms' => 3000000, 'product_surface' => 1],
        ];

        $sequence = new UserActionSequence([
            'user_id' => 123,
            'actions' => $actions,
        ]);

        $likeActions = $sequence->getActionsByType(1);

        $this->assertCount(2, $likeActions);
        foreach ($likeActions as $action) {
            $this->assertEquals(1, $action->actionType);
        }
    }

    public function testToArray(): void
    {
        $sequence = new UserActionSequence([
            'user_id' => 123,
            'actions' => [
                ['tweet_id' => 100, 'author_id' => 50, 'action_type' => 1, 'timestamp_ms' => 1000000, 'product_surface' => 1],
            ],
        ]);

        $array = $sequence->toArray();

        $this->assertArrayHasKey('user_id', $array);
        $this->assertArrayHasKey('actions', $array);
        $this->assertArrayHasKey('created_at_ms', $array);
        $this->assertEquals(123, $array['user_id']);
        $this->assertCount(1, $array['actions']);
    }

    public function testEmptyActions(): void
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
