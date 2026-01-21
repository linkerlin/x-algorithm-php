<?php

declare(strict_types=1);

namespace XAlgorithm\Tests\Unit\Scorers;

use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\Scorers\AuthorDiversityScorer;

class AuthorDiversityScorerTest extends \PHPUnit\Framework\TestCase
{
    public function testGetName(): void
    {
        $scorer = new AuthorDiversityScorer(0.8);

        $this->assertEquals('AuthorDiversityScorer', $scorer->getName());
    }

    public function testScoreAppliesDecayForSameAuthor(): void
    {
        $scorer = new AuthorDiversityScorer(0.8);
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $candidates = [];
        for ($i = 0; $i < 4; $i++) {
            $candidates[] = new PostCandidate([
                'tweet_id' => $i + 1,
                'author_id' => 100,
                'tweet_text' => "Tweet $i",
                'weighted_score' => 0.5,
            ]);
        }

        $result = $scorer->score($query, $candidates);

        $this->assertCount(4, $result);
        $this->assertTrue(
            $result[0]->weightedScore > $result[1]->weightedScore,
            'First tweet from author should have highest score'
        );
        $this->assertTrue(
            $result[1]->weightedScore > $result[2]->weightedScore,
            'Second tweet should have lower score than first'
        );
        $this->assertTrue(
            $result[2]->weightedScore > $result[3]->weightedScore,
            'Third tweet should have lower score than second'
        );
    }

    public function testScoreMaintainsScoreForDifferentAuthors(): void
    {
        $scorer = new AuthorDiversityScorer(0.8);
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $candidates = [];
        for ($i = 0; $i < 4; $i++) {
            $candidates[] = new PostCandidate([
                'tweet_id' => $i + 10,
                'author_id' => 100 + $i,
                'tweet_text' => "Tweet $i",
                'weighted_score' => 0.5,
            ]);
        }

        $result = $scorer->score($query, $candidates);

        foreach ($result as $candidate) {
            $this->assertEquals(0.5, $candidate->weightedScore);
        }
    }

    public function testScoreWithMixedAuthors(): void
    {
        $scorer = new AuthorDiversityScorer(0.9);
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $candidates = [
            new PostCandidate(['tweet_id' => 1, 'author_id' => 100, 'weighted_score' => 0.5]),
            new PostCandidate(['tweet_id' => 2, 'author_id' => 100, 'weighted_score' => 0.5]),
            new PostCandidate(['tweet_id' => 3, 'author_id' => 200, 'weighted_score' => 0.5]),
            new PostCandidate(['tweet_id' => 4, 'author_id' => 100, 'weighted_score' => 0.5]),
        ];

        $result = $scorer->score($query, $candidates);

        $this->assertEquals(0.5, $result[0]->weightedScore);
        $this->assertLessThan(0.5, $result[1]->weightedScore);
        $this->assertEquals(0.5, $result[2]->weightedScore);
        $this->assertLessThan($result[1]->weightedScore, $result[3]->weightedScore);
    }

    public function testScoreWithEmptyCandidates(): void
    {
        $scorer = new AuthorDiversityScorer(0.8);
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $result = $scorer->score($query, []);

        $this->assertCount(0, $result);
    }

    public function testDecayFactor(): void
    {
        $highDecay = new AuthorDiversityScorer(0.5);
        $lowDecay = new AuthorDiversityScorer(0.95);
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $candidates = [];
        for ($i = 0; $i < 3; $i++) {
            $candidates[] = new PostCandidate([
                'tweet_id' => $i + 1,
                'author_id' => 100,
                'weighted_score' => 1.0,
            ]);
        }

        $highDecayResult = $highDecay->score($query, $candidates);
        $lowDecayResult = $lowDecay->score($query, $candidates);

        $this->assertLessThan(
            $lowDecayResult[2]->weightedScore,
            $highDecayResult[2]->weightedScore,
            'Higher decay factor should result in lower scores'
        );
    }
}
