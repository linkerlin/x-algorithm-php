<?php

declare(strict_types=1);

require_once __DIR__ . '/../autoload.php';

use XAlgorithm\Core\DataStructures\UserFeatures;
use XAlgorithm\Core\DataStructures\UserActionItem;
use XAlgorithm\Core\DataStructures\UserActionSequence;
use XAlgorithm\Core\Filters\AgeFilter;
use XAlgorithm\Core\Filters\AuthorSocialgraphFilter;
use XAlgorithm\Core\Filters\DedupConversationFilter;
use XAlgorithm\Core\Filters\DropDuplicatesFilter;
use XAlgorithm\Core\Filters\FilterManager;
use XAlgorithm\Core\Filters\IneligibleSubscriptionFilter;
use XAlgorithm\Core\Filters\MutedKeywordFilter;
use XAlgorithm\Core\Filters\PreviouslySeenPostsFilter;
use XAlgorithm\Core\Filters\PreviouslyServedPostsFilter;
use XAlgorithm\Core\Filters\RetweetDeduplicationFilter;
use XAlgorithm\Core\Filters\SelfTweetFilter;
use XAlgorithm\VisibilityFiltering\Models\FilteredReason;
use XAlgorithm\Core\Hydrators\CoreDataCandidateHydrator;
use XAlgorithm\Core\Pipeline\CandidatePipeline;
use XAlgorithm\Core\Scorers\AuthorDiversityScorer;
use XAlgorithm\Core\Scorers\OONScorer;
use XAlgorithm\Core\Scorers\PhoenixScorer;
use XAlgorithm\Core\Scorers\WeightedScorer;
use XAlgorithm\Core\Selectors\TopKScoreSelector;
use XAlgorithm\Core\Sources\PhoenixSource;
use XAlgorithm\Core\Sources\ThunderSource;
use XAlgorithm\HomeMixer\HomeMixerService;
use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\DataStructures\PhoenixScores;
use XAlgorithm\Utility\RequestIdGenerator;
use XAlgorithm\ML\Phoenix\MockPhoenixClient;
use XAlgorithm\ML\Phoenix\PhoenixClientInterface;

class ComprehensiveTest
{
    private int $passed = 0;
    private int $failed = 0;
    private array $errors = [];

    public function run(): void
    {
        echo "=== X Algorithm Comprehensive Test Suite ===\n\n";

        $this->testRequestIdGenerator();
        $this->testPostCandidate();
        $this->testPhoenixScores();
        $this->testScoredPostsQuery();
        $this->testUserFeatures();
        $this->testUserActionSequence();
        $this->testFilteredReason();
        $this->testAgeFilter();
        $this->testAuthorSocialgraphFilter();
        $this->testDedupConversationFilter();
        $this->testDropDuplicatesFilter();
        $this->testFilterManager();
        $this->testIneligibleSubscriptionFilter();
        $this->testMutedKeywordFilter();
        $this->testPreviouslySeenPostsFilter();
        $this->testPreviouslyServedPostsFilter();
        $this->testRetweetDeduplicationFilter();
        $this->testSelfTweetFilter();
        $this->testWeightedScorer();
        $this->testAuthorDiversityScorer();
        $this->testOONScorer();
        $this->testPhoenixScorer();
        $this->testTopKScoreSelector();
        $this->testThunderSource();
        $this->testPhoenixSource();
        $this->testCoreDataCandidateHydrator();
        $this->testCandidatePipeline();
        $this->testHomeMixerService();
        $this->testMockPhoenixClient();
        $this->testAlgorithm();

        $this->printSummary();
    }

    private function testRequestIdGenerator(): void
    {
        echo "Testing RequestIdGenerator...\n";

        $id1 = RequestIdGenerator::generate(123);
        $this->assertNotEmpty($id1, 'Generated ID should not be empty');
        $this->assertTrue(strlen($id1) > 10, 'ID should be reasonably long');

        $snowflake = RequestIdGenerator::generateSnowflake();
        $this->assertTrue($snowflake > 0, 'Snowflake ID should be positive');
        $timestamp = RequestIdGenerator::getTimestampFromSnowflake($snowflake);
        $this->assertTrue($timestamp > 0, 'Should extract timestamp from snowflake');
        $this->assertTrue($timestamp <= (int)(microtime(true) * 1000), 'Timestamp should be current or past');

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
        $this->assertArrayHasKey('author_id', $array, 'Should export author ID');
        $this->assertArrayHasKey('tweet_text', $array, 'Should export text');

        $this->assertEquals(1234567890, $candidate->getLookupTweetId(), 'Lookup tweet ID should match');
        $this->assertEquals(111, $candidate->getLookupAuthorId(), 'Lookup author ID should match');

        $retweeted = new PostCandidate([
            'tweet_id' => 100,
            'author_id' => 200,
            'retweeted_tweet_id' => 50,
            'retweeted_user_id' => 60,
        ]);
        $this->assertEquals(50, $retweeted->getLookupTweetId(), 'Should use retweeted tweet ID');
        $this->assertEquals(60, $retweeted->getLookupAuthorId(), 'Should use retweeted user ID');

        $candidateWithScores = new PostCandidate([
            'tweet_id' => 200,
            'author_id' => 201,
            'phoenix_scores' => ['favoriteScore' => 0.5, 'replyScore' => 0.3],
        ]);
        $this->assertNotNull($candidateWithScores->phoenixScores, 'Should have phoenix scores');

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
        $this->assertEquals(0.5, $array['favorite_score'], 'Snake case key should have correct value');

        $scoresFromArray = PhoenixScores::fromArray([
            'favorite_score' => 0.8,
            'reply_score' => 0.4,
        ]);
        $this->assertEquals(0.8, $scoresFromArray->favoriteScore, 'Should create from array');

        $emptyScores = new PhoenixScores();
        $this->assertNull($emptyScores->favoriteScore, 'Empty scores should have null values');

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

        $query->userFeatures = new UserFeatures([
            'muted_keywords' => ['spam', 'ads'],
        ]);
        $this->assertTrue($query->isMutedKeyword('This is spam'), 'Should detect muted keyword');
        $this->assertFalse($query->isMutedKeyword('Good content'), 'Should not detect good content');

        $query->userFeatures = new UserFeatures([
            'blocked_authors' => [100, 200],
        ]);
        $this->assertTrue($query->isBlockedAuthor(100), 'Should detect blocked author');
        $this->assertFalse($query->isBlockedAuthor(999), 'Should not detect unblocked author');

        $array = $query->toArray();
        $this->assertArrayHasKey('user_id', $array, 'Should export user ID');
        $this->assertArrayHasKey('country_code', $array, 'Should export country code');

        echo "  ✓ ScoredPostsQuery tests passed\n\n";
    }

    private function testUserFeatures(): void
    {
        echo "Testing UserFeatures...\n";

        $features = new UserFeatures([
            'following_list' => [100, 200, 300],
            'preferences' => ['sports', 'tech'],
            'muted_keywords' => ['spam'],
            'blocked_authors' => [500],
            'account_age_days' => 365,
            'follower_count' => 1000,
            'is_verified' => true,
            'language_code' => 'en',
            'country_code' => 'US',
        ]);

        $this->assertCount(3, $features->followingList, 'Should have following list');
        $this->assertCount(2, $features->preferences, 'Should have preferences');
        $this->assertCount(1, $features->mutedKeywords, 'Should have muted keywords');
        $this->assertCount(1, $features->blockedAuthors, 'Should have blocked authors');
        $this->assertEquals(365, $features->accountAgeDays, 'Account age should match');
        $this->assertEquals(1000, $features->followerCount, 'Follower count should match');
        $this->assertTrue($features->isVerified, 'Should be verified');
        $this->assertEquals('en', $features->languageCode, 'Language code should match');
        $this->assertEquals('US', $features->countryCode, 'Country code should match');

        $array = $features->toArray();
        $this->assertArrayHasKey('following_list', $array, 'Should export following list');
        $this->assertArrayHasKey('is_verified', $array, 'Should export verified status');

        $emptyFeatures = new UserFeatures();
        $this->assertEmpty($emptyFeatures->followingList, 'Empty features should have empty arrays');

        echo "  ✓ UserFeatures tests passed\n\n";
    }

    private function testUserActionSequence(): void
    {
        echo "Testing UserActionSequence...\n";

        $action1 = new UserActionItem([
            'tweet_id' => 100,
            'author_id' => 50,
            'action_type' => 1,
            'timestamp_ms' => 1000000,
            'product_surface' => 1,
        ]);
        $action2 = new UserActionItem([
            'tweet_id' => 200,
            'author_id' => 60,
            'action_type' => 2,
            'timestamp_ms' => 2000000,
            'product_surface' => 1,
        ]);

        $sequence = new UserActionSequence([
            'user_id' => 123,
            'actions' => [$action1->toArray(), $action2->toArray()],
            'created_at_ms' => 3000000,
        ]);

        $this->assertEquals(123, $sequence->userId, 'User ID should match');
        $this->assertCount(2, $sequence->actions, 'Should have 2 actions');
        $this->assertEquals(3000000, $sequence->createdAtMs, 'Created at should match');

        $recentActions = $sequence->getRecentActions(1);
        $this->assertCount(1, $recentActions, 'Should return 1 recent action');
        $this->assertEquals(2000000, $recentActions[0]->timestampMs, 'Most recent action should be first');

        $likeActions = $sequence->getActionsByType(1);
        $this->assertCount(1, $likeActions, 'Should find 1 like action');

        $array = $sequence->toArray();
        $this->assertArrayHasKey('user_id', $array, 'Should export user ID');
        $this->assertArrayHasKey('actions', $array, 'Should export actions');

        echo "  ✓ UserActionSequence tests passed\n\n";
    }

    private function testFilteredReason(): void
    {
        echo "Testing FilteredReason...\n";

        $this->assertEquals(0, FilteredReason::NONE, 'NONE should be 0');
        $this->assertEquals(1, FilteredReason::NOT_FILTERED, 'NOT_FILTERED should be 1');
        $this->assertEquals(2, FilteredReason::AGE, 'AGE should be 2');
        $this->assertEquals(3, FilteredReason::DUPLICATE, 'DUPLICATE should be 3');
        $this->assertEquals(4, FilteredReason::SELF_TWEET, 'SELF_TWEET should be 4');
        $this->assertEquals(5, FilteredReason::BLOCKED_AUTHOR, 'BLOCKED_AUTHOR should be 5');
        $this->assertEquals(6, FilteredReason::MUTED_KEYWORD, 'MUTED_KEYWORD should be 6');
        $this->assertEquals(7, FilteredReason::PREVIOUSLY_SEEN, 'PREVIOUSLY_SEEN should be 7');
        $this->assertEquals(8, FilteredReason::PREVIOUSLY_SERVED, 'PREVIOUSLY_SERVED should be 8');
        $this->assertEquals(9, FilteredReason::INELIGIBLE_SUBSCRIPTION, 'INELIGIBLE_SUBSCRIPTION should be 9');

        $reason = new FilteredReason(FilteredReason::AGE);
        $this->assertEquals(2, $reason->getValue(), 'Reason value should match');

        echo "  ✓ FilteredReason tests passed\n\n";
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

        $allRecent = $filter->filter($query, [$recentCandidate, $recentCandidate]);
        $this->assertCount(2, $allRecent->kept, 'Should keep all recent tweets');

        $allOld = $filter->filter($query, [$oldCandidate, $oldCandidate]);
        $this->assertCount(0, $allOld->kept, 'Should remove all old tweets');

        echo "  ✓ AgeFilter tests passed\n\n";
    }

    private function testAuthorSocialgraphFilter(): void
    {
        echo "Testing AuthorSocialgraphFilter...\n";

        $filter = new AuthorSocialgraphFilter();
        $this->assertEquals('AuthorSocialgraphFilter', $filter->getName(), 'Filter name should match');

        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'user_features' => [
                'blocked_authors' => [100, 200],
            ],
        ]);

        $c1 = new PostCandidate(['tweet_id' => 1, 'author_id' => 50]);
        $c2 = new PostCandidate(['tweet_id' => 2, 'author_id' => 100]);
        $c3 = new PostCandidate(['tweet_id' => 3, 'author_id' => 200]);

        $result = $filter->filter($query, [$c1, $c2, $c3]);

        $this->assertCount(1, $result->kept, 'Should keep only non-blocked author');
        $this->assertCount(2, $result->removed, 'Should remove blocked authors');
        $this->assertEquals(1, $result->kept[0]->tweetId, 'Should keep correct candidate');

        echo "  ✓ AuthorSocialgraphFilter tests passed\n\n";
    }

    private function testDedupConversationFilter(): void
    {
        echo "Testing DedupConversationFilter...\n";

        $filter = new DedupConversationFilter();
        $this->assertEquals('DedupConversationFilter', $filter->getName(), 'Filter name should match');

        $c1 = new PostCandidate(['tweet_id' => 1, 'in_reply_to_tweet_id' => 100]);
        $c2 = new PostCandidate(['tweet_id' => 2, 'in_reply_to_tweet_id' => 200]);
        $c3 = new PostCandidate(['tweet_id' => 3, 'in_reply_to_tweet_id' => 100]);

        $query = new ScoredPostsQuery(['user_id' => 123]);
        $result = $filter->filter($query, [$c1, $c2, $c3]);

        $this->assertCount(2, $result->kept, 'Should keep unique conversations');
        $this->assertCount(1, $result->removed, 'Should remove duplicate conversation');

        $noReplies = new PostCandidate(['tweet_id' => 4, 'in_reply_to_tweet_id' => 400]);
        $result2 = $filter->filter($query, [$c1, $noReplies]);
        $this->assertCount(2, $result2->kept, 'Should keep tweets without conversations');

        echo "  ✓ DedupConversationFilter tests passed\n\n";
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

    private function testFilterManager(): void
    {
        echo "Testing FilterManager...\n";

        $manager = new FilterManager();
        $this->assertEquals('FilterManager', $manager->getName(), 'Manager name should match');
        $this->assertCount(0, $manager->getFilters(), 'Should start with no filters');

        $ageFilter = new AgeFilter(86400);
        $manager->addFilter($ageFilter);
        $this->assertCount(1, $manager->getFilters(), 'Should have 1 filter');
        $this->assertTrue($manager->hasFilter('AgeFilter'), 'Should have age filter');

        $muteFilter = new MutedKeywordFilter();
        $manager->addFilter($muteFilter);
        $this->assertCount(2, $manager->getFilters(), 'Should have 2 filters');
        $this->assertTrue($manager->hasFilter('MutedKeywordFilter'), 'Should have mute filter');

        $manager->removeFilter('AgeFilter');
        $this->assertCount(1, $manager->getFilters(), 'Should have 1 filter after removal');
        $this->assertFalse($manager->hasFilter('AgeFilter'), 'Should not have age filter');
        $this->assertTrue($manager->hasFilter('MutedKeywordFilter'), 'Should still have mute filter');

        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'user_features' => ['muted_keywords' => ['spam']],
        ]);
        $now = (int)(microtime(true) * 1000);
        $epoch = 1288834974657;
        $recentTweetId = (($now - $epoch) << 22) | (1 << 17) | (1 << 12);
        $oldTweetId = ((($now - 86400000 * 10) - $epoch) << 22) | (1 << 17) | (1 << 12);

        $recentGood = new PostCandidate(['tweet_id' => $recentTweetId, 'tweet_text' => 'Good content']);
        $oldBad = new PostCandidate(['tweet_id' => $oldTweetId, 'tweet_text' => 'This is spam']);

        $result = $manager->filter($query, [$recentGood, $oldBad]);
        $this->assertCount(1, $result->kept, 'Should keep only valid candidate');

        echo "  ✓ FilterManager tests passed\n\n";
    }

    private function testIneligibleSubscriptionFilter(): void
    {
        echo "Testing IneligibleSubscriptionFilter...\n";

        $filter = new IneligibleSubscriptionFilter();
        $this->assertEquals('IneligibleSubscriptionFilter', $filter->getName(), 'Filter name should match');

        $query = new ScoredPostsQuery(['user_id' => 123]);

        $eligible = new PostCandidate(['tweet_id' => 1, 'subscription_author_id' => 100]);
        $ineligible = new PostCandidate(['tweet_id' => 2, 'subscription_author_id' => null]);

        $result = $filter->filter($query, [$eligible, $ineligible]);

        $this->assertCount(1, $result->kept, 'Should keep only eligible');
        $this->assertEquals(1, $result->kept[0]->tweetId, 'Should keep correct candidate');

        echo "  ✓ IneligibleSubscriptionFilter tests passed\n\n";
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

        $caseSensitive = new PostCandidate(['tweet_id' => 4, 'tweet_text' => 'This has SPAM in it']);
        $result2 = $filter->filter($query, [$caseSensitive]);
        $this->assertCount(0, $result2->kept, 'Should detect muted keyword (case insensitive)');

        $cleanContent = new PostCandidate(['tweet_id' => 5, 'tweet_text' => 'Good clean content here']);
        $result3 = $filter->filter($query, [$cleanContent]);
        $this->assertCount(1, $result3->kept, 'Should keep clean content');

        echo "  ✓ MutedKeywordFilter tests passed\n\n";
    }

    private function testPreviouslySeenPostsFilter(): void
    {
        echo "Testing PreviouslySeenPostsFilter...\n";

        $filter = new PreviouslySeenPostsFilter();
        $this->assertEquals('PreviouslySeenPostsFilter', $filter->getName(), 'Filter name should match');

        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'seen_ids' => [100, 200],
        ]);

        $seen1 = new PostCandidate(['tweet_id' => 100]);
        $seen2 = new PostCandidate(['tweet_id' => 200]);
        $new1 = new PostCandidate(['tweet_id' => 300]);
        $new2 = new PostCandidate(['tweet_id' => 400]);

        $result = $filter->filter($query, [$seen1, $new1, $seen2, $new2]);

        $this->assertCount(2, $result->kept, 'Should keep new tweets');
        $this->assertCount(2, $result->removed, 'Should remove seen tweets');

        echo "  ✓ PreviouslySeenPostsFilter tests passed\n\n";
    }

    private function testPreviouslyServedPostsFilter(): void
    {
        echo "Testing PreviouslyServedPostsFilter...\n";

        $filter = new PreviouslyServedPostsFilter();
        $this->assertEquals('PreviouslyServedPostsFilter', $filter->getName(), 'Filter name should match');

        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'served_ids' => [100, 200],
        ]);

        $served1 = new PostCandidate(['tweet_id' => 100]);
        $served2 = new PostCandidate(['tweet_id' => 200]);
        $new1 = new PostCandidate(['tweet_id' => 300]);

        $result = $filter->filter($query, [$served1, $new1, $served2]);

        $this->assertCount(1, $result->kept, 'Should keep unserved tweets');
        $this->assertCount(2, $result->removed, 'Should remove served tweets');

        echo "  ✓ PreviouslyServedPostsFilter tests passed\n\n";
    }

    private function testRetweetDeduplicationFilter(): void
    {
        echo "Testing RetweetDeduplicationFilter...\n";

        $filter = new RetweetDeduplicationFilter();
        $this->assertEquals('RetweetDeduplicationFilter', $filter->getName(), 'Filter name should match');

        $original = new PostCandidate(['tweet_id' => 100, 'author_id' => 50]);
        $retweet1 = new PostCandidate(['tweet_id' => 101, 'author_id' => 60, 'retweeted_tweet_id' => 100]);
        $retweet2 = new PostCandidate(['tweet_id' => 102, 'author_id' => 70, 'retweeted_tweet_id' => 100]);

        $query = new ScoredPostsQuery(['user_id' => 123]);
        $result = $filter->filter($query, [$original, $retweet1, $retweet2]);

        $this->assertCount(1, $result->kept, 'Should keep only original');
        $this->assertCount(2, $result->removed, 'Should remove retweets');

        echo "  ✓ RetweetDeduplicationFilter tests passed\n\n";
    }

    private function testSelfTweetFilter(): void
    {
        echo "Testing SelfTweetFilter...\n";

        $filter = new SelfTweetFilter();
        $this->assertEquals('SelfTweetFilter', $filter->getName(), 'Filter name should match');

        $query = new ScoredPostsQuery(['user_id' => 123]);

        $selfTweet = new PostCandidate(['tweet_id' => 1, 'author_id' => 123]);
        $otherTweet = new PostCandidate(['tweet_id' => 2, 'author_id' => 456]);

        $result = $filter->filter($query, [$selfTweet, $otherTweet]);

        $this->assertCount(1, $result->kept, 'Should keep only other tweets');
        $this->assertCount(1, $result->removed, 'Should remove self tweets');

        echo "  ✓ SelfTweetFilter tests passed\n\n";
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

        $customScorer = new WeightedScorer([
            'reply_score' => 1.0,
            'retweet_score' => 0.5,
        ]);
        $customResult = $customScorer->score($query, [$candidate]);
        $this->assertNotNull($customResult[0]->weightedScore, 'Custom scorer should have score');

        $emptyCandidate = new PostCandidate(['tweet_id' => 2, 'author_id' => 222]);
        $emptyResult = $scorer->score($query, [$emptyCandidate]);
        $this->assertEquals(0.0, $emptyResult[0]->weightedScore, 'Empty scores should have zero weighted score');

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

        $diverseCandidates = [];
        for ($i = 0; $i < 4; $i++) {
            $diverseCandidates[] = new PostCandidate([
                'tweet_id' => $i + 10,
                'author_id' => 100 + $i,
                'tweet_text' => "Tweet $i",
                'weighted_score' => 0.5,
            ]);
        }
        $diverseResult = $scorer->score($query, $diverseCandidates);
        $scores = array_map(fn($c) => $c->weightedScore, $diverseResult);
        $this->assertEquals(0.5, min($scores), 'Different authors should maintain score');

        echo "  ✓ AuthorDiversityScorer tests passed\n\n";
    }

    private function testOONScorer(): void
    {
        echo "Testing OONScorer...\n";

        $scorer = new OONScorer(1.5);
        $this->assertEquals('OONScorer', $scorer->getName(), 'Scorer name should match');

        $query = new ScoredPostsQuery(['user_id' => 123]);

        $inNetwork = new PostCandidate([
            'tweet_id' => 1,
            'author_id' => 111,
            'in_network' => true,
            'weighted_score' => 0.5,
        ]);

        $outOfNetwork = new PostCandidate([
            'tweet_id' => 2,
            'author_id' => 222,
            'in_network' => false,
            'weighted_score' => 0.5,
        ]);

        $result = $scorer->score($query, [$inNetwork, $outOfNetwork]);

        $this->assertCount(2, $result, 'Should return all candidates');
        $this->assertEquals(0.5, $result[0]->weightedScore, 'In-network should keep original score');
        $this->assertEquals(0.75, $result[1]->weightedScore, 'Out-of-network should be boosted');

        $boostedScorer = new OONScorer(2.0);
        $boostedResult = $boostedScorer->score($query, [$outOfNetwork]);
        $this->assertEquals(1.0, $boostedResult[0]->weightedScore, 'Should apply 2x boost');

        $noScore = new PostCandidate([
            'tweet_id' => 3,
            'author_id' => 333,
            'in_network' => false,
        ]);
        $noScoreResult = $scorer->score($query, [$noScore]);
        $this->assertFalse($noScoreResult[0]->inNetwork, 'Should set in_network to false');

        echo "  ✓ OONScorer tests passed\n\n";
    }

    private function testPhoenixScorer(): void
    {
        echo "Testing PhoenixScorer...\n";

        $mockClient = new MockPhoenixClient();
        $scorer = new PhoenixScorer($mockClient);
        $this->assertEquals('PhoenixScorer', $scorer->getName(), 'Scorer name should match');

        $query = new ScoredPostsQuery([
            'user_id' => 123,
            'user_action_sequence' => [
                'user_id' => 123,
                'actions' => [],
            ],
        ]);

        $candidates = [
            new PostCandidate(['tweet_id' => 1, 'author_id' => 100]),
            new PostCandidate(['tweet_id' => 2, 'author_id' => 200]),
        ];

        $result = $scorer->score($query, $candidates);

        $this->assertCount(2, $result, 'Should return all candidates');
        $this->assertNotNull($result[0]->predictionRequestId, 'Should have prediction request ID');
        $this->assertNotNull($result[0]->lastScoredAtMs, 'Should have last scored timestamp');

        $queryWithoutSequence = new ScoredPostsQuery(['user_id' => 123]);
        $resultWithoutSequence = $scorer->score($queryWithoutSequence, $candidates);
        $this->assertCount(2, $resultWithoutSequence, 'Should return candidates without sequence');

        $emptyCandidates = $scorer->score($query, []);
        $this->assertCount(0, $emptyCandidates, 'Should handle empty candidates');

        echo "  ✓ PhoenixScorer tests passed\n\n";
    }

    private function testTopKScoreSelector(): void
    {
        echo "Testing TopKScoreSelector...\n";

        $selector = new TopKScoreSelector();
        $this->assertEquals('TopKScoreSelector', $selector->getName(), 'Selector name should match');

        $query = new ScoredPostsQuery(['user_id' => 123]);

        $candidates = [];
        for ($i = 1; $i <= 5; $i++) {
            $candidates[] = new PostCandidate([
                'tweet_id' => $i,
                'author_id' => 100 + $i,
                'weighted_score' => 0.5 * (6 - $i),
            ]);
        }

        $result = $selector->select($query, $candidates, 3);

        $this->assertCount(3, $result, 'Should return top 3');
        $this->assertEquals(1, $result[0]->tweetId, 'Highest score should be first');
        $this->assertEquals(2, $result[1]->tweetId, 'Second highest should be second');
        $this->assertEquals(3, $result[2]->tweetId, 'Third highest should be third');

        $emptyResult = $selector->select($query, [], 3);
        $this->assertCount(0, $emptyResult, 'Should handle empty candidates');

        $allResult = $selector->select($query, $candidates, 10);
        $this->assertCount(5, $allResult, 'Should return all if limit > count');

        $selectorWithScore = new TopKScoreSelector('score');
        $candidatesWithScore = [
            new PostCandidate(['tweet_id' => 1, 'score' => 0.3, 'weighted_score' => 0.9]),
            new PostCandidate(['tweet_id' => 2, 'score' => 0.5, 'weighted_score' => 0.1]),
        ];
        $scoreResult = $selectorWithScore->select($query, $candidatesWithScore, 1);
        $this->assertEquals(2, $scoreResult[0]->tweetId, 'Should use score field');

        echo "  ✓ TopKScoreSelector tests passed\n\n";
    }

    private function testThunderSource(): void
    {
        echo "Testing ThunderSource...\n";

        $source = new ThunderSource(null, 50);
        $this->assertEquals('ThunderSource', $source->getName(), 'Source name should match');

        $query = new ScoredPostsQuery(['user_id' => 123]);

        $result = $source->fetch($query);

        $this->assertArrayHasKey('candidates', $result, 'Should have candidates');
        $this->assertArrayHasKey('source', $result, 'Should have source');
        $this->assertEquals('ThunderSource', $result['source'], 'Source should be ThunderSource');

        echo "  ✓ ThunderSource tests passed\n\n";
    }

    private function testPhoenixSource(): void
    {
        echo "Testing PhoenixSource...\n";

        $mockClient = new MockPhoenixClient();
        $source = new PhoenixSource($mockClient, 50);
        $this->assertEquals('PhoenixSource', $source->getName(), 'Source name should match');

        $query = new ScoredPostsQuery(['user_id' => 123]);

        $result = $source->fetch($query);

        $this->assertArrayHasKey('candidates', $result, 'Should have candidates');
        $this->assertArrayHasKey('source', $result, 'Should have source');
        $this->assertEquals('PhoenixSource', $result['source'], 'Source should be PhoenixSource');
        $this->assertIsArray($result['candidates'], 'Candidates should be array');

        echo "  ✓ PhoenixSource tests passed\n\n";
    }

    private function testCoreDataCandidateHydrator(): void
    {
        echo "Testing CoreDataCandidateHydrator...\n";

        $hydrator = new CoreDataCandidateHydrator();
        $this->assertEquals('CoreDataCandidateHydrator', $hydrator->getName(), 'Hydrator name should match');

        $query = new ScoredPostsQuery(['user_id' => 123]);

        $candidates = [
            new PostCandidate(['tweet_id' => 1, 'author_id' => 100]),
            new PostCandidate(['tweet_id' => 2, 'author_id' => 200]),
        ];

        $result = $hydrator->hydrate($query, $candidates);

        $this->assertCount(2, $result, 'Should return all candidates');

        echo "  ✓ CoreDataCandidateHydrator tests passed\n\n";
    }

    private function testCandidatePipeline(): void
    {
        echo "Testing CandidatePipeline...\n";

        $pipeline = new CandidatePipeline('TestPipeline');
        $this->assertEquals('TestPipeline', $pipeline->getName(), 'Pipeline name should match');

        $pipeline->addSource(new ThunderSource(null, 10));
        $pipeline->addFilter(new AgeFilter(86400 * 7));
        $pipeline->addFilter(new DropDuplicatesFilter());
        $pipeline->addScorer(new WeightedScorer());
        $pipeline->setSelector(new TopKScoreSelector());

        $query = new ScoredPostsQuery(['user_id' => 123]);
        $result = $pipeline->execute($query, 5);

        $this->assertNotNull($result, 'Pipeline should return result');
        $this->assertIsArray($result, 'Result should be array');
        $this->assertArrayHasKey('candidates', $result, 'Should have candidates');
        $this->assertArrayHasKey('request_id', $result, 'Should have request ID');
        $this->assertArrayHasKey('count', $result, 'Should have count');
        $this->assertArrayHasKey('pipeline_stats', $result, 'Should have stats');

        $stats = $result['pipeline_stats'];
        $this->assertNotNull($stats, 'Stats should not be null');
        $this->assertIsArray($stats, 'Stats should be array');
        $this->assertArrayHasKey('sources', $stats, 'Should have source stats');
        $this->assertArrayHasKey('filters', $stats, 'Should have filter stats');
        $this->assertArrayHasKey('scorers', $stats, 'Should have scorer stats');

        echo "  ✓ CandidatePipeline tests passed\n\n";
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

        $updatedService = $service->updateConfig(['default_limit' => 20]);
        $this->assertEquals(20, $updatedService->getConfig()['default_limit'], 'Should update config');

        $pipeline = $service->getPipeline();
        $this->assertNotNull($pipeline, 'Should have pipeline');

        echo "  ✓ HomeMixerService tests passed\n\n";
    }

    private function testMockPhoenixClient(): void
    {
        echo "Testing MockPhoenixClient...\n";

        $healthyClient = new MockPhoenixClient(false);
        $this->assertTrue($healthyClient->healthCheck(), 'Healthy client should pass health check');

        $unhealthyClient = new MockPhoenixClient(true);
        $this->assertFalse($unhealthyClient->healthCheck(), 'Unhealthy client should fail health check');

        $prediction = $healthyClient->predict(123, [], [['tweet_id' => 1]]);
        $this->assertArrayHasKey('predictions', $prediction, 'Should have predictions');
        $this->assertCount(1, $prediction['predictions'], 'Should have 1 prediction');

        $retrieval = $healthyClient->getRetrieval(123, [], 10);
        $this->assertArrayHasKey('candidates', $retrieval, 'Should have candidates');
        $this->assertCount(10, $retrieval['candidates'], 'Should have 10 candidates');

        $count = $healthyClient->getPredictionCount();
        $this->assertEquals(1, $count, 'Should track prediction count');

        echo "  ✓ MockPhoenixClient tests passed\n\n";
    }

    private function testAlgorithm(): void
    {
        echo "Testing Algorithm...\n";

        $recommendations = \XAlgorithm\Algorithm::getRecommendations(
            userId: 12345,
            countryCode: 'US',
            languageCode: 'en',
            limit: 5
        );
        $this->assertIsArray($recommendations, 'Should return array');

        $requestId = \XAlgorithm\Algorithm::generateRequestId();
        $this->assertTrue($requestId > 0, 'Should generate request ID');

        $health = \XAlgorithm\Algorithm::checkHealth();
        $this->assertTrue($health, 'Service should be healthy');

        \XAlgorithm\Algorithm::reset();
        $this->assertTrue(true, 'Reset should not throw');

        echo "  ✓ Algorithm tests passed\n\n";
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

$test = new ComprehensiveTest();
$test->run();
