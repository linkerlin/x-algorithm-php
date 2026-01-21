<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Sources;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\Pipeline\Interfaces\SourceInterface;
use XAlgorithm\ML\Phoenix\PhoenixClientInterface;

class PhoenixSource implements SourceInterface
{
    private string $name;
    private PhoenixClientInterface $phoenixClient;
    private int $defaultLimit;

    public function __construct(PhoenixClientInterface $phoenixClient, int $defaultLimit = 100)
    {
        $this->name = 'PhoenixSource';
        $this->phoenixClient = $phoenixClient;
        $this->defaultLimit = $defaultLimit;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function fetch(ScoredPostsQuery $query): array
    {
        $limit = $query->isBottomRequest ? $this->defaultLimit * 2 : $this->defaultLimit;

        try {
            $response = $this->phoenixClient->getRetrieval(
                $query->userId,
                $query->userFeatures->toArray(),
                $limit
            );

            $candidates = $this->buildCandidates($response['candidates'] ?? []);

            return [
                'candidates' => $candidates,
                'source' => $this->name,
            ];
        } catch (\Exception $e) {
            error_log('Phoenix source fetch failed: ' . $e->getMessage());
            return [
                'candidates' => [],
                'source' => $this->name,
            ];
        }
    }

    private function buildCandidates(array $retrievalCandidates): array
    {
        return array_map(function ($data) {
            return new PostCandidate([
                'tweet_id' => $data['tweet_id'],
                'author_id' => $data['author_id'],
                'tweet_text' => $data['text'] ?? '',
                'in_network' => false,
                'score' => $data['score'] ?? null,
            ]);
        }, $retrievalCandidates);
    }

    public function setDefaultLimit(int $limit): self
    {
        $this->defaultLimit = $limit;
        return $this;
    }
}
