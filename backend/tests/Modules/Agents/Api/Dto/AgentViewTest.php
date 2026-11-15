<?php

declare(strict_types=1);

namespace App\Tests\Modules\Agents\Api\Dto;

use DateTimeImmutable;
use Modules\Agents\Api\Dto\AgentView;
use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\AgentTemplateId;
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
        $templateId = AgentTemplateId::generate();
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
            '- Coordination.',
            '- Open with briefing.',
            '- Terse and direct.',
            '- One-line confirmations.',
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
        self::assertSame('You are Maja.', $sut->roleDescription);
        self::assertSame('- Coordination.', $sut->expertise);
        self::assertSame('- Open with briefing.', $sut->workflow);
        self::assertSame('- Terse and direct.', $sut->style);
        self::assertSame('- One-line confirmations.', $sut->outputFormat);
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
            'Custom role description.',
            '- Custom expertise.',
            '- Custom workflow.',
            '- Custom style.',
            '- Custom output.',
            true,
            new DateTimeImmutable('2026-11-13T10:00:00+00:00'),
            new DateTimeImmutable('2026-11-13T10:00:00+00:00'),
        );

        self::assertNull($sut->sourceTemplateId);
    }
}
