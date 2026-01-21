<?php

declare(strict_types=1);

namespace XAlgorithm\Tests\Feature;

use XAlgorithm\Algorithm;

class AlgorithmTest extends \PHPUnit\Framework\TestCase
{
    public function testGetRecommendations(): void
    {
        $recommendations = Algorithm::getRecommendations(
            userId: 12345,
            countryCode: 'US',
            languageCode: 'en',
            limit: 5
        );

        $this->assertIsArray($recommendations);
    }

    public function testGenerateRequestId(): void
    {
        $requestId = Algorithm::generateRequestId();

        $this->assertIsInt($requestId);
        $this->assertGreaterThan(0, $requestId);
    }

    public function testCheckHealth(): void
    {
        $health = Algorithm::checkHealth();

        $this->assertTrue($health);
    }

    public function testReset(): void
    {
        Algorithm::reset();

        $this->assertTrue(true);
    }

    public function testGetSingleRecommendation(): void
    {
        $single = Algorithm::getSingleRecommendation(
            userId: 12345,
            countryCode: 'US',
            languageCode: 'en'
        );

        $this->assertNull($single);
    }
}
