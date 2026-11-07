<?php

declare(strict_types=1);

namespace App\Tests\Modules\UserManagement\Application;

use App\Tests\Fixtures\UserManagement\InMemoryUserProfileReadModel;
use DateTimeImmutable;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\UserManagement\Application\Query\ListAllUsersQuery;
use Modules\UserManagement\Application\Query\ListAllUsersQueryHandler;
use Modules\UserManagement\Application\Query\UserListFilters;
use PHPUnit\Framework\TestCase;

use function sprintf;

use Symfony\Component\Uid\Uuid;

final class ListAllUsersHandlerTest extends TestCase
{
    public function testShouldReturnAllUsersWhenNoFilterGiven(): void
    {
        $readModel = new InMemoryUserProfileReadModel();
        $readModel->seedUser('11111111-1111-7111-8111-111111111111', 'alice@example.com', true, new DateTimeImmutable('2026-11-01T10:00:00+00:00'));
        $readModel->seedUser('22222222-2222-7222-8222-222222222222', 'bob@example.com', false, new DateTimeImmutable('2026-11-02T10:00:00+00:00'));

        $sut = new ListAllUsersQueryHandler($readModel);

        $page = $sut(new ListAllUsersQuery(new UserListFilters()));

        self::assertSame(2, $page->total);
        self::assertCount(2, $page->items);
    }

    public function testShouldFilterUsersByEmailWhenQProvided(): void
    {
        $readModel = new InMemoryUserProfileReadModel();
        $readModel->seedUser('11111111-1111-7111-8111-111111111111', 'alice@example.com', false, new DateTimeImmutable('2026-11-01T10:00:00+00:00'));
        $readModel->seedUser('22222222-2222-7222-8222-222222222222', 'bob@example.com', false, new DateTimeImmutable('2026-11-02T10:00:00+00:00'));

        $sut = new ListAllUsersQueryHandler($readModel);

        $page = $sut(new ListAllUsersQuery(new UserListFilters(q: 'alice')));

        self::assertSame(1, $page->total);
        self::assertSame('alice@example.com', $page->items[0]->email);
    }

    public function testShouldFilterByWorkspaceWhenWorkspaceIdProvided(): void
    {
        $workspaceId = (string) Uuid::v7();
        $other = (string) Uuid::v7();
        $readModel = new InMemoryUserProfileReadModel();
        $readModel->seedUser('11111111-1111-7111-8111-111111111111', 'alice@example.com', false, new DateTimeImmutable('2026-11-01T10:00:00+00:00'), workspaceIds: [$workspaceId]);
        $readModel->seedUser('22222222-2222-7222-8222-222222222222', 'bob@example.com', false, new DateTimeImmutable('2026-11-02T10:00:00+00:00'), workspaceIds: [$other]);

        $sut = new ListAllUsersQueryHandler($readModel);

        $page = $sut(new ListAllUsersQuery(new UserListFilters(workspaceId: new WorkspaceId(Uuid::fromString($workspaceId)))));

        self::assertSame(1, $page->total);
        self::assertSame('alice@example.com', $page->items[0]->email);
    }

    public function testShouldReturnOnlyAdminsWhenHasSuperAdminTrue(): void
    {
        $readModel = new InMemoryUserProfileReadModel();
        $readModel->seedUser('11111111-1111-7111-8111-111111111111', 'admin@example.com', true, new DateTimeImmutable('2026-11-01T10:00:00+00:00'));
        $readModel->seedUser('22222222-2222-7222-8222-222222222222', 'user@example.com', false, new DateTimeImmutable('2026-11-02T10:00:00+00:00'));

        $sut = new ListAllUsersQueryHandler($readModel);

        $page = $sut(new ListAllUsersQuery(new UserListFilters(hasSuperAdmin: true)));

        self::assertSame(1, $page->total);
        self::assertTrue($page->items[0]->isSuperAdmin);
    }

    public function testShouldReturnOnlyDeactivatedWhenFilterSet(): void
    {
        $readModel = new InMemoryUserProfileReadModel();
        $readModel->seedUser('11111111-1111-7111-8111-111111111111', 'active@example.com', false, new DateTimeImmutable('2026-11-01T10:00:00+00:00'));
        $readModel->seedUser('22222222-2222-7222-8222-222222222222', 'gone@example.com', false, new DateTimeImmutable('2026-11-02T10:00:00+00:00'), deactivatedAt: new DateTimeImmutable('2026-11-05T10:00:00+00:00'));

        $sut = new ListAllUsersQueryHandler($readModel);

        $page = $sut(new ListAllUsersQuery(new UserListFilters(onlyDeactivated: true)));

        self::assertSame(1, $page->total);
        self::assertSame('gone@example.com', $page->items[0]->email);
    }

    public function testShouldPaginateWhenPerPageExceedsResults(): void
    {
        $readModel = new InMemoryUserProfileReadModel();
        for ($i = 1; $i <= 7; $i++) {
            $readModel->seedUser(sprintf('%s-1111-7111-8111-111111111111', str_pad((string) $i, 8, '0', STR_PAD_LEFT)), sprintf('u%d@example.com', $i), false, new DateTimeImmutable(sprintf('2026-11-%02dT10:00:00+00:00', $i)));
        }

        $sut = new ListAllUsersQueryHandler($readModel);

        $page = $sut(new ListAllUsersQuery(new UserListFilters(page: 2, perPage: 5)));

        self::assertSame(7, $page->total);
        self::assertSame(2, $page->page);
        self::assertCount(2, $page->items);
    }

    public function testShouldReportWorkspaceCountInEachItem(): void
    {
        $readModel = new InMemoryUserProfileReadModel();
        $readModel->seedUser('11111111-1111-7111-8111-111111111111', 'alice@example.com', false, new DateTimeImmutable('2026-11-01T10:00:00+00:00'), workspaceIds: [(string) Uuid::v7(), (string) Uuid::v7()]);

        $sut = new ListAllUsersQueryHandler($readModel);

        $page = $sut(new ListAllUsersQuery(new UserListFilters()));

        self::assertSame(2, $page->items[0]->workspaceCount);
    }
}
