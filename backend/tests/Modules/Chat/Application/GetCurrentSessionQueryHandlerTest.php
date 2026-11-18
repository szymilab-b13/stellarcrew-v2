<?php

declare(strict_types=1);

namespace App\Tests\Modules\Chat\Application;

use App\Tests\Fixtures\Agents\FakeOrganizationsApi;
use App\Tests\Fixtures\Chat\InMemoryChatSessionRepository;
use DateTimeImmutable;
use Modules\Chat\Application\GetCurrentSessionQuery;
use Modules\Chat\Application\GetCurrentSessionQueryHandler;
use Modules\Chat\Domain\ChatSession;
use Modules\Shared\Kernel\ChatSessionId;
use Modules\Shared\Kernel\FrozenClock;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;
use Modules\Workspaces\Api\Exception\OwnerHasNoOrganizationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class GetCurrentSessionQueryHandlerTest extends TestCase
{
    private const string NOW = '2026-11-16T14:00:00+00:00';

    public function testShouldReturnExistingLatestActiveSessionWhenActorHasOne(): void
    {
        $sessions = new InMemoryChatSessionRepository();
        $organizations = new FakeOrganizationsApi();
        $actorId = new UserId(Uuid::v7());
        $orgId = OrganizationId::generate();
        $organizations->withOrganization($orgId, $actorId);

        $older = ChatSession::create(
            ChatSessionId::generate(),
            $orgId,
            $actorId,
            'Older',
            new DateTimeImmutable('2026-11-16T10:00:00+00:00'),
        );
        $newer = ChatSession::create(
            ChatSessionId::generate(),
            $orgId,
            $actorId,
            'Newer',
            new DateTimeImmutable('2026-11-16T12:00:00+00:00'),
        );
        $sessions->save($older);
        $sessions->save($newer);

        $sut = new GetCurrentSessionQueryHandler(
            $sessions,
            $organizations,
            new FrozenClock(new DateTimeImmutable(self::NOW)),
        );

        $result = $sut(new GetCurrentSessionQuery($actorId));

        self::assertTrue($result->id()->equals($newer->id()));
        self::assertSame(2, $sessions->count());
    }

    public function testShouldAutoCreateSessionWhenActorHasNoActiveSession(): void
    {
        $sessions = new InMemoryChatSessionRepository();
        $organizations = new FakeOrganizationsApi();
        $actorId = new UserId(Uuid::v7());
        $orgId = OrganizationId::generate();
        $organizations->withOrganization($orgId, $actorId);
        $now = new DateTimeImmutable(self::NOW);
        $sut = new GetCurrentSessionQueryHandler(
            $sessions,
            $organizations,
            new FrozenClock($now),
        );

        $result = $sut(new GetCurrentSessionQuery($actorId));

        self::assertSame(1, $sessions->count());
        self::assertTrue($result->ownerUserId()->equals($actorId));
        self::assertTrue($result->organizationId()->equals($orgId));
        self::assertSame(GetCurrentSessionQueryHandler::DEFAULT_LABEL, $result->label());
        self::assertSame($now, $result->startedAt());
        self::assertFalse($result->isEnded());
    }

    public function testShouldAutoCreateSessionWhenActorsOnlySessionsAreEnded(): void
    {
        $sessions = new InMemoryChatSessionRepository();
        $organizations = new FakeOrganizationsApi();
        $actorId = new UserId(Uuid::v7());
        $orgId = OrganizationId::generate();
        $organizations->withOrganization($orgId, $actorId);
        $ended = ChatSession::create(
            ChatSessionId::generate(),
            $orgId,
            $actorId,
            'Closed',
            new DateTimeImmutable('2026-11-16T09:00:00+00:00'),
        );
        $ended->end(new DateTimeImmutable('2026-11-16T10:00:00+00:00'));

        $sessions->save($ended);

        $sut = new GetCurrentSessionQueryHandler(
            $sessions,
            $organizations,
            new FrozenClock(new DateTimeImmutable(self::NOW)),
        );

        $result = $sut(new GetCurrentSessionQuery($actorId));

        self::assertSame(2, $sessions->count());
        self::assertFalse($result->id()->equals($ended->id()));
        self::assertFalse($result->isEnded());
    }

    public function testShouldIgnoreSessionsOwnedByOtherUsersWhenAutoCreating(): void
    {
        $sessions = new InMemoryChatSessionRepository();
        $organizations = new FakeOrganizationsApi();
        $actorId = new UserId(Uuid::v7());
        $otherUserId = new UserId(Uuid::v7());
        $orgId = OrganizationId::generate();
        $organizations->withOrganization($orgId, $actorId);
        $foreign = ChatSession::create(
            ChatSessionId::generate(),
            $orgId,
            $otherUserId,
            'Foreign session',
            new DateTimeImmutable('2026-11-16T13:00:00+00:00'),
        );
        $sessions->save($foreign);

        $sut = new GetCurrentSessionQueryHandler(
            $sessions,
            $organizations,
            new FrozenClock(new DateTimeImmutable(self::NOW)),
        );

        $result = $sut(new GetCurrentSessionQuery($actorId));

        self::assertSame(2, $sessions->count());
        self::assertFalse($result->id()->equals($foreign->id()));
        self::assertTrue($result->ownerUserId()->equals($actorId));
    }

    public function testShouldThrowOwnerHasNoOrganizationWhenActorHasNoOrgAndNoSession(): void
    {
        $sessions = new InMemoryChatSessionRepository();
        $organizations = new FakeOrganizationsApi();
        $actorId = new UserId(Uuid::v7());
        $sut = new GetCurrentSessionQueryHandler(
            $sessions,
            $organizations,
            new FrozenClock(new DateTimeImmutable(self::NOW)),
        );

        $this->expectException(OwnerHasNoOrganizationException::class);

        $sut(new GetCurrentSessionQuery($actorId));
    }
}
