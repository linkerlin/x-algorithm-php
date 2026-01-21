<?php

declare(strict_types=1);

namespace XAlgorithm\Tests\Unit\Filters;

use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\Filters\SelfTweetFilter;

class SelfTweetFilterTest extends \PHPUnit\Framework\TestCase
{
    public function testGetName(): void
    {
        $filter = new SelfTweetFilter();

        $this->assertEquals('SelfTweetFilter', $filter->getName());
    }

    public function testFilterRemovesSelfTweets(): void
    {
        $filter = new SelfTweetFilter();
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $selfTweet = new PostCandidate(['tweet_id' => 1, 'author_id' => 123]);
        $otherTweet = new PostCandidate(['tweet_id' => 2, 'author_id' => 456]);

        $result = $filter->filter($query, [$selfTweet, $otherTweet]);

        $this->assertCount(1, $result->kept);
        $this->assertCount(1, $result->removed);
        $this->assertEquals(2, $result->kept[0]->tweetId);
    }

    public function testFilterWithAllSelfTweets(): void
    {
        $filter = new SelfTweetFilter();
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $selfTweet1 = new PostCandidate(['tweet_id' => 1, 'author_id' => 123]);
        $selfTweet2 = new PostCandidate(['tweet_id' => 2, 'author_id' => 123]);

        $result = $filter->filter($query, [$selfTweet1, $selfTweet2]);

        $this->assertCount(0, $result->kept);
        $this->assertCount(2, $result->removed);
    }

    public function testFilterWithNoSelfTweets(): void
    {
        $filter = new SelfTweetFilter();
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $otherTweet1 = new PostCandidate(['tweet_id' => 1, 'author_id' => 456]);
        $otherTweet2 = new PostCandidate(['tweet_id' => 2, 'author_id' => 789]);

        $result = $filter->filter($query, [$otherTweet1, $otherTweet2]);

        $this->assertCount(2, $result->kept);
        $this->assertCount(0, $result->removed);
    }

    public function testFilterWithEmptyCandidates(): void
    {
        $filter = new SelfTweetFilter();
        $query = new ScoredPostsQuery(['user_id' => 123]);
        $result = $filter->filter($query, []);

        $this->assertCount(0, $result->kept);
        $this->assertCount(0, $result->removed);
    }
}
