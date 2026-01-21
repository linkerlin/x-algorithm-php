<?php

declare(strict_types=1);

namespace XAlgorithm\Tests\Unit\Filters;

use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\Filters\AgeFilter;

class AgeFilterTest extends \PHPUnit\Framework\TestCase
{
    public function testGetName(): void
    {
        $filter = new AgeFilter(86400);

        $this->assertEquals('AgeFilter', $filter->getName());
    }

    public function testFilterRemovesOldTweets(): void
    {
        $filter = new AgeFilter(86400);
        $now = (int)(microtime(true) * 1000);
        $epoch = 1288834974657;

        $recentTweetId = (($now - $epoch) << 22) | (1 << 17) | (1 << 12);
        $oldTweetId = ((($now - 86400000 * 10) - $epoch) << 22) | (1 << 17) | (1 << 12);

        $recentCandidate = new PostCandidate(['tweet_id' => $recentTweetId]);
        $oldCandidate = new PostCandidate(['tweet_id' => $oldTweetId]);

        $query = new ScoredPostsQuery(['user_id' => 123]);
        $result = $filter->filter($query, [$recentCandidate, $oldCandidate]);

        $this->assertCount(1, $result->kept);
        $this->assertCount(1, $result->removed);
        $this->assertEquals($recentTweetId, $result->kept[0]->tweetId);
    }

    public function testFilterKeepsAllRecentTweets(): void
    {
        $filter = new AgeFilter(86400);
        $now = (int)(microtime(true) * 1000);
        $epoch = 1288834974657;

        $recentTweet1 = new PostCandidate(['tweet_id' => (($now - $epoch) << 22) | (1 << 17) | (1 << 12)]);
        $recentTweet2 = new PostCandidate(['tweet_id' => (($now - $epoch) << 22) | (2 << 17) | (1 << 12)]);

        $query = new ScoredPostsQuery(['user_id' => 123]);
        $result = $filter->filter($query, [$recentTweet1, $recentTweet2]);

        $this->assertCount(2, $result->kept);
        $this->assertCount(0, $result->removed);
    }

    public function testFilterRemovesAllOldTweets(): void
    {
        $filter = new AgeFilter(86400);
        $now = (int)(microtime(true) * 1000);
        $epoch = 1288834974657;

        $oldTweet1 = new PostCandidate(['tweet_id' => ((($now - 86400000 * 10) - $epoch) << 22) | (1 << 17) | (1 << 12)]);
        $oldTweet2 = new PostCandidate(['tweet_id' => ((($now - 86400000 * 20) - $epoch) << 22) | (2 << 17) | (1 << 12)]);

        $query = new ScoredPostsQuery(['user_id' => 123]);
        $result = $filter->filter($query, [$oldTweet1, $oldTweet2]);

        $this->assertCount(0, $result->kept);
        $this->assertCount(2, $result->removed);
    }

    public function testFilterWithEmptyCandidates(): void
    {
        $filter = new AgeFilter(86400);
        $query = new ScoredPostsQuery(['user_id' => 123]);
        $result = $filter->filter($query, []);

        $this->assertCount(0, $result->kept);
        $this->assertCount(0, $result->removed);
    }
}
