<?php

declare(strict_types=1);

namespace XAlgorithm\Utility;

/**
 * 请求ID生成器
 */
class RequestIdGenerator
{
    private static int $counter = 0;
    private static int $lastTimestamp = 0;

    public static function generate(int $userId = 0): string
    {
        $now = (int)(microtime(true) * 1000);
        
        if ($now === self::$lastTimestamp) {
            self::$counter++;
        } else {
            self::$counter = 0;
            self::$lastTimestamp = $now;
        }
        
        $random = mt_rand(0, 999999);
        
        return sprintf('%d-%d-%06d-%d', $now, $userId, self::$counter, $random);
    }

    public static function generateSnowflake(?int $timestamp = null): int
    {
        if ($timestamp === null) {
            $timestamp = (int)(microtime(true) * 1000);
        }
        
        $epoch = 1288834974657;
        $timeSinceEpoch = $timestamp - $epoch;
        
        if ($timeSinceEpoch < 0) {
            throw new \InvalidArgumentException('Timestamp is before Twitter epoch');
        }
        
        $workerId = mt_rand(0, 31);
        $datacenterId = mt_rand(0, 31);
        $sequence = mt_rand(0, 4095);
        
        $result = 0;
        $result |= ($timeSinceEpoch & 0x1FFFFFFFFFFF) << 22;
        $result |= ($datacenterId & 0x1F) << 17;
        $result |= ($workerId & 0x1F) << 12;
        $result |= $sequence & 0xFFF;
        
        return $result;
    }

    public static function getTimestampFromSnowflake(int $snowflake): int
    {
        $epoch = 1288834974657;
        $timeBits = $snowflake >> 22;
        
        return ($timeBits & 0x1FFFFFFFFFFF) + $epoch;
    }

    public static function durationSinceSnowflake(int $snowflake): ?int
    {
        $tweetTime = self::getTimestampFromSnowflake($snowflake);
        $now = (int)(microtime(true) * 1000);
        
        if ($tweetTime > $now) {
            return null;
        }
        
        return $now - $tweetTime;
    }
}
