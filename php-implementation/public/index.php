<?php

declare(strict_types=1);

require_once __DIR__ . '/autoload.php';

use XAlgorithm\HomeMixer\HomeMixerService;
use XAlgorithm\Core\DataStructures\ScoredPostsQuery;

$service = new HomeMixerService([
    'default_limit' => 50,
    'max_age_seconds' => 86400 * 7,
    'enable_diversity' => true,
    'diversity_decay_factor' => 0.9,
    'oon_boost_factor' => 1.0,
]);

$recommendations = $service->getPersonalizedRecommendations(
    userId: 12345,
    clientAppId: 1,
    countryCode: 'US',
    languageCode: 'en',
    seenIds: [],
    servedIds: [],
    inNetworkOnly: false,
    isBottomRequest: false
);

header('Content-Type: application/json');
echo json_encode($recommendations, JSON_PRETTY_PRINT);
