<?php

declare(strict_types=1);

namespace App\Tests\Modules\UserManagement\Infrastructure\Menu;

use DateTimeImmutable;
use Modules\Auth\Api\Dto\AuthUser;
use Modules\Shared\Kernel\UserId;
use Modules\UserManagement\Infrastructure\Menu\UserManagementMenuContributor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UserManagementMenuContributorTest extends TestCase
{
    private UserManagementMenuContributor $sut;

    protected function setUp(): void
    {
        $this->sut = new UserManagementMenuContributor();
    }

    public function testShouldContributeAdminSectionWithUsersEntry(): void
    {
        $user = new AuthUser(
            new UserId(Uuid::v7()),
            'admin@example.com',
            new DateTimeImmutable('2026-11-07T09:00:00+00:00'),
            'Tomek',
        );

        $sections = $this->sut->contribute($user);

        self::assertCount(1, $sections);
        self::assertSame('admin', $sections[0]->id);
        self::assertSame('menu.sections.admin', $sections[0]->labelKey);
        self::assertCount(1, $sections[0]->items);

        $item = $sections[0]->items[0];
        self::assertSame('admin.users', $item->id);
        self::assertSame('menu.items.users', $item->labelKey);
        self::assertSame('Users', $item->icon);
        self::assertSame('/admin/users', $item->path);
        self::assertSame('admin', $item->requiresRole);
    }
}
