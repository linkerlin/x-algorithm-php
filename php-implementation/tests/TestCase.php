<?php

declare(strict_types=1);

namespace XAlgorithm\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    protected function createPostCandidate(array $data = []): \XAlgorithm\Core\DataStructures\PostCandidate
    {
        return new \XAlgorithm\Core\DataStructures\PostCandidate($data);
    }

    protected function createPhoenixScores(array $scores = []): \XAlgorithm\Core\DataStructures\PhoenixScores
    {
        return new \XAlgorithm\Core\DataStructures\PhoenixScores($scores);
    }

    protected function createScoredPostsQuery(array $data = []): \XAlgorithm\Core\DataStructures\ScoredPostsQuery
    {
        return new \XAlgorithm\Core\DataStructures\ScoredPostsQuery($data);
    }

    protected function createUserFeatures(array $data = []): \XAlgorithm\Core\DataStructures\UserFeatures
    {
        return new \XAlgorithm\Core\DataStructures\UserFeatures($data);
    }

    protected function assertPostCandidateEquals(\XAlgorithm\Core\DataStructures\PostCandidate $expected, \XAlgorithm\Core\DataStructures\PostCandidate $actual): void
    {
        $this->assertEquals($expected->tweetId, $actual->tweetId);
        $this->assertEquals($expected->authorId, $actual->authorId);
        $this->assertEquals($expected->tweetText, $actual->tweetText);
    }

    protected function assertPhoenixScoresEquals(\XAlgorithm\Core\DataStructures\PhoenixScores $expected, \XAlgorithm\Core\DataStructures\PhoenixScores $actual): void
    {
        $this->assertEquals($expected->favoriteScore, $actual->favoriteScore);
        $this->assertEquals($expected->replyScore, $actual->replyScore);
        $this->assertEquals($expected->retweetScore, $actual->retweetScore);
        $this->assertEquals($expected->shareScore, $actual->shareScore);
        $this->assertEquals($expected->dwellScore, $actual->dwellScore);
        $this->assertEquals($expected->clickScore, $actual->clickScore);
    }
}
