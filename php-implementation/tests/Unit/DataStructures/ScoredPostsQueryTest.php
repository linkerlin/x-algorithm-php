<?php

declare(strict_types=1);

namespace XAlgorithm\Tests\Unit\DataStructures;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\UserFeatures;
use XAlgorithm\Core\DataStructures\UserActionSequence;

class ScoredPostsQueryTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructorWithBasicData(): void
    {
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'client_app_id' => 1,
            'country_code' => 'US',
            'language_code' => 'en',
        ]);

        $this->assertEquals(123, $query->userId);
        $this->assertEquals(1, $query->clientAppId);
        $this->assertEquals('US', $query->countryCode);
        $this->assertEquals('en', $query->languageCode);
    }

    public function testConstructorWithDefaultValues(): void
    {
        $query = new ScoredPostsQuery();

        $this->assertEquals(0, $query->userId);
        $this->assertEquals(0, $query->clientAppId);
        $this->assertEquals('US', $query->countryCode);
        $this->assertEquals('en', $query->languageCode);
        $this->assertEmpty($query->seenIds);
        $this->assertEmpty($query->servedIds);
        $this->assertFalse($query->inNetworkOnly);
        $this->assertFalse($query->isBottomRequest);
    }

    public function testConstructorWithSeenIds(): void
    {
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'seen_ids' => [100, 200, 300],
        ]);

        $this->assertCount(3, $query->seenIds);
        $this->assertContains(100, $query->seenIds);
        $this->assertContains(200, $query->seenIds);
        $this->assertContains(300, $query->seenIds);
    }

    public function testHasSeenTweetReturnsTrue(): void
    {
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'seen_ids' => [100, 200, 300],
        ]);

        $this->assertTrue($query->hasSeenTweet(100));
        $this->assertTrue($query->hasSeenTweet(200));
        $this->assertTrue($query->hasSeenTweet(300));
    }

    public function testHasSeenTweetReturnsFalse(): void
    {
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'seen_ids' => [100, 200, 300],
        ]);

        $this->assertFalse($query->hasSeenTweet(400));
        $this->assertFalse($query->hasSeenTweet(999));
    }

    public function testHasSeenTweetWithEmptySeenIds(): void
    {
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $this->assertFalse($query->hasSeenTweet(100));
    }

    public function testIsMutedKeywordWithMatch(): void
    {
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'user_features' => [
                'muted_keywords' => ['spam', 'ads', 'promo'],
            ],
        ]);

        $this->assertTrue($query->isMutedKeyword('This is spam'));
        $this->assertTrue($query->isMutedKeyword('Buy ads now'));
        $this->assertTrue($query->isMutedKeyword('Special promo offer'));
    }

    public function testIsMutedKeywordWithNoMatch(): void
    {
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'user_features' => [
                'muted_keywords' => ['spam'],
            ],
        ]);

        $this->assertFalse($query->isMutedKeyword('Good content here'));
        $this->assertFalse($query->isMutedKeyword('Interesting article'));
    }

    public function testIsMutedKeywordCaseInsensitive(): void
    {
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'user_features' => [
                'muted_keywords' => ['spam'],
            ],
        ]);

        $this->assertTrue($query->isMutedKeyword('SPAM in caps'));
        $this->assertTrue($query->isMutedKeyword('SpAm mixed case'));
    }

    public function testIsMutedKeywordWithEmptyFeatures(): void
    {
        $query = new ScoredPostsQuery(['user_id' => 123]);

        $this->assertFalse($query->isMutedKeyword('This is spam'));
    }

    public function testIsBlockedAuthorWithMatch(): void
    {
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'user_features' => [
                'blocked_authors' => [100, 200],
            ],
        ]);

        $this->assertTrue($query->isBlockedAuthor(100));
        $this->assertTrue($query->isBlockedAuthor(200));
    }

    public function testIsBlockedAuthorWithNoMatch(): void
    {
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'user_features' => [
                'blocked_authors' => [100],
            ],
        ]);

        $this->assertFalse($query->isBlockedAuthor(200));
        $this->assertFalse($query->isBlockedAuthor(999));
    }

    public function testToArray(): void
    {
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'client_app_id' => 1,
            'country_code' => 'US',
            'language_code' => 'en',
            'seen_ids' => [100, 200],
        ]);

        $array = $query->toArray();

        $this->assertArrayHasKey('user_id', $array);
        $this->assertArrayHasKey('client_app_id', $array);
        $this->assertArrayHasKey('country_code', $array);
        $this->assertArrayHasKey('language_code', $array);
        $this->assertArrayHasKey('seen_ids', $array);
        $this->assertEquals(123, $array['user_id']);
        $this->assertEquals([100, 200], $array['seen_ids']);
    }

    public function testRequestIdGeneration(): void
    {
        $query1 = new ScoredPostsQuery(['user_id' => 123]);
        $query2 = new ScoredPostsQuery(['user_id' => 123]);

        $this->assertNotEmpty($query1->requestId);
        $this->assertNotEmpty($query2->requestId);
    }

    public function testCustomRequestId(): void
    {
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'request_id' => 'custom-request-id',
        ]);

        $this->assertEquals('custom-request-id', $query->requestId);
    }

    public function testBloomFilterEntries(): void
    {
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'bloom_filter_entries' => ['entry1', 'entry2'],
        ]);

        $this->assertCount(2, $query->bloomFilterEntries);
        $this->assertContains('entry1', $query->bloomFilterEntries);
    }
}
