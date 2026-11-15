<?php

declare(strict_types=1);

namespace App\Tests\Modules\Agents\Application;

use App\Tests\Fixtures\Agents\FakeOrganizationsApi;
use App\Tests\Fixtures\Agents\FakePlanLimitsService;
use App\Tests\Fixtures\Agents\InMemoryAgentMemberRepository;
use App\Tests\Fixtures\Agents\InMemoryAgentRepository;
use App\Tests\Fixtures\Agents\InMemoryAgentTemplateRepository;
use DateTimeImmutable;
use Modules\Agents\Application\CreateAgentFromTemplateCommand;
use Modules\Agents\Application\CreateAgentFromTemplateCommandHandler;
use Modules\Agents\Domain\AgentLimitReachedException;
use Modules\Agents\Domain\AgentSlugAlreadyTakenException;
use Modules\Agents\Domain\AgentTemplate;
use Modules\Shared\Api\LimitType;
use Modules\Shared\Kernel\AgentTemplateId;
use Modules\Shared\Kernel\FrozenClock;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;
use Modules\Workspaces\Api\Exception\OwnerHasNoOrganizationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

final class CreateAgentFromTemplateCommandHandlerTest extends TestCase
{
    public function testShouldDeepCopyAllFieldsFromTemplateWhenNoOverrides(): void
    {
        $actorId = new UserId(Uuid::v7());
        $organizationId = new OrganizationId(Uuid::v7());
        $templates = new InMemoryAgentTemplateRepository();
        $template = $this->seedTemplate($templates);
        $sut = $this->buildHandler(
            actorId: $actorId,
            organizationId: $organizationId,
            templates: $templates,
        );

        $agent = $sut(new CreateAgentFromTemplateCommand(
            actorId: $actorId,
            templateSlug: 'maja',
        ));

        self::assertSame($template->name(), $agent->name());
        self::assertSame($template->role(), $agent->role());
        self::assertSame($template->roleDescription(), $agent->roleDescription());
        self::assertSame($template->expertise(), $agent->expertise());
        self::assertSame($template->workflow(), $agent->workflow());
        self::assertSame($template->style(), $agent->style());
        self::assertSame($template->outputFormat(), $agent->outputFormat());
        self::assertSame('maja', $agent->slug());
        self::assertNotNull($agent->sourceTemplateId());
        self::assertTrue($agent->sourceTemplateId()->equals($template->id()));
        self::assertTrue($agent->ownerUserId()->equals($actorId));
        self::assertTrue($agent->organizationId()->equals($organizationId));
    }

    public function testShouldApplyNameAndRoleOverridesWhenProvided(): void
    {
        $actorId = new UserId(Uuid::v7());
        $templates = new InMemoryAgentTemplateRepository();
        $this->seedTemplate($templates);
        $sut = $this->buildHandler(
            actorId: $actorId,
            organizationId: new OrganizationId(Uuid::v7()),
            templates: $templates,
        );

        $agent = $sut(new CreateAgentFromTemplateCommand(
            actorId: $actorId,
            templateSlug: 'maja',
            name: 'Custom Maja',
            role: 'Scrum Master',
        ));

        self::assertSame('Custom Maja', $agent->name());
        self::assertSame('Scrum Master', $agent->role());
        self::assertSame('custom-maja', $agent->slug());
    }

    public function testShouldApplySectionOverridesWhenProvided(): void
    {
        $actorId = new UserId(Uuid::v7());
        $templates = new InMemoryAgentTemplateRepository();
        $this->seedTemplate($templates);
        $sut = $this->buildHandler(
            actorId: $actorId,
            organizationId: new OrganizationId(Uuid::v7()),
            templates: $templates,
        );

        $agent = $sut(new CreateAgentFromTemplateCommand(
            actorId: $actorId,
            templateSlug: 'maja',
            roleDescription: 'Custom role description.',
            expertise: '- Custom expertise.',
            workflow: '- Custom workflow.',
            style: '- Custom style.',
            outputFormat: '- Custom output.',
        ));

        self::assertSame('Custom role description.', $agent->roleDescription());
        self::assertSame('- Custom expertise.', $agent->expertise());
        self::assertSame('- Custom workflow.', $agent->workflow());
        self::assertSame('- Custom style.', $agent->style());
        self::assertSame('- Custom output.', $agent->outputFormat());
    }

    public function testShouldGrantOwnerAgentMembershipAfterCreate(): void
    {
        $actorId = new UserId(Uuid::v7());
        $members = new InMemoryAgentMemberRepository();
        $templates = new InMemoryAgentTemplateRepository();
        $this->seedTemplate($templates);
        $sut = $this->buildHandler(
            actorId: $actorId,
            organizationId: new OrganizationId(Uuid::v7()),
            templates: $templates,
            members: $members,
        );

        $agent = $sut(new CreateAgentFromTemplateCommand(
            actorId: $actorId,
            templateSlug: 'maja',
        ));

        $granted = $members->find($agent->id(), $actorId);
        self::assertNotNull($granted);
        self::assertTrue($granted->userId()->equals($actorId));
        self::assertTrue($granted->grantedBy()->equals($actorId));
    }

    public function testShouldUseProvidedSlugWhenGiven(): void
    {
        $actorId = new UserId(Uuid::v7());
        $templates = new InMemoryAgentTemplateRepository();
        $this->seedTemplate($templates);
        $sut = $this->buildHandler(
            actorId: $actorId,
            organizationId: new OrganizationId(Uuid::v7()),
            templates: $templates,
        );

        $agent = $sut(new CreateAgentFromTemplateCommand(
            actorId: $actorId,
            templateSlug: 'maja',
            slug: 'my-maja',
        ));

        self::assertSame('my-maja', $agent->slug());
    }

    public function testShouldAppendRandomSuffixWhenAutoSlugClashes(): void
    {
        $actorId = new UserId(Uuid::v7());
        $templates = new InMemoryAgentTemplateRepository();
        $this->seedTemplate($templates);
        $sut = $this->buildHandler(
            actorId: $actorId,
            organizationId: new OrganizationId(Uuid::v7()),
            templates: $templates,
            agentLimit: 10,
        );
        $sut(new CreateAgentFromTemplateCommand(
            actorId: $actorId,
            templateSlug: 'maja',
        ));

        $second = $sut(new CreateAgentFromTemplateCommand(
            actorId: $actorId,
            templateSlug: 'maja',
        ));

        self::assertNotSame('maja', $second->slug());
        self::assertStringStartsWith('maja-', $second->slug());
    }

    public function testShouldThrowWhenTemplateSlugIsUnknown(): void
    {
        $actorId = new UserId(Uuid::v7());
        $sut = $this->buildHandler(
            actorId: $actorId,
            organizationId: new OrganizationId(Uuid::v7()),
            templates: new InMemoryAgentTemplateRepository(),
        );

        $this->expectException(NotFoundHttpException::class);

        $sut(new CreateAgentFromTemplateCommand(
            actorId: $actorId,
            templateSlug: 'ghost',
        ));
    }

    public function testShouldThrowWhenActorHasNoOrganization(): void
    {
        $actorId = new UserId(Uuid::v7());
        $templates = new InMemoryAgentTemplateRepository();
        $this->seedTemplate($templates);
        $sut = new CreateAgentFromTemplateCommandHandler(
            new InMemoryAgentRepository(),
            new InMemoryAgentMemberRepository(),
            $templates,
            new FakeOrganizationsApi(),
            (new FakePlanLimitsService())->withLimit(LimitType::Agents, 5),
            new FrozenClock(new DateTimeImmutable('2026-11-15T15:00:00+00:00')),
        );

        $this->expectException(OwnerHasNoOrganizationException::class);

        $sut(new CreateAgentFromTemplateCommand(
            actorId: $actorId,
            templateSlug: 'maja',
        ));
    }

    public function testShouldThrowWhenAgentLimitReached(): void
    {
        $actorId = new UserId(Uuid::v7());
        $templates = new InMemoryAgentTemplateRepository();
        $this->seedTemplate($templates);
        $sut = $this->buildHandler(
            actorId: $actorId,
            organizationId: new OrganizationId(Uuid::v7()),
            templates: $templates,
            agentLimit: 1,
        );
        $sut(new CreateAgentFromTemplateCommand(
            actorId: $actorId,
            templateSlug: 'maja',
            slug: 'first',
        ));

        $this->expectException(AgentLimitReachedException::class);

        $sut(new CreateAgentFromTemplateCommand(
            actorId: $actorId,
            templateSlug: 'maja',
            slug: 'second',
        ));
    }

    public function testShouldThrowWhenProvidedSlugAlreadyTaken(): void
    {
        $actorId = new UserId(Uuid::v7());
        $templates = new InMemoryAgentTemplateRepository();
        $this->seedTemplate($templates);
        $sut = $this->buildHandler(
            actorId: $actorId,
            organizationId: new OrganizationId(Uuid::v7()),
            templates: $templates,
            agentLimit: 10,
        );
        $sut(new CreateAgentFromTemplateCommand(
            actorId: $actorId,
            templateSlug: 'maja',
            slug: 'reserved',
        ));

        $this->expectException(AgentSlugAlreadyTakenException::class);

        $sut(new CreateAgentFromTemplateCommand(
            actorId: $actorId,
            templateSlug: 'maja',
            slug: 'reserved',
        ));
    }

    public function testShouldStampCreatedAtAndUpdatedAtFromClock(): void
    {
        $actorId = new UserId(Uuid::v7());
        $templates = new InMemoryAgentTemplateRepository();
        $this->seedTemplate($templates);
        $now = new DateTimeImmutable('2026-11-15T15:00:00+00:00');
        $sut = $this->buildHandler(
            actorId: $actorId,
            organizationId: new OrganizationId(Uuid::v7()),
            templates: $templates,
            now: $now,
        );

        $agent = $sut(new CreateAgentFromTemplateCommand(
            actorId: $actorId,
            templateSlug: 'maja',
        ));

        self::assertEquals($now, $agent->createdAt());
        self::assertEquals($now, $agent->updatedAt());
    }

    private function seedTemplate(InMemoryAgentTemplateRepository $templates): AgentTemplate
    {
        $template = AgentTemplate::create(
            AgentTemplateId::generate(),
            'maja',
            'Maja',
            'Project Manager',
            'You are Maja, a Project Manager.',
            '- Work coordination.',
            '- Open every session with a briefing.',
            '- Calm, direct, slightly dry.',
            '- Briefing: TL;DR + bullets + next action.',
            new DateTimeImmutable('2026-11-15T00:00:00+00:00'),
        );
        $templates->save($template);

        return $template;
    }

    private function buildHandler(
        UserId $actorId,
        OrganizationId $organizationId,
        InMemoryAgentTemplateRepository $templates,
        ?InMemoryAgentMemberRepository $members = null,
        int $agentLimit = 5,
        ?DateTimeImmutable $now = null,
    ): CreateAgentFromTemplateCommandHandler {
        $organizations = (new FakeOrganizationsApi())->withOrganization($organizationId, $actorId);
        $planLimits = (new FakePlanLimitsService())->withLimit(LimitType::Agents, $agentLimit);

        return new CreateAgentFromTemplateCommandHandler(
            new InMemoryAgentRepository(),
            $members ?? new InMemoryAgentMemberRepository(),
            $templates,
            $organizations,
            $planLimits,
            new FrozenClock($now ?? new DateTimeImmutable('2026-11-15T15:00:00+00:00')),
        );
    }
}
