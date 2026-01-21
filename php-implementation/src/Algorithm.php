<?php

declare(strict_types=1);

namespace XAlgorithm;

use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\HomeMixer\HomeMixerService;

class Algorithm
{
    private static ?HomeMixerService $service = null;

    public static function getRecommendations(
        int $userId,
        string $countryCode = 'US',
        string $languageCode = 'en',
        int $limit = 30,
        array $options = []
    ): array {
        $query = new ScoredPostsQuery([
            'user_id' => $userId,
            'country_code' => $countryCode,
            'language_code' => $languageCode,
        ]);

        return self::getService($options)->getPersonalizedRecommendations(
            $userId,
            1,
            $countryCode,
            $languageCode
        )['candidates'] ?? [];
    }

    public static function getPersonalizedRecommendations(
        int $userId,
        int $clientAppId = 1,
        string $countryCode = 'US',
        string $languageCode = 'en',
        array $seenIds = [],
        array $servedIds = [],
        bool $inNetworkOnly = false,
        bool $isBottomRequest = false,
        array $options = []
    ): array {
        return self::getService($options)->getPersonalizedRecommendations(
            $userId,
            $clientAppId,
            $countryCode,
            $languageCode,
            $seenIds,
            $servedIds,
            $inNetworkOnly,
            $isBottomRequest
        );
    }

    public static function getSingleRecommendation(
        int $userId,
        string $countryCode = 'US',
        string $languageCode = 'en'
    ): ?PostCandidate {
        $result = self::getRecommendations($userId, $countryCode, $languageCode, 1);
        return !empty($result) ? new PostCandidate($result[0]) : null;
    }

    public static function create(array $config = []): self
    {
        $instance = new self();
        self::$service = new HomeMixerService($config);
        return $instance;
    }

    private static function getService(array $options): HomeMixerService
    {
        if (self::$service === null) {
            self::$service = new HomeMixerService($options);
        }
        return self::$service;
    }

    public static function reset(): void
    {
        self::$service = null;
    }

    public static function generateRequestId(): int
    {
        return \XAlgorithm\Utility\RequestIdGenerator::generateSnowflake();
    }

    public static function checkHealth(): bool
    {
        $service = self::getService([]);
        $pipeline = $service->getPipeline();
        return $pipeline !== null;
    }
}
