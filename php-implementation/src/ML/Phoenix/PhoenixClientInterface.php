<?php

declare(strict_types=1);

namespace XAlgorithm\ML\Phoenix;

interface PhoenixClientInterface
{
    public function predict(int $userId, array $userActionSequence, array $tweetInfos): array;
    public function getRetrieval(int $userId, array $userFeatures, int $limit): array;
    public function healthCheck(): bool;
}
