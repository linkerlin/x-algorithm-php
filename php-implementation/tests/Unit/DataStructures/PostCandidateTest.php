<?php

declare(strict_types=1);

namespace XAlgorithm\Tests\Unit\DataStructures;

use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\DataStructures\PhoenixScores;
use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\UserFeatures;
use XAlgorithm\Core\DataStructures\UserActionItem;
use XAlgorithm\Core\DataStructures\UserActionSequence;
use XAlgorithm\VisibilityFiltering\Models\FilteredReason;
use XAlgorithm\Utility\RequestIdGenerator;
use XAlgorithm\Core\Filters\AgeFilter;
use XAlgorithm\Core\Filters\DropDuplicatesFilter;
use XAlgorithm\Core\Filters\MutedKeywordFilter;
use XAlgorithm\Core\Scorers\WeightedScorer;
use XAlgorithm\Core\Scorers\AuthorDiversityScorer;
use XAlgorithm\Core\Selectors\TopKScoreSelector;
use XAlgorithm\Core\Sources\ThunderSource;
use XAlgorithm\Core\Sources\PhoenixSource;
use XAlgorithm\Core\Hydrators\CoreDataCandidateHydrator;
use XAlgorithm\Core\Pipeline\CandidatePipeline;
use XAlgorithm\HomeMixer\HomeMixerService;
use XAlgorithm\ML\Phoenix\MockPhoenixClient;
use XAlgorithm\Algorithm;

class PostCandidateTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructorWithBasicData(): void
    {
        $candidate = new PostCandidate([
            'tweet_id' => 1234567890,
            'author_id' => 111,
            'tweet_text' => 'Hello World',
        ]);

        $this->assertEquals(1234567890, $candidate->tweetId);
        $this->assertEquals(111, $candidate->authorId);
        $this->assertEquals('Hello World', $candidate->tweetText);
    }

    public function testConstructorWithNullValues(): void
    {
        $candidate = new PostCandidate([
            'tweet_id' => 1,
            'author_id' => 100,
            'tweet_text' => 'Test',
            'in_reply_to_tweet_id' => null,
            'retweeted_tweet_id' => null,
            'retweeted_user_id' => null,
        ]);

        $this->assertNull($candidate->inReplyToTweetId);
        $this->assertNull($candidate->retweetedTweetId);
        $this->assertNull($candidate->retweetedUserId);
    }

    public function testConstructorWithPhoenixScores(): void
    {
        $scores = new PhoenixScores([
            'favoriteScore' => 0.5,
            'replyScore' => 0.3,
            'retweetScore' => 0.2,
        ]);

        $candidate = new PostCandidate([
            'tweet_id' => 1,
            'author_id' => 100,
            'tweet_text' => 'Test',
            'phoenix_scores' => $scores->toArray(),
        ]);

        $this->assertInstanceOf(PhoenixScores::class, $candidate->phoenixScores);
        $this->assertEquals(0.5, $candidate->phoenixScores->favoriteScore);
    }

    public function testGetScreenNamesReturnsEmptyInitially(): void
    {
        $candidate = new PostCandidate([
            'tweet_id' => 1,
            'author_id' => 100,
            'tweet_text' => 'Test',
        ]);

        $this->assertEmpty($candidate->getScreenNames());
    }

    public function testGetScreenNamesWithAuthor(): void
    {
        $candidate = new PostCandidate([
            'tweet_id' => 1,
            'author_id' => 100,
            'tweet_text' => 'Test',
            'author_screen_name' => 'testuser',
        ]);

        $screenNames = $candidate->getScreenNames();
        $this->assertArrayHasKey(100, $screenNames);
        $this->assertEquals('testuser', $screenNames[100]);
    }

    public function testGetScreenNamesWithRetweetedAuthor(): void
    {
        $candidate = new PostCandidate([
            'tweet_id' => 1,
            'author_id' => 100,
            'tweet_text' => 'RT',
            'retweeted_user_id' => 200,
            'retweeted_screen_name' => 'originaluser',
        ]);

        $screenNames = $candidate->getScreenNames();
        $this->assertArrayHasKey(200, $screenNames);
        $this->assertEquals('originaluser', $screenNames[200]);
    }

    public function testGetLookupTweetIdReturnsOwnId(): void
    {
        $candidate = new PostCandidate([
            'tweet_id' => 100,
            'author_id' => 50,
        ]);

        $this->assertEquals(100, $candidate->getLookupTweetId());
    }

    public function testGetLookupTweetIdReturnsRetweetedId(): void
    {
        $candidate = new PostCandidate([
            'tweet_id' => 100,
            'author_id' => 50,
            'retweeted_tweet_id' => 200,
        ]);

        $this->assertEquals(200, $candidate->getLookupTweetId());
    }

    public function testGetLookupAuthorIdReturnsOwnId(): void
    {
        $candidate = new PostCandidate([
            'tweet_id' => 100,
            'author_id' => 50,
        ]);

        $this->assertEquals(50, $candidate->getLookupAuthorId());
    }

    public function testGetLookupAuthorIdReturnsRetweetedUserId(): void
    {
        $candidate = new PostCandidate([
            'tweet_id' => 100,
            'author_id' => 50,
            'retweeted_user_id' => 60,
        ]);

        $this->assertEquals(60, $candidate->getLookupAuthorId());
    }

    public function testToArray(): void
    {
        $candidate = new PostCandidate([
            'tweet_id' => 1,
            'author_id' => 100,
            'tweet_text' => 'Test',
            'weighted_score' => 0.5,
        ]);

        $array = $candidate->toArray();

        $this->assertArrayHasKey('tweet_id', $array);
        $this->assertArrayHasKey('author_id', $array);
        $this->assertArrayHasKey('tweet_text', $array);
        $this->assertArrayHasKey('weighted_score', $array);
        $this->assertEquals(1, $array['tweet_id']);
        $this->assertEquals(0.5, $array['weighted_score']);
    }

    public function testFromArray(): void
    {
        $data = [
            'tweet_id' => 123,
            'author_id' => 456,
            'tweet_text' => 'Test Tweet',
        ];

        $candidate = PostCandidate::fromArray($data);

        $this->assertEquals(123, $candidate->tweetId);
        $this->assertEquals(456, $candidate->authorId);
        $this->assertEquals('Test Tweet', $candidate->tweetText);
    }

    public function testWithAllOptionalFields(): void
    {
        $candidate = new PostCandidate([
            'tweet_id' => 1,
            'author_id' => 100,
            'tweet_text' => 'Full Test',
            'in_reply_to_tweet_id' => 50,
            'retweeted_tweet_id' => 60,
            'retweeted_user_id' => 70,
            'weighted_score' => 0.75,
            'score' => 0.8,
            'served_type' => 1,
            'in_network' => true,
            'video_duration_ms' => 30000,
            'author_followers_count' => 1000,
            'author_screen_name' => 'testuser',
        ]);

        $this->assertEquals(50, $candidate->inReplyToTweetId);
        $this->assertEquals(60, $candidate->retweetedTweetId);
        $this->assertEquals(70, $candidate->retweetedUserId);
        $this->assertEquals(0.75, $candidate->weightedScore);
        $this->assertEquals(0.8, $candidate->score);
        $this->assertEquals(1, $candidate->servedType);
        $this->assertTrue($candidate->inNetwork);
        $this->assertEquals(30000, $candidate->videoDurationMs);
        $this->assertEquals(1000, $candidate->authorFollowersCount);
        $this->assertEquals('testuser', $candidate->authorScreenName);
    }
}
