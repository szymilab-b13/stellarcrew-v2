<?php

declare(strict_types=1);

namespace App\Tests\Modules\Workspaces\Application;

use App\Tests\Fixtures\Workspaces\InMemoryWorkspaceRepository;
use DateTimeImmutable;
use Modules\Shared\Kernel\FrozenClock;
use Modules\Shared\Kernel\UserId;
use Modules\Workspaces\Application\CreateWorkspaceCommand;
use Modules\Workspaces\Application\CreateWorkspaceCommandHandler;
use Modules\Workspaces\Domain\WorkspaceRole;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class CreateWorkspaceCommandHandlerTest extends TestCase
{
    public function testShouldPersistWorkspaceAndOwnerMembershipWhenCommandExecuted(): void
    {
        $repo = new InMemoryWorkspaceRepository();
        $ownerId = new UserId(Uuid::v7());
        $sut = new CreateWorkspaceCommandHandler(
            $repo,
            new FrozenClock(new DateTimeImmutable('2026-11-06T10:00:00+00:00')),
        );

        $workspace = $sut(new CreateWorkspaceCommand($ownerId, 'Acme Corp'));

        self::assertSame(1, $repo->count());
        self::assertSame('Acme Corp', $workspace->name());
        self::assertSame('acme-corp', $workspace->slug());
        self::assertTrue($workspace->ownerId()->equals($ownerId));
        $membership = $repo->findMembership($workspace->id(), $ownerId);
        self::assertNotNull($membership);
        self::assertSame(WorkspaceRole::Owner, $membership->role());
    }

    public function testShouldAppendSuffixWhenSlugAlreadyTaken(): void
    {
        $repo = new InMemoryWorkspaceRepository();
        $clock = new FrozenClock(new DateTimeImmutable('2026-11-06T10:00:00+00:00'));
        $sut = new CreateWorkspaceCommandHandler($repo, $clock);
        $sut(new CreateWorkspaceCommand(new UserId(Uuid::v7()), 'Acme'));

        $workspace = $sut(new CreateWorkspaceCommand(new UserId(Uuid::v7()), 'Acme'));

        self::assertNotSame('acme', $workspace->slug());
        self::assertStringStartsWith('acme-', $workspace->slug());
    }
}
