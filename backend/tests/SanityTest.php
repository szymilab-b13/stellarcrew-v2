<?php

declare(strict_types=1);

namespace App\Tests;

use App\Kernel;
use PHPUnit\Framework\TestCase;

final class SanityTest extends TestCase
{
    public function testShouldReturnExpectedEnvironmentWhenInstantiatedForTest(): void
    {
        $sut = new Kernel('test', true);

        $this->assertSame('test', $sut->getEnvironment());
    }

    public function testShouldReturnExpectedDebugFlagWhenInstantiatedForTest(): void
    {
        $sut = new Kernel('test', true);

        $this->assertTrue($sut->isDebug());
    }
}
