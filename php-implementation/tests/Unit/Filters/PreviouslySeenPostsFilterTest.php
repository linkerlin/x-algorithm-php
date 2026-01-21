<?php

declare(strict_types=1);

namespace XAlgorithm\Tests\Unit\Filters;

use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\Filters\PreviouslySeenPostsFilter;

class PreviouslySeenPostsFilterTest extends \PHPUnit\Framework\TestCase
{
    public function testGetName(): void
    {
        $filter = new PreviouslySeenPostsFilter();

        $this->assertEquals('PreviouslySeenPostsFilter', $filter->getName());
    }

    public function testFilterRemovesSeenPosts(): void
    {
        $filter = new PreviouslySeenPostsFilter();
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'seen_ids' => [100, 200],
        ]);

        $seen1 = new PostCandidate(['tweet_id' => 100]);
        $seen2 = new PostCandidate(['tweet_id' => 200]);
        $new1 = new PostCandidate(['tweet_id' => 300]);
        $new2 = new PostCandidate(['tweet_id' => 400]);

        $result = $filter->filter($query, [$seen1, $new1, $seen2, $new2]);

        $this->assertCount(2, $result->kept);
        $this->assertCount(2, $result->removed);
        $this->assertContains($new1, $result->kept);
        $this->assertContains($new2, $result->kept);
    }

    public function testFilterWithNoSeenPosts(): void
    {
        $filter = new PreviouslySeenPostsFilter();
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'seen_ids' => [],
        ]);

        $tweet1 = new PostCandidate(['tweet_id' => 1]);
        $tweet2 = new PostCandidate(['tweet_id' => 2]);

        $result = $filter->filter($query, [$tweet1, $tweet2]);

        $this->assertCount(2, $result->kept);
        $this->assertCount(0, $result->removed);
    }

    public function testFilterWithAllSeenPosts(): void
    {
        $filter = new PreviouslySeenPostsFilter();
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'seen_ids' => [1, 2, 3],
        ]);

        $tweet1 = new PostCandidate(['tweet_id' => 1]);
        $tweet2 = new PostCandidate(['tweet_id' => 2]);
        $tweet3 = new PostCandidate(['tweet_id' => 3]);

        $result = $filter->filter($query, [$tweet1, $tweet2, $tweet3]);

        $this->assertCount(0, $result->kept);
        $this->assertCount(3, $result->removed);
    }

    public function testFilterWithEmptyCandidates(): void
    {
        $filter = new PreviouslySeenPostsFilter();
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'seen_ids' => [1],
        ]);

        $result = $filter->filter($query, []);

        $this->assertCount(0, $result->kept);
        $this->assertCount(0, $result->removed);
    }
}
