<?php

declare(strict_types=1);

namespace XAlgorithm\Tests\Unit\Filters;

use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\Filters\DedupConversationFilter;
use XAlgorithm\Core\Filters\RetweetDeduplicationFilter;
use XAlgorithm\Core\Filters\VFFilter;
use XAlgorithm\Core\Filters\CoreDataHydrationFilter;

class NewFiltersTest extends \PHPUnit\Framework\TestCase
{
    public function testDedupConversationFilterRemovesDuplicates(): void
    {
        $filter = new DedupConversationFilter();
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $c1 = new PostCandidate(['tweet_id' => 1, 'score' => 0.5, 'ancestors' => [100]]);
        $c2 = new PostCandidate(['tweet_id' => 2, 'score' => 0.9, 'ancestors' => [100]]);
        $c3 = new PostCandidate(['tweet_id' => 3, 'score' => 0.3, 'ancestors' => [200]]);

        $result = $filter->filter($query, [$c1, $c2, $c3]);

        $this->assertCount(2, $result->kept);
        $this->assertCount(1, $result->removed);
        $this->assertEquals(2, $result->kept[0]->tweetId);
    }

    public function testRetweetDeduplicationFilter(): void
    {
        $filter = new RetweetDeduplicationFilter();
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $original = new PostCandidate(['tweet_id' => 100]);
        $retweet1 = new PostCandidate(['tweet_id' => 101, 'retweeted_tweet_id' => 100]);
        $retweet2 = new PostCandidate(['tweet_id' => 102, 'retweeted_tweet_id' => 100]);

        $result = $filter->filter($query, [$original, $retweet1, $retweet2]);

        $this->assertCount(2, $result->kept);
        $this->assertCount(1, $result->removed);
    }

    public function testVFFilter(): void
    {
        $filter = new VFFilter();
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $safe = new PostCandidate(['tweet_id' => 1, 'vf_status' => '']);
        $unsafe = new PostCandidate(['tweet_id' => 2, 'vf_status' => 'flagged']);

        $result = $filter->filter($query, [$safe, $unsafe]);

        $this->assertCount(1, $result->kept);
        $this->assertCount(1, $result->removed);
    }

    public function testCoreDataHydrationFilter(): void
    {
        $filter = new CoreDataHydrationFilter();
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $valid = new PostCandidate(['tweet_id' => 1, 'tweet_text' => 'Test', 'author_id' => 100]);
        $invalid = new PostCandidate(['tweet_id' => 0, 'tweet_text' => '', 'author_id' => 0]);

        $result = $filter->filter($query, [$valid, $invalid]);

        $this->assertCount(1, $result->kept);
        $this->assertCount(1, $result->removed);
    }
}
