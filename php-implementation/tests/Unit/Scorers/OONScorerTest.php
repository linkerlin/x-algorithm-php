<?php

declare(strict_types=1);

namespace XAlgorithm\Tests\Unit\Scorers;

use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\Scorers\OONScorer;

class OONScorerTest extends \PHPUnit\Framework\TestCase
{
    public function testGetName(): void
    {
        $scorer = new OONScorer(1.5);

        $this->assertEquals('OONScorer', $scorer->getName());
    }

    public function testScoreBoostsOutOfNetwork(): void
    {
        $scorer = new OONScorer(1.5);
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $inNetwork = new PostCandidate([
            'tweet_id' => 1,
            'author_id' => 111,
            'in_network' => true,
            'weighted_score' => 0.5,
        ]);

        $outOfNetwork = new PostCandidate([
            'tweet_id' => 2,
            'author_id' => 222,
            'in_network' => false,
            'weighted_score' => 0.5,
        ]);

        $result = $scorer->score($query, [$inNetwork, $outOfNetwork]);

        $this->assertCount(2, $result);
        $this->assertEquals(0.5, $result[0]->weightedScore, 'In-network should keep original score');
        $this->assertEquals(0.75, $result[1]->weightedScore, 'Out-of-network should be boosted');
    }

    public function testScoreWithDifferentBoostFactors(): void
    {
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $outOfNetwork = new PostCandidate([
            'tweet_id' => 1,
            'author_id' => 222,
            'in_network' => false,
            'weighted_score' => 0.5,
        ]);

        $scorer1 = new OONScorer(2.0);
        $result1 = $scorer1->score($query, [$outOfNetwork]);
        $this->assertEquals(1.0, $result1[0]->weightedScore, '2x boost should double score');

        $scorer2 = new OONScorer(1.1);
        $result2 = $scorer2->score($query, [$outOfNetwork]);
        $this->assertEquals(0.55, $result2[0]->weightedScore, '1.1x boost should multiply by 1.1');
    }

    public function testScoreWithNoScoreSet(): void
    {
        $scorer = new OONScorer(1.5);
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $outOfNetwork = new PostCandidate([
            'tweet_id' => 1,
            'author_id' => 222,
            'in_network' => false,
        ]);

        $result = $scorer->score($query, [$outOfNetwork]);

        $this->assertFalse($outOfNetwork->inNetwork, 'Should set in_network to false');
        $this->assertNull($result[0]->weightedScore, 'Should have null weighted score');
    }

    public function testScoreWithEmptyCandidates(): void
    {
        $scorer = new OONScorer(1.5);
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $result = $scorer->score($query, []);

        $this->assertCount(0, $result);
    }

    public function testScoreWithDefaultBoostFactor(): void
    {
        $scorer = new OONScorer();
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $outOfNetwork = new PostCandidate([
            'tweet_id' => 1,
            'author_id' => 222,
            'in_network' => false,
            'weighted_score' => 1.0,
        ]);

        $result = $scorer->score($query, [$outOfNetwork]);

        $this->assertEquals(1.0, $result[0]->weightedScore);
    }
}
