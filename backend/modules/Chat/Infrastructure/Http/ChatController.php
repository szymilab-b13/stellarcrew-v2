<?php

declare(strict_types=1);

namespace Modules\Chat\Infrastructure\Http;

use function array_map;

use DateTimeInterface;

use function max;
use function min;

use Modules\Auth\Infrastructure\Security\SecurityUser;
use Modules\Chat\Application\AgentConversationView;
use Modules\Chat\Application\AttachThreadCommand;
use Modules\Chat\Application\AttachThreadCommandHandler;
use Modules\Chat\Application\CreateThreadCommand;
use Modules\Chat\Application\CreateThreadCommandHandler;
use Modules\Chat\Application\DetachThreadCommand;
use Modules\Chat\Application\DetachThreadCommandHandler;
use Modules\Chat\Application\GetAgentConversationsQuery;
use Modules\Chat\Application\GetAgentConversationsQueryHandler;
use Modules\Chat\Application\GetCurrentSessionQuery;
use Modules\Chat\Application\GetCurrentSessionQueryHandler;
use Modules\Chat\Application\ListMessagesQuery;
use Modules\Chat\Application\ListMessagesQueryHandler;
use Modules\Chat\Application\ListThreadsQuery;
use Modules\Chat\Application\ListThreadsQueryHandler;
use Modules\Chat\Application\ListUserAgentConversationsQuery;
use Modules\Chat\Application\ListUserAgentConversationsQueryHandler;
use Modules\Chat\Application\SendMessageCommand;
use Modules\Chat\Application\SendMessageCommandHandler;
use Modules\Chat\Application\UserAgentConversationView;
use Modules\Chat\Domain\ChatMessage;
use Modules\Chat\Domain\ChatSession;
use Modules\Chat\Domain\ChatThread;
use Modules\Shared\Kernel\ChatMessageId;
use Modules\Shared\Kernel\ChatSessionId;
use Modules\Shared\Kernel\ChatThreadId;
use Modules\Workspaces\Api\Exception\OwnerHasNoOrganizationException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

final readonly class ChatController
{
    public const int MESSAGES_DEFAULT_LIMIT = 50;

    public const int MESSAGES_MAX_LIMIT = 200;

    public function __construct(
        private SendMessageCommandHandler $sendMessage,
        private GetCurrentSessionQueryHandler $getCurrentSession,
        private ListThreadsQueryHandler $listThreads,
        private ListMessagesQueryHandler $listMessages,
        private GetAgentConversationsQueryHandler $getAgentConversations,
        private ListUserAgentConversationsQueryHandler $listUserAgentConversations,
        private CreateThreadCommandHandler $createThread,
        private AttachThreadCommandHandler $attachThread,
        private DetachThreadCommandHandler $detachThread,
    ) {
    }

    #[Route(
        '/api/chat/sessions/current',
        name: 'chat_get_current_session',
        methods: ['GET'],
    )]
    #[OA\Get(
        path: '/api/chat/sessions/current',
        summary: 'Return the current active chat session for the caller, auto-creating one if none exists.',
        tags: ['chat'],
        responses: [
            new OA\Response(response: 200, description: 'Current session returned (existing or newly auto-created)'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 409, description: 'Caller has no organization; cannot auto-create session'),
        ],
    )]
    public function getCurrentSession(
        #[CurrentUser]
        SecurityUser $user,
    ): JsonResponse {
        try {
            $session = ($this->getCurrentSession)(new GetCurrentSessionQuery(
                actorId: $user->domainUser()->id(),
            ));
        } catch (OwnerHasNoOrganizationException $ownerHasNoOrganizationException) {
            throw new ConflictHttpException($ownerHasNoOrganizationException->getMessage(), $ownerHasNoOrganizationException);
        }

        return new JsonResponse($this->serialiseSession($session));
    }

    #[Route(
        '/api/chat/sessions/{id}/threads',
        name: 'chat_list_threads',
        methods: ['GET'],
    )]
    #[OA\Get(
        path: '/api/chat/sessions/{id}/threads',
        summary: 'List threads belonging to the given chat session (owner-only).',
        tags: ['chat'],
        responses: [
            new OA\Response(response: 200, description: 'Threads returned ordered by creation time (ascending)'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 403, description: 'Caller is not the session owner'),
            new OA\Response(response: 404, description: 'Session not found'),
        ],
    )]
    public function listThreads(
        string $id,
        #[CurrentUser]
        SecurityUser $user,
    ): JsonResponse {
        $threads = ($this->listThreads)(new ListThreadsQuery(
            actorId: $user->domainUser()->id(),
            sessionId: ChatSessionId::fromString($id),
        ));

        return new JsonResponse([
            'threads' => array_map($this->serialiseThread(...), $threads),
        ]);
    }

    #[Route(
        '/api/chat/conversations',
        name: 'chat_list_user_agent_conversations',
        methods: ['GET'],
    )]
    #[OA\Get(
        path: '/api/chat/conversations',
        summary: 'List all agents accessible to the caller together with their chat activity in the caller-owned session.',
        tags: ['chat'],
        responses: [
            new OA\Response(response: 200, description: 'Agents returned ordered by latest message time (descending, nulls last). Agents with no messages yet carry isNew=true.'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
        ],
    )]
    public function listUserAgentConversations(
        #[CurrentUser]
        SecurityUser $user,
    ): JsonResponse {
        $conversations = ($this->listUserAgentConversations)(new ListUserAgentConversationsQuery(
            actorId: $user->domainUser()->id(),
        ));

        return new JsonResponse([
            'conversations' => array_map($this->serialiseUserAgentConversation(...), $conversations),
        ]);
    }

    #[Route(
        '/api/chat/agents/{agentId}/threads',
        name: 'chat_get_agent_conversations',
        methods: ['GET'],
    )]
    #[OA\Get(
        path: '/api/chat/agents/{agentId}/threads',
        summary: 'List threads involving the given agent (as sender or recipient), restricted to the caller-owned sessions.',
        tags: ['chat'],
        responses: [
            new OA\Response(response: 200, description: 'Conversations returned ordered by latest message time (descending, nulls last)'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
        ],
    )]
    public function getAgentConversations(
        string $agentId,
        #[CurrentUser]
        SecurityUser $user,
    ): JsonResponse {
        $conversations = ($this->getAgentConversations)(new GetAgentConversationsQuery(
            actorId: $user->domainUser()->id(),
            agentId: Uuid::fromString($agentId),
        ));

        return new JsonResponse([
            'threads' => array_map($this->serialiseAgentConversation(...), $conversations),
        ]);
    }

    #[Route(
        '/api/chat/threads/{id}/messages',
        name: 'chat_list_messages',
        methods: ['GET'],
    )]
    #[OA\Get(
        path: '/api/chat/threads/{id}/messages',
        summary: 'List messages belonging to the given chat thread (paginated, owner-only).',
        tags: ['chat'],
        responses: [
            new OA\Response(response: 200, description: 'Messages returned ordered by creation time (ascending) with pagination meta'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 403, description: 'Caller is not the session owner'),
            new OA\Response(response: 404, description: 'Thread or its session not found'),
        ],
    )]
    public function listMessages(
        string $id,
        Request $request,
        #[CurrentUser]
        SecurityUser $user,
    ): JsonResponse {
        $limit = min(self::MESSAGES_MAX_LIMIT, max(1, $request->query->getInt('limit', self::MESSAGES_DEFAULT_LIMIT)));
        $offset = max(0, $request->query->getInt('offset', 0));

        $result = ($this->listMessages)(new ListMessagesQuery(
            actorId: $user->domainUser()->id(),
            threadId: ChatThreadId::fromString($id),
            limit: $limit,
            offset: $offset,
        ));

        return new JsonResponse([
            'messages' => array_map($this->serialiseMessage(...), $result->messages),
            'total' => $result->total,
            'limit' => $result->limit,
            'offset' => $result->offset,
        ]);
    }

    #[Route(
        '/api/chat/threads',
        name: 'chat_create_thread',
        methods: ['POST'],
    )]
    #[OA\Post(
        path: '/api/chat/threads',
        summary: 'Create a new chat thread within a session, optionally attached to a parent thread.',
        tags: ['chat'],
        responses: [
            new OA\Response(response: 201, description: 'Thread created'),
            new OA\Response(response: 400, description: 'Parent thread does not belong to the session'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 403, description: 'Caller is not the session owner'),
            new OA\Response(response: 404, description: 'Session or parent thread not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function createThread(
        #[CurrentUser]
        SecurityUser $user,
        #[MapRequestPayload]
        CreateThreadRequest $input,
    ): JsonResponse {
        $thread = ($this->createThread)(new CreateThreadCommand(
            actorId: $user->domainUser()->id(),
            sessionId: ChatSessionId::fromString($input->sessionId),
            subject: $input->subject,
            parentThreadId: $input->parentThreadId === null
                ? null
                : ChatThreadId::fromString($input->parentThreadId),
        ));

        return new JsonResponse($this->serialiseThread($thread), Response::HTTP_CREATED);
    }

    #[Route(
        '/api/chat/threads/{id}/attach',
        name: 'chat_attach_thread',
        methods: ['POST'],
    )]
    #[OA\Post(
        path: '/api/chat/threads/{id}/attach',
        summary: 'Attach a chat thread to a parent thread within the same session (owner-only).',
        tags: ['chat'],
        responses: [
            new OA\Response(response: 200, description: 'Thread attached to the parent'),
            new OA\Response(response: 400, description: 'Self-attach or parent thread does not belong to session'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 403, description: 'Caller is not the session owner'),
            new OA\Response(response: 404, description: 'Thread, session or parent thread not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function attachThread(
        string $id,
        #[CurrentUser]
        SecurityUser $user,
        #[MapRequestPayload]
        AttachThreadRequest $input,
    ): JsonResponse {
        $thread = ($this->attachThread)(new AttachThreadCommand(
            actorId: $user->domainUser()->id(),
            threadId: ChatThreadId::fromString($id),
            parentThreadId: ChatThreadId::fromString($input->parentThreadId),
        ));

        return new JsonResponse($this->serialiseThread($thread));
    }

    #[Route(
        '/api/chat/threads/{id}/detach',
        name: 'chat_detach_thread',
        methods: ['POST'],
    )]
    #[OA\Post(
        path: '/api/chat/threads/{id}/detach',
        summary: 'Detach a chat thread from its parent, turning it into a root thread (owner-only, no-op when already detached).',
        tags: ['chat'],
        responses: [
            new OA\Response(response: 200, description: 'Thread detached (or already had no parent)'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 403, description: 'Caller is not the session owner'),
            new OA\Response(response: 404, description: 'Thread or its session not found'),
        ],
    )]
    public function detachThread(
        string $id,
        #[CurrentUser]
        SecurityUser $user,
    ): JsonResponse {
        $thread = ($this->detachThread)(new DetachThreadCommand(
            actorId: $user->domainUser()->id(),
            threadId: ChatThreadId::fromString($id),
        ));

        return new JsonResponse($this->serialiseThread($thread));
    }

    #[Route(
        '/api/chat/messages',
        name: 'chat_send_message',
        methods: ['POST'],
    )]
    #[OA\Post(
        path: '/api/chat/messages',
        summary: 'Send a message within an existing chat session and thread.',
        tags: ['chat'],
        responses: [
            new OA\Response(response: 201, description: 'Message sent'),
            new OA\Response(response: 400, description: 'Thread does not belong to session or reply target mismatch'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
            new OA\Response(response: 403, description: 'Caller is not the session owner'),
            new OA\Response(response: 404, description: 'Session, thread or reply target not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function sendMessage(
        #[CurrentUser]
        SecurityUser $user,
        #[MapRequestPayload]
        SendMessageRequest $input,
    ): JsonResponse {
        $message = ($this->sendMessage)(new SendMessageCommand(
            actorId: $user->domainUser()->id(),
            sessionId: ChatSessionId::fromString($input->sessionId),
            threadId: ChatThreadId::fromString($input->threadId),
            senderType: $input->senderTypeEnum(),
            senderId: Uuid::fromString($input->senderId),
            recipientType: $input->recipientTypeEnum(),
            recipientId: Uuid::fromString($input->recipientId),
            subject: $input->subject,
            priority: $input->priorityEnum(),
            content: $input->content,
            contentType: $input->contentTypeEnum(),
            metadata: $input->metadata,
            replyToMessageId: $input->replyToMessageId === null
                ? null
                : ChatMessageId::fromString($input->replyToMessageId),
            engineId: Uuid::fromString($input->engineId),
            engineType: $input->engineTypeEnum(),
            classifiedBy: $input->classifiedByEnum(),
            classificationConfidence: $input->classificationConfidence,
        ));

        return new JsonResponse($this->serialiseMessage($message), Response::HTTP_CREATED);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialiseSession(ChatSession $session): array
    {
        return [
            'id' => (string) $session->id(),
            'organizationId' => (string) $session->organizationId(),
            'ownerUserId' => (string) $session->ownerUserId(),
            'label' => $session->label(),
            'startedAt' => $session->startedAt()->format(DateTimeInterface::ATOM),
            'endedAt' => $session->endedAt()?->format(DateTimeInterface::ATOM),
            'createdAt' => $session->createdAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $session->updatedAt()->format(DateTimeInterface::ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serialiseThread(ChatThread $thread): array
    {
        $parent = $thread->parentThreadId();

        return [
            'id' => (string) $thread->id(),
            'sessionId' => (string) $thread->sessionId(),
            'subject' => $thread->subject(),
            'parentThreadId' => $parent instanceof ChatThreadId ? (string) $parent : null,
            'createdAt' => $thread->createdAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $thread->updatedAt()->format(DateTimeInterface::ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serialiseUserAgentConversation(UserAgentConversationView $view): array
    {
        return [
            'agentId' => (string) $view->agentId,
            'agentName' => $view->agentName,
            'agentSlug' => $view->agentSlug,
            'agentRole' => $view->agentRole,
            'agentColor' => $view->agentColor,
            'lastMessagePreview' => $view->lastMessagePreview,
            'lastMessageAt' => $view->lastMessageAt?->format(DateTimeInterface::ATOM),
            'threadCount' => $view->threadCount,
            'isNew' => $view->isNew,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serialiseAgentConversation(AgentConversationView $view): array
    {
        return [
            'threadId' => (string) $view->threadId,
            'subject' => $view->subject,
            'shortDescription' => $view->shortDescription,
            'messageCount' => $view->messageCount,
            'lastMessageAt' => $view->lastMessageAt?->format(DateTimeInterface::ATOM),
            'parentThreadId' => $view->parentThreadId instanceof ChatThreadId ? (string) $view->parentThreadId : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serialiseMessage(ChatMessage $message): array
    {
        return [
            'id' => (string) $message->id(),
            'sessionId' => (string) $message->sessionId(),
            'threadId' => (string) $message->threadId(),
            'senderType' => $message->senderType()->value,
            'senderId' => (string) $message->senderId(),
            'recipientType' => $message->recipientType()->value,
            'recipientId' => (string) $message->recipientId(),
            'subject' => $message->subject(),
            'priority' => $message->priority()->value,
            'content' => $message->content(),
            'contentType' => $message->contentType()->value,
            'metadata' => $message->metadata(),
            'replyToMessageId' => $message->replyToMessageId() instanceof ChatMessageId
                ? (string) $message->replyToMessageId()
                : null,
            'engineId' => (string) $message->engineId(),
            'engineType' => $message->engineType()->value,
            'classifiedBy' => $message->classifiedBy()->value,
            'classificationConfidence' => $message->classificationConfidence(),
            'createdAt' => $message->createdAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
