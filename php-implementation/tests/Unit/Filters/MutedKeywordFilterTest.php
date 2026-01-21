<?php

declare(strict_types=1);

namespace XAlgorithm\Tests\Unit\Filters;

use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\Filters\MutedKeywordFilter;

class MutedKeywordFilterTest extends \PHPUnit\Framework\TestCase
{
    public function testGetName(): void
    {
        $filter = new MutedKeywordFilter();

        $this->assertEquals('MutedKeywordFilter', $filter->getName());
    }

    public function testFilterRemovesMutedKeywords(): void
    {
        $filter = new MutedKeywordFilter();
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'user_features' => [
                'muted_keywords' => ['spam', 'ads', 'promo'],
            ],
        ]);

        $goodContent = new PostCandidate(['tweet_id' => 1, 'tweet_text' => 'Good content here']);
        $spamContent = new PostCandidate(['tweet_id' => 2, 'tweet_text' => 'Buy now! This is spam!']);
        $adsContent = new PostCandidate(['tweet_id' => 3, 'tweet_text' => 'Special promo offer']);

        $result = $filter->filter($query, [$goodContent, $spamContent, $adsContent]);

        $this->assertCount(1, $result->kept);
        $this->assertCount(2, $result->removed);
        $this->assertEquals(1, $result->kept[0]->tweetId);
    }

    public function testFilterIsCaseInsensitive(): void
    {
        $filter = new MutedKeywordFilter();
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'user_features' => [
                'muted_keywords' => ['spam'],
            ],
        ]);

        $lowerCase = new PostCandidate(['tweet_id' => 1, 'tweet_text' => 'this is spam']);
        $upperCase = new PostCandidate(['tweet_id' => 2, 'tweet_text' => 'THIS IS SPAM']);
        $mixedCase = new PostCandidate(['tweet_id' => 3, 'tweet_text' => 'SpAm HeRe']);

        $result = $filter->filter($query, [$lowerCase, $upperCase, $mixedCase]);

        $this->assertCount(0, $result->kept);
        $this->assertCount(3, $result->removed);
    }

    public function testFilterKeepsCleanContent(): void
    {
        $filter = new MutedKeywordFilter();
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'user_features' => [
                'muted_keywords' => ['spam'],
            ],
        ]);

        $cleanContent = new PostCandidate(['tweet_id' => 1, 'tweet_text' => 'Good clean content']);

        $result = $filter->filter($query, [$cleanContent]);

        $this->assertCount(1, $result->kept);
        $this->assertCount(0, $result->removed);
    }

    public function testFilterWithNoMutedKeywords(): void
    {
        $filter = new MutedKeywordFilter();
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'user_features' => [
                'muted_keywords' => [],
            ],
        ]);

        $content = new PostCandidate(['tweet_id' => 1, 'tweet_text' => 'This is spam but not muted']);

        $result = $filter->filter($query, [$content]);

        $this->assertCount(1, $result->kept);
    }

    public function testFilterWithEmptyCandidates(): void
    {
        $filter = new MutedKeywordFilter();
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'user_features' => [
                'muted_keywords' => ['spam'],
            ],
        ]);

        $result = $filter->filter($query, []);

        $this->assertCount(0, $result->kept);
        $this->assertCount(0, $result->removed);
    }
}
