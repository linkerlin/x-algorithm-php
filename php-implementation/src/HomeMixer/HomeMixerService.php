<?php

declare(strict_types=1);

namespace XAlgorithm\HomeMixer;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\Pipeline\CandidatePipeline;
use XAlgorithm\Core\Filters\FilterManager;
use XAlgorithm\Core\Scorers\WeightedScorer;
use XAlgorithm\Core\Scorers\AuthorDiversityScorer;
use XAlgorithm\Core\Scorers\PhoenixScorer;
use XAlgorithm\Core\Scorers\OONScorer;
use XAlgorithm\Core\Selectors\TopKScoreSelector;
use XAlgorithm\Core\Sources\PhoenixSource;
use XAlgorithm\Core\Sources\ThunderSource;
use XAlgorithm\ML\Phoenix\PhoenixClientInterface;
use XAlgorithm\Utility\RequestIdGenerator;

/**
 * 主编排服务
 * 协调整个推荐流程
 */
class HomeMixerService
{
    private string $name;
    private CandidatePipeline $pipeline;
    private ?PhoenixClientInterface $phoenixClient;
    private array $config;

    public function __construct(array $config = [])
    {
        $this->name = 'HomeMixerService';
        $this->config = array_merge([
            'default_limit' => 50,
            'max_age_seconds' => 86400 * 7,
            'enable_diversity' => true,
            'diversity_decay_factor' => 0.9,
            'oon_boost_factor' => 1.0,
        ], $config);
        $this->pipeline = $this->buildPipeline();
        $this->phoenixClient = null;
    }

    private function buildPipeline(): CandidatePipeline
    {
        $pipeline = new CandidatePipeline('HomeMixerPipeline');

        $pipeline->addSource(new ThunderSource(null, 100));
        $pipeline->addSource(new PhoenixSource($this->createMockPhoenixClient(), 100));

        $filterManager = new FilterManager();
        $filterManager
            ->addFilter(new \XAlgorithm\Core\Filters\AgeFilter($this->config['max_age_seconds']))
            ->addFilter(new \XAlgorithm\Core\Filters\DropDuplicatesFilter())
            ->addFilter(new \XAlgorithm\Core\Filters\PreviouslySeenPostsFilter())
            ->addFilter(new \XAlgorithm\Core\Filters\PreviouslyServedPostsFilter())
            ->addFilter(new \XAlgorithm\Core\Filters\SelfTweetFilter())
            ->addFilter(new \XAlgorithm\Core\Filters\MutedKeywordFilter())
            ->addFilter(new \XAlgorithm\Core\Filters\RetweetDeduplicationFilter())
            ->addFilter(new \XAlgorithm\Core\Filters\DedupConversationFilter());

        $pipeline->addFilter($filterManager);

        $pipeline->addScorer(new WeightedScorer());
        $pipeline->addScorer(new OONScorer($this->config['oon_boost_factor']));

        if ($this->config['enable_diversity']) {
            $pipeline->addScorer(new AuthorDiversityScorer($this->config['diversity_decay_factor']));
        }

        $pipeline->setSelector(new TopKScoreSelector());

        return $pipeline;
    }

    private function createMockPhoenixClient(): PhoenixClientInterface
    {
        return new \XAlgorithm\ML\Phoenix\MockPhoenixClient();
    }

    public function getRecommendations(ScoredPostsQuery $query): array
    {
        $limit = $this->config['default_limit'];

        $result = $this->pipeline->execute($query, $limit);
        $candidates = $result['candidates'] ?? [];

        return [
            'candidates' => array_map(fn($c) => $c->toArray(), $candidates),
            'request_id' => $query->requestId,
            'count' => count($candidates),
            'pipeline_stats' => $this->pipeline->getStats(),
        ];
    }

    public function getPersonalizedRecommendations(
        int $userId,
        int $clientAppId = 1,
        string $countryCode = 'US',
        string $languageCode = 'en',
        array $seenIds = [],
        array $servedIds = [],
        bool $inNetworkOnly = false,
        bool $isBottomRequest = false
    ): array {
        $query = new ScoredPostsQuery([
            'user_id' => $userId,
            'client_app_id' => $clientAppId,
            'country_code' => $countryCode,
            'language_code' => $languageCode,
            'seen_ids' => $seenIds,
            'served_ids' => $servedIds,
            'in_network_only' => $inNetworkOnly,
            'is_bottom_request' => $isBottomRequest,
        ]);

        return $this->getRecommendations($query);
    }

    public function setPhoenixClient(PhoenixClientInterface $client): self
    {
        $this->phoenixClient = $client;
        return $this;
    }

    public function getPipeline(): CandidatePipeline
    {
        return $this->pipeline;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function updateConfig(array $config): self
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }
}
