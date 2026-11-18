<?php

declare(strict_types=1);

namespace App\Tests\Modules\Chat\Application;

use App\Tests\Fixtures\Chat\InMemoryChatMessageRepository;
use App\Tests\Fixtures\Chat\InMemoryChatSessionRepository;
use App\Tests\Fixtures\Chat\InMemoryChatThreadRepository;
use DateTimeImmutable;
use Modules\Agents\Api\AgentColorPalette;
use Modules\Agents\Api\AgentsApi;
use Modules\Agents\Api\Dto\AgentView;
use Modules\Chat\Application\ListUserAgentConversationsQuery;
use Modules\Chat\Application\ListUserAgentConversationsQueryHandler;
use Modules\Chat\Domain\ChatMessage;
use Modules\Chat\Domain\ChatSession;
use Modules\Chat\Domain\ChatThread;
use Modules\Chat\Domain\ClassifiedBy;
use Modules\Chat\Domain\ContentType;
use Modules\Chat\Domain\EngineType;
use Modules\Chat\Domain\ParticipantType;
use Modules\Chat\Domain\Priority;
use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\ChatMessageId;
use Modules\Shared\Kernel\ChatSessionId;
use Modules\Shared\Kernel\ChatThreadId;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ListUserAgentConversationsQueryHandlerTest extends TestCase
{
    public function testShouldReturnEmptyListWhenUserHasNoAgents(): void
    {
        $sut = new ListUserAgentConversationsQueryHandler(
            $this->makeAgentsApi([]),
            new InMemoryChatSessionRepository(),
            new InMemoryChatThreadRepository(),
            new InMemoryChatMessageRepository(),
        );

        $result = $sut(new ListUserAgentConversationsQuery(new UserId(Uuid::v7())));

        self::assertSame([], $result);
    }

    public function testShouldReturnAgentsAsNewWhenUserHasNoSession(): void
    {
        $actorId = new UserId(Uuid::v7());
        $agent = $this->makeAgentView('Hefajstos', 'hefajstos', 'Builder');

        $sut = new ListUserAgentConversationsQueryHandler(
            $this->makeAgentsApi([$agent]),
            new InMemoryChatSessionRepository(),
            new InMemoryChatThreadRepository(),
            new InMemoryChatMessageRepository(),
        );

        $result = $sut(new ListUserAgentConversationsQuery($actorId));

        self::assertCount(1, $result);
        self::assertTrue($result[0]->agentId->value->equals($agent->id->value));
        self::assertSame('Hefajstos', $result[0]->agentName);
        self::assertSame('hefajstos', $result[0]->agentSlug);
        self::assertSame('Builder', $result[0]->agentRole);
        self::assertNull($result[0]->lastMessagePreview);
        self::assertNull($result[0]->lastMessageAt);
        self::assertSame(0, $result[0]->threadCount);
        self::assertTrue($result[0]->isNew);
    }

    public function testShouldDeriveAgentColorFromSlugWhenAgentHasNoColor(): void
    {
        $actorId = new UserId(Uuid::v7());
        $agent = $this->makeAgentView('Hefajstos', 'hefajstos', 'Builder');

        $sut = new ListUserAgentConversationsQueryHandler(
            $this->makeAgentsApi([$agent]),
            new InMemoryChatSessionRepository(),
            new InMemoryChatThreadRepository(),
            new InMemoryChatMessageRepository(),
        );

        $result = $sut(new ListUserAgentConversationsQuery($actorId));

        self::assertSame(AgentColorPalette::deriveFromSlug('hefajstos'), $result[0]->agentColor);
    }

    public function testShouldReturnAgentExplicitColorWhenAgentHasColorAssigned(): void
    {
        $actorId = new UserId(Uuid::v7());
        $agent = $this->makeAgentView('Hefajstos', 'hefajstos', 'Builder', color: '#123456');

        $sut = new ListUserAgentConversationsQueryHandler(
            $this->makeAgentsApi([$agent]),
            new InMemoryChatSessionRepository(),
            new InMemoryChatThreadRepository(),
            new InMemoryChatMessageRepository(),
        );

        $result = $sut(new ListUserAgentConversationsQuery($actorId));

        self::assertSame('#123456', $result[0]->agentColor);
    }

    public function testShouldReturnAgentAsNewWhenSessionHasNoMessagesForThatAgent(): void
    {
        $sessions = new InMemoryChatSessionRepository();
        $threads = new InMemoryChatThreadRepository();
        $messages = new InMemoryChatMessageRepository();

        $actorId = new UserId(Uuid::v7());
        $session = $this->makeSession($actorId, new DateTimeImmutable('2026-11-18T08:00:00+00:00'));
        $sessions->save($session);

        $agent = $this->makeAgentView('Hefajstos', 'hefajstos', 'Builder');

        $sut = new ListUserAgentConversationsQueryHandler(
            $this->makeAgentsApi([$agent]),
            $sessions,
            $threads,
            $messages,
        );

        $result = $sut(new ListUserAgentConversationsQuery($actorId));

        self::assertCount(1, $result);
        self::assertTrue($result[0]->isNew);
        self::assertSame(0, $result[0]->threadCount);
    }

    public function testShouldAggregateThreadCountAndLastMessageWhenAgentHasActivity(): void
    {
        $sessions = new InMemoryChatSessionRepository();
        $threads = new InMemoryChatThreadRepository();
        $messages = new InMemoryChatMessageRepository();

        $actorId = new UserId(Uuid::v7());
        $session = $this->makeSession($actorId, new DateTimeImmutable('2026-11-18T08:00:00+00:00'));
        $sessions->save($session);

        $agent = $this->makeAgentView('Hefajstos', 'hefajstos', 'Builder');
        $agentUuid = $agent->id->value;

        $thread1 = $this->makeThread($session->id(), 'Deploy', new DateTimeImmutable('2026-11-18T08:05:00+00:00'));
        $thread2 = $this->makeThread($session->id(), 'Bugs', new DateTimeImmutable('2026-11-18T08:10:00+00:00'));
        $threads->save($thread1);
        $threads->save($thread2);

        $messages->save($this->makeMessageBetween(
            $session->id(),
            $thread1->id(),
            $actorId->value,
            $agentUuid,
            'Deploy jutro proszę',
            new DateTimeImmutable('2026-11-18T08:06:00+00:00'),
        ));
        $messages->save($this->makeMessageBetween(
            $session->id(),
            $thread2->id(),
            $agentUuid,
            $actorId->value,
            'Bug w kontrolerze',
            new DateTimeImmutable('2026-11-18T08:15:00+00:00'),
        ));

        $sut = new ListUserAgentConversationsQueryHandler(
            $this->makeAgentsApi([$agent]),
            $sessions,
            $threads,
            $messages,
        );

        $result = $sut(new ListUserAgentConversationsQuery($actorId));

        self::assertCount(1, $result);
        self::assertFalse($result[0]->isNew);
        self::assertSame(2, $result[0]->threadCount);
        self::assertSame('Bug w kontrolerze', $result[0]->lastMessagePreview);
        self::assertEquals(new DateTimeImmutable('2026-11-18T08:15:00+00:00'), $result[0]->lastMessageAt);
    }

    public function testShouldOrderAgentsByLastMessageAtDescendingWithNullsLast(): void
    {
        $sessions = new InMemoryChatSessionRepository();
        $threads = new InMemoryChatThreadRepository();
        $messages = new InMemoryChatMessageRepository();

        $actorId = new UserId(Uuid::v7());
        $session = $this->makeSession($actorId, new DateTimeImmutable('2026-11-18T08:00:00+00:00'));
        $sessions->save($session);

        $hefajstos = $this->makeAgentView('Hefajstos', 'hefajstos', 'Builder');
        $mentar = $this->makeAgentView('Mentar', 'mentar', 'Mentor');
        $luna = $this->makeAgentView('Luna', 'luna', 'Designer');

        $hThread = $this->makeThread($session->id(), 'Hef', new DateTimeImmutable('2026-11-18T08:05:00+00:00'));
        $mThread = $this->makeThread($session->id(), 'Men', new DateTimeImmutable('2026-11-18T08:06:00+00:00'));
        $threads->save($hThread);
        $threads->save($mThread);

        $messages->save($this->makeMessageBetween(
            $session->id(),
            $hThread->id(),
            $actorId->value,
            $hefajstos->id->value,
            'old',
            new DateTimeImmutable('2026-11-18T08:07:00+00:00'),
        ));
        $messages->save($this->makeMessageBetween(
            $session->id(),
            $mThread->id(),
            $actorId->value,
            $mentar->id->value,
            'new',
            new DateTimeImmutable('2026-11-18T08:08:00+00:00'),
        ));

        $sut = new ListUserAgentConversationsQueryHandler(
            $this->makeAgentsApi([$hefajstos, $mentar, $luna]),
            $sessions,
            $threads,
            $messages,
        );

        $result = $sut(new ListUserAgentConversationsQuery($actorId));

        self::assertCount(3, $result);
        self::assertSame('Mentar', $result[0]->agentName);
        self::assertSame('Hefajstos', $result[1]->agentName);
        self::assertSame('Luna', $result[2]->agentName);
        self::assertTrue($result[2]->isNew);
    }

    public function testShouldIgnoreThreadsBelongingToOtherSessions(): void
    {
        $sessions = new InMemoryChatSessionRepository();
        $threads = new InMemoryChatThreadRepository();
        $messages = new InMemoryChatMessageRepository();

        $actorId = new UserId(Uuid::v7());
        $foreignOwnerId = new UserId(Uuid::v7());

        $mine = $this->makeSession($actorId, new DateTimeImmutable('2026-11-18T08:00:00+00:00'));
        $foreign = $this->makeSession($foreignOwnerId, new DateTimeImmutable('2026-11-18T08:00:00+00:00'));
        $sessions->save($mine);
        $sessions->save($foreign);

        $agent = $this->makeAgentView('Hefajstos', 'hefajstos', 'Builder');

        $mineThread = $this->makeThread($mine->id(), 'Mine', new DateTimeImmutable('2026-11-18T08:05:00+00:00'));
        $foreignThread = $this->makeThread($foreign->id(), 'Foreign', new DateTimeImmutable('2026-11-18T08:05:00+00:00'));
        $threads->save($mineThread);
        $threads->save($foreignThread);

        $messages->save($this->makeMessageBetween(
            $mine->id(),
            $mineThread->id(),
            $actorId->value,
            $agent->id->value,
            'mine',
            new DateTimeImmutable('2026-11-18T08:10:00+00:00'),
        ));
        $messages->save($this->makeMessageBetween(
            $foreign->id(),
            $foreignThread->id(),
            $foreignOwnerId->value,
            $agent->id->value,
            'foreign-later',
            new DateTimeImmutable('2026-11-18T09:00:00+00:00'),
        ));

        $sut = new ListUserAgentConversationsQueryHandler(
            $this->makeAgentsApi([$agent]),
            $sessions,
            $threads,
            $messages,
        );

        $result = $sut(new ListUserAgentConversationsQuery($actorId));

        self::assertCount(1, $result);
        self::assertSame(1, $result[0]->threadCount);
        self::assertSame('mine', $result[0]->lastMessagePreview);
        self::assertEquals(new DateTimeImmutable('2026-11-18T08:10:00+00:00'), $result[0]->lastMessageAt);
    }

    /**
     * @param list<AgentView> $agents
     */
    private function makeAgentsApi(array $agents): AgentsApi
    {
        return new readonly class ($agents) implements AgentsApi {
            /**
             * @param list<AgentView> $agents
             */
            public function __construct(private array $agents)
            {
            }

            public function findById(AgentId $id): ?AgentView
            {
                foreach ($this->agents as $agent) {
                    if ($agent->id->value->equals($id->value)) {
                        return $agent;
                    }
                }

                return null;
            }

            public function findBySlug(OrganizationId $organizationId, string $slug): ?AgentView
            {
                foreach ($this->agents as $agent) {
                    if ($agent->slug === $slug && $agent->organizationId->value->equals($organizationId->value)) {
                        return $agent;
                    }
                }

                return null;
            }

            /**
             * @return list<AgentView>
             */
            public function listForOwner(UserId $ownerUserId): array
            {
                return $this->agents;
            }

            public function countActiveFor(OrganizationId $organizationId): int
            {
                $count = 0;
                foreach ($this->agents as $agent) {
                    if ($agent->isActive && $agent->organizationId->value->equals($organizationId->value)) {
                        ++$count;
                    }
                }

                return $count;
            }
        };
    }

    private function makeAgentView(string $name, string $slug, string $role, ?string $color = null): AgentView
    {
        $now = new DateTimeImmutable('2026-11-18T07:00:00+00:00');

        return new AgentView(
            id: AgentId::generate(),
            organizationId: OrganizationId::generate(),
            ownerUserId: new UserId(Uuid::v7()),
            sourceTemplateId: null,
            slug: $slug,
            name: $name,
            role: $role,
            roleDescription: 'desc',
            expertise: 'expertise',
            workflow: 'workflow',
            style: 'style',
            outputFormat: 'format',
            isActive: true,
            createdAt: $now,
            updatedAt: $now,
            color: $color,
        );
    }

    private function makeSession(UserId $ownerId, DateTimeImmutable $startedAt): ChatSession
    {
        return ChatSession::create(
            ChatSessionId::generate(),
            OrganizationId::generate(),
            $ownerId,
            'Chat',
            $startedAt,
        );
    }

    private function makeThread(ChatSessionId $sessionId, string $subject, DateTimeImmutable $createdAt): ChatThread
    {
        return ChatThread::create(
            ChatThreadId::generate(),
            $sessionId,
            $subject,
            $createdAt,
        );
    }

    private function makeMessageBetween(
        ChatSessionId $sessionId,
        ChatThreadId $threadId,
        Uuid $senderId,
        Uuid $recipientId,
        string $content,
        DateTimeImmutable $createdAt,
    ): ChatMessage {
        return ChatMessage::send(
            ChatMessageId::generate(),
            $sessionId,
            $threadId,
            ParticipantType::User,
            $senderId,
            ParticipantType::Agent,
            $recipientId,
            'Subject',
            Priority::P2,
            $content,
            ContentType::Text,
            [],
            null,
            Uuid::v7(),
            EngineType::CliTerminal,
            ClassifiedBy::Sender,
            null,
            $createdAt,
        );
    }
}
