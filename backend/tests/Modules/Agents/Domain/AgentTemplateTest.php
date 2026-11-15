<?php

declare(strict_types=1);

namespace App\Tests\Modules\Agents\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use Modules\Agents\Domain\AgentTemplate;
use Modules\Shared\Kernel\AgentTemplateId;
use PHPUnit\Framework\TestCase;

final class AgentTemplateTest extends TestCase
{
    public function testShouldCreateTemplateWithValidFields(): void
    {
        $id = AgentTemplateId::generate();
        $now = new DateTimeImmutable('2026-11-15T10:00:00+00:00');

        $template = AgentTemplate::create(
            id: $id,
            slug: 'maja',
            name: 'Maja',
            role: 'Project Manager',
            roleDescription: 'You are Maja, a Project Manager.',
            expertise: '- Work coordination',
            workflow: '- Open every session with a briefing.',
            style: '- Tone: direct.',
            outputFormat: '- Briefing: TL;DR first.',
            now: $now,
        );

        self::assertSame($id, $template->id());
        self::assertSame('maja', $template->slug());
        self::assertSame('Maja', $template->name());
        self::assertSame('Project Manager', $template->role());
        self::assertSame('You are Maja, a Project Manager.', $template->roleDescription());
        self::assertSame('- Work coordination', $template->expertise());
        self::assertSame('- Open every session with a briefing.', $template->workflow());
        self::assertSame('- Tone: direct.', $template->style());
        self::assertSame('- Briefing: TL;DR first.', $template->outputFormat());
        self::assertEquals($now, $template->createdAt());
        self::assertEquals($now, $template->updatedAt());
    }

    public function testShouldRejectEmptySlug(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Agent template slug cannot be empty.');

        AgentTemplate::create(
            id: AgentTemplateId::generate(),
            slug: '',
            name: 'Maja',
            role: 'PM',
            roleDescription: 'd',
            expertise: 'e',
            workflow: 'w',
            style: 's',
            outputFormat: 'o',
            now: new DateTimeImmutable(),
        );
    }

    public function testShouldRejectNonKebabSlug(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Agent template slug must be kebab-case');

        AgentTemplate::create(
            id: AgentTemplateId::generate(),
            slug: 'Maja_PM',
            name: 'Maja',
            role: 'PM',
            roleDescription: 'd',
            expertise: 'e',
            workflow: 'w',
            style: 's',
            outputFormat: 'o',
            now: new DateTimeImmutable(),
        );
    }

    public function testShouldRejectSlugExceedingMaxLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Agent template slug cannot exceed');

        AgentTemplate::create(
            id: AgentTemplateId::generate(),
            slug: str_repeat('a', AgentTemplate::SLUG_MAX + 1),
            name: 'Maja',
            role: 'PM',
            roleDescription: 'd',
            expertise: 'e',
            workflow: 'w',
            style: 's',
            outputFormat: 'o',
            now: new DateTimeImmutable(),
        );
    }

    public function testShouldRejectEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Agent template name cannot be empty.');

        AgentTemplate::create(
            id: AgentTemplateId::generate(),
            slug: 'maja',
            name: '  ',
            role: 'PM',
            roleDescription: 'd',
            expertise: 'e',
            workflow: 'w',
            style: 's',
            outputFormat: 'o',
            now: new DateTimeImmutable(),
        );
    }

    public function testShouldRejectEmptyRoleDescription(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Agent template role description cannot be empty.');

        AgentTemplate::create(
            id: AgentTemplateId::generate(),
            slug: 'maja',
            name: 'Maja',
            role: 'PM',
            roleDescription: '',
            expertise: 'e',
            workflow: 'w',
            style: 's',
            outputFormat: 'o',
            now: new DateTimeImmutable(),
        );
    }

    public function testShouldRejectRoleDescriptionExceedingMaxLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Agent template role description cannot exceed');

        AgentTemplate::create(
            id: AgentTemplateId::generate(),
            slug: 'maja',
            name: 'Maja',
            role: 'PM',
            roleDescription: str_repeat('a', AgentTemplate::ROLE_DESCRIPTION_MAX + 1),
            expertise: 'e',
            workflow: 'w',
            style: 's',
            outputFormat: 'o',
            now: new DateTimeImmutable(),
        );
    }

    public function testShouldRejectExpertiseExceedingMaxLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Agent template expertise cannot exceed');

        AgentTemplate::create(
            id: AgentTemplateId::generate(),
            slug: 'maja',
            name: 'Maja',
            role: 'PM',
            roleDescription: 'd',
            expertise: str_repeat('a', AgentTemplate::SECTION_MAX + 1),
            workflow: 'w',
            style: 's',
            outputFormat: 'o',
            now: new DateTimeImmutable(),
        );
    }

    public function testShouldBuildSystemPromptFromXmlSections(): void
    {
        $template = AgentTemplate::create(
            id: AgentTemplateId::generate(),
            slug: 'maja',
            name: 'Maja',
            role: 'Project Manager',
            roleDescription: 'You are Maja, a PM.',
            expertise: '- Coordination.',
            workflow: '- Open sessions with briefing.',
            style: '- Direct tone.',
            outputFormat: '- TL;DR first.',
            now: new DateTimeImmutable(),
        );

        $expected = <<<'XML'
            <role>
            You are Maja, a PM.
            </role>

            <expertise>
            - Coordination.
            </expertise>

            <workflow>
            - Open sessions with briefing.
            </workflow>

            <style>
            - Direct tone.
            </style>

            <output_format>
            - TL;DR first.
            </output_format>
            XML;

        self::assertSame($expected, $template->toSystemPrompt());
    }
}
