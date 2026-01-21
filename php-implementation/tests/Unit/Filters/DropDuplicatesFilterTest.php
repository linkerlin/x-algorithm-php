<?php

declare(strict_types=1);

namespace XAlgorithm\Tests\Unit\Filters;

use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\Filters\DropDuplicatesFilter;

class DropDuplicatesFilterTest extends \PHPUnit\Framework\TestCase
{
    public function testGetName(): void
    {
        $filter = new DropDuplicatesFilter();

        $this->assertEquals('DropDuplicatesFilter', $filter->getName());
    }

    public function testFilterRemovesDuplicates(): void
    {
        $filter = new DropDuplicatesFilter();
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'seen_ids' => [100, 200],
        ]);

        $candidate1 = new PostCandidate(['tweet_id' => 100]);
        $candidate2 = new PostCandidate(['tweet_id' => 300]);
        $candidate3 = new PostCandidate(['tweet_id' => 100]);
        $candidate4 = new PostCandidate(['tweet_id' => 400]);

        $result = $filter->filter($query, [$candidate1, $candidate2, $candidate3, $candidate4]);

        $this->assertCount(2, $result->kept);
        $this->assertCount(2, $result->removed);
        $this->assertContains($candidate2, $result->kept);
        $this->assertContains($candidate4, $result->kept);
    }

    public function testFilterWithNoDuplicates(): void
    {
        $filter = new DropDuplicatesFilter();
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'seen_ids' => [],
        ]);

        $candidate1 = new PostCandidate(['tweet_id' => 1]);
        $candidate2 = new PostCandidate(['tweet_id' => 2]);
        $candidate3 = new PostCandidate(['tweet_id' => 3]);

        $result = $filter->filter($query, [$candidate1, $candidate2, $candidate3]);

        $this->assertCount(3, $result->kept);
        $this->assertCount(0, $result->removed);
    }

    public function testFilterWithAllDuplicates(): void
    {
        $filter = new DropDuplicatesFilter();
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'seen_ids' => [1, 2, 3],
        ]);

        $candidate1 = new PostCandidate(['tweet_id' => 1]);
        $candidate2 = new PostCandidate(['tweet_id' => 2]);
        $candidate3 = new PostCandidate(['tweet_id' => 3]);

        $result = $filter->filter($query, [$candidate1, $candidate2, $candidate3]);

        $this->assertCount(0, $result->kept);
        $this->assertCount(3, $result->removed);
    }

    public function testFilterWithEmptyCandidates(): void
    {
        $filter = new DropDuplicatesFilter();
        $query = new ScoredPostsQuery(['user_id' => 123]);
        $result = $filter->filter($query, []);

        $this->assertCount(0, $result->kept);
        $this->assertCount(0, $result->removed);
    }
}
