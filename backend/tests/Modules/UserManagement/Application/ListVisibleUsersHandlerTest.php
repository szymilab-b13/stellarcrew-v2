<?php

declare(strict_types=1);

namespace App\Tests\Modules\UserManagement\Application;

use App\Tests\Fixtures\UserManagement\InMemoryUserProfileReadModel;
use DateTimeImmutable;
use Modules\Shared\Kernel\UserId;
use Modules\UserManagement\Application\Query\ListVisibleUsersQuery;
use Modules\UserManagement\Application\Query\ListVisibleUsersQueryHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ListVisibleUsersHandlerTest extends TestCase
{
    public function testShouldReturnUsersFromSharedWorkspacesExcludingViewer(): void
    {
        $sharedWorkspace = (string) Uuid::v7();
        $viewerId = '11111111-1111-7111-8111-111111111111';
        $readModel = new InMemoryUserProfileReadModel();
        $readModel->seedUser($viewerId, 'viewer@example.com', false, new DateTimeImmutable('2026-11-01T10:00:00+00:00'), workspaceIds: [$sharedWorkspace]);
        $readModel->seedUser('22222222-2222-7222-8222-222222222222', 'peer@example.com', false, new DateTimeImmutable('2026-11-02T10:00:00+00:00'), workspaceIds: [$sharedWorkspace]);

        $sut = new ListVisibleUsersQueryHandler($readModel);

        $items = $sut(new ListVisibleUsersQuery(new UserId(Uuid::fromString($viewerId))));

        self::assertCount(1, $items);
        self::assertSame('peer@example.com', $items[0]->email);
    }

    public function testShouldNotReturnUsersFromOtherWorkspaces(): void
    {
        $viewerWorkspace = (string) Uuid::v7();
        $otherWorkspace = (string) Uuid::v7();
        $viewerId = '11111111-1111-7111-8111-111111111111';
        $readModel = new InMemoryUserProfileReadModel();
        $readModel->seedUser($viewerId, 'viewer@example.com', false, new DateTimeImmutable('2026-11-01T10:00:00+00:00'), workspaceIds: [$viewerWorkspace]);
        $readModel->seedUser('22222222-2222-7222-8222-222222222222', 'stranger@example.com', false, new DateTimeImmutable('2026-11-02T10:00:00+00:00'), workspaceIds: [$otherWorkspace]);

        $sut = new ListVisibleUsersQueryHandler($readModel);

        $items = $sut(new ListVisibleUsersQuery(new UserId(Uuid::fromString($viewerId))));

        self::assertSame([], $items);
    }

    public function testShouldReturnEmptyListWhenViewerHasNoWorkspaces(): void
    {
        $viewerId = '11111111-1111-7111-8111-111111111111';
        $readModel = new InMemoryUserProfileReadModel();
        $readModel->seedUser($viewerId, 'viewer@example.com', false, new DateTimeImmutable('2026-11-01T10:00:00+00:00'));
        $readModel->seedUser('22222222-2222-7222-8222-222222222222', 'peer@example.com', false, new DateTimeImmutable('2026-11-02T10:00:00+00:00'), workspaceIds: [(string) Uuid::v7()]);

        $sut = new ListVisibleUsersQueryHandler($readModel);

        $items = $sut(new ListVisibleUsersQuery(new UserId(Uuid::fromString($viewerId))));

        self::assertSame([], $items);
    }

    public function testShouldFilterByEmailWhenQProvided(): void
    {
        $sharedWorkspace = (string) Uuid::v7();
        $viewerId = '11111111-1111-7111-8111-111111111111';
        $readModel = new InMemoryUserProfileReadModel();
        $readModel->seedUser($viewerId, 'viewer@example.com', false, new DateTimeImmutable('2026-11-01T10:00:00+00:00'), workspaceIds: [$sharedWorkspace]);
        $readModel->seedUser('22222222-2222-7222-8222-222222222222', 'alice@example.com', false, new DateTimeImmutable('2026-11-02T10:00:00+00:00'), workspaceIds: [$sharedWorkspace]);
        $readModel->seedUser('33333333-3333-7333-8333-333333333333', 'bob@example.com', false, new DateTimeImmutable('2026-11-03T10:00:00+00:00'), workspaceIds: [$sharedWorkspace]);

        $sut = new ListVisibleUsersQueryHandler($readModel);

        $items = $sut(new ListVisibleUsersQuery(new UserId(Uuid::fromString($viewerId)), 'alice'));

        self::assertCount(1, $items);
        self::assertSame('alice@example.com', $items[0]->email);
    }
}
