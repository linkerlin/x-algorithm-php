<?php

declare(strict_types=1);

namespace XAlgorithm\Tests\Unit\Filters;

use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\Filters\AuthorSocialgraphFilter;

class AuthorSocialgraphFilterTest extends \PHPUnit\Framework\TestCase
{
    public function testGetName(): void
    {
        $filter = new AuthorSocialgraphFilter();

        $this->assertEquals('AuthorSocialgraphFilter', $filter->getName());
    }

    public function testFilterRemovesBlockedAuthors(): void
    {
        $filter = new AuthorSocialgraphFilter();
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'user_features' => [
                'blocked_authors' => [100, 200],
            ],
        ]);

        $normalAuthor = new PostCandidate(['tweet_id' => 1, 'author_id' => 50]);
        $blockedAuthor1 = new PostCandidate(['tweet_id' => 2, 'author_id' => 100]);
        $blockedAuthor2 = new PostCandidate(['tweet_id' => 3, 'author_id' => 200]);

        $result = $filter->filter($query, [$normalAuthor, $blockedAuthor1, $blockedAuthor2]);

        $this->assertCount(1, $result->kept);
        $this->assertCount(2, $result->removed);
        $this->assertEquals(50, $result->kept[0]->authorId);
    }

    public function testFilterWithNoBlockedAuthors(): void
    {
        $filter = new AuthorSocialgraphFilter();
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'user_features' => [
                'blocked_authors' => [],
            ],
        ]);

        $author1 = new PostCandidate(['tweet_id' => 1, 'author_id' => 50]);
        $author2 = new PostCandidate(['tweet_id' => 2, 'author_id' => 60]);

        $result = $filter->filter($query, [$author1, $author2]);

        $this->assertCount(2, $result->kept);
        $this->assertCount(0, $result->removed);
    }

    public function testFilterWithEmptyCandidates(): void
    {
        $filter = new AuthorSocialgraphFilter();
        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'user_features' => [
                'blocked_authors' => [100],
            ],
        ]);

        $result = $filter->filter($query, []);

        $this->assertCount(0, $result->kept);
        $this->assertCount(0, $result->removed);
    }
}
