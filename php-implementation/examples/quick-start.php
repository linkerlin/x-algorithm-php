<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use XAlgorithm\Algorithm;
use XAlgorithm\Core\DataStructures\ScoredPostsQuery;

echo "=== X Algorithm PHP Quick Start ===\n\n";

echo "1. Basic Recommendation:\n";
$recommendations = Algorithm::getRecommendations(
    userId: 12345,
    countryCode: 'US',
    languageCode: 'en',
    limit: 5
);
echo "   Got " . count($recommendations) . " recommendations\n\n";

echo "2. Single Recommendation:\n";
$single = Algorithm::getSingleRecommendation(
    userId: 12345,
    countryCode: 'US',
    languageCode: 'en'
);
if ($single) {
    echo "   Tweet ID: " . $single->tweetId . "\n";
    echo "   Author ID: " . $single->authorId . "\n";
    echo "   Weighted Score: " . ($single->weightedScore ?? 'N/A') . "\n\n";
}

echo "3. Advanced Usage with Query Object:\n";
$result = Algorithm::getPersonalizedRecommendations(
    userId: 12345,
    clientAppId: 1,
    countryCode: 'US',
    languageCode: 'en',
    seenIds: [100, 200, 300]
);
echo "   Got result with " . ($result['count'] ?? 0) . " candidates\n\n";

echo "4. Generate Request ID:\n";
$requestId = Algorithm::generateRequestId();
echo "   Request ID: " . $requestId . "\n\n";

echo "5. Health Check:\n";
$healthy = Algorithm::checkHealth();
echo "   Service Status: " . ($healthy ? 'Healthy' : 'Unknown') . "\n\n";

echo "=== Quick Start Complete ===\n";
