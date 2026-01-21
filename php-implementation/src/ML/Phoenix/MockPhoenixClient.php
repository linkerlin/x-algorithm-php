<?php

declare(strict_types=1);

namespace XAlgorithm\ML\Phoenix;

class MockPhoenixClient implements PhoenixClientInterface
{
    private bool $shouldFail;
    private int $predictionCount;

    public function __construct(bool $shouldFail = false)
    {
        $this->shouldFail = $shouldFail;
        $this->predictionCount = 0;
    }

    public function predict(int $userId, array $userActionSequence, array $tweetInfos): array
    {
        $this->predictionCount++;

        if ($this->shouldFail) {
            throw new \RuntimeException('Mock Phoenix client failure');
        }

        return $this->buildMockPredictions($tweetInfos);
    }

    private function buildMockPredictions(array $tweetInfos): array
    {
        $predictions = [];

        foreach ($tweetInfos as $info) {
            $tweetId = $info['tweet_id'];
            $predictions[] = [
                'tweet_id' => $tweetId,
                'scores' => $this->generateRandomScores(),
            ];
        }

        return ['predictions' => $predictions];
    }

    private function generateRandomScores(): array
    {
        return [
            'favoriteScore' => mt_rand(0, 100) / 100,
            'replyScore' => mt_rand(0, 100) / 100,
            'retweetScore' => mt_rand(0, 100) / 100,
            'shareScore' => mt_rand(0, 100) / 100,
            'dwellScore' => mt_rand(0, 100) / 100,
            'quoteScore' => mt_rand(0, 100) / 100,
            'clickScore' => mt_rand(0, 100) / 100,
            'profileClickScore' => mt_rand(0, 100) / 100,
        ];
    }

    public function getRetrieval(int $userId, array $userFeatures, int $limit): array
    {
        $candidates = [];

        for ($i = 0; $i < $limit; $i++) {
            $candidates[] = [
                'tweet_id' => 1000 + $i,
                'author_id' => 2000 + ($i % 100),
                'text' => 'Test tweet ' . $i,
                'score' => mt_rand(0, 100) / 100,
            ];
        }

        return ['candidates' => $candidates];
    }

    public function getPredictionCount(): int
    {
        return $this->predictionCount;
    }

    public function healthCheck(): bool
    {
        return !$this->shouldFail;
    }
}
