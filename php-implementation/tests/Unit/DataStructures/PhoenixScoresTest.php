<?php

declare(strict_types=1);

namespace XAlgorithm\Tests\Unit\DataStructures;

use XAlgorithm\Core\DataStructures\PhoenixScores;

class PhoenixScoresTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructorWithScores(): void
    {
        $scores = new PhoenixScores([
            'favoriteScore' => 0.5,
            'replyScore' => 0.3,
            'retweetScore' => 0.2,
        ]);

        $this->assertEquals(0.5, $scores->favoriteScore);
        $this->assertEquals(0.3, $scores->replyScore);
        $this->assertEquals(0.2, $scores->retweetScore);
    }

    public function testConstructorWithEmptyScores(): void
    {
        $scores = new PhoenixScores();

        $this->assertNull($scores->favoriteScore);
        $this->assertNull($scores->replyScore);
        $this->assertNull($scores->retweetScore);
        $this->assertNull($scores->shareScore);
        $this->assertNull($scores->dwellScore);
    }

    public function testGetWeightedEngagementScore(): void
    {
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

        $weightedScore = $scores->getWeightedEngagementScore();

        $this->assertIsFloat($weightedScore);
        $this->assertGreaterThan(0, $weightedScore);
    }

    public function testGetWeightedEngagementScoreWithEmptyScores(): void
    {
        $scores = new PhoenixScores();

        $weightedScore = $scores->getWeightedEngagementScore();

        $this->assertEquals(0.0, $weightedScore);
    }

    public function testToArrayWithSnakeCaseKeys(): void
    {
        $scores = new PhoenixScores([
            'favoriteScore' => 0.5,
            'replyScore' => 0.3,
            'retweetScore' => 0.2,
        ]);

        $array = $scores->toArray();

        $this->assertArrayHasKey('favorite_score', $array);
        $this->assertArrayHasKey('reply_score', $array);
        $this->assertArrayHasKey('retweet_score', $array);
        $this->assertEquals(0.5, $array['favorite_score']);
        $this->assertEquals(0.3, $array['reply_score']);
        $this->assertEquals(0.2, $array['retweet_score']);
    }

    public function testFromArray(): void
    {
        $data = [
            'favorite_score' => 0.8,
            'reply_score' => 0.4,
            'retweet_score' => 0.6,
        ];

        $scores = PhoenixScores::fromArray($data);

        $this->assertEquals(0.8, $scores->favoriteScore);
        $this->assertEquals(0.4, $scores->replyScore);
        $this->assertEquals(0.6, $scores->retweetScore);
    }

    public function testFromArrayWithEmptyData(): void
    {
        $scores = PhoenixScores::fromArray([]);

        $this->assertNull($scores->favoriteScore);
        $this->assertNull($scores->replyScore);
    }

    public function testAllScoreTypes(): void
    {
        $scores = new PhoenixScores([
            'favoriteScore' => 0.1,
            'replyScore' => 0.2,
            'retweetScore' => 0.3,
            'photoExpandScore' => 0.4,
            'clickScore' => 0.5,
            'profileClickScore' => 0.6,
            'vqvScore' => 0.7,
            'shareScore' => 0.8,
            'shareViaDmScore' => 0.9,
            'shareViaCopyLinkScore' => 1.0,
            'dwellScore' => 0.15,
            'quoteScore' => 0.25,
            'quotedClickScore' => 0.35,
            'followAuthorScore' => 0.45,
            'notInterestedScore' => 0.55,
            'blockAuthorScore' => 0.65,
            'muteAuthorScore' => 0.75,
            'reportScore' => 0.85,
            'dwellTime' => 30.0,
        ]);

        $this->assertEquals(0.1, $scores->favoriteScore);
        $this->assertEquals(0.2, $scores->replyScore);
        $this->assertEquals(0.3, $scores->retweetScore);
        $this->assertEquals(0.4, $scores->photoExpandScore);
        $this->assertEquals(0.5, $scores->clickScore);
        $this->assertEquals(0.6, $scores->profileClickScore);
        $this->assertEquals(0.7, $scores->vqvScore);
        $this->assertEquals(0.8, $scores->shareScore);
        $this->assertEquals(0.9, $scores->shareViaDmScore);
        $this->assertEquals(1.0, $scores->shareViaCopyLinkScore);
        $this->assertEquals(0.15, $scores->dwellScore);
        $this->assertEquals(0.25, $scores->quoteScore);
        $this->assertEquals(0.35, $scores->quotedClickScore);
        $this->assertEquals(0.45, $scores->followAuthorScore);
        $this->assertEquals(0.55, $scores->notInterestedScore);
        $this->assertEquals(0.65, $scores->blockAuthorScore);
        $this->assertEquals(0.75, $scores->muteAuthorScore);
        $this->assertEquals(0.85, $scores->reportScore);
        $this->assertEquals(30.0, $scores->dwellTime);
    }

    public function testToArrayAndFromArrayAreInverse(): void
    {
        $original = new PhoenixScores([
            'favoriteScore' => 0.5,
            'replyScore' => 0.3,
            'retweetScore' => 0.2,
        ]);

        $array = $original->toArray();
        $restored = PhoenixScores::fromArray($array);

        $this->assertEquals($original->favoriteScore, $restored->favoriteScore);
        $this->assertEquals($original->replyScore, $restored->replyScore);
        $this->assertEquals($original->retweetScore, $restored->retweetScore);
    }

    public function testCustomWeights(): void
    {
        $scores = new PhoenixScores([
            'favoriteScore' => 1.0,
            'replyScore' => 1.0,
        ]);

        $customWeighted = $scores->getWeightedEngagementScore([
            'favorite_score' => 1.0,
            'reply_score' => 0.5,
        ]);

        $defaultWeighted = $scores->getWeightedEngagementScore();

        $this->assertGreaterThan($defaultWeighted, $customWeighted);
    }
}
