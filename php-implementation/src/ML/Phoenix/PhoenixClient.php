<?php

declare(strict_types=1);

namespace XAlgorithm\ML\Phoenix;

/**
 * 凤凰 ML 预测响应
 */
class PhoenixPredictionResponse
{
    public array $predictions;
    public int $requestId;
    public int $timestampMs;
    public ?string $error = null;

    public function __construct(array $data = [])
    {
        $this->predictions = $data['predictions'] ?? [];
        $this->requestId = $data['request_id'] ?? 0;
        $this->timestampMs = $data['timestamp_ms'] ?? 0;
        $this->error = $data['error'] ?? null;
    }

    public function isSuccess(): bool
    {
        return $this->error === null;
    }

    public function toArray(): array
    {
        return [
            'predictions' => $this->predictions,
            'request_id' => $this->requestId,
            'timestamp_ms' => $this->timestampMs,
            'error' => $this->error,
        ];
    }
}

/**
 * 凤凰 ML 客户端接口
 */
interface PhoenixClientInterface
{
    public function predict(int $userId, array $userActionSequence, array $tweetInfos): array;
    public function getRetrieval(int $userId, array $userFeatures, int $limit): array;
    public function healthCheck(): bool;
}

/**
 * 远程凤凰 ML 客户端
 * 通过 HTTP/gRPC 调用外部 ML 服务
 */
class RemotePhoenixClient implements PhoenixClientInterface
{
    private string $baseUrl;
    private float $timeout;
    private int $retries;
    private array $headers;

    public function __construct(string $baseUrl, float $timeout = 5.0, int $retries = 3)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
        $this->retries = $retries;
        $this->headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    public function predict(int $userId, array $userActionSequence, array $tweetInfos): array
    {
        $payload = [
            'user_id' => $userId,
            'user_action_sequence' => $userActionSequence,
            'tweet_infos' => $tweetInfos,
        ];

        return $this->request('/predict', $payload);
    }

    public function getRetrieval(int $userId, array $userFeatures, int $limit): array
    {
        $payload = [
            'user_id' => $userId,
            'user_features' => $userFeatures,
            'limit' => $limit,
        ];

        return $this->request('/retrieval', $payload);
    }

    public function healthCheck(): bool
    {
        try {
            $response = $this->request('/health', [], false);
            return ($response['status'] ?? '') === 'healthy';
        } catch (\Exception $e) {
            return false;
        }
    }

    private function request(string $endpoint, array $payload, bool $retry = true): array
    {
        $url = $this->baseUrl . $endpoint;
        $attempt = 0;
        $lastError = null;

        while ($attempt < $this->retries) {
            try {
                $context = stream_context_create([
                    'http' => [
                        'method' => 'POST',
                        'header' => array_map(fn($k, $v) => "$k: $v", array_keys($this->headers), array_values($this->headers)),
                        'content' => json_encode($payload),
                        'timeout' => $this->timeout,
                        'ignore_errors' => true,
                    ],
                ]);

                $response = @file_get_contents($url, false, $context);

                if ($response === false) {
                    throw new \Exception("Request failed");
                }

                $result = json_decode($response, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Invalid JSON response');
                }

                return $result;
            } catch (\Exception $e) {
                $lastError = $e;
                $attempt++;

                if ($attempt < $this->retries) {
                    usleep(pow(2, $attempt) * 10000);
                }
            }
        }

        throw new \Exception("Phoenix request failed after {$this->retries} attempts: " . $lastError->getMessage());
    }

    public function setHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }
}

/**
 * 模拟凤凰 ML 客户端
 * 用于测试和开发
 */
class MockPhoenixClient implements PhoenixClientInterface
{
    private array $mockPredictions;
    private int $callCount;

    public function __construct()
    {
        $this->mockPredictions = [];
        $this->callCount = 0;
    }

    public function predict(int $userId, array $userActionSequence, array $tweetInfos): array
    {
        $this->callCount++;

        $predictions = [];
        foreach ($tweetInfos as $tweetInfo) {
            $tweetId = $tweetInfo['tweet_id'] ?? 0;
            $predictions[] = [
                'tweet_id' => $tweetId,
                'scores' => $this->generateRandomScores($tweetId),
            ];
        }

        return [
            'predictions' => $predictions,
            'request_id' => mt_rand(1, 999999999),
            'timestamp_ms' => (int)(microtime(true) * 1000),
            'error' => null,
        ];
    }

    public function getRetrieval(int $userId, array $userFeatures, int $limit): array
    {
        $this->callCount++;

        $candidates = [];
        for ($i = 0; $i < $limit; $i++) {
            $tweetId = $this->generateMockTweetId();
            $candidates[] = [
                'tweet_id' => $tweetId,
                'author_id' => mt_rand(1, 99999999),
                'score' => mt_rand(0, 100) / 100,
            ];
        }

        usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);

        return [
            'candidates' => $candidates,
            'request_id' => mt_rand(1, 999999999),
            'timestamp_ms' => (int)(microtime(true) * 1000),
        ];
    }

    public function healthCheck(): bool
    {
        return true;
    }

    private function generateRandomScores(int $seed): array
    {
        mt_rand($seed, $seed + 1000);

        return [
            'favorite_score' => mt_rand(0, 100) / 100,
            'reply_score' => mt_rand(0, 100) / 100,
            'retweet_score' => mt_rand(0, 100) / 100,
            'share_score' => mt_rand(0, 100) / 100,
            'dwell_score' => mt_rand(0, 100) / 100,
            'quote_score' => mt_rand(0, 100) / 100,
            'click_score' => mt_rand(0, 100) / 100,
            'profile_click_score' => mt_rand(0, 100) / 100,
        ];
    }

    private function generateMockTweetId(): int
    {
        $now = (int)(microtime(true) * 1000);
        $epoch = 1288834974657;
        $timeSinceEpoch = $now - $epoch;

        $workerId = mt_rand(0, 31);
        $datacenterId = mt_rand(0, 31);
        $sequence = mt_rand(0, 4095);

        return ($timeSinceEpoch << 22) | ($datacenterId << 17) | ($workerId << 12) | $sequence;
    }

    public function getCallCount(): int
    {
        return $this->callCount;
    }

    public function resetCallCount(): void
    {
        $this->callCount = 0;
    }
}
