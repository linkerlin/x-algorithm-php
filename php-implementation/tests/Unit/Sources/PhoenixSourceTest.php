<?php

declare(strict_types=1);

namespace XAlgorithm\Tests\Unit\Sources;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\Sources\PhoenixSource;
use XAlgorithm\ML\Phoenix\MockPhoenixClient;

class PhoenixSourceTest extends \PHPUnit\Framework\TestCase
{
    public function testGetName(): void
    {
        $mockClient = new MockPhoenixClient();
        $source = new PhoenixSource($mockClient, 50);

        $this->assertEquals('PhoenixSource', $source->getName());
    }

    public function testFetchReturnsCandidatesWithSource(): void
    {
        $mockClient = new MockPhoenixClient();
        $source = new PhoenixSource($mockClient, 50);
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $result = $source->fetch($query);

        $this->assertArrayHasKey('candidates', $result);
        $this->assertArrayHasKey('source', $result);
        $this->assertEquals('PhoenixSource', $result['source']);
        $this->assertIsArray($result['candidates']);
    }

    public function testFetchWithMockClient(): void
    {
        $mockClient = new MockPhoenixClient();
        $source = new PhoenixSource($mockClient, 10);
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $result = $source->fetch($query);

        $this->assertNotEmpty($result['candidates']);
    }

    public function testFetchWithBottomRequest(): void
    {
        $mockClient = new MockPhoenixClient();
        $source = new PhoenixSource($mockClient, 10);
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'is_bottom_request' => true,
        ]);

        $result = $source->fetch($query);

        $this->assertArrayHasKey('candidates', $result);
        $this->assertIsArray($result['candidates']);
    }
}
