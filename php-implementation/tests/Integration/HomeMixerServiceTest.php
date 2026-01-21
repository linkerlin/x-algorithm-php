<?php

declare(strict_types=1);

namespace XAlgorithm\Tests\Integration;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\HomeMixer\HomeMixerService;

class HomeMixerServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testGetName(): void
    {
        $service = new HomeMixerService();

        $this->assertEquals('HomeMixerService', $service->getName());
    }

    public function testGetConfig(): void
    {
        $service = new HomeMixerService([
            'default_limit' => 20,
            'max_age_seconds' => 86400,
        ]);

        $config = $service->getConfig();

        $this->assertArrayHasKey('default_limit', $config);
        $this->assertArrayHasKey('max_age_seconds', $config);
        $this->assertEquals(20, $config['default_limit']);
        $this->assertEquals(86400, $config['max_age_seconds']);
    }

    public function testUpdateConfig(): void
    {
        $service = new HomeMixerService(['default_limit' => 10]);
        $updatedService = $service->updateConfig(['default_limit' => 30]);

        $this->assertEquals(30, $updatedService->getConfig()['default_limit']);
    }

    public function testGetPersonalizedRecommendations(): void
    {
        $service = new HomeMixerService([
            'default_limit' => 10,
            'max_age_seconds' => 86400 * 7,
            'enable_diversity' => true,
            'diversity_decay_factor' => 0.9,
            'oon_boost_factor' => 1.0,
        ]);

        $result = $service->getPersonalizedRecommendations(
            userId: 12345,
            clientAppId: 1,
            countryCode: 'US',
            languageCode: 'en',
            seenIds: [],
            servedIds: [],
            inNetworkOnly: false,
            isBottomRequest: false
        );

        $this->assertArrayHasKey('candidates', $result);
        $this->assertArrayHasKey('request_id', $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertArrayHasKey('pipeline_stats', $result);
        $this->assertIsArray($result['candidates']);
        $this->assertIsString($result['request_id']);
        $this->assertIsInt($result['count']);
    }

    public function testGetRecommendations(): void
    {
        $service = new HomeMixerService();
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $result = $service->getRecommendations($query);

        $this->assertArrayHasKey('candidates', $result);
        $this->assertArrayHasKey('count', $result);
    }

    public function testGetPipeline(): void
    {
        $service = new HomeMixerService();
        $pipeline = $service->getPipeline();

        $this->assertNotNull($pipeline);
    }
}
