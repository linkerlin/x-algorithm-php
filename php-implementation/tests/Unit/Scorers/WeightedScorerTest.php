<?php

declare(strict_types=1);

namespace XAlgorithm\Tests\Unit\Scorers;

use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\DataStructures\PhoenixScores;
use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\Scorers\WeightedScorer;

class WeightedScorerTest extends \PHPUnit\Framework\TestCase
{
    public function testGetName(): void
    {
        $scorer = new WeightedScorer();

        $this->assertEquals('WeightedScorer', $scorer->getName());
    }

    public function testScoreReturnsWeightedScores(): void
    {
        $scorer = new WeightedScorer();
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $scores = new PhoenixScores([
            'favoriteScore' => 0.5,
            'replyScore' => 0.5,
            'retweetScore' => 0.5,
            'shareScore' => 0.5,
            'dwellScore' => 0.5,
            'quoteScore' => 0.5,
            'clickScore' => 0.5,
            'profileClickScore' => 0.5,
        ]);

        $candidate = new PostCandidate([
            'tweet_id' => 1,
            'author_id' => 111,
            'tweet_text' => 'Test',
            'phoenix_scores' => $scores->toArray(),
        ]);

        $result = $scorer->score($query, [$candidate]);

        $this->assertCount(1, $result);
        $this->assertNotNull($result[0]->weightedScore);
        $this->assertIsFloat($result[0]->weightedScore);
        $this->assertGreaterThan(0, $result[0]->weightedScore);
    }

    public function testScoreWithCustomWeights(): void
    {
        $scorer = new WeightedScorer([
            'reply_score' => 1.0,
            'retweet_score' => 0.0,
        ]);
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $scores = new PhoenixScores([
            'replyScore' => 1.0,
            'retweetScore' => 1.0,
        ]);

        $candidate = new PostCandidate([
            'tweet_id' => 1,
            'author_id' => 111,
            'phoenix_scores' => $scores->toArray(),
        ]);

        $result = $scorer->score($query, [$candidate]);

        $this->assertNotNull($result[0]->weightedScore);
        $this->assertEquals(1.0, $result[0]->weightedScore);
    }

    public function testScoreWithEmptyPhoenixScores(): void
    {
        $scorer = new WeightedScorer();
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $candidate = new PostCandidate([
            'tweet_id' => 1,
            'author_id' => 111,
            'tweet_text' => 'Test',
        ]);

        $result = $scorer->score($query, [$candidate]);

        $this->assertEquals(0.0, $result[0]->weightedScore);
    }

    public function testScoreWithMultipleCandidates(): void
    {
        $scorer = new WeightedScorer();
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $candidate1 = new PostCandidate([
            'tweet_id' => 1,
            'author_id' => 111,
            'phoenix_scores' => ['favoriteScore' => 0.9],
        ]);

        $candidate2 = new PostCandidate([
            'tweet_id' => 2,
            'author_id' => 222,
            'phoenix_scores' => ['favoriteScore' => 0.1],
        ]);

        $result = $scorer->score($query, [$candidate1, $candidate2]);

        $this->assertCount(2, $result);
        $this->assertGreaterThan($result[1]->weightedScore, $result[0]->weightedScore);
    }

    public function testScoreWithEmptyCandidates(): void
    {
        $scorer = new WeightedScorer();
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $result = $scorer->score($query, []);

        $this->assertCount(0, $result);
    }

    public function testDefaultWeights(): void
    {
        $scorer = new WeightedScorer();

        $query = new ScoredPostsQuery(['user_id' => 123]);
        $scores = new PhoenixScores([
            'replyScore' => 1.0,
        ]);

        $candidate = new PostCandidate([
            'tweet_id' => 1,
            'author_id' => 111,
            'phoenix_scores' => $scores->toArray(),
        ]);

        $result = $scorer->score($query, [$candidate]);

        $this->assertGreaterThan(0, $result[0]->weightedScore);
    }
}
