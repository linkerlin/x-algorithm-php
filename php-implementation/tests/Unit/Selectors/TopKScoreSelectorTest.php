<?php

declare(strict_types=1);

namespace XAlgorithm\Tests\Unit\Selectors;

use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\Selectors\TopKScoreSelector;

class TopKScoreSelectorTest extends \PHPUnit\Framework\TestCase
{
    public function testGetName(): void
    {
        $selector = new TopKScoreSelector();

        $this->assertEquals('TopKScoreSelector', $selector->getName());
    }

    public function testSelectReturnsTopK(): void
    {
        $selector = new TopKScoreSelector();
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $candidates = [];
        for ($i = 1; $i <= 5; $i++) {
            $candidates[] = new PostCandidate([
                'tweet_id' => $i,
                'author_id' => 100 + $i,
                'weighted_score' => 0.5 * (6 - $i),
            ]);
        }

        $result = $selector->select($query, $candidates, 3);

        $this->assertCount(3, $result);
        $this->assertEquals(1, $result[0]->tweetId);
        $this->assertEquals(2, $result[1]->tweetId);
        $this->assertEquals(3, $result[2]->tweetId);
    }

    public function testSelectWithEmptyCandidates(): void
    {
        $selector = new TopKScoreSelector();
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $result = $selector->select($query, [], 3);

        $this->assertCount(0, $result);
    }

    public function testSelectWithLimitGreaterThanCount(): void
    {
        $selector = new TopKScoreSelector();
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $candidates = [
            new PostCandidate(['tweet_id' => 1, 'weighted_score' => 0.5]),
            new PostCandidate(['tweet_id' => 2, 'weighted_score' => 0.3]),
        ];

        $result = $selector->select($query, $candidates, 10);

        $this->assertCount(2, $result);
    }

    public function testSelectWithScoreField(): void
    {
        $selector = new TopKScoreSelector('score');
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $candidates = [
            new PostCandidate(['tweet_id' => 1, 'score' => 0.3, 'weighted_score' => 0.9]),
            new PostCandidate(['tweet_id' => 2, 'score' => 0.5, 'weighted_score' => 0.1]),
        ];

        $result = $selector->select($query, $candidates, 1);

        $this->assertCount(1, $result);
        $this->assertEquals(2, $result[0]->tweetId);
    }

    public function testSelectOrdersByScoreDescending(): void
    {
        $selector = new TopKScoreSelector();
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $candidates = [
            new PostCandidate(['tweet_id' => 1, 'weighted_score' => 0.1]),
            new PostCandidate(['tweet_id' => 2, 'weighted_score' => 0.9]),
            new PostCandidate(['tweet_id' => 3, 'weighted_score' => 0.5]),
        ];

        $result = $selector->select($query, $candidates, 3);

        $this->assertEquals(0.9, $result[0]->weightedScore);
        $this->assertEquals(0.5, $result[1]->weightedScore);
        $this->assertEquals(0.1, $result[2]->weightedScore);
    }
}
