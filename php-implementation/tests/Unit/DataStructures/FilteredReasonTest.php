<?php

declare(strict_types=1);

namespace XAlgorithm\VisibilityFiltering\Models;

class FilteredReasonTest extends \PHPUnit\Framework\TestCase
{
    public function testConstantValues(): void
    {
        $this->assertEquals(0, FilteredReason::NONE);
        $this->assertEquals(1, FilteredReason::NOT_FILTERED);
        $this->assertEquals(2, FilteredReason::AGE);
        $this->assertEquals(3, FilteredReason::DUPLICATE);
        $this->assertEquals(4, FilteredReason::SELF_TWEET);
        $this->assertEquals(5, FilteredReason::BLOCKED_AUTHOR);
        $this->assertEquals(6, FilteredReason::MUTED_KEYWORD);
        $this->assertEquals(7, FilteredReason::PREVIOUSLY_SEEN);
        $this->assertEquals(8, FilteredReason::PREVIOUSLY_SERVED);
        $this->assertEquals(9, FilteredReason::INELIGIBLE_SUBSCRIPTION);
    }

    public function testConstructor(): void
    {
        $reason = new FilteredReason(FilteredReason::AGE);

        $this->assertEquals(2, $reason->getValue());
    }

    public function testGetValue(): void
    {
        $reasonNone = new FilteredReason(FilteredReason::NONE);
        $reasonAge = new FilteredReason(FilteredReason::AGE);
        $reasonDuplicate = new FilteredReason(FilteredReason::DUPLICATE);

        $this->assertEquals(0, $reasonNone->getValue());
        $this->assertEquals(2, $reasonAge->getValue());
        $this->assertEquals(3, $reasonDuplicate->getValue());
    }
}
