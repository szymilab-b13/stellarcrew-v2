<?php

declare(strict_types=1);

namespace App\Tests\Modules\Audit\Infrastructure\Menu;

use DateTimeImmutable;
use Modules\Audit\Infrastructure\Menu\AuditMenuContributor;
use Modules\Auth\Api\Dto\AuthUser;
use Modules\Shared\Kernel\UserId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class AuditMenuContributorTest extends TestCase
{
    private AuditMenuContributor $sut;

    protected function setUp(): void
    {
        $this->sut = new AuditMenuContributor();
    }

    public function testShouldContributeAdminSectionWithAuditEntry(): void
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
        self::assertSame('admin.audit', $item->id);
        self::assertSame('menu.items.auditLog', $item->labelKey);
        self::assertSame('ScrollText', $item->icon);
        self::assertSame('/admin/audit', $item->path);
        self::assertSame(20, $item->order);
        self::assertSame('admin', $item->requiresRole);
    }
}
