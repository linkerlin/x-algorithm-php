<?php

declare(strict_types=1);

namespace XAlgorithm\Tests\Unit\Sources;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\Sources\ThunderSource;

class ThunderSourceTest extends \PHPUnit\Framework\TestCase
{
    public function testGetName(): void
    {
        $source = new ThunderSource(null, 50);

        $this->assertEquals('ThunderSource', $source->getName());
    }

    public function testFetchReturnsCandidatesWithSource(): void
    {
        $source = new ThunderSource(null, 50);
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $result = $source->fetch($query);

        $this->assertArrayHasKey('candidates', $result);
        $this->assertArrayHasKey('source', $result);
        $this->assertEquals('ThunderSource', $result['source']);
        $this->assertIsArray($result['candidates']);
    }

    public function testFetchWithInNetworkOnly(): void
    {
        $source = new ThunderSource(null, 50);
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'in_network_only' => true,
        ]);

        $result = $source->fetch($query);

        $this->assertArrayHasKey('candidates', $result);
        $this->assertArrayHasKey('source', $result);
    }

    public function testDefaultLimit(): void
    {
        $source = new ThunderSource(null, 100);
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $result = $source->fetch($query);

        $this->assertArrayHasKey('candidates', $result);
    }
}
