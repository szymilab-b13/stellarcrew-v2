<?php

declare(strict_types=1);

namespace App\Tests\Modules\Agents\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use Modules\Agents\Domain\Agent;
use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\AgentTemplateId;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class AgentTest extends TestCase
{
    public function testShouldExposeFieldsWhenCreated(): void
    {
        $id = AgentId::generate();
        $organizationId = new OrganizationId(Uuid::v7());
        $ownerId = new UserId(Uuid::v7());
        $now = new DateTimeImmutable('2026-11-13T15:00:00+00:00');

        $sut = Agent::create(
            $id,
            $organizationId,
            $ownerId,
            'maja',
            'Maja',
            'Project Manager',
            'You are Maja, the PM.',
            '- Coordination.',
            '- Open with briefing.',
            '- Terse and direct.',
            '- One-line confirmations.',
            $now,
        );

        self::assertTrue($sut->id()->equals($id));
        self::assertTrue($sut->organizationId()->equals($organizationId));
        self::assertTrue($sut->ownerUserId()->equals($ownerId));
        self::assertNull($sut->sourceTemplateId());
        self::assertSame('maja', $sut->slug());
        self::assertSame('Maja', $sut->name());
        self::assertSame('Project Manager', $sut->role());
        self::assertSame('You are Maja, the PM.', $sut->roleDescription());
        self::assertSame('- Coordination.', $sut->expertise());
        self::assertSame('- Open with briefing.', $sut->workflow());
        self::assertSame('- Terse and direct.', $sut->style());
        self::assertSame('- One-line confirmations.', $sut->outputFormat());
        self::assertTrue($sut->isActive());
        self::assertEquals($now, $sut->createdAt());
        self::assertEquals($now, $sut->updatedAt());
        self::assertFalse($sut->isDeleted());
    }

    public function testShouldCarrySourceTemplateIdWhenClonedFromTemplate(): void
    {
        $templateId = AgentTemplateId::generate();
        $sut = $this->makeAgent(sourceTemplateId: $templateId);

        self::assertNotNull($sut->sourceTemplateId());
        self::assertTrue($sut->sourceTemplateId()->equals($templateId));
    }

    public function testShouldRejectBlankSlugWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeAgent(slug: '');
    }

    public function testShouldRejectNonKebabSlugWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeAgent(slug: 'Maja PM');
    }

    public function testShouldRejectBlankNameWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeAgent(name: '   ');
    }

    public function testShouldRejectBlankRoleWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeAgent(role: '   ');
    }

    public function testShouldRejectBlankRoleDescriptionWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeAgent(roleDescription: '   ');
    }

    public function testShouldRejectRoleDescriptionExceedingLimitWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeAgent(roleDescription: str_repeat('x', Agent::ROLE_DESCRIPTION_MAX + 1));
    }

    public function testShouldRejectBlankExpertiseWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeAgent(expertise: '   ');
    }

    public function testShouldRejectExpertiseExceedingLimitWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeAgent(expertise: str_repeat('x', Agent::SECTION_MAX + 1));
    }

    public function testShouldReturnNullColorWhenCreated(): void
    {
        $sut = $this->makeAgent();

        self::assertNull($sut->color());
    }

    public function testShouldAssignColorAndBumpUpdatedAtWhenValidHex(): void
    {
        $sut = $this->makeAgent();
        $later = new DateTimeImmutable('2026-11-13T16:00:00+00:00');

        $sut->assignColor('#6366f1', $later);

        self::assertSame('#6366f1', $sut->color());
        self::assertEquals($later, $sut->updatedAt());
    }

    public function testShouldClearColorWhenAssigningNull(): void
    {
        $sut = $this->makeAgent();
        $later = new DateTimeImmutable('2026-11-13T16:00:00+00:00');
        $sut->assignColor('#6366f1', $later);

        $sut->assignColor(null, new DateTimeImmutable('2026-11-13T17:00:00+00:00'));

        self::assertNull($sut->color());
    }

    public function testShouldRejectInvalidColorWhenAssigning(): void
    {
        $sut = $this->makeAgent();

        $this->expectException(InvalidArgumentException::class);
        $sut->assignColor('red', new DateTimeImmutable('2026-11-13T16:00:00+00:00'));
    }

    public function testShouldRejectShortHexColorWhenAssigning(): void
    {
        $sut = $this->makeAgent();

        $this->expectException(InvalidArgumentException::class);
        $sut->assignColor('#fff', new DateTimeImmutable('2026-11-13T16:00:00+00:00'));
    }

    public function testShouldLeaveUpdatedAtUnchangedWhenAssigningSameColor(): void
    {
        $sut = $this->makeAgent();
        $first = new DateTimeImmutable('2026-11-13T16:00:00+00:00');
        $sut->assignColor('#6366f1', $first);
        $later = new DateTimeImmutable('2026-11-13T17:00:00+00:00');

        $sut->assignColor('#6366f1', $later);

        self::assertEquals($first, $sut->updatedAt());
    }

    public function testShouldRenameAndBumpUpdatedAtWhenRenamed(): void
    {
        $sut = $this->makeAgent();
        $later = new DateTimeImmutable('2026-11-13T16:00:00+00:00');

        $sut->rename('Maja New', $later);

        self::assertSame('Maja New', $sut->name());
        self::assertEquals($later, $sut->updatedAt());
    }

    public function testShouldChangeRoleAndBumpUpdatedAtWhenRoleChanged(): void
    {
        $sut = $this->makeAgent();
        $later = new DateTimeImmutable('2026-11-13T16:00:00+00:00');

        $sut->changeRole('Lead PM', $later);

        self::assertSame('Lead PM', $sut->role());
        self::assertEquals($later, $sut->updatedAt());
    }

    public function testShouldUpdateRoleDescriptionAndBumpUpdatedAtWhenUpdated(): void
    {
        $sut = $this->makeAgent();
        $later = new DateTimeImmutable('2026-11-13T16:00:00+00:00');

        $sut->updateRoleDescription('You are the renewed Maja.', $later);

        self::assertSame('You are the renewed Maja.', $sut->roleDescription());
        self::assertEquals($later, $sut->updatedAt());
    }

    public function testShouldUpdateExpertiseAndBumpUpdatedAtWhenUpdated(): void
    {
        $sut = $this->makeAgent();
        $later = new DateTimeImmutable('2026-11-13T16:00:00+00:00');

        $sut->updateExpertise('- Renewed expertise.', $later);

        self::assertSame('- Renewed expertise.', $sut->expertise());
        self::assertEquals($later, $sut->updatedAt());
    }

    public function testShouldUpdateWorkflowAndBumpUpdatedAtWhenUpdated(): void
    {
        $sut = $this->makeAgent();
        $later = new DateTimeImmutable('2026-11-13T16:00:00+00:00');

        $sut->updateWorkflow('- Renewed workflow.', $later);

        self::assertSame('- Renewed workflow.', $sut->workflow());
        self::assertEquals($later, $sut->updatedAt());
    }

    public function testShouldUpdateStyleAndBumpUpdatedAtWhenUpdated(): void
    {
        $sut = $this->makeAgent();
        $later = new DateTimeImmutable('2026-11-13T16:00:00+00:00');

        $sut->updateStyle('- Renewed style.', $later);

        self::assertSame('- Renewed style.', $sut->style());
        self::assertEquals($later, $sut->updatedAt());
    }

    public function testShouldUpdateOutputFormatAndBumpUpdatedAtWhenUpdated(): void
    {
        $sut = $this->makeAgent();
        $later = new DateTimeImmutable('2026-11-13T16:00:00+00:00');

        $sut->updateOutputFormat('- Renewed output.', $later);

        self::assertSame('- Renewed output.', $sut->outputFormat());
        self::assertEquals($later, $sut->updatedAt());
    }

    public function testShouldComposeSystemPromptFromFiveSections(): void
    {
        $sut = $this->makeAgent();

        $prompt = $sut->toSystemPrompt();

        self::assertStringContainsString('<role>', $prompt);
        self::assertStringContainsString('</role>', $prompt);
        self::assertStringContainsString('<expertise>', $prompt);
        self::assertStringContainsString('<workflow>', $prompt);
        self::assertStringContainsString('<style>', $prompt);
        self::assertStringContainsString('<output_format>', $prompt);
        self::assertStringContainsString('You are Maja, the PM.', $prompt);
    }

    public function testShouldDeactivateAndReactivateWhenToggled(): void
    {
        $sut = $this->makeAgent();
        $later = new DateTimeImmutable('2026-11-13T16:00:00+00:00');

        $sut->deactivate($later);
        self::assertFalse($sut->isActive());

        $evenLater = new DateTimeImmutable('2026-11-13T17:00:00+00:00');
        $sut->activate($evenLater);
        self::assertTrue($sut->isActive());
        self::assertEquals($evenLater, $sut->updatedAt());
    }

    public function testShouldNotBumpUpdatedAtWhenActivatingAlreadyActive(): void
    {
        $created = new DateTimeImmutable('2026-11-13T15:00:00+00:00');
        $sut = $this->makeAgent(now: $created);

        $later = new DateTimeImmutable('2026-11-13T16:00:00+00:00');
        $sut->activate($later);

        self::assertEquals($created, $sut->updatedAt());
    }

    public function testShouldSoftDeleteAndRestoreWhenAsked(): void
    {
        $sut = $this->makeAgent();
        $deletedAt = new DateTimeImmutable('2026-11-13T18:00:00+00:00');

        $sut->softDelete($deletedAt);
        self::assertTrue($sut->isDeleted());
        self::assertEquals($deletedAt, $sut->deletedAt());

        $restoredAt = new DateTimeImmutable('2026-11-13T19:00:00+00:00');
        $sut->restore($restoredAt);
        self::assertFalse($sut->isDeleted());
        self::assertNull($sut->deletedAt());
        self::assertEquals($restoredAt, $sut->updatedAt());
    }

    public function testShouldBeIdempotentWhenSoftDeletingTwice(): void
    {
        $sut = $this->makeAgent();
        $first = new DateTimeImmutable('2026-11-13T18:00:00+00:00');
        $second = new DateTimeImmutable('2026-11-13T19:00:00+00:00');

        $sut->softDelete($first);
        $sut->softDelete($second);

        self::assertEquals($first, $sut->deletedAt());
    }

    private function makeAgent(
        ?string $slug = null,
        ?string $name = null,
        ?string $role = null,
        ?string $roleDescription = null,
        ?string $expertise = null,
        ?string $workflow = null,
        ?string $style = null,
        ?string $outputFormat = null,
        ?DateTimeImmutable $now = null,
        ?AgentTemplateId $sourceTemplateId = null,
    ): Agent {
        return Agent::create(
            AgentId::generate(),
            new OrganizationId(Uuid::v7()),
            new UserId(Uuid::v7()),
            $slug ?? 'maja',
            $name ?? 'Maja',
            $role ?? 'Project Manager',
            $roleDescription ?? 'You are Maja, the PM.',
            $expertise ?? '- Coordination.',
            $workflow ?? '- Open with briefing.',
            $style ?? '- Terse and direct.',
            $outputFormat ?? '- One-line confirmations.',
            $now ?? new DateTimeImmutable('2026-11-13T15:00:00+00:00'),
            $sourceTemplateId,
        );
    }
}
