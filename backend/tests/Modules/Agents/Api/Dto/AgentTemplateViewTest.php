<?php

declare(strict_types=1);

namespace App\Tests\Modules\Agents\Api\Dto;

use DateTimeImmutable;
use Modules\Agents\Api\Dto\AgentTemplateView;
use Modules\Shared\Kernel\AgentTemplateId;
use PHPUnit\Framework\TestCase;

final class AgentTemplateViewTest extends TestCase
{
    public function testShouldExposeFieldsWhenConstructed(): void
    {
        $id = AgentTemplateId::generate();
        $createdAt = new DateTimeImmutable('2026-11-15T10:00:00+00:00');
        $updatedAt = new DateTimeImmutable('2026-11-15T11:00:00+00:00');

        $sut = new AgentTemplateView(
            $id,
            'maja',
            'Maja',
            'Project Manager',
            'You are Maja, a Project Manager.',
            '- Coordination.',
            '- Open sessions with briefing.',
            '- Direct tone.',
            '- TL;DR first.',
            $createdAt,
            $updatedAt,
        );

        self::assertSame($id, $sut->id);
        self::assertSame('maja', $sut->slug);
        self::assertSame('Maja', $sut->name);
        self::assertSame('Project Manager', $sut->role);
        self::assertSame('You are Maja, a Project Manager.', $sut->roleDescription);
        self::assertSame('- Coordination.', $sut->expertise);
        self::assertSame('- Open sessions with briefing.', $sut->workflow);
        self::assertSame('- Direct tone.', $sut->style);
        self::assertSame('- TL;DR first.', $sut->outputFormat);
        self::assertSame($createdAt, $sut->createdAt);
        self::assertSame($updatedAt, $sut->updatedAt);
    }
}
