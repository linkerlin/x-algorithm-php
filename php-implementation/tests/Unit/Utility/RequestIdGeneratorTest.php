<?php

declare(strict_types=1);

namespace XAlgorithm\Tests\Unit\Utility;

use XAlgorithm\Utility\RequestIdGenerator;

class RequestIdGeneratorTest extends \PHPUnit\Framework\TestCase
{
    public function testGenerateReturnsString(): void
    {
        $id = RequestIdGenerator::generate(123);

        $this->assertIsString($id);
        $this->assertNotEmpty($id);
        $this->assertTrue(strlen($id) > 10);
    }

    public function testGenerateReturnsUniqueIds(): void
    {
        $id1 = RequestIdGenerator::generate(123);
        $id2 = RequestIdGenerator::generate(123);
        $id3 = RequestIdGenerator::generate(456);

        $this->assertNotEquals($id1, $id2);
        $this->assertNotEquals($id2, $id3);
        $this->assertNotEquals($id1, $id3);
    }

    public function testGenerateSnowflakeReturnsInteger(): void
    {
        $snowflake = RequestIdGenerator::generateSnowflake();

        $this->assertIsInt($snowflake);
        $this->assertGreaterThan(0, $snowflake);
    }

    public function testGetTimestampFromSnowflake(): void
    {
        $snowflake = RequestIdGenerator::generateSnowflake();
        $timestamp = RequestIdGenerator::getTimestampFromSnowflake($snowflake);

        $this->assertIsInt($timestamp);
        $this->assertGreaterThan(0, $timestamp);
        $this->assertLessThanOrEqual((int)(microtime(true) * 1000), $timestamp);
    }

    public function testGetTimestampFromSnowflakeIsConsistent(): void
    {
        $snowflake = RequestIdGenerator::generateSnowflake();

        $timestamp1 = RequestIdGenerator::getTimestampFromSnowflake($snowflake);
        $timestamp2 = RequestIdGenerator::getTimestampFromSnowflake($snowflake);

        $this->assertEquals($timestamp1, $timestamp2);
    }

    public function testGenerateWithCustomTimestamp(): void
    {
        $customTime = (int)(microtime(true) * 1000) - 1000;
        $snowflake = RequestIdGenerator::generateSnowflake($customTime);
        $timestamp = RequestIdGenerator::getTimestampFromSnowflake($snowflake);

        $this->assertEquals($customTime, $timestamp);
    }
}
