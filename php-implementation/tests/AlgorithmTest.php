<?php

declare(strict_types=1);

require_once __DIR__ . '/../autoload.php';

use XAlgorithm\HomeMixer\HomeMixerService;
use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\DataStructures\PhoenixScores;
use XAlgorithm\Core\Filters\AgeFilter;
use XAlgorithm\Core\Filters\DropDuplicatesFilter;
use XAlgorithm\Core\Filters\MutedKeywordFilter;
use XAlgorithm\Core\Scorers\WeightedScorer;
use XAlgorithm\Core\Scorers\AuthorDiversityScorer;
use XAlgorithm\Utility\RequestIdGenerator;

class AlgorithmTest
{
    private int $passed = 0;
    private int $failed = 0;
    private array $errors = [];

    public function run(): void
    {
        echo "=== X Algorithm PHP Test Suite ===\n\n";

        $this->testRequestIdGenerator();
        $this->testPostCandidate();
        $this->testPhoenixScores();
        $this->testScoredPostsQuery();
        $this->testAgeFilter();
        $this->testDropDuplicatesFilter();
        $this->testMutedKeywordFilter();
        $this->testWeightedScorer();
        $this->testAuthorDiversityScorer();
        $this->testHomeMixerService();

        $this->printSummary();
    }

    private function testRequestIdGenerator(): void
    {
        echo "Testing RequestIdGenerator...\n";

        $id1 = RequestIdGenerator::generate(123);
        $this->assertNotEmpty($id1, 'Generated ID should not be empty');
        $this->assertTrue(strlen($id1) > 10, 'ID should be reasonably long');

        $snowflake = RequestIdGenerator::generateSnowflake();
        $timestamp = RequestIdGenerator::getTimestampFromSnowflake($snowflake);
        $this->assertTrue($timestamp > 0, 'Should extract timestamp from snowflake');

        echo "  ✓ RequestIdGenerator tests passed\n\n";
    }

    private function testPostCandidate(): void
    {
        echo "Testing PostCandidate...\n";

        $candidate = new PostCandidate([
            'tweet_id' => 1234567890,
            'author_id' => 111,
            'tweet_text' => 'Hello World',
            'in_reply_to_tweet_id' => null,
        ]);

        $this->assertEquals(1234567890, $candidate->tweetId, 'Tweet ID should match');
        $this->assertEquals(111, $candidate->authorId, 'Author ID should match');
        $this->assertEquals('Hello World', $candidate->tweetText, 'Tweet text should match');

        $screenNames = $candidate->getScreenNames();
        $this->assertEmpty($screenNames, 'Should have no screen names initially');

        $candidate->authorScreenName = 'testuser';
        $screenNames = $candidate->getScreenNames();
        $this->assertArrayHasKey(111, $screenNames, 'Should have author screen name');

        $array = $candidate->toArray();
        $this->assertArrayHasKey('tweet_id', $array, 'Should export to array');

        echo "  ✓ PostCandidate tests passed\n\n";
    }

    private function testPhoenixScores(): void
    {
        echo "Testing PhoenixScores...\n";

        $scores = new PhoenixScores([
            'favoriteScore' => 0.5,
            'replyScore' => 0.3,
            'retweetScore' => 0.2,
        ]);

        $this->assertEquals(0.5, $scores->favoriteScore, 'Favorite score should match');
        $this->assertEquals(0.3, $scores->replyScore, 'Reply score should match');
        $this->assertEquals(0.2, $scores->retweetScore, 'Retweet score should match');

        $weightedScore = $scores->getWeightedEngagementScore();
        $this->assertTrue($weightedScore > 0, 'Weighted score should be positive');

        $array = $scores->toArray();
        $this->assertArrayHasKey('favorite_score', $array, 'Should export with snake_case keys');

        echo "  ✓ PhoenixScores tests passed\n\n";
    }

    private function testScoredPostsQuery(): void
    {
        echo "Testing ScoredPostsQuery...\n";

        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'client_app_id' => 1,
            'country_code' => 'US',
            'language_code' => 'en',
            'seen_ids' => [1, 2, 3],
        ]);

        $this->assertEquals(123, $query->userId, 'User ID should match');
        $this->assertEquals('US', $query->countryCode, 'Country code should match');
        $this->assertCount(3, $query->seenIds, 'Should have 3 seen IDs');

        $this->assertTrue($query->hasSeenTweet(1), 'Should detect seen tweet');
        $this->assertFalse($query->hasSeenTweet(999), 'Should not detect unseen tweet');

        $query->userFeatures = new \XAlgorithm\Core\DataStructures\UserFeatures([
            'muted_keywords' => ['spam', 'ads'],
        ]);
        $this->assertTrue($query->isMutedKeyword('This is spam'), 'Should detect muted keyword');
        $this->assertFalse($query->isMutedKeyword('Good content'), 'Should not detect good content');

        echo "  ✓ ScoredPostsQuery tests passed\n\n";
    }

    private function testAgeFilter(): void
    {
        echo "Testing AgeFilter...\n";

        $filter = new AgeFilter(86400);
        $this->assertEquals('AgeFilter', $filter->getName(), 'Filter name should match');

        $query = new ScoredPostsQuery(['user_id' => 123]);
        $now = (int)(microtime(true) * 1000);
        $epoch = 1288834974657;
        $recentTweetId = (($now - $epoch) << 22) | (1 << 17) | (1 << 12);
        $oldTweetId = ((($now - 86400000 * 10) - $epoch) << 22) | (1 << 17) | (1 << 12);

        $recentCandidate = new PostCandidate(['tweet_id' => $recentTweetId]);
        $oldCandidate = new PostCandidate(['tweet_id' => $oldTweetId]);

        $result = $filter->filter($query, [$recentCandidate, $oldCandidate]);

        $this->assertCount(1, $result->kept, 'Should keep only recent tweet');
        $this->assertCount(1, $result->removed, 'Should remove only old tweet');

        echo "  ✓ AgeFilter tests passed\n\n";
    }

    private function testDropDuplicatesFilter(): void
    {
        echo "Testing DropDuplicatesFilter...\n";

        $filter = new DropDuplicatesFilter();
        $this->assertEquals('DropDuplicatesFilter', $filter->getName(), 'Filter name should match');

        $query = new ScoredPostsQuery(['user_id' => 123, 'seen_ids' => [100, 200]]);

        $c1 = new PostCandidate(['tweet_id' => 100]);
        $c2 = new PostCandidate(['tweet_id' => 300]);
        $c3 = new PostCandidate(['tweet_id' => 100]);
        $c4 = new PostCandidate(['tweet_id' => 400]);

        $result = $filter->filter($query, [$c1, $c2, $c3, $c4]);

        $this->assertCount(2, $result->kept, 'Should keep 2 unique candidates');
        $this->assertCount(2, $result->removed, 'Should remove 2 duplicates');

        echo "  ✓ DropDuplicatesFilter tests passed\n\n";
    }

    private function testMutedKeywordFilter(): void
    {
        echo "Testing MutedKeywordFilter...\n";

        $filter = new MutedKeywordFilter();
        $this->assertEquals('MutedKeywordFilter', $filter->getName(), 'Filter name should match');

        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'user_features' => [
                'muted_keywords' => ['spam', 'promo'],
            ],
        ]);

        $good = new PostCandidate(['tweet_id' => 1, 'tweet_text' => 'Great content!']);
        $bad = new PostCandidate(['tweet_id' => 2, 'tweet_text' => 'Buy now! This is spam!']);
        $alsoBad = new PostCandidate(['tweet_id' => 3, 'tweet_text' => 'Special promo offer']);

        $result = $filter->filter($query, [$good, $bad, $alsoBad]);

        $this->assertCount(1, $result->kept, 'Should keep only good content');
        $this->assertCount(2, $result->removed, 'Should remove muted content');

        echo "  ✓ MutedKeywordFilter tests passed\n\n";
    }

    private function testWeightedScorer(): void
    {
        echo "Testing WeightedScorer...\n";

        $scorer = new WeightedScorer();
        $this->assertEquals('WeightedScorer', $scorer->getName(), 'Scorer name should match');

        $query = new ScoredPostsQuery(['user_id' => 123]);
        $scores = new PhoenixScores([
            'favoriteScore' => 0.5,
            'replyScore' => 0.5,
            'retweetScore' => 0.5,
            'shareScore' => 0.5,
            'dwellScore' => 0.5,
            'quoteScore' => 0.5,
            'clickScore' => 0.5,
            'profileClickScore' => 0.5,
        ]);

        $candidate = new PostCandidate([
            'tweet_id' => 1,
            'author_id' => 111,
            'tweet_text' => 'Test',
            'phoenix_scores' => $scores->toArray(),
        ]);

        $result = $scorer->score($query, [$candidate]);

        $this->assertCount(1, $result, 'Should return scored candidates');
        $this->assertNotNull($result[0]->weightedScore, 'Should have weighted score');
        $this->assertTrue($result[0]->weightedScore > 0, 'Weighted score should be positive');

        echo "  ✓ WeightedScorer tests passed\n\n";
    }

    private function testAuthorDiversityScorer(): void
    {
        echo "Testing AuthorDiversityScorer...\n";

        $scorer = new AuthorDiversityScorer(0.8);
        $this->assertEquals('AuthorDiversityScorer', $scorer->getName(), 'Scorer name should match');

        $query = new ScoredPostsQuery(['user_id' => 123]);

        $candidates = [];
        for ($i = 0; $i < 4; $i++) {
            $candidates[] = new PostCandidate([
                'tweet_id' => $i + 1,
                'author_id' => 100,
                'tweet_text' => "Tweet $i",
                'weighted_score' => 0.5,
            ]);
        }

        $result = $scorer->score($query, $candidates);

        $this->assertCount(4, $result, 'Should return all candidates');
        $this->assertTrue(
            $result[0]->weightedScore > $result[3]->weightedScore,
            'First author should have higher score due to decay'
        );

        echo "  ✓ AuthorDiversityScorer tests passed\n\n";
    }

    private function testHomeMixerService(): void
    {
        echo "Testing HomeMixerService...\n";

        $service = new HomeMixerService([
            'default_limit' => 10,
            'max_age_seconds' => 86400 * 7,
            'enable_diversity' => true,
            'diversity_decay_factor' => 0.9,
            'oon_boost_factor' => 1.0,
        ]);

        $this->assertEquals('HomeMixerService', $service->getName(), 'Service name should match');
        $this->assertIsArray($service->getConfig(), 'Should have config');
        $this->assertEquals(10, $service->getConfig()['default_limit'], 'Config should be set');

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

        $this->assertArrayHasKey('candidates', $recommendations, 'Should have candidates');
        $this->assertArrayHasKey('request_id', $recommendations, 'Should have request ID');
        $this->assertArrayHasKey('count', $recommendations, 'Should have count');
        $this->assertArrayHasKey('pipeline_stats', $recommendations, 'Should have stats');

        echo "  ✓ HomeMixerService tests passed\n\n";
    }

    private function assertEquals($expected, $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            $this->fail("$message: Expected " . var_export($expected, true) . " but got " . var_export($actual, true));
        } else {
            $this->passed++;
        }
    }

    private function assertTrue($condition, string $message = ''): void
    {
        if ($condition !== true) {
            $this->fail($message ?: 'Expected true but got false');
        } else {
            $this->passed++;
        }
    }

    private function assertFalse($condition, string $message = ''): void
    {
        if ($condition !== false) {
            $this->fail($message ?: 'Expected false but got true');
        } else {
            $this->passed++;
        }
    }

    private function assertEmpty($value, string $message = ''): void
    {
        if (!empty($value)) {
            $this->fail($message ?: 'Expected empty value');
        } else {
            $this->passed++;
        }
    }

    private function assertNotEmpty($value, string $message = ''): void
    {
        if (empty($value)) {
            $this->fail($message ?: 'Expected non-empty value');
        } else {
            $this->passed++;
        }
    }

    private function assertCount(int $expected, array $array, string $message = ''): void
    {
        $actual = count($array);
        if ($expected !== $actual) {
            $this->fail("$message: Expected count $expected but got $actual");
        } else {
            $this->passed++;
        }
    }

    private function assertArrayHasKey($key, array $array, string $message = ''): void
    {
        if (!array_key_exists($key, $array)) {
            $this->fail($message ?: "Expected array to have key '$key'");
        } else {
            $this->passed++;
        }
    }

    private function assertIsArray($value, string $message = ''): void
    {
        if (!is_array($value)) {
            $this->fail($message ?: 'Expected array');
        } else {
            $this->passed++;
        }
    }

    private function assertNull($value, string $message = ''): void
    {
        if ($value !== null) {
            $this->fail($message ?: 'Expected null');
        } else {
            $this->passed++;
        }
    }

    private function assertNotNull($value, string $message = ''): void
    {
        if ($value === null) {
            $this->fail($message ?: 'Expected non-null value');
        } else {
            $this->passed++;
        }
    }

    private function fail(string $message): void
    {
        $this->failed++;
        $this->errors[] = $message;
        echo "  ✗ $message\n";
    }

    private function printSummary(): void
    {
        echo "\n=== Test Summary ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        echo "Total: " . ($this->passed + $this->failed) . "\n";

        if ($this->failed > 0) {
            echo "\nErrors:\n";
            foreach ($this->errors as $error) {
                echo "  - $error\n";
            }
            exit(1);
        }

        echo "\n✓ All tests passed!\n";
    }
}

$test = new AlgorithmTest();
$test->run();
