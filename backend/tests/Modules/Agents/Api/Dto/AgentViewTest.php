<?php

declare(strict_types=1);

namespace App\Tests\Modules\Agents\Api\Dto;

use DateTimeImmutable;
use Modules\Agents\Api\Dto\AgentView;
use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;
use PHPUnit\Framework\TestCase;

final class AgentViewTest extends TestCase
{
    public function testShouldExposeFieldsWhenConstructed(): void
    {
        $id = AgentId::generate();
        $organizationId = OrganizationId::generate();
        $ownerUserId = UserId::generate();
        $templateId = AgentId::generate();
        $createdAt = new DateTimeImmutable('2026-11-13T10:00:00+00:00');
        $updatedAt = new DateTimeImmutable('2026-11-13T11:00:00+00:00');

        $sut = new AgentView(
            $id,
            $organizationId,
            $ownerUserId,
            $templateId,
            'maja',
            'Maja',
            'Project Manager',
            'You are Maja.',
            true,
            $createdAt,
            $updatedAt,
        );

        self::assertSame($id, $sut->id);
        self::assertSame($organizationId, $sut->organizationId);
        self::assertSame($ownerUserId, $sut->ownerUserId);
        self::assertSame($templateId, $sut->sourceTemplateId);
        self::assertSame('maja', $sut->slug);
        self::assertSame('Maja', $sut->name);
        self::assertSame('Project Manager', $sut->role);
        self::assertSame('You are Maja.', $sut->instructions);
        self::assertTrue($sut->isActive);
        self::assertSame($createdAt, $sut->createdAt);
        self::assertSame($updatedAt, $sut->updatedAt);
    }

    public function testShouldAcceptNullSourceTemplateIdWhenAgentIsCustom(): void
    {
        $sut = new AgentView(
            AgentId::generate(),
            OrganizationId::generate(),
            UserId::generate(),
            null,
            'custom',
            'Custom',
            'Developer',
            'Custom instructions.',
            true,
            new DateTimeImmutable('2026-11-13T10:00:00+00:00'),
            new DateTimeImmutable('2026-11-13T10:00:00+00:00'),
        );

        self::assertNull($sut->sourceTemplateId);
    }
}
