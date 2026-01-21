<?php

declare(strict_types=1);

namespace XAlgorithm\Tests;

use PHPUnit\Runner\BeforeFirstTestHook;
use PHPUnit\Runner\BeforeTestHook;

class Bootstrap implements BeforeFirstTestHook, BeforeTestHook
{
    public function executeBeforeFirstTest(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
    }

    public function executeBeforeTest(string $test): void
    {
        \Mockery::close();
    }
}
