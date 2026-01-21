<?php

declare(strict_types=1);

namespace XAlgorithm\Tests\Integration;

use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\Filters\AgeFilter;
use XAlgorithm\Core\Filters\DropDuplicatesFilter;
use XAlgorithm\Core\Filters\MutedKeywordFilter;
use XAlgorithm\Core\Scorers\WeightedScorer;
use XAlgorithm\Core\Selectors\TopKScoreSelector;
use XAlgorithm\Core\Sources\ThunderSource;
use XAlgorithm\Core\Pipeline\CandidatePipeline;

class CandidatePipelineTest extends \PHPUnit\Framework\TestCase
{
    public function testExecuteReturnsResultStructure(): void
    {
        $pipeline = new CandidatePipeline('TestPipeline');
        $pipeline->addSource(new ThunderSource(null, 10));
        $pipeline->addFilter(new AgeFilter(86400 * 7));
        $pipeline->addFilter(new DropDuplicatesFilter());
        $pipeline->addScorer(new WeightedScorer());
        $pipeline->setSelector(new TopKScoreSelector());

        $query = new ScoredPostsQuery(['user_id' => 123]);
        $result = $pipeline->execute($query, 5);

        $this->assertArrayHasKey('candidates', $result);
        $this->assertArrayHasKey('request_id', $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertArrayHasKey('pipeline_stats', $result);
    }

    public function testExecuteWithEmptySources(): void
    {
        $pipeline = new CandidatePipeline('EmptyPipeline');
        $pipeline->setSelector(new TopKScoreSelector());

        $query = new ScoredPostsQuery(['user_id' => 123]);
        $result = $pipeline->execute($query, 5);

        $this->assertArrayHasKey('candidates', $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertEquals(0, $result['count']);
    }

    public function testGetStats(): void
    {
        $pipeline = new CandidatePipeline('TestPipeline');
        $pipeline->addSource(new ThunderSource(null, 10));
        $pipeline->addFilter(new AgeFilter(86400));
        $pipeline->addScorer(new WeightedScorer());
        $pipeline->setSelector(new TopKScoreSelector());

        $stats = $pipeline->getStats();

        $this->assertArrayHasKey('sources', $stats);
        $this->assertArrayHasKey('hydrators', $stats);
        $this->assertArrayHasKey('filters', $stats);
        $this->assertArrayHasKey('scorers', $stats);
        $this->assertArrayHasKey('selector', $stats);
        $this->assertEquals(1, $stats['sources']);
        $this->assertEquals(1, $stats['filters']);
        $this->assertEquals(1, $stats['scorers']);
        $this->assertTrue($stats['selector']);
    }

    public function testPipelineWithMultipleFilters(): void
    {
        $pipeline = new CandidatePipeline('MultiFilterPipeline');
        $pipeline->addSource(new ThunderSource(null, 10));
        $pipeline->addFilter(new AgeFilter(86400 * 7));
        $pipeline->addFilter(new DropDuplicatesFilter());
        $pipeline->addFilter(new MutedKeywordFilter());
        $pipeline->setSelector(new TopKScoreSelector());

        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'user_features' => [
                'muted_keywords' => ['spam'],
            ],
        ]);

        $result = $pipeline->execute($query, 5);

        $this->assertArrayHasKey('candidates', $result);
        $stats = $pipeline->getStats();
        $this->assertEquals(3, $stats['filters']);
    }

    public function testPipelineWithMultipleScorers(): void
    {
        $pipeline = new CandidatePipeline('MultiScorerPipeline');
        $pipeline->addSource(new ThunderSource(null, 10));
        $pipeline->addScorer(new WeightedScorer());
        $pipeline->setSelector(new TopKScoreSelector());

        $query = new ScoredPostsQuery(['user_id' => 123]);
        $result = $pipeline->execute($query, 5);

        $stats = $pipeline->getStats();
        $this->assertEquals(1, $stats['scorers']);
    }
}
