<?php

declare(strict_types=1);

namespace App\Tests\Modules\Workspaces\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\Workspaces\Domain\Workspace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class WorkspaceTest extends TestCase
{
    public function testShouldExposeFieldsAndDeriveInitialsWhenCreated(): void
    {
        $owner = new UserId(Uuid::v7());
        $now = new DateTimeImmutable('2026-11-06T10:00:00+00:00');

        $sut = Workspace::create(
            WorkspaceId::generate(),
            'Acme Corp',
            'acme-corp',
            'blue',
            $owner,
            $now,
        );

        self::assertSame('Acme Corp', $sut->name());
        self::assertSame('acme-corp', $sut->slug());
        self::assertSame('blue', $sut->iconColor());
        self::assertSame('AC', $sut->iconInitials());
        self::assertSame($owner, $sut->ownerId());
        self::assertEquals($now, $sut->createdAt());
        self::assertEquals($now, $sut->updatedAt());
        self::assertFalse($sut->isDeleted());
    }

    public function testShouldRejectEmptyNameWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Workspace::create(
            WorkspaceId::generate(),
            '  ',
            'slug-x',
            'blue',
            new UserId(Uuid::v7()),
            new DateTimeImmutable('2026-11-06T10:00:00+00:00'),
        );
    }

    public function testShouldRejectUnsupportedIconColorWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Workspace::create(
            WorkspaceId::generate(),
            'Acme',
            'acme',
            'neon',
            new UserId(Uuid::v7()),
            new DateTimeImmutable('2026-11-06T10:00:00+00:00'),
        );
    }

    public function testShouldRejectNonKebabSlugWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Workspace::create(
            WorkspaceId::generate(),
            'Acme',
            'Acme HQ',
            'blue',
            new UserId(Uuid::v7()),
            new DateTimeImmutable('2026-11-06T10:00:00+00:00'),
        );
    }

    public function testShouldUpdateUpdatedAtWhenRenamed(): void
    {
        $sut = $this->makeWorkspace('2026-11-06T10:00:00+00:00');

        $later = new DateTimeImmutable('2026-11-07T10:00:00+00:00');
        $sut->rename('New Name', $later);

        self::assertSame('New Name', $sut->name());
        self::assertEquals($later, $sut->updatedAt());
    }

    public function testShouldMarkAsDeletedWhenSoftDeleted(): void
    {
        $sut = $this->makeWorkspace('2026-11-06T10:00:00+00:00');

        $deletedAt = new DateTimeImmutable('2026-11-08T10:00:00+00:00');
        $sut->softDelete($deletedAt);

        self::assertTrue($sut->isDeleted());
        self::assertEquals($deletedAt, $sut->deletedAt());
    }

    public function testShouldChangeIconColorAndInitialsWhenAsked(): void
    {
        $sut = $this->makeWorkspace('2026-11-06T10:00:00+00:00');

        $sut->changeIcon('emerald', 'AC', new DateTimeImmutable('2026-11-09T10:00:00+00:00'));

        self::assertSame('emerald', $sut->iconColor());
        self::assertSame('AC', $sut->iconInitials());
    }

    private function makeWorkspace(string $isoDate): Workspace
    {
        return Workspace::create(
            WorkspaceId::generate(),
            'Acme Corp',
            'acme-corp',
            'blue',
            new UserId(Uuid::v7()),
            new DateTimeImmutable($isoDate),
        );
    }
}
