<?php

declare(strict_types=1);

namespace XAlgorithm\Tests\Unit\ML;

use XAlgorithm\ML\Phoenix\MockPhoenixClient;

class MockPhoenixClientTest extends \PHPUnit\Framework\TestCase
{
    public function testHealthCheckWithHealthyClient(): void
    {
        $client = new MockPhoenixClient(false);

        $this->assertTrue($client->healthCheck());
    }

    public function testHealthCheckWithUnhealthyClient(): void
    {
        $client = new MockPhoenixClient(true);

        $this->assertFalse($client->healthCheck());
    }

    public function testPredictReturnsPredictions(): void
    {
        $client = new MockPhoenixClient();
        $result = $client->predict(123, [], [['tweet_id' => 1]]);

        $this->assertArrayHasKey('predictions', $result);
        $this->assertCount(1, $result['predictions']);
        $this->assertArrayHasKey('tweet_id', $result['predictions'][0]);
        $this->assertArrayHasKey('scores', $result['predictions'][0]);
    }

    public function testGetRetrievalReturnsCandidates(): void
    {
        $client = new MockPhoenixClient();
        $result = $client->getRetrieval(123, [], 10);

        $this->assertArrayHasKey('candidates', $result);
        $this->assertCount(10, $result['candidates']);
    }

    public function testGetPredictionCount(): void
    {
        $client = new MockPhoenixClient();

        $this->assertEquals(0, $client->getPredictionCount());

        $client->predict(123, [], [['tweet_id' => 1]]);

        $this->assertEquals(1, $client->getPredictionCount());

        $client->predict(456, [], [['tweet_id' => 2]]);

        $this->assertEquals(2, $client->getPredictionCount());
    }

    public function testPredictWithMultipleCandidates(): void
    {
        $client = new MockPhoenixClient();
        $result = $client->predict(123, [], [
            ['tweet_id' => 1],
            ['tweet_id' => 2],
            ['tweet_id' => 3],
        ]);

        $this->assertCount(3, $result['predictions']);
    }

    public function testPredictThrowsExceptionForUnhealthyClient(): void
    {
        $client = new MockPhoenixClient(true);

        $this->expectException(\RuntimeException::class);
        $client->predict(123, [], [['tweet_id' => 1]]);
    }
}
