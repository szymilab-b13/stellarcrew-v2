<?php

declare(strict_types=1);

namespace App\Tests\Modules\Shared\Kernel;

use InvalidArgumentException;
use Modules\Shared\Kernel\WorkspaceId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class WorkspaceIdTest extends TestCase
{
    public function testShouldGenerateUuidV7WhenCalled(): void
    {
        $sut = WorkspaceId::generate();

        self::assertTrue(Uuid::isValid((string) $sut->value));
    }

    public function testShouldRejectInvalidStringWhenParsing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        WorkspaceId::fromString('not-a-uuid');
    }

    public function testShouldConsiderSameUuidsEqual(): void
    {
        $uuid = Uuid::v7();

        $sut = new WorkspaceId($uuid);

        self::assertTrue($sut->equals(new WorkspaceId($uuid)));
    }
}
