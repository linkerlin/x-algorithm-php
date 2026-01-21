<?php

declare(strict_types=1);

namespace XAlgorithm\Tests\Unit\DataStructures;

use XAlgorithm\Core\DataStructures\UserFeatures;

class UserFeaturesTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructorWithAllFields(): void
    {
        $features = new UserFeatures([
            'following_list' => [100, 200, 300],
            'preferences' => ['sports', 'tech'],
            'muted_keywords' => ['spam'],
            'blocked_authors' => [500],
            'account_age_days' => 365,
            'follower_count' => 1000,
            'following_count' => 500,
            'is_verified' => true,
            'language_code' => 'en',
            'country_code' => 'US',
        ]);

        $this->assertEquals([100, 200, 300], $features->followingList);
        $this->assertEquals(['sports', 'tech'], $features->preferences);
        $this->assertEquals(['spam'], $features->mutedKeywords);
        $this->assertEquals([500], $features->blockedAuthors);
        $this->assertEquals(365, $features->accountAgeDays);
        $this->assertEquals(1000, $features->followerCount);
        $this->assertEquals(500, $features->followingCount);
        $this->assertTrue($features->isVerified);
        $this->assertEquals('en', $features->languageCode);
        $this->assertEquals('US', $features->countryCode);
    }

    public function testConstructorWithEmptyData(): void
    {
        $features = new UserFeatures();

        $this->assertEmpty($features->followingList);
        $this->assertEmpty($features->preferences);
        $this->assertEmpty($features->mutedKeywords);
        $this->assertEmpty($features->blockedAuthors);
        $this->assertEquals(0, $features->accountAgeDays);
        $this->assertEquals(0, $features->followerCount);
        $this->assertFalse($features->isVerified);
        $this->assertEquals('', $features->languageCode);
        $this->assertEquals('', $features->countryCode);
    }

    public function testToArray(): void
    {
        $features = new UserFeatures([
            'following_list' => [100, 200],
            'account_age_days' => 100,
        ]);

        $array = $features->toArray();

        $this->assertArrayHasKey('following_list', $array);
        $this->assertArrayHasKey('account_age_days', $array);
        $this->assertEquals([100, 200], $array['following_list']);
        $this->assertEquals(100, $array['account_age_days']);
    }

    public function testFromArray(): void
    {
        $data = [
            'following_list' => [1, 2, 3],
            'preferences' => ['news'],
        ];

        $features = UserFeatures::fromArray($data);

        $this->assertEquals([1, 2, 3], $features->followingList);
        $this->assertEquals(['news'], $features->preferences);
    }
}
