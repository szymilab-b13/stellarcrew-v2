<?php

declare(strict_types=1);

namespace App\Tests\Modules\Workspaces\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\Workspaces\Domain\Invitation;
use Modules\Workspaces\Domain\WorkspaceRole;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class InvitationTest extends TestCase
{
    public function testShouldIssueInvitationWithTokenWhenValid(): void
    {
        $sut = Invitation::issue(
            WorkspaceId::generate(),
            'guest@example.com',
            WorkspaceRole::Member,
            new UserId(Uuid::v7()),
            new DateTimeImmutable('2026-11-13T10:00:00+00:00'),
        );

        self::assertSame('guest@example.com', $sut->email());
        self::assertSame(WorkspaceRole::Member, $sut->role());
        self::assertNotSame('', $sut->token());
        self::assertFalse($sut->isAccepted());
    }

    public function testShouldRejectOwnerRoleWhenIssuing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Invitation::issue(
            WorkspaceId::generate(),
            'guest@example.com',
            WorkspaceRole::Owner,
            new UserId(Uuid::v7()),
            new DateTimeImmutable('2026-11-13T10:00:00+00:00'),
        );
    }

    public function testShouldRejectInvalidEmailWhenIssuing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Invitation::issue(
            WorkspaceId::generate(),
            'not-email',
            WorkspaceRole::Member,
            new UserId(Uuid::v7()),
            new DateTimeImmutable('2026-11-13T10:00:00+00:00'),
        );
    }

    public function testShouldMarkAcceptedWhenAcceptedBeforeExpiry(): void
    {
        $sut = Invitation::issue(
            WorkspaceId::generate(),
            'guest@example.com',
            WorkspaceRole::Member,
            new UserId(Uuid::v7()),
            new DateTimeImmutable('2026-11-13T10:00:00+00:00'),
        );

        $sut->accept(new DateTimeImmutable('2026-11-10T10:00:00+00:00'));

        self::assertTrue($sut->isAccepted());
    }

    public function testShouldRejectAcceptanceWhenExpired(): void
    {
        $sut = Invitation::issue(
            WorkspaceId::generate(),
            'guest@example.com',
            WorkspaceRole::Member,
            new UserId(Uuid::v7()),
            new DateTimeImmutable('2026-11-05T10:00:00+00:00'),
        );

        $this->expectException(InvalidArgumentException::class);

        $sut->accept(new DateTimeImmutable('2026-11-10T10:00:00+00:00'));
    }
}
