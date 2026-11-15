<?php

declare(strict_types=1);

namespace App\Tests\Modules\Agents\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use Modules\Agents\Domain\Agent;
use Modules\Shared\Kernel\AgentId;
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

        $sut = Agent::create($id, $organizationId, $ownerId, 'maja', 'Maja', 'Project Manager', 'You are Maja, the PM.', $now);

        self::assertTrue($sut->id()->equals($id));
        self::assertTrue($sut->organizationId()->equals($organizationId));
        self::assertTrue($sut->ownerUserId()->equals($ownerId));
        self::assertNull($sut->sourceTemplateId());
        self::assertSame('maja', $sut->slug());
        self::assertSame('Maja', $sut->name());
        self::assertSame('Project Manager', $sut->role());
        self::assertSame('You are Maja, the PM.', $sut->instructions());
        self::assertTrue($sut->isActive());
        self::assertEquals($now, $sut->createdAt());
        self::assertEquals($now, $sut->updatedAt());
        self::assertFalse($sut->isDeleted());
    }

    public function testShouldCarrySourceTemplateIdWhenClonedFromTemplate(): void
    {
        $templateId = AgentId::generate();
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

    public function testShouldRejectBlankInstructionsWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeAgent(instructions: '   ');
    }

    public function testShouldRejectInstructionsExceedingLimitWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeAgent(instructions: str_repeat('x', Agent::INSTRUCTIONS_MAX + 1));
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

    public function testShouldUpdateInstructionsAndBumpUpdatedAtWhenInstructionsUpdated(): void
    {
        $sut = $this->makeAgent();
        $later = new DateTimeImmutable('2026-11-13T16:00:00+00:00');

        $sut->updateInstructions('New instructions.', $later);

        self::assertSame('New instructions.', $sut->instructions());
        self::assertEquals($later, $sut->updatedAt());
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
        ?string $instructions = null,
        ?DateTimeImmutable $now = null,
        ?AgentId $sourceTemplateId = null,
    ): Agent {
        return Agent::create(
            AgentId::generate(),
            new OrganizationId(Uuid::v7()),
            new UserId(Uuid::v7()),
            $slug ?? 'maja',
            $name ?? 'Maja',
            $role ?? 'Project Manager',
            $instructions ?? 'You are Maja.',
            $now ?? new DateTimeImmutable('2026-11-13T15:00:00+00:00'),
            $sourceTemplateId,
        );
    }
}
